<?php
// +----------------------------------------------------------------------
// | Program：multi-process
// +----------------------------------------------------------------------
// | Date: 2023/3/2 0002
// +----------------------------------------------------------------------
// | Author: js
// +----------------------------------------------------------------------
// | CreatedBy: phpStorm
// +----------------------------------------------------------------------


pcntl_async_signals(true);


$a = function ():Generator{
    yield "signal".PHP_EOL;
};
$mainPid = getmypid();
$pid = pcntl_fork();
if(!$pid){
    sleep(2);
    echo "send signal".PHP_EOL;
    for($i = 0; $i<=50; $i++){
        posix_kill($mainPid, SIGCHLD);
    }
    die();
}
pcntl_signal(SIGTERM, function ()use($a){
    echo "get signal".PHP_EOL;
    $a()->current();
});

pcntl_signal(SIGCHLD, function ()use($a){
    echo "get child signal".PHP_EOL;
    $a()->current();
});

while (true){
    echo 'wait'.PHP_EOL;
    sleep(1);
}

