<?php
// +----------------------------------------------------------------------
// | Program：multi-process
// +----------------------------------------------------------------------
// | Date: 2023/2/26 0026
// +----------------------------------------------------------------------
// | Author: js
// +----------------------------------------------------------------------
// | CreatedBy: phpStorm
// +----------------------------------------------------------------------

namespace phpth\process\supply;

class Status
{
    /**
     * @var bool 子进程是否还在运行中
     */
    public bool $run = false;

    /**
     * @var int 子进程pid
     */
    public int $pid = 0;

    /**
     * @var int|null 进程重启前的pid
     */
    public ?int $beforePid = null;

    /**
     * @var bool
     */
    public bool $isRestart = false;

    /**
     * @var int|null 子进程退出状态码
     */
    public ?int $code = null;

    /**
     * @var string 子进程状态描述信息
     */
    public string $msg = '';

    /**
     * @var bool 是否是子进程自身原因退出
     */
    public bool $selfExit = false;

    /**
     * @var bool wait子进程是否成功
     */
    public bool $wait = false;

    /**
     * @var int|null
     */
    public ?int $childRestart = null;

    /**
     * @var array
     */
    public array $stopSig = [];
}
