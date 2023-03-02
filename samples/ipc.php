<?php
// +----------------------------------------------------------------------
// | Program：multi-process
// +----------------------------------------------------------------------
// | Date: 2023/2/25 0025
// +----------------------------------------------------------------------
// | Author: js
// +----------------------------------------------------------------------
// | CreatedBy: phpStorm
// +----------------------------------------------------------------------

include dirname(__DIR__) . '/src/autoload.php';

use phpth\process\exception\IpcEmptyException;
use phpth\process\exception\IpcException;
use phpth\process\exception\IpcFullException;
use phpth\process\Process;

$que = Process::getIpcQueue('5');

// 如果设置为false, 则会在队列满或者空时抛出异常
$que->setBlock(true);

$time = microtime(true);

for ($i = 1; $i <= 5000; $i++) {
    try {
        $que->push(true);
    } catch (IpcFullException $e) {
        echo $e->getMessage(), PHP_EOL;
        break;
    } catch (IpcException $e) {
        throw $e;
    }
}


while (true) {
    try {
        $data = $que->pop();
        echo print_r($data, true), PHP_EOL;
    } catch (IpcEmptyException $e) {
        echo 'queue empty: ' . $e->getMessage() . PHP_EOL;
        break;
    } catch (IpcException $e) {
        throw $e;
    }
}