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
    for($i=0;$i<=10;$i++){
        echo $i.PHP_EOL;
        sleep(1);

    }
    if(0){
        yield $i;
    }
};
$pid = pcntl_fork();
if(!$pid){
    sleep(5);
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

for($i = 0; $i<=10; $i++){
    echo "send signal".PHP_EOL;
    posix_kill(getmypid(), SIGTERM);
    sleep(1);
}
