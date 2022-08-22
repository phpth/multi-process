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

class Opt
{
    public const DEF_TOTAL_SIZE = 1024*1024*100;
    public const DEF_MSG_MAX_SIZE = 1024*1024*10;
    public const DEF_MODE = 0666;

    public bool $block = false;
    public ?int $mode = Opt::DEF_MODE;
    public ?int $total_size = Opt::DEF_TOTAL_SIZE;
    public ?int $msg_max_size = Opt::DEF_MSG_MAX_SIZE;
    public ?int $uid = null;
    public ?int $gid = null;

    /**
     * @deprecated
     */
    public const DEF_NUM = 65535;

    /**
     * @deprecated
     */
    public ?int $num = Opt::DEF_NUM;

    /**
     * @param int|null  $total_size
     * @param bool|null $block
     * @param int|null  $mode
     * @param int|null  $uid
     * @param int|null  $gid
     * @param int|null  $msg_max_size
     * @param int|null  $num
     */
    public function __construct(?int $total_size = null, ?bool $block = false, ?int $mode = null, ?int $uid = null, ?int $gid = null, ?int $msg_max_size = null, ?int $num = null)
    {
        if($total_size){
            $this->total_size = $total_size;
        }
        if($num){
            $this->num = $num;
        }
        if($mode){
            $this->mode = $mode;
        }
        if($msg_max_size){
            $this->msg_max_size = $msg_max_size;
        }
        $this->block = $block?: false;
        $this->gid = $gid;
        $this->uid = $uid;
    }

    /**
     * @param string $path_string
     * @param string $type
     *
     * @return int
     * @throws IpcException
     */
    public static function getId(string $path_string, string $type = 'queue'): int
    {
        self::errHdl();
        $ipc_path = '/var/run/ipc';
        $path = "$ipc_path/".trim($type, " \t\n\r\0\x0B/\\");
        if(!is_dir($path)){
            mkdir($path, 0644, true);
        }
        $ipc_file = $path.'/'.hash('sha3-224', $path_string);
        if(!file_put_contents($ipc_file, time())){
            trigger_error("can't write contents to file: $ipc_file", E_USER_ERROR);
        }
        $id = ftok($ipc_file, $type[0]);
        if(!$id || $id<=0){
            trigger_error("failed get id for $path_string", E_USER_ERROR);
        }
        restore_error_handler();
        return $id;
    }

    /**
     * @return void
     * @throws IpcException
     */
    public static function errHdl(){
        set_error_handler(function(int $e_no, string $e_str, string $e_file, int $e_line){
            $e = new IpcException($e_str, $e_no, E_USER_ERROR, $e_file, $e_line);
            restore_error_handler();
            throw $e;
        }, E_ALL);
    }
}
