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

use phpth\process\exception\RunnerException;

class Runner
{
    /**
     * @var array
     */
    public array $sig_handle = [];

    /**
     * @var int
     */
    public int $main_pid = 0;

    /**
     * @var int
     */
    public int $pid = 0;

    /**
     * @var bool
     */
    public bool $stop_by_main_process_exit = false;

    /**
     * @var callable
     */
    protected $call;

    /**
     * @var array
     */
    protected array $param = [];

    /**
     * @var int
     */
    protected int $priority = 0;

    /**
     * @var string
     */
    protected string $name = '';

    /**
     * @var bool true is main process flow and false is child process flow
     */
    private bool $main = true;

    /**
     * @param callable $call
     * @param array $param
     * @param int $priority
     * @param string $name
     * @param int|null $main_pid
     */
    public function __construct(callable $call, array $param = [], int $priority = 0, string $name = '', ?int $main_pid = null)
    {
        $this->name = trim($name);
        $this->call = $call;
        $this->param = $param;
        $this->priority = $priority;
        if (!$main_pid) {
            $main_pid = getmypid();
        }
        $this->main_pid = $main_pid;
    }

    /**
     *
     * @return $this
     * @throws RunnerException
     */
    public function run(): self
    {
        if($this->pid){
            throw new RunnerException("there be runner for callable, please wait or call wait child process");
        }
        $this->pid = pcntl_fork();
        if ($this->pid != 0) {
            if ($this->pid == -1) {
                throw new RunnerException("create process failed: " . pcntl_strerror(pcntl_errno()));
            }
            if ($this->priority) {
                pcntl_setpriority($this->priority, $this->pid);
            }
            goto end;
        }
        $this->initChild();
        ($this->call)(...$this->param);
        p_exit:
        exit;
        end:
        return $this;
    }

    /**
     * @param $signal int|int[]
     * @param $handler callable
     *
     * @return $this
     * @throws RunnerException
     */
    public function setSigHandle($signal, callable $handler): self
    {
        Runner::checkSignal($signal);
        foreach ($signal as $sig) {
            $this->sig_handle[$sig] = $handler;
        }
        return $this;
    }

    /**
     * restore signal handle to default
     * @param $signal int|int[]
     *
     * @return $this
     * @throws RunnerException
     */
    public function restoreSigHandle($signal): self
    {
        Runner::checkSignal($signal);
        foreach ($signal as $sig) {
            $this->sig_handle[$sig] = SIG_DFL;
        }
        return $this;
    }

    /**
     * @param $signal int|int[]
     *
     * @return $this
     */
    public function delSigHandle($signal): self
    {
        $signal = (array)$signal;
        foreach ($signal as $sig) {
            unset($this->sig_handle[$sig]);
        }
        return $this;
    }

    /**
     * set ignore signal
     * @param $signal int|int[]
     *
     * @return $this
     * @throws RunnerException
     */
    public function setIgnoreSigHandle($signal): self
    {
        Runner::checkSignal($signal);
        foreach ($signal as $sig) {
            $this->sig_handle[$sig] = SIG_IGN;
        }
        return $this;
    }

    /**
     * @param $signal
     *
     * @return void
     * @throws RunnerException
     */
    public static function checkSignal(&$signal)
    {
        $signal = (array)$signal;
        foreach ($signal as $sig) {
            if (!is_numeric($sig) || $sig <= 0 || $sig > 64) {
                throw new RunnerException("un accept signal and must be number or error signal number must in 1~64");
            }
        }
    }

    /**
     * @param bool $block
     *
     * @return array ['pid'=>0, 'code'=> '', 'stop_signal'=> [], 'msg'=>'']
     */
    public function wait(bool $block = false): array
    {
        $res = Options::STATUS;
        if ($this->pid > 0) {
            $res['pid'] = pcntl_waitpid($this->pid, $status, $block ? WUNTRACED : WNOHANG | WUNTRACED);
            Runner::getStatus($status, $res);
            if($res['pid'] > 0){
                $this->pid = 0;
            }
        }
        return $res;
    }

    /**
     * @param int $status
     * @param array $res
     *
     * @return array
     */
    public static function getStatus(int $status, array &$res): array
    {
        if ($res['pid'] == -1) {
            $res['msg'] = 'wait failed';
            goto end;
        }
        $res['wait'] = true;
        if ($res['pid'] == 0) {
            $res['run'] = true;
            $res['msg'] = 'process still running';
            goto end;
        }
        $exit_code = pcntl_wexitstatus($status);
        $exit_code === false ?: $res['code'] = $exit_code;
        if (pcntl_wifexited($status)) {
            $res['self_exit'] = true;
            $res['msg'] = 'exited by process exception, error or other';
            goto end;
        }
        if (pcntl_wifstopped($status)) {
            $res['stop_signal'][] = Options::SIGNAL_LIST[pcntl_wstopsig($status)] ?? 'null';
        }
        if (pcntl_wifsignaled($status)) {
            $res['stop_signal'][] = Options::SIGNAL_LIST[pcntl_wtermsig($status)] ?? 'null';
        }
        $res['msg'] = "{$res['msg']}, stop_signal: " . join($res['stop_signal']);
        end:
        $res['msg'] = "{$res['msg']}, wait " . ($res['wait'] ? 'success' : 'failed');
        if ($res['code'] !== null) {
            $res['msg'] = "{$res['msg']}, code: {$res['code']}";
        }
        if ($res['pid'] > 0) {
            $res['msg'] = "{$res['msg']}, pid: {$res['pid']}";
        }
        return $res;
    }

    /**
     * @param int $signal
     *
     * @return bool|null
     */
    public function stop(int $signal = SIGTERM): bool
    {
        $res = null;
        if ($this->pid) {
            $res = posix_kill($this->pid, $signal);
        }
        return $res;
    }

    /**
     * @return void
     */
    protected function initChild()
    {
        $this->main = false;
        pcntl_async_signals(false);
        if ($this->name) {
            cli_set_process_title($this->name);
        }
        foreach ($this->sig_handle as $k => $v) {
            pcntl_signal($k, $v, false);
        }
        Options::restoreSignal(Options::SIGNAL_USE);
        global $main_pid;
        $main_pid = $this->main_pid;
        unset($this->sig_handle, $this->name, $this->priority, $this->main_pid);
    }

    /**
     * @return void
     */
    public function close()
    {
        // main process exit will send SIGTERM signal to child
        if($this->stop_by_main_process_exit){
            $this->stop();
        }
    }

    public function __destruct()
    {
        if ($this->main) {
            $this->close();
        } else {
            exit;
        }
    }
}
