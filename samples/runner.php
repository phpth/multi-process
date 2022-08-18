<?php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\supply\Runner;
use phpth\process\supply\Call;
use \phpth\process\supply\Options;

class a {

    public static $a = '';
    public function test()
    {
        pcntl_async_signals(true);
       /* Options::registerSignal(SIGTERM, function (){
            self::$a = 'TERMINAL';
            echo 'TERMINAL', PHP_EOL;
        });*/
        sleep(20);
        $a = range(0, 99);
    }
}
$a = new a();
$r = new Runner([$a, 'test']);
$r->run();
sleep(2);
//posix_kill($r->pid, SIGTERM);
//print_r($r->wait(true));


