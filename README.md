# multi-process

open multi process by pcntl

Simple enough and fast enough, and excellent performance to start multi-process. There is no process pool, so don't
worry about uncontrollable situations


It provides multiple subprocesses in the process Linux, and can redirect the output of the subprocesses. Simple and
convenient implementation of system message queue, and the performance of single machine is far better than that of
Redis queue. You can also start the process to process different blocks of data according to the incoming split data 
blocks.



## install package by composer

```bash
composer require phpth/multi-process

```

## simple samples

```php
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
// or 
$p->waitExecutor($e);

// or 
$p->waitExecutor($e, true ,1.9, function(int $idx, array $status, $append_param1, $append_param2){
    // do log or other things
}, ['$append_param1', '$append_param2']);
```

## set process name

```php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\Process;
$p = new Process();
$p->name = 'process name'
// or 
$p = new Process('process name');
```



## child process use signal

```php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\Process;
use phpth\process\supply\Options;
$p = new Process();
$e = $p->runCall(function ($a, $b){
    pcntl_async_signals(true);
    Options::registerSignal(SIGTERM, function($signal){
    //your exit logic
    });
    // or
    Options::registerSignal([SIGINT, SIGHUP], function($signal){
    //your deal logic
    });
    
    // do something
    
    sleep(1);
}, ['a', 'b'], 3);

$p->waitExecutor($e, true ,1.9, function(int $idx, array $status, $append_param1, $append_param2){
    // do log or other things
}, ['$append_param1', '$append_param2']);

```


## in normal main process exited set child process stop

```php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\Process;
use phpth\process\supply\Options;
$p = new Process();

// main process on normal finished, it will stop child process 
$p->stopChildOnFinish = true;
// open your child process code

```

## simple create process and wait

```php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\supply\Call;
use phpth\process\Process;
use phpth\process\supply\ChildRestart;

$p = new Process();

// main process title
$p->name = 'tset';

$p->childRestart = 0;

$wait = $p->runCallWait(function (int $processNo, mixed $data){
    //do something
    
    sleep(1);
}, [], 3, 'child process title');


$wait->wait(true, 1.9, function(int $idx, array $status, $append_param1, $append_param2){
    echo 'idx: ',var_export($status, true),$idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
},  ['append_param1', 'append_param2']);

// or
$p->runCallWait(function (){

    //do something
    echo 'CHILD RUN'.PHP_EOL;
    sleep(1);
})->wait(true, 1.9, function(int $idx, array $status, $append_param1, $append_param2){

    // main process log
    echo 'idx: ',var_export($status, true),$idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
},  ['append_param1', 'append_param2']);


// multi
$p->runMultiCallWait([[
    'call' => function(){
        echo 'child for ddd'.PHP_EOL;
        sleep(3);},
    'name' => 'ddd',
    'child_restart' =>ChildRestart::NO_RESTART,
    ], ['call' => function(){
        echo 'child for ccc'.PHP_EOL;
        sleep(3);
    },
     'name' => 'ccc',  'child_restart' =>ChildRestart::NO_RESTART,
    ]])->wait(true, 1.9, function(int $idx, array $status, $append_param1, $append_param2){
    echo 'idx: ',var_export($status, true),$idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
},  ['append_param1', 'append_param2']);

```

## run in demonize

```php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\supply\Call;
use phpth\process\Process;
use phpth\process\supply\ChildRestart;


$p = new Process();
// main process title

$p->name = 'tset';

$p->childRestart = ChildRestart::NO_RESTART;

$wait = $p->demonize()->runCallWait(function (){

    echo 'CHILD RUN'.PHP_EOL;
    sleep(1);
}, [], 3, 'child process title');

$wait->wait(true, 1.9, function(int $idx, array $status, $append_param1, $append_param2){
    echo 'idx: ',var_export($status, true),$idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
},  ['append_param1', 'append_param2']);

```

> process use ipc queue[^1]

```php
include dirname(__DIR__).'/src/autoload.php';

use phpth\process\Process;
use phpth\process\supply\Call;
use phpth\process\supply\ChildRestart;


$key   = 'test';
$queue = Process::getIpcQueue($key);
for($i = 1; $i <= 10; $i++) {
    $queue->push($i);
}

$p = new Process();
$p->runMultiCallWait([
    [
        'call'       => function () use ($key) {
            $queue = Process::getIpcQueue($key);
            try {
                while(true) {
                    echo 'child[ddd], pop data: '.$queue->pop().PHP_EOL;
                    sleep(1);
                }
            } catch(Throwable $e) {
                echo 'process name[ddd]: queue is empty'.PHP_EOL;
            }
        },
        'name'       => 'ddd',
        'child_restart' => ChildRestart::NO_RESTART,
    ],
    [
        'call'       => function () use ($key) {
            $queue = Process::getIpcQueue($key);
            try {
                while(true) {
                    echo 'child[ccc], pop data: '.$queue->pop().PHP_EOL;
                    sleep(1);
                }
            } catch(Throwable $e) {
                echo 'process name[ccc]: queue is empty'.PHP_EOL;
            }
        },
        'name'       => 'ccc',
        'child_restart' => ChildRestart::NO_RESTART,
    ],
])->wait(true, 1.9, function (int $idx, array $status, $append_param1, $append_param2) {
    echo 'idx: ', var_export($status, true), $idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
}, ['append_param1', 'append_param2']);
$queue->remove();
```

[^1]: <font color="red" size=5> you must open sysvmsg extension for use ipc queue before</font>