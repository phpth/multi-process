<?php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\supply\Executor;
use phpth\process\supply\Call;

$c = new Call();
$e = new Executor($c);
$c->add(function(){
   global $main_pid;
   echo $main_pid, PHP_EOL;
   sleep(3);
   exit(3);
});

$e->start();
$e->wait(true, 3);

