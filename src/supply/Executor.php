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

use phpth\process\exception\ExecutorException;
use Generator;

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
    public bool $stop_child_on_exit = false;

    /**
     * @var int
     */
    public int $priority = 0;

    /**
     * @var string
     */
    public string $output = '';

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
    private array $run_list = [];

    /**
     * @var int
     */
    private int $main_pid = 0;

    /**
     * @var int
     */
    private int $exit = 0;

    /**
     * @param Call $call
     * @param string|null $name
     * @param int $priority
     * @param string|null $output
     */
    public function __construct(Call $call, ?string $name = '', int $priority = 0, ?string $output = "php://stdout")
    {
        $this->main_pid = getmypid();
        $this->priority = $priority;
        $this->call = $call;
        if ($name) {
            $this->name = "$name: " . Executor::$new_count;
        }
        Executor::$new_count++;
        if ($output) {
            // todo: event output to log
            $this->output = $output;
        }
    }

    /**
     * @return $this
     * @throws \phpth\process\exception\RunnerException
     */
    /**
     * @return $this
     * @throws ExecutorException
     * @throws \phpth\process\exception\RunnerException
     */
    public function start(): Executor
    {
        $this->init();
        $this->runChild();
        pcntl_async_signals(true);
        Options::registerSignal(Options::SIGNAL_USE, [$this, 'signalHandle']);
        Options::ignoreSignal(Options::SIGNAL_IGNORE);
        return $this;
    }

    /**
     * @param bool $block
     * @param float $interval
     *
     * @return Generator
     * @throws \phpth\process\exception\RunnerException
     */
    public function wait(bool $block = true, float $interval = 0.5): Generator
    {
        do {
            foreach ($this->run_list as $k => $v) {
                $restart_by = $this->calls[$k]['restart_by']; // great 0 is restart child
                $status = null;
                if($this->exit){
                    $restart_by = Call::EXIT_NO_START;
                    if($v){
                        $v->stop($this->exit);
                    }else{
                        unset($this->run_list[$k]);
                    }
                }
                if (!$v) {
                    goto cue;
                }
                $status = $v->wait($this->exit);
                $status['restart_by'] = $restart_by;
                if ($status['pid'] <= 0) {
                    goto cue;
                }
                if($restart_by >= Call::EXIT_ALL){
                    $this->run_list[$k] = null;
                    goto cue;
                }else if($restart_by > Call::EXIT_SIGNAL){
                    if($status['stop_signal'] || ($status['self_exit'] && $status['code'] === 0)){
                        $this->run_list[$k] = null;
                        goto cue;
                    }
                }else if($restart_by == Call::EXIT_SIGNAL){
                    if($status['stop_signal']){
                        $this->run_list[$k] = null;
                        goto cue;
                    }
                }else if($restart_by >= Call::EXIT_SELF){
                    if($status['self_exit']){
                        $this->run_list[$k] = null;
                        goto cue;
                    }
                }else if($restart_by >= Call::EXIT_NORMAL){
                    if($status['self_exit'] && $status['code'] === 0){
                        $this->run_list[$k] = null;
                        goto cue;
                    }
                }
                //stop and remove run list
                unset($this->run_list[$k]);
                cue:
                yield $k=>$status;
            }
            $this->runChild();
            if ($block) Options::sleep($interval);
        } while ($block && $this->inRun());
    }

    /**
     * @return bool
     */
    public function inRun(): bool
    {
        $res = false;
        if (count($this->run_list) > 0) {
            $res = true;
        }
        return $res;
    }

    /**
     * @param bool $force
     *
     * @return void
     * @throws \phpth\process\exception\RunnerException
     */
    public function stop(bool $force = false)
    {
        $this->exit = SIGTERM;
        if($force){
            $this->exit = SIGKILL;
        }
        $this->wait();
    }

    /**
     * @param bool $block
     *
     * @return array
     */
    public static function waitChild(bool $block = false): array
    {
        $res = Options::STATUS;
        $res['pid'] = pcntl_wait($status, $block ? WUNTRACED : WNOHANG | WUNTRACED);
        Runner::getStatus($status, $res);
        return $res;
    }

    /**
     * @return int
     */
    public function getMainPid(): int
    {
        return $this->main_pid;
    }

    /**
     * @param int $signal
     *
     * @return void
     * @throws \phpth\process\exception\RunnerException
     */
    public function signalHandle(int $signal)
    {
        switch ($signal) {
            case SIGTERM:
                $this->exit = SIGTERM;
                break;
            case SIGCHLD :
                $this->wait(false);
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
     *
     * @return mixed
     * @throws ExecutorException
     */
    public function errorHandle($e_no, $e_str, $e_file, $e_line)
    {
        throw new ExecutorException("$e_str in file $e_file:$e_line", $e_no);
    }

    /**
     * @return void
     * @throws \phpth\process\exception\RunnerException
     */
    protected function runChild()
    {
        foreach ($this->run_list as $k => $v) {
            if ($v) {
                continue;
            }
            $this->run_list[$k] = new Runner($this->calls[$k]['call'], $this->calls[$k]['param'], $this->calls[$k]['priority'], $this->calls[$k]['name'], $this->main_pid);
            $this->run_list[$k]->stop_by_main_process_exit = $this->stop_child_on_exit;
            $this->run_list[$k]->run();
        }
    }

    /**
     * @return void
     * @throws ExecutorException
     */
    protected function init()
    {
        if($this->inRun()){
            throw new ExecutorException("there is in run for callable list");
        }
        $this->exit = 0;
        $this->calls = $this->call->getCalls();
        $len = count($this->calls);
        if ($len <= 0) {
            throw new ExecutorException("calls is empty");
        }
        if($this->name){
            cli_set_process_title($this->name);
        }
        $this->run_list = array_combine(array_keys($this->calls), array_fill(0, $len, null));
        pcntl_setpriority($this->priority);
    }

    /**
     * @return void
     */
    public function close()
    {
        pcntl_async_signals(false);
    }

    public function __destruct()
    {
        $this->close();
    }
}
