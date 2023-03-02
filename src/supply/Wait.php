<?php
// +----------------------------------------------------------------------
// | multi-process
// +----------------------------------------------------------------------
// | Copyright (c) 2023
// +----------------------------------------------------------------------
// | Licensed MIT
// +----------------------------------------------------------------------
// | Author: js
// +----------------------------------------------------------------------
// | Date: 2023-02-25
// +----------------------------------------------------------------------
// | Time: 下午 03:22
// +----------------------------------------------------------------------

namespace phpth\process\supply;

use phpth\process\exception\ExecutorException;
use phpth\process\exception\RunnerException;
use Throwable;

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
     * @return void
     * @throws ExecutorException|RunnerException|Throwable
     */
    public function wait(bool $block = true, float $interval = 0.9, ?callable $call_func_on_child_exit = null, array $call_func_param = []): void
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