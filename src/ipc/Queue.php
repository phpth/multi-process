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

namespace phpth\process\ipc;

use phpth\process\exception\IpcEmptyException;
use phpth\process\exception\IpcException;
use phpth\process\exception\IpcFullException;
use SysvMessageQueue;

class Queue
{
    /**
     * default msg type
     */
    public const IPC_MSG_TYPE = 1;

    /**
     * @var bool in some system or not –privileged container, set opt will fail, use this option to ignore exception
     */
    public bool $exceptionOnSetOptFailed = false;

    /**
     * @var false|SysvMessageQueue
     */
    protected SysvMessageQueue|false $queue;

    /**
     * @var int
     */
    protected int $id;

    /**
     * @var Opt|null
     */
    protected ?Opt $opt;

    /**
     * @param string $path_key
     * @param Opt|null $opt
     * @param bool|null $exception_on_set_opt_failed
     * @throws IpcException
     */
    public function __construct(string $path_key, ?Opt $opt = null, ?bool $exception_on_set_opt_failed = null)
    {
        if ($exception_on_set_opt_failed !== null) {
            $this->exceptionOnSetOptFailed = $exception_on_set_opt_failed;
        }
        if (!$path_key) {
            throw new IpcException("path key can't empty");
        }
        $this->id = Opt::getId($path_key);
        if ($opt) {
            if ($opt->mode) {
                $opt->mode = Opt::DEF_MODE;
            }
            if ($opt->totalSize) {
                $opt->totalSize = Opt::DEF_TOTAL_SIZE;
            }
            if ($opt->msgMaxSize) {
                $opt->msgMaxSize = Opt::DEF_MSG_MAX_SIZE;
            }
            if ($opt->block) {
                $opt->block = true;
            } else {
                $opt->block = false;
            }
        } else {
            $opt = new Opt();
        }
        $this->opt = $opt;
        $this->queue = $this->getQueue($this->id, $this->opt->mode);
        if (!$this->queue) {
            throw new IpcException("open queue failed");
        }
        $this->opt($opt);
    }

    /**
     * @param Opt $opt
     * @return void
     * @throws IpcException
     */
    public function opt(Opt $opt): void
    {
        $this->opt = $opt;
        $opt_arr = [];
        if ($this->opt->uid) {
            $opt_arr['msg_perm.uid'] = $this->opt->uid;
        }
        if ($this->opt->gid) {
            $opt_arr['msg_perm.gid'] = $this->opt->gid;
        }
        if ($this->opt->totalSize) {
            $opt_arr['msg_qbytes'] = $this->opt->totalSize;
        }
        if ($this->opt->mode) {
            $opt_arr['msg_perm.mode'] = $this->opt->mode;
        }
        Opt::errHdl();
        $r = msg_set_queue($this->queue, $opt_arr);
        restore_error_handler();
        if ($this->exceptionOnSetOptFailed && !$r) {
            throw new IpcException("error set queue opt");
        }
    }

    /**
     * set block if the queue is empty in pop or full in push
     * @param bool $block
     * @return static
     */
    public function setBlock(bool $block): static
    {
        $this->opt->block = $block;
        return $this;
    }

    /**
     * push msg to queue
     * @param float|bool|int|array|string|null $message
     * @return bool
     * @throws IpcException|IpcFullException
     */
    public function push(float|bool|int|array|string|null $message): bool
    {
        Opt::errHdl();
        try {
            $res = msg_send($this->queue, Queue::IPC_MSG_TYPE, $message, true, $this->opt->block, $error_code);
        } catch (IpcException $e) {
            if ($error_code == MSG_EAGAIN) {
                throw new IpcFullException("{$e->getMessage()}, queue is full, please adjust the number of consumer or situation queue data");
            }
            throw $e;
        } finally {
            restore_error_handler();
        }
        if ($error_code || !$res) {
            throw new IpcException("queue push failed, code: $error_code");
        }
        return true;
    }

    /**
     * 出列
     * @return float|bool|int|array|string|null
     * @throws IpcException|IpcEmptyException
     */
    public function pop(): float|bool|int|array|string|null
    {
        if ($this->opt->block) {
            $flags = 0;
        } else {
            $flags = MSG_IPC_NOWAIT;
        }
        Opt::errHdl();
        $res = msg_receive($this->queue, 0, $mistype, $this->opt->msgMaxSize, $message, true, $flags, $error_code);
        restore_error_handler();
        if ($error_code || !$res) {
            if ($error_code == MSG_ENOMSG) {
                throw new IpcEmptyException("msg queue is empty");
            }
            throw new IpcException("queue pop failed, code: $error_code");
        }
        return $message;
    }

    /**
     * @param string|null $path_key
     * @param int|null $id
     * @return bool
     * @throws IpcException
     */
    public function remove(?string $path_key = '', ?int $id = null): bool
    {
        $queue = $this->queue;
        if ($path_key) {
            $id = Opt::getId($path_key);
            if (!msg_queue_exists($id)) {
                return true;
            }
            $queue = $this->getQueue($id, Opt::DEF_MODE);
        } else if ($id) {
            if (!msg_queue_exists($id)) {
                return true;
            }
            $queue = $this->getQueue($id, Opt::DEF_MODE);
        }
        if (!$queue) {
            throw new IpcException("can't get target queue");
        }
        return msg_remove_queue($queue);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        $res = false;
        if (empty($this->stat()['msg_qnum'])) {
            $res = true;
        }
        return $res;
    }

    /**
     *
     * @return array
     */
    public function stat(): array
    {
        //[
        //'msg_perm.uid'  => '' ,
        //组id
        //'msg_perm.gid'  => '' ,
        //队列的权限
        //'msg_perm.mode' => 0666 ,
        //队列的最后的发送时间
        //'msg_stime'     => '' ,
        //队列的最后接收时间
        //'msg_rtime'     => '' ,
        //队列的最后修改时间
        //'msg_ctime'     => '' ,
        //队列的数量
        //'msg_qnum'      => '' ,
        //队列的大小
        //'msg_qbytes'    => 1024*1024*500,
        //最后发送的进程pid
        //'msg_lspid'     => '' ,
        //最后接收的进程pid
        //'msg_lrpid'     => '' ,
        //];
        return msg_stat_queue($this->queue);
    }

    /**
     * @param int $id
     * @param int $per
     * @return false|SysvMessageQueue
     * @throws IpcException
     */
    protected function getQueue(int $id, int $per = 0666): bool|SysvMessageQueue
    {
        Opt::errHdl();
        $queue = msg_get_queue($id, $per);
        restore_error_handler();
        return $queue;
    }
}
