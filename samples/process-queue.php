<?php
// +----------------------------------------------------------------------
// | Program：multi-process
// +----------------------------------------------------------------------
// | Date: 2023/3/1 0001
// +----------------------------------------------------------------------
// | Author: js
// +----------------------------------------------------------------------
// | CreatedBy: phpStorm
// +----------------------------------------------------------------------

include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\Process;
use phpth\process\supply\Call;
use phpth\process\supply\Status;

$p = new Process();

$data = [
    'child data 1',
    'child data 2',
    'child data 3'
];

$que = Process::getIpcQueue('./queue.sv');

$que->setBlock(true);

// Supports all types except objects
$que->push([1]);
$que->push(null);
$que->push(false);
$que->push('');
$que->push('123');
$que->push('456');
$que->push('789');
$que->push('qwe');
$que->push('asd');
$que->push('zxc');

/*
//it will throw exception
$que->push(new class{})
$que->push([new class{}])
*/
//$que->push(new class{});
//$que->push(['sgsd']);
//$que->push([new class{}]);

$e = $p->runCall(function (int $processNo, mixed $param){
    // child process execute: do something

    // if you wang get parent process id, can do this
    global $maiPid;
    echo 'parent process id: '.$maiPid, PHP_EOL;

    // callback function first param is process no, second param


    echo "current process no: {$processNo}, process param: ".print_r($param).PHP_EOL;
    sleep(1);

    $que = Process::getIpcQueue('./queue.sv');

    $que->setBlock(true);

    $queueData = $que->pop();
    echo 'get queue data: '.var_export($queueData, true).PHP_EOL;

},
    $data,
    Call::PROCESS_DATA_DISPATCH // Automatically start the process according to the number of array elements and split an element passed in $data into the second parameter of the callback function
);

foreach($e->wait(true, 1.5) as $processNo=> $status){
    /**@var Status $status */
    if(!$status->run){
        echo $processNo.'stopped'.PHP_EOL;
    }
}