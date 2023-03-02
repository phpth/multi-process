<?php
include dirname(__DIR__) . '/src/autoload.php';

use phpth\process\Process;

$redis = new Redis();
$redis->connect('192.168.0.80');
$time = microtime(true);
for ($i = 1; $i <= 5000; $i++) {
    $redis->lPush('c', $i);
}
echo 'redis push cost time: ' . (microtime(true) - $time), PHP_EOL;
$redis->del('c');

$time = microtime(true);
for ($i = 1; $i <= 5000; $i++) {
    $redis->rpop('c');
}
echo 'redis pop cost time: ' . (microtime(true) - $time), PHP_EOL;
$redis->del('c');

$que = Process::getIpcQueue('5');
$time = microtime(true);
for ($i = 1; $i <= 5000; $i++) {
    $que->push($i);
}
echo 'system ipc push cost time: ' . (microtime(true) - $time), PHP_EOL;
$time = microtime(true);
for ($i = 1; $i <= 5000; $i++) {
    $que->pop();
}
echo 'system ipc pop cost time: ' . (microtime(true) - $time), PHP_EOL;
$que->remove();

$que = Process::getIpcQueue('5');

print_r($que->stat());
while ($data = $que->pop()) {
    echo $data, PHP_EOL;
}

// push int
for ($i = 0; $i < 20; $i++) {
    $que->push($i);
}

// push arr
for ($i = 0; $i < 20; $i++) {
    $arr = [$i];
    $que->push($arr);
}

// push object
class QueueObj
{
    public ?int $data = null;
}

for ($i = 0; $i < 20; $i++) {
    $obj = new QueueObj();
    $obj->data = $i;
    $que->push($obj);
}

while (true) {
    try {
        $data = $que->pop();
        print_r($data);
    } catch (Throwable $e) {
        echo 'queue empty: ' . $e->getMessage() . PHP_EOL;
        break;
    }
}


// del ipc queue
$que->remove();


// del exists ipc queue, id is command ipcs -q is key
//------ Message Queues --------
//key        msqid      owner      perms      used-bytes   messages
//0x71b8348d 12         root       666        0            0
$que->remove(null, 0x71b8348d);

// or with path string
$que->remove('5');

