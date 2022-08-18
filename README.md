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