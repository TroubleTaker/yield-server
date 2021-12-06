<?php
declare(strict_types=1);

require 'bootstrap.php';

function main($argv) {
    if (in_array('-d', $argv)) {
        getLogger()->setLevel('debug');
    }
    $scheduler = getScheduler();
//    $scheduler->addTask(test1());
//    $scheduler->addTask(test2());
    $scheduler->addTask(startServer(9001));
    $scheduler->runAll();
}

function test1() {
    foreach ([1,2,3,4,5] as $i) {
        echo $i . PHP_EOL;
        yield;
    }
}

function test2() {
    foreach ([10,20,30,40,50] as $i) {
        echo $i . PHP_EOL;
        yield;
    }
}


pcntl_async_signals(true);
pcntl_signal(SIGINT, 'Scheduler::signalHandler');

main($argv);
