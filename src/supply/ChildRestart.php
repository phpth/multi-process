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
// | Time: 下午 09:08
// +----------------------------------------------------------------------

namespace phpth\process\supply;

/**
 * indicate the manager process how to relaunch child process
 */
class ChildRestart
{
    /**
     * indicate the manager process not relaunch child process
     */
    public const NO_RESTART = 0;

    /**
     * indicate the manager process relaunch which the child process on normal finished
     */
    public const NORMAL_FINISH = 1;

    /**
     * indicate the manager process relaunch which the child process exit on no zero exit
     */
    public const ABNORMAL_EXIT = 2;

    /**
     * indicate the manager process relaunch which the child process exit on uncatched signal
     */
    public const EXIT_ON_SIGNAL = 4;

    /**
     * indicate the manager process relaunch any exited child process
     */
    public const EXIT_ANY = 6;
}
