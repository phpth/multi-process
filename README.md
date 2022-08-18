# multi-process
open multi process by pcntl

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