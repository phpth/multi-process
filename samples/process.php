<?php
include dirname(__DIR__) . '/src/autoload.php';

use phpth\process\Process;
use phpth\process\supply\ChildRestart;

$p = new Process();
// main process title
$p->name = 'tset';
$p->childRestart = ChildRestart::NO_RESTART;
$e = $p->runCall(function () {
    echo 'CHILD RUN' . PHP_EOL;
    sleep(1);
}, [], 3, 'escc');


$p->waitExecutor($e, true, 0.9, function (int $idx, array $status, $append_param1, $append_param2) {
    //print_r($status);
    echo 'idx: ', $idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
}, ['append_param1', 'append_param2']);


$e = $p->runMultiCall([
    ['call' => function () {
        echo 'child for ddd' . PHP_EOL;
        sleep(3);
    },
        'name' => 'ddd'],
    ['call' => function () {
        echo 'child for ccc' . PHP_EOL;
        sleep(3);
    },
        'name' => 'ccc']
]);

$p->waitExecutor($e, true, 0.9, function (int $idx, array $status, $append_param1, $append_param2) {
    //print_r($status);
    echo 'idx: ', $idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
}, ['append_param1', 'append_param2']);
/*function test():Generator
{
    $i = 0;
    while(true){
        sleep(1);
        yield $i;
        $i++;
    }
}

foreach(test() as $k=> $v){
    echo $v, PHP_EOL;
}*/