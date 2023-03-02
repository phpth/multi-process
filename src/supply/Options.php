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


use phpth\process\exception\PathException;

class Options
{
    public const SIGNAL_USE = [
        SIGTERM,
        SIGCHLD,
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
    public static function restoreSignal(array|int $signals): void
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
    public static function registerSignal(array|int $signals, callable $handle): void
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
    public static function ignoreSignal(array|int $signals): void
    {
        $signals = (array)$signals;
        foreach ($signals as $signal) {
            pcntl_signal($signal, SIG_IGN, false);
        }
    }

    /**
     * @param string $path_file
     * @param bool $only_path
     * @param string $append_name_on_path 如果传入的第一个参数是目录，则附加一个文件名上去
     * @return string
     * @throws PathException
     */
    public static function outPathFile(string $path_file = '', bool $only_path = false, string $append_name_on_path = ''): string
    {
        if (!$path_file) {
            $path_file = './';
        }
        $path_file = rtrim(str_replace('\\', '/', $path_file), '/');
        if ($path_file === '.') {
            $path_file = realpath('./');
        } else if ($path_file === '..') {
            $path_file = realpath('../');
        }
        $idx = strrpos($path_file, '/');
        if ($idx === false) {
            $file = $path_file;
            $path = './';
            goto end;
        }
        $file = substr($path_file, $idx + 1);
        if (!str_contains($file, '.')) {
            $path = $path_file;
            $file = $append_name_on_path;
        } else {
            $path = dirname($path_file);
        }
        end:
        if (!is_dir($path)) {
            error_clear_last();
            @mkdir($path, 0644, true);
            $lsgErr = error_get_last();
            if ($lsgErr) {
                throw new PathException("$path create failed, {$lsgErr['message']} in file {$lsgErr['file']}:{$lsgErr['line']}");
            }
        }
        $path = rtrim(realpath($path), '/');
        return str_replace('\\', '/', $only_path ? $path : "$path/$file");
    }
}
