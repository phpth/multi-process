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

use phpth\process\exception\CallException;
use phpth\process\exception\ExecutorException;
use phpth\process\exception\ProcessException;
use phpth\process\exception\RunnerException;
use phpth\process\ipc\Queue;
use phpth\process\supply\Call;
use phpth\process\supply\Executor;
use phpth\process\supply\Wait;

class Process
{
    public int $priority = 0;
    public bool $stop_child_on_exit = false;
    public int $restart_by = Call::EXIT_ALL;
    public ?string $name = null;
    public ?string $output = null;
    public Call $call;
    public Executor $executor;
    private bool $demonize = false;

    /**
     * @param string|null $name
     * @param int $priority
     * @param int|null $restart_by
     * @param string|null $output
     */
    public function __construct(?string $name = null, int $priority = 0, ?int $restart_by = null, ?string $output = null)
    {
        $this->priority = $priority;
        $this->name = $name;
        $this->output = $output;
        $restart_by === null ?: $this->restart_by = $restart_by;
    }

    /**
     * @param callable $c
     * @param array $param
     * @param int|null $num
     * @param string|null $name
     * @param int|null $priority
     * @param int|null $restart_by
     *
     * @return Executor
     * @throws ExecutorException
     * @throws RunnerException
     */
    public function runCall(callable $c, array $param = [], ?int $num = 1, ?string $name = null, ?int $priority = null, ?int $restart_by = null): Executor
    {
        $call = new Call();
        $call->add($c,
            $param,
            $num,
            $priority,
            $name,
            $restart_by === null ? $this->restart_by : $restart_by,
        );
        $e = new Executor($call, $this->name, $this->priority, $this->output);
        $e->stop_child_on_exit = $this->stop_child_on_exit;
        $e->start();
        return $e;
    }

    /**
     * @param array[] $c_arr
     *
     * @return Executor
     * @throws CallException
     * @throws ExecutorException
     * @throws RunnerException
     */
    public function runMultiCall(array $c_arr): Executor
    {
        $calls = new Call();
        $calls->multiAdd($c_arr);
        $e = new Executor($calls, $this->name, $this->priority, $this->output);
        $e->stop_child_on_exit = $this->stop_child_on_exit;
        $e->start();
        return $e;
    }

    /**
     * @param callable $c
     * @param array $param
     * @param int|null $num
     * @param string|null $name
     * @param int|null $priority
     * @param int|null $restart_by
     *
     * @return Wait
     * @throws ExecutorException
     * @throws RunnerException
     */
    public function runCallWait(callable $c, array $param = [], ?int $num = 1, ?string $name = null, ?int $priority = null, ?int $restart_by = null): Wait
    {
        return new Wait($this->runCall($c, $param, $num, $name, $priority, $restart_by));
    }

    /**
     * @param array[] $c_arr
     *
     * @return Wait
     * @throws CallException
     * @throws ExecutorException
     * @throws RunnerException
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
     *
     * @return void
     * @throws ExecutorException
     * @throws RunnerException
     */
    public function waitExecutor(Executor $e, bool $block = true, float $interval = 0.9, ?callable $call_func_on_child_exit = null, array $call_func_param = [])
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
        if($dp < 0){
            throw new ProcessException("demonize failed: ".pcntl_strerror(pcntl_errno()));
        }
        $this->demonize = true;
        if($dp > 0){
            exit ;
        }
        $sid = posix_setsid();
        if($sid < 0){
            throw new ProcessException("error in daemonize");
        }
        return $this;
    }

    /**
     * @param string $path_key
     *
     * @return Queue
     * @throws exception\IpcException
     */
    public static function getIpcQueue(string $path_key): Queue
    {
        return new Queue($path_key);
    }
}
