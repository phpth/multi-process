# multi-process
open multi process by pcntl

Simple enough and fast enough, and excellent performance to start multi-process. There is no process pool, so don't worry about uncontrollable situations

 
　　　
　　

> install package by composer
```bash
composer require phpth/multi-process
```

> simple samples
```php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\Process;
$p = new Process();
$e = $p->runCall(function ($a, $b){
    echo "CHILD RUN, param a: $a,  param b: $b".PHP_EOL;
    sleep(1);
}, ['a', 'b'], 3);
foreach($e->wait(true, 1.5) as $k=> $status){
    if($status){
    // do something
    }   
}
// or 
$p->waitExecutor($e);
// or 
$p->waitExecutor($e, true ,1.9, function(int $idx, array $status, $append_param1, $append_param2){
    // do log or other things
}, ['$append_param1', '$append_param2']);
```

> set process name 
```php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\Process;
$p = new Process();
$p->name = 'process name'
// or 
$p = new Process('process name');
```

> child process use signal
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
    echo "CHILD RUN, param a: $a,  param b: $b".PHP_EOL;
    sleep(1);
}, ['a', 'b'], 3);

$p->waitExecutor($e, true ,1.9, function(int $idx, array $status, $append_param1, $append_param2){
    // do log or other things
}, ['$append_param1', '$append_param2']);

```


> in normal main process exited set child process stop
```php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\Process;
use phpth\process\supply\Options;
$p = new Process();
$p->stop_child_on_exit = true;
// open your child process code

```

> simple create process and wait
```php
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

```

> run in demonize
```php
include dirname(__DIR__) . '/src/autoload.php';
use phpth\process\supply\Call;
use phpth\process\Process;

$p = new Process();
// main process title
$p->name = 'tset';
$p->restart_by = 0;
$wait = $p->demonize()->runCallWait(function (){
    echo 'CHILD RUN'.PHP_EOL;
    sleep(1);
}, [], 3, 'escc');
$wait->wait(true, 1.9, function(int $idx, array $status, $append_param1, $append_param2){
    echo 'idx: ',var_export($status, true),$idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
},  ['append_param1', 'append_param2']);

```