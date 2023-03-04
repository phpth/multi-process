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

namespace phpth\process\supply;

use Closure;
use Generator;
use phpth\process\exception\ExecutorException;
use phpth\process\exception\OutputException;
use phpth\process\exception\PathException;
use phpth\process\exception\RunnerException;

class Executor
{
    /**
     * @var array[]
     */
    public array $calls = [];

    /**
     * @var Call
     */
    public Call $call;

    /**
     * @var bool
     */
    public bool $stopChildOnFinish = false;

    /**
     * @var int
     */
    public int $priority = 0;


    /**
     * @var null|string|Closure  如果是回调函数，则会在子进程输出时调用，第一个参数为输出内容，第二个参数为进程编号, 如果是字符串， 这被视为输出目录或者文件
     */
    public null|string|Closure $output = Stdout::STD;

    /**
     * @var string
     */
    public string $name = '';

    /**
     * @var int
     */
    public static int $new_count = 0;

    /**
     * @var array
     */
    private array $runList = [];

    /**
     * @var int
     */
    private int $mainPid;

    /**
     * @var int
     */
    private int $exit = 0;

    /**
     * @var bool is called in wait
     */
    private bool $waiting = false;

    /**
     * @param Call $call
     * @param string|null $name
     * @param int $priority
     * @param string|Closure|null $output 如果是回调函数，则会在子进程输出时调用，第一个参数为输出内容，第二个参数为进程编号, 如果是字符串， 这被视为输出目录或者文件
     */
    public function __construct(Call $call, ?string $name = '', int $priority = 0, null|string|Closure $output = Stdout::STD)
    {
        $this->mainPid = getmypid();
        $this->priority = $priority;
        $this->call = $call;
        if ($name) {
            $this->name = "$name: " . Executor::$new_count;
        }
        Executor::$new_count++;
        $this->output = $output;
    }

    /**
     * @return static
     * @throws RunnerException|ExecutorException|OutputException|PathException
     */
    public function start(): static
    {
        $this->init();
        foreach($this->runList as $processNo=>&$runner){
            $this->mustRun($runner, $processNo);
        }
        Options::registerSignal(Options::SIGNAL_USE, [$this, 'signalHandle']);
        Options::ignoreSignal(Options::SIGNAL_IGNORE);
        return $this;
    }

    /**
     * @param bool $block
     * @param float $interval
     * @return Generator
     * @throws ExecutorException
     * @throws OutputException
     * @throws PathException
     * @throws RunnerException
     */
    public function wait(bool $block = true, float $interval = 0.5): Generator
    {
        $this->waiting = true;
        if(!$this->inRun()){
            throw new ExecutorException("should call start on this object first");
        }
        do {
            yield from $this->waitClean();
            if ($block && $interval) Options::sleep($interval);
        } while ($block && $this->inRun());
        $this->waiting = false;
    }

    /**
     * @return bool
     */
    public function inRun(): bool
    {
        $res = false;
        if (count($this->runList) > 0) {
            $res = true;
        }
        return $res;
    }

    /**
     * @param bool $force
     * @return void
     * @throws ExecutorException
     * @throws OutputException
     * @throws PathException
     * @throws RunnerException
     */
    public function stop(bool $force = false): void
    {
        $this->exit = SIGTERM;
        if ($force) {
            $this->exit = SIGKILL;
        }
        $this->wait(true, 0.001);
    }

    /**
     * @return int
     */
    public function getMainPid(): int
    {
        return $this->mainPid;
    }

    /**
     * @param int $signal
     * @return void
     * @throws OutputException
     * @throws PathException
     * @throws RunnerException
     */
    public function signalHandle(int $signal): void
    {
        switch ($signal) {
            case SIGTERM:
                $this->exit = SIGTERM;
                break;
            case SIGCHLD :
                // free stopped child resource.
                // if call this object wait method, it only frees resource, or it will be checked child process status
                $this->waitClean($this->waiting)->current();
                break;
            case SIGHUP:
                break;
        }
    }

    /**
     * @param $e_no
     * @param $e_str
     * @param $e_file
     * @param $e_line
     * @throws ExecutorException
     */
    public function errorHandle($e_no, $e_str, $e_file, $e_line)
    {
        throw new ExecutorException("$e_str in file $e_file:$e_line", $e_no);
    }

    /**
     * @param bool $onlyWait true: ignore check runner status
     * @return Generator
     * @throws OutputException
     * @throws PathException
     * @throws RunnerException
     */
    protected function waitClean(bool $onlyWait = false): Generator
    {
        /**@var Runner $runner*/
        foreach ($this->runList as $processNo => &$runner) {
            $status = $runner->wait();
            $status->childRestart = $this->calls[$processNo]['child_restart']; // great 0 is restart child
            if($onlyWait){
                continue;
            }
            if($status->run){
                goto cue;
            }
            $status->beforePid = $status->pid;
            $restart = $this->shouldRestart($status);
            if(!$restart){
                //stop and remove run list
                unset($this->runList[$processNo]);
                goto cue;
            }
            $this->mustRun($runner, $processNo);
            $status->isRestart = true;
            $status->msg = trim("{$status->msg}, process is success restarted", ", ");
            cue:
            if($this->waiting){
                yield $processNo => $status;
            }
        }
    }

    /**
     * @param Status $status
     * @return bool
     */
    protected function shouldRestart(Status $status): bool
    {
        $restart = true;
        if($this->exit){
            $restart = false;
            goto end;
        }
        if ($status->run) {
            $restart = false;
            goto end;
        }
        if ($status->childRestart >= ChildRestart::EXIT_ANY) {
            goto end;
        }
        if ($status->childRestart > ChildRestart::EXIT_ON_SIGNAL && ($status->stopSig || ($status->selfExit && $status->code === 0))) {
            goto end;
        }
        if ($status->childRestart == ChildRestart::EXIT_ON_SIGNAL && $status->stopSig) {
            goto end;
        }
        if ($status->childRestart >= ChildRestart::ABNORMAL_EXIT && $status->selfExit) {
            goto end;
        }
        if ($status->childRestart >= ChildRestart::NORMAL_FINISH && $status->selfExit && $status->code === 0) {
            goto end;
        }
        $restart = false;
        end:
        return $restart;
    }

    /**
     * make runner in running
     * @param Runner|null $runner
     * @param int $processNo
     * @return bool is call run
     * @throws OutputException
     * @throws PathException
     * @throws RunnerException
     */
    protected function mustRun(?Runner &$runner, int $processNo): bool
    {
        $isCallRun = false;
        if(!$runner){
            $runner = $this->createRunner($processNo);
        }
        if(!$runner->pid){
            $runner->run();
            $isCallRun = true;
        }
        return $isCallRun;
    }

    /**
     * @param int $processNo
     * @return Runner
     * @throws PathException
     */
    protected function createRunner(int $processNo): Runner
    {
        $param = [$processNo, $this->calls[$processNo]['param']];
        $name = Call::getCallName($this->calls[$processNo]['call'], $param, $this->calls[$processNo]['name']);
        return new Runner(
            $this->calls[$processNo]['call'],
            $param,
            $this->calls[$processNo]['priority'],
            $name,
            $this->mainPid,
            is_string($this->output) ? new Stdout($this->output, $processNo) : $this->output
        );
    }
    
    /**
     * @return void
     * @throws ExecutorException
     */
    protected function init(): void
    {
        if ($this->inRun()) {
            throw new ExecutorException("there is in run for callable list");
        }
        pcntl_async_signals(true);
        $this->exit = 0;
        $this->calls = $this->call->getCalls();
        $len = count($this->calls);
        if ($len <= 0) {
            throw new ExecutorException("calls is empty");
        }
        if ($this->name) {
            cli_set_process_title($this->name);
        }
        $this->runList = array_combine(array_keys($this->calls), array_fill(0, $len, null));
        pcntl_setpriority($this->priority);
    }

    /**
     * @return void
     */
    protected function close(): void
    {
        // free source
    }

    /**
     * @throws ExecutorException
     * @throws OutputException
     * @throws PathException
     * @throws RunnerException
     */
    public function __destruct()
    {
        $this->close();
        // parent finish stop child
        if($this->stopChildOnFinish){
            $this->stop(true);
        }
    }
}
