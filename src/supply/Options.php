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


class Options
{
    public const SIGNAL_USE = [
        SIGTERM,
        SIGCHLD,
    ];

    public const STATUS = [
        'code' => null,
        'run' => false,
        'self_exit' => false,
        'wait' => false,
        'pid' => 0,
        'stop_signal' => [],
        'msg' => '',
    ];

    public const SIGNAL_IGNORE = [
        SIGHUP
    ];

    public const SIGNAL_LIST = [
        1 => 'SIGHUP',
        2 => 'SIGINT',
        3 => 'SIGQUIT',
        4 => 'SIGILL',
        5 => 'SIGTRAP',
        6 => 'SIGABRT',
        7 => 'SIGBUS',
        8 => 'SIGFPE',
        9 => 'SIGKILL',
        10 => 'SIGUSR1',
        11 => 'SIGSEGV',
        12 => 'SIGUSR2',
        13 => 'SIGPIPE',
        14 => 'SIGALRM',
        15 => 'SIGTERM',
        17 => 'SIGCHLD',
        18 => 'SIGCONT',
        19 => 'SIGSTOP',
        20 => 'SIGTSTP',
        21 => 'SIGTTIN',
        22 => 'SIGTTOU',
        23 => 'SIGURG',
        24 => 'SIGXCPU',
        25 => 'SIGXFSZ',
        26 => 'SIGVTALRM',
        27 => 'SIGPROF',
        28 => 'SIGWINCH',
        29 => 'SIGIO',
        30 => 'SIGPWR',
        31 => 'SIGSYS',
        34 => 'SIGRTMIN',
        35 => 'SIGRTMIN+1',
        36 => 'SIGRTMIN+2',
        37 => 'SIGRTMIN+3',
        38 => 'SIGRTMIN+4',
        39 => 'SIGRTMIN+5',
        40 => 'SIGRTMIN+6',
        41 => 'SIGRTMIN+7',
        42 => 'SIGRTMIN+8',
        43 => 'SIGRTMIN+9',
        44 => 'SIGRTMIN+10',
        45 => 'SIGRTMIN+11',
        46 => 'SIGRTMIN+12',
        47 => 'SIGRTMIN+13',
        48 => 'SIGRTMIN+14',
        49 => 'SIGRTMIN+15',
        50 => 'SIGRTMAX-14',
        51 => 'SIGRTMAX-13',
        52 => 'SIGRTMAX-12',
        53 => 'SIGRTMAX-11',
        54 => 'SIGRTMAX-10',
        55 => 'SIGRTMAX-9',
        56 => 'SIGRTMAX-8',
        57 => 'SIGRTMAX-7',
        58 => 'SIGRTMAX-6',
        59 => 'SIGRTMAX-5',
        60 => 'SIGRTMAX-4',
        61 => 'SIGRTMAX-3',
        62 => 'SIGRTMAX-2',
        63 => 'SIGRTMAX-1',
        64 => 'SIGRTMAX',
    ];

    /**
     * @param float $sleep
     *
     * @return void
     */
    public static function sleep(float $sleep): void
    {
        usleep((int)($sleep * 1000000));
    }

    /**
     * @param int|int[] $signals
     *
     * @return void
     */
    public static function restoreSignal($signals)
    {
        $signals = (array)$signals;
        foreach ($signals as $signal) {
            pcntl_signal($signal, SIG_DFL, false);
        }
    }

    /**
     * @param int|int[] $signals
     * @param callable $handle
     *
     * @return void
     */
    public static function registerSignal($signals, callable $handle)
    {
        $signals = (array)$signals;
        foreach ($signals as $signal) {
            pcntl_signal($signal, $handle, false);
        }
    }

    /**
     * @param int|int[] $signals
     *
     * @return void
     */
    public static function ignoreSignal($signals)
    {
        $signals = (array)$signals;
        foreach ($signals as $signal) {
            pcntl_signal($signal, SIG_IGN, false);
        }
    }
}
