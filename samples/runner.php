<?php
include dirname(__DIR__) . '/src/autoload.php';

use phpth\process\supply\Runner;

class a
{
    public static string $a = '';

    public function test()
    {
       // pcntl_async_signals(true);
        /* Options::registerSignal(SIGTERM, function (){
             self::$a = 'TERMINAL';
             echo 'TERMINAL', PHP_EOL;
         });*/
        sleep(20);
        $a = range(0, 99);
        sleep(3);

        throw new Exception("sgcc");
    }
}

$a = new a();

$r = [];

for($i=0;$i<=1;$i++){
    $r[$i] = new Runner([$a, 'test'], [], 0, 'gsdfgg');
    $r[$i]->run();
    echo "main process id: ".getmypid().PHP_EOL;
}

while (true){
    sleep(1);
}
//posix_kill($r->pid, SIGTERM);
//print_r($r->wait(true));


