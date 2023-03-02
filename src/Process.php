<?php
// +----------------------------------------------------------------------
// | multi-process
// +----------------------------------------------------------------------
// | Copyright (c) 2022
// +----------------------------------------------------------------------
// | Licensed MIT
// +----------------------------------------------------------------------
// | Author: js
// +----------------------------------------------------------------------
// | Date: 2022-07-09
// +----------------------------------------------------------------------
// | Time: 下午 03:22
// +----------------------------------------------------------------------

namespace phpth\process;

use Closure;
use phpth\process\exception\CallException;
use phpth\process\exception\ExecutorException;
use phpth\process\exception\IpcException;
use phpth\process\exception\ProcessException;
use phpth\process\exception\RunnerException;
use phpth\process\ipc\Queue;
use phpth\process\supply\Call;
use phpth\process\supply\ChildRestart;
use phpth\process\supply\Executor;
use phpth\process\supply\Stdout;
use phpth\process\supply\Wait;
use Throwable;

class Process
{
    /**
     * @var int process priority
     */
    public int $priority = 0;

    /**
     * @var bool true: if main process normal finished, it will stop child process
     */
    public bool $stopChildOnFinish = false;

    /**
     * @var int indicate the manager process how to relaunch child process
     */
    public int $childRestart = ChildRestart::EXIT_ANY;

    /**
     * @var string|null  set main process title on unix process name
     */
    public ?string $name = null;

    /**
     * @var Closure|string|null   如果是回调函数，则会在子进程输出时调用，第一个参数为输出内容，第二个参数为进程编号, 如果是字符串， 这被视为输出目录或者文件
     */
    public null|Closure|string $output = Stdout::STD;

    /**
     * @var bool true: run on backend
     */
    private bool $demonize = false;

    /**
     * @param string|null $name
     * @param int $priority
     * @param int|null $child_restart
     * @param string|Closure|null $output 如果是回调函数，则会在子进程输出时调用，第一个参数为输出内容，第二个参数为进程编号, 如果是字符串， 这被视为输出目录或者文件
     * @throws ProcessException
     */
    public function __construct(?string $name = null, int $priority = 0, ?int $child_restart = null, null|string|Closure $output = Stdout::STD)
    {
        if (!function_exists('pcntl_fork')) {
            throw new ProcessException("php extension pcntl not exists, please rebuild php in cli with --enable-pcntl option or build php ext in source code");
        }
        $this->priority = $priority;
        $this->name = $name;
        $this->output = $output;
        $child_restart === null ?: $this->childRestart = $child_restart;
    }

    /**
     * @param callable $c 子进程的执行的回调函数， 回调函数的第一个参数为int 类型的进程编号， 第二个参数为$param
     * @param array $param 传递给地子进程回调函数的第二个参数
     *
     * 如果设置常量为Call::PROCESS_DATA_DISPATCH
     * 将会根据数组元素个数开启相同数量的子进程，
     * 并且数组的每一个元素都将被传递到子进程回调函数的第二个参数中，
     * 否则将整个数据传递到子进程回调函数的第二个参数，
     * 子进程的第一个参数为子进程编号
     * @param int|null $num 0, null for one call process
     * @param string|null $name
     * @param int|null $priority
     * @param int|null $child_restart
     * @return Executor
     * @throws ExecutorException|RunnerException|Throwable
     */
    public function runCall(callable $c, array $param = [], ?int $num = 1, ?string $name = null, ?int $priority = null, ?int $child_restart = null): Executor
    {
        $call = new Call();
        $call->add($c,
            $param,
            $num,
            $priority,
            $name,
            $child_restart === null ? $this->childRestart : $child_restart,
        );
        $e = new Executor($call, $this->name, $this->priority, $this->output);
        $e->stopChildOnFinish = $this->stopChildOnFinish;
        $e->start();
        return $e;
    }

    /**
     * @param array $c_arr
     * @return Executor
     * @throws CallException|ExecutorException|RunnerException|Throwable
     */
    public function runMultiCall(array $c_arr): Executor
    {
        $calls = new Call();
        $calls->multiAdd($c_arr);
        $e = new Executor($calls, $this->name, $this->priority, $this->output);
        $e->stopChildOnFinish = $this->stopChildOnFinish;
        $e->start();
        return $e;
    }

    /**
     *
     * @param callable $c 子进程的执行的回调函数， 回调函数的第一个参数为int 类型的进程编号， 第二个参数为$param
     * @param array $param 传递给地子进程回调函数的第二个参数
     *
     * 如果设置常量为Call::PROCESS_DATA_DISPATCH
     * 将会根据数组元素个数开启相同数量的子进程，
     * 并且数组的每一个元素都将被传递到子进程回调函数的第二个参数中，
     * 否则将整个数据传递到子进程回调函数的第二个参数，
     * 子进程的第一个参数为子进程编号
     * @param int|null $num 0, null for one call process
     * @param string|null $name
     * @param int|null $priority
     * @param int|null $child_restart
     * @return Wait
     * @throws ExecutorException|RunnerException|Throwable
     */
    public function runCallWait(callable $c, array $param = [], ?int $num = 1, ?string $name = null, ?int $priority = null, ?int $child_restart = null): Wait
    {
        return new Wait($this->runCall($c, $param, $num, $name, $priority, $child_restart));
    }

    /**
     * @param array[] $c_arr
     *
     * @return Wait
     * @throws CallException|ExecutorException|RunnerException|Throwable
     */
    public function runMultiCallWait(array $c_arr): Wait
    {
        return new Wait($this->runMultiCall($c_arr));
    }

    /**
     * @param Executor $e
     * @param bool $block
     * @param float $interval
     * @param callable|null $call_func_on_child_exit call first param is int for calls idx, second param is array for child process, other is extract of $call_func_param
     * @param array $call_func_param
     * @return void
     * @throws RunnerException|Throwable|ExecutorException
     */
    public function waitExecutor(Executor $e, bool $block = true, float $interval = 0.9, ?callable $call_func_on_child_exit = null, array $call_func_param = []): void
    {
        $wait = new Wait($e);
        $wait->wait($block, $interval, $call_func_on_child_exit, $call_func_param);
    }

    /**
     * @return bool
     */
    public function inDemonize(): bool
    {
        return $this->demonize;
    }

    /**
     * @return $this
     * @throws ProcessException
     */
    public function demonize(): self
    {
        $dp = pcntl_fork();
        if ($dp < 0) {
            throw new ProcessException("demonize failed: " . pcntl_strerror(pcntl_errno()));
        }
        $this->demonize = true;
        if ($dp > 0) {
            exit;
        }
        $sid = posix_setsid();
        if ($sid < 0) {
            throw new ProcessException("error in daemonize: " . posix_strerror(posix_get_last_error()));
        }
        return $this;
    }

    /**
     * @param string $path_key
     * @param bool|null $block set block if the queue is empty in pop or full in push
     * @param bool|null $exception_on_set_opt_failed
     * @return Queue
     * @throws IpcException
     */
    public static function getIpcQueue(string $path_key, ?bool $block = null, ?bool $exception_on_set_opt_failed = null): Queue
    {
        if (!function_exists('msg_get_queue')) {
            throw new IpcException("php extension sysvmsg not exists, please rebuild php in cli with --enable-sysvmsg option or build php ext in source code");
        }
        $queue = new Queue($path_key, null, $exception_on_set_opt_failed);
        if ($block !== null) {
            $queue->setBlock($block);
        }
        return $queue;
    }
}
