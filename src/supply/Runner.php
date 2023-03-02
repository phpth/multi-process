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
use Throwable;
use phpth\process\exception\OutputException;
use phpth\process\exception\RunnerException;

class Runner
{
    /**
     * @var array
     */
    public array $sigHandle = [];

    /**
     * @var int
     */
    public int $mainPid = 0;

    /**
     * @var int
     */
    public int $pid = 0;

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
     * @var null|Stdout|Closure
     */
    private null|Stdout|Closure $output;

    /**
     * @var Status|null
     */
    private ?Status $stat;

    /**
     * @param callable $call
     * @param array $param
     * @param int $priority
     * @param string $name
     * @param int|null $main_pid
     * @param Stdout|Closure|null $output
     */
    public function __construct(callable $call, array $param = [], int $priority = 0, string $name = '', ?int $main_pid = null, null|Stdout|Closure $output = Stdout::STD)
    {
        $this->name = trim($name);
        $this->call = $call;
        $this->param = $param;
        $this->priority = $priority;
        if (!$main_pid) {
            $main_pid = getmypid();
        }
        $this->mainPid = $main_pid;
        $this->output = $output;
        $this->stat = new Status();
    }

    /**
     * @return int current process no
     */
    public function getProcessNo(): ?int
    {
        return $this->param[0]??null;
    }

    /**
     * @return $this
     * @throws OutputException|RunnerException
     */
    public function run(): static
    {
        if ($this->pid) {
            throw new RunnerException("there be runner for callable, please wait or call wait child process");
        }
        $this->pid = pcntl_fork();
        if ($this->pid == 0) {
            define('CHILD', true);
            goto child;
        }
        if ($this->pid == -1) {
            throw new RunnerException("create process failed: " . pcntl_strerror(pcntl_errno()));
        }
        // set child priority
        if ($this->priority) {
            pcntl_setpriority($this->priority, $this->pid);
        }
        return $this;
        child:
        // child process execute
        $exitCode = 0;
        try {
            $this->initChild();
            ($this->call)(...$this->param);
        } catch (Throwable $e) {
            $exitCode = 255;
            echo "No{$this->getProcessNo()} child process {".getmypid()."} has been exception: [{$e->getCode()}]{$e->getMessage()} in file {$e->getFile()}:{$e->getLine()}\n{$e->getTraceAsString()}\n";
        } finally {
            $this->flushOutput();
            exit($exitCode);
        }
    }

    /**
     * @param $signal int|int[]
     * @param $handler callable
     *
     * @return static
     * @throws RunnerException
     */
    public function setSigHandle(array|int $signal, callable $handler): static
    {
        Runner::checkSignal($signal);
        foreach ($signal as $sig) {
            $this->sigHandle[$sig] = $handler;
        }
        return $this;
    }

    /**
     * restore signal handle to default
     * @param $signal int|int[]
     *
     * @return static
     * @throws RunnerException
     */
    public function restoreSigHandle(array|int $signal): static
    {
        Runner::checkSignal($signal);
        foreach ($signal as $sig) {
            $this->sigHandle[$sig] = SIG_DFL;
        }
        return $this;
    }

    /**
     * @param $signal int|int[]
     *
     * @return static
     */
    public function delSigHandle(array|int $signal): static
    {
        $signal = (array)$signal;
        foreach ($signal as $sig) {
            unset($this->sigHandle[$sig]);
        }
        return $this;
    }

    /**
     * set ignore signal
     * @param $signal int|int[]
     *
     * @return static
     * @throws RunnerException
     */
    public function setIgnoreSigHandle(array|int $signal): static
    {
        Runner::checkSignal($signal);
        foreach ($signal as $sig) {
            $this->sigHandle[$sig] = SIG_IGN;
        }
        return $this;
    }

    /**
     * @param $signal
     *
     * @return void
     * @throws RunnerException
     */
    public static function checkSignal(&$signal): void
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
     * @return Status
     */
    public function wait(bool $block = false): Status
    {
        if(!$this->pid){
            goto end;
        }
        $this->stat->pid = $this->pid;
        $stopPid = pcntl_waitpid($this->pid, $statusCode, $block ? WUNTRACED : WNOHANG | WUNTRACED);
        if ($stopPid > 0) {
            $this->pid = 0;
        }
        Runner::getStatusInfo($statusCode, $stopPid, $this->stat);
        end:
        return clone $this->stat;
    }

    /**
     * @param int|null $statusCode
     * @param int|null $stopPid
     * @param Status $status
     * @return void
     */
    public static function getStatusInfo(?int $statusCode, ?int $stopPid, Status $status): void
    {
        // init
        $status->code = null;
        $status->run = false;
        $status->msg = '';
        $status->beforePid = null;
        $status->childRestart = null;
        $status->stopSig = [];
        $status->isRestart = false;
        $status->selfExit = false;
        $status->wait = false;
        if ($stopPid == -1) {
            $status->msg = 'wait failed';
            goto end;
        }
        $status->wait = true;
        if ($stopPid == 0) {
            $status->run = true;
            $status->msg = 'process still running';
            goto end;
        }
        $exit_code = pcntl_wexitstatus($statusCode);
        $exit_code === false ?: $status->code = $exit_code;
        if (pcntl_wifexited($statusCode)) {
            $status->selfExit = true;
            $status->msg = 'exited by process exception error or other';
            goto end;
        }
        if (pcntl_wifstopped($statusCode)) {
            $status->stopSig['stopSig'] = Options::SIGNAL_LIST[pcntl_wstopsig($statusCode)] ?? 'null';
        }
        if (pcntl_wifsignaled($statusCode)) {
            $status->stopSig['termSig'] = Options::SIGNAL_LIST[pcntl_wtermsig($statusCode)] ?? 'null';
        }
        if($status->stopSig){
            $status->msg = "stop or term signal: " . json_encode($status->stopSig);
        }

        end:
        $status->msg = "{$status->msg}, wait " . ($status->wait ? 'success' : 'failed');
        if ($status->code !== null) {
            $status->msg = "{$status->msg}, code: {$status->code}";
        }
        if ($status->pid > 0) {
            $status->msg = "{$status->msg}, pid: {$status->pid}";
        }
        $status->msg = trim($status->msg);
    }

    /**
     * @param int $signal
     *
     * @return bool|null
     */
    public function stop(int $signal = SIGTERM): ?bool
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
    protected function initChild(): void
    {
        if ($this->name) {
            cli_set_process_title($this->name);
        }
        //redirect output
        $this->setOutput();
        foreach ($this->sigHandle as $k => $v) {
            pcntl_signal($k, $v, false);
        }
        Options::restoreSignal(Options::SIGNAL_USE);
        global $mainPid;
        $mainPid = $this->mainPid;
        $this->name = '';
        $this->sigHandle = [];
        $this->priority = 0;
        $this->mainPid = 0;
    }

    /**
     * @return void
     */
    protected function setOutput(): void
    {
        if ($this->output) {
            ob_start(function (string $output, int $phase) {
                try{
                    ($this->output)($output, $this->getProcessNo());
                }catch (Throwable){}
                return '';
            }, Stdout::$flushSize);
        }
    }

    /**
     * @return void
     */
    protected function flushOutput(): void
    {
        if ($this->output && ob_get_length()) {
            ob_end_flush();
        }
    }

    /**
     * @return void
     */
    protected function close(): void
    {
        // parent do clean or other
    }

    public function __destruct()
    {
        if (defined('CHILD')) {
            $this->flushOutput();
            exit;
        }else{
            $this->close();
        }
    }
}
