<?php
include dirname(__DIR__) . '/src/autoload.php';

use phpth\process\Process;

$p = new Process();
$p->demonize();

echo 'daemon', PHP_EOL;
sleep(110000);