<?php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\supply\Call;
use phpth\process\Process;

$p = new Process();
// main process title
$p->name = 'tset';
$p->restart_by = 0;
$wait = $p->runCallWait(function (){
    echo 'CHILD RUN'.PHP_EOL;
    sleep(1);
}, [], 3, 'escc');
$wait->wait(true, 1.9, function(int $idx, array $status, $append_param1, $append_param2){
    echo 'idx: ',var_export($status, true),$idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
},  ['append_param1', 'append_param2']);

// or
$p->runCallWait(function (){
    echo 'CHILD RUN'.PHP_EOL;
    sleep(1);
})->wait(true, 1.9, function(int $idx, array $status, $append_param1, $append_param2){
    echo 'idx: ',var_export($status, true),$idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
},  ['append_param1', 'append_param2']);


// multi
$p->runMultiCallWait([[
    'call' => function(){
        echo 'child for ddd'.PHP_EOL;
        sleep(3);},
    'name' => 'ddd',
    'restart_by' => Call::EXIT_NO_START,
    ], ['call' => function(){
        echo 'child for ccc'.PHP_EOL;
        sleep(3);
    },
     'name' => 'ccc',  'restart_by' => Call::EXIT_NO_START,
    ]])->wait(true, 1.9, function(int $idx, array $status, $append_param1, $append_param2){
    echo 'idx: ',var_export($status, true),$idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
},  ['append_param1', 'append_param2']);

