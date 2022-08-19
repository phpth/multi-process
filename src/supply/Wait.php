<?php

namespace phpth\process\supply;

use \phpth\process\exception\ExecutorException;
use \phpth\process\exception\RunnerException;

class Wait
{
    /**
     * @var Executor
     */
    private Executor $e;

    public function __construct(Executor $e)
    {
        $this->e = $e;
    }

    /**
     * @param bool $block
     * @param float $interval
     * @param callable|null $call_func_on_child_exit
     * @param array $call_func_param
     *
     * @return void
     * @throws ExecutorException
     * @throws RunnerException
     */
    public function wait(bool $block = true, float $interval = 0.9, ?callable $call_func_on_child_exit = null, array $call_func_param = [])
    {
        if (!$this->e->inRun()) {
            $this->e->start();
        }
        foreach ($this->e->wait($block, $interval) as $k => $v) {
            if ($v && $v['pid'] > 0 && is_callable($call_func_on_child_exit)) {
                $call_func_on_child_exit($k, $v, ...$call_func_param);
            }
        }
    }
}