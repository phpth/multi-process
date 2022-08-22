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

use phpth\process\exception\IpcException;

class Queue
{
    public const IPC_MSG_TYPE = 1;

    /**
     * @var resource|SysvMessageQueue
     */
    protected $queue;

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
     *
     * @throws IpcException
     */
    public function __construct (string $path_key, ?Opt $opt = null)
    {
        if(!$path_key){
            throw new IpcException("path key can't empty");
        }
        $this->id = Opt::getId($path_key);
        if($opt){
            if($opt->mode){
                $opt->mode = Opt::DEF_MODE;
            }
            if($opt->total_size){
                $opt->total_size = Opt::DEF_TOTAL_SIZE;
            }
            if($opt->num){
                $opt->num = Opt::DEF_NUM;
            }
            if($opt->msg_max_size){
                $opt->msg_max_size = Opt::DEF_MSG_MAX_SIZE;
            }
            if($opt->block){
                $opt->block = true;
            }else{
                $opt->block = false;
            }
        }else{
            $opt = new Opt();
        }
        $this->opt = $opt;
        $this->queue = $this->getQueue($this->id, $this->opt->mode);
        if(!$this->queue){
            throw new IpcException("open queue failed");
        }
        $this->opt($opt);
    }

    /**
     * @param opt $opt
     *
     * @return void
     * @throws IpcException
     */
    public function opt(opt $opt)
    {
        $this->opt = $opt;
        $opt_arr = [];
        if($this->opt->uid){
            $opt_arr['msg_perm.uid'] = $this->opt->uid;
        }
        if($this->opt->gid){
            $opt_arr['msg_perm.gid'] = $this->opt->gid;
        }
        if($this->opt->num){
            $opt_arr['msg_qnum'] = $this->opt->num;
        }
        if($this->opt->total_size){
            $opt_arr['msg_qbytes'] = $this->opt->total_size;
        }
        if($this->opt->mode){
            $opt_arr['msg_perm.mode'] = $this->opt->mode;
        }
        Opt::errHdl();
        $r = msg_set_queue($this->queue, $opt_arr);
        restore_error_handler();
        if(!$r){
            throw new IpcException("error set queue opt");
        }
    }

    /**
     * 入列
     * @param $message
     * @return bool
     * @throws IpcException
     */
    public function push ($message):bool
    {
        Opt::errHdl();
        $res = msg_send ( $this -> queue , Queue::IPC_MSG_TYPE, $message , true, $this->opt->block, $error_code);
        restore_error_handler();
        if($error_code || !$res) {
            throw new IpcException("queue push failed, code: $error_code");
        }
        return true;
    }

    /**
     * 出列
     * @return mixed
     * @throws IpcException
     */
    public function pop ()
    {
        //MSG_IPC_NOWAIT | MSG_EXCEPT |MSG_NOERROR
        if($this->opt->block) {
            $flags = 0;
        } else {
            $flags = MSG_IPC_NOWAIT;
        }
        Opt::errHdl();
        $res = msg_receive ($this -> queue , 0, $msgtype , $this->opt->msg_max_size, $message, true, $flags, $error_code);
        restore_error_handler();
        if($error_code || !$res){
            throw new IpcException("queue pop failed, code: $error_code");
        }
        return $message;
    }

    /**
     * @param string|null $path_key
     * @param int|null $id
     *
     * @return bool
     * @throws IpcException
     */
    public function remove (?string $path_key = '', ?int $id = null): bool
    {
        if(!$path_key && !$id){
            $queue = $this->queue;
        }


        if($path_key){
            $id = Opt::getId($path_key);
            if(!msg_queue_exists($id)){
                return true;
            }
            $queue = $this->getQueue($id, Opt::DEF_MODE);
        }else if($id){
            if(!msg_queue_exists($id)){
                return true;
            }
            $queue = $this->getQueue($id, Opt::DEF_MODE);
        }
        if(!$queue){
            throw new IpcException("can't get target queue");
        }
        return msg_remove_queue ($queue);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        $res = false;
        if(empty($this->stat()['msg_qnum'])) {
           $res = true;
        }
        return $res;
    }

    /**
     *
     * @return array
     */
    public function stat (): array
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
        return msg_stat_queue ( $this->queue);
    }

    /**
     * @param int $id
     * @param int $per
     *
     * @return false|resource
     * @throws IpcException
     */
    protected function getQueue(int $id, int $per = 0666)
    {
        Opt::errHdl();
        $queue = msg_get_queue($id, $per);
        restore_error_handler();
        return $queue;
    }
}
