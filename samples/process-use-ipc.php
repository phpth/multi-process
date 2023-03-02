<?php
include dirname(__DIR__) . '/src/autoload.php';

use phpth\process\Process;
use phpth\process\supply\ChildRestart;

$key = 'test';
$queue = Process::getIpcQueue($key);
for ($i = 1; $i <= 10; $i++) {
    $queue->push($i);
}

$p = new Process();
$p->runMultiCallWait([
    [
        'call' => function () use ($key) {
            $queue = Process::getIpcQueue($key);
            try {
                while (true) {
                    echo 'child[ddd], pop data: ' . $queue->pop() . PHP_EOL;
                    sleep(1);
                }
            } catch (Throwable $e) {
                echo 'process name[ddd]: queue is empty' . PHP_EOL;
            }
        },
        'name' => 'ddd',
        'child_restart' => ChildRestart::NO_RESTART,
    ],
    [
        'call' => function () use ($key) {
            $queue = Process::getIpcQueue($key);
            try {
                while (true) {
                    echo 'child[ccc], pop data: ' . $queue->pop() . PHP_EOL;
                    sleep(1);
                }
            } catch (Throwable $e) {
                echo 'process name[ccc]: queue is empty' . PHP_EOL;
            }
        },
        'name' => 'ccc',
        'child_restart' => ChildRestart::NO_RESTART,
    ],
])->wait(true, 1.9, function (int $idx, array $status, $append_param1, $append_param2) {
    echo 'idx: ', var_export($status, true), $idx, ' - ', $append_param1, ' - ', $append_param2, PHP_EOL;
}, ['append_param1', 'append_param2']);
// remove ipc queue
$queue->remove();
