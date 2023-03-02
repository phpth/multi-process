<?php
error_reporting(E_ALL);
include dirname(__DIR__) . '/src/autoload.php';
//include  'vendor/autoload.php';

use phpth\process\Process;
use phpth\process\supply\Call;
use phpth\process\supply\ChildRestart;
use phpth\process\supply\Status;

$arr = [1, 2, 3, 4, 5, 6];
$data = array_chunk($arr, 2);

$p = new Process(output: './output/');
//$p = new Process();
$p->stopChildOnFinish = true;

// 非正常退出重启子进程
$p->childRestart = ChildRestart::ABNORMAL_EXIT;

ini_set('memory_limit', -1);
$e = $p->runCall(function (int $processNo, mixed $data) {
    // child execute code body
    // echo 'child process:' . $processNo . 'process data:' . print_r($data, true) . PHP_EOL;

    usleep(100000*mt_rand(1,9));
    throw new Exception("child exp");
},
    [function($a, $b){}],
    30, //Call::PROCESS_DATA_DISPATCH // 自动根据数组元素数量开启进程并且分割传入$data 一个元素到回调函数的第二个参数中
    'exp proc',
);

echo 'main process' . PHP_EOL;


// need wait child process, or child process may be exit self
try{
    // foreach ($e->wait(true, 0.5) as $processNo => $status) {
    //    /**@var Status $status */
    //   //print_r($status);
    //}
    // or
    while(true){
        // do other things
        sleep(1);
    }
}catch (Throwable $e){
    echo $e->getMessage().PHP_EOL;
} finally {
    // do free resource or logging
    echo 'main process end'.PHP_EOL;
}
