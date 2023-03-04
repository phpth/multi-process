<?php
// +----------------------------------------------------------------------
// | Program：multi-process
// +----------------------------------------------------------------------
// | Date: 2023/3/4 0004
// +----------------------------------------------------------------------
// | Author: js
// +----------------------------------------------------------------------
// | CreatedBy: phpStorm
// +----------------------------------------------------------------------


error_reporting(E_ALL);
include dirname(__DIR__) . '/src/autoload.php';
//include  'vendor/autoload.php';

use phpth\process\Process;
use phpth\process\supply\Call;
use phpth\process\supply\ChildRestart;
use phpth\process\supply\Status;

$p = new Process();
$p->childRestart = ChildRestart::EXIT_ANY;
$p->name = 'main load';

$e = $p->runCall(function(){
    while (true)
    str_shuffle(str_shuffle(str_shuffle(str_shuffle(str_shuffle(str_shuffle(str_shuffle(str_shuffle(str_shuffle(str_shuffle(bin2hex(random_bytes(50))))))))))));
}, param: [1,2,3,4,5,6,7,8], num:Call::PROCESS_DATA_DISPATCH, name: 'test');

$p->stopChildOnFinish = false;
$p->waitExecutor($e);