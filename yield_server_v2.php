<?php
declare(strict_types=1);

require 'bootstrap.php';

function main($argv)
{
    //日志级别
    $is_debug = in_array('-d', $argv);
    getLogger()->setLevel($is_debug ? 'debug' : 'info');
    getLogger()->info("RUN debug_mode:%s", $is_debug ? "true" : "false");
    //监听端口
    $port = 9707;
    if (in_array('-p', $argv)) {
        $port = $argv[array_search('-p', $argv, true) + 1] ?? $port;
    }
    //初始化调度器
    $scheduler = IoPollScheduler::getDefault();
    //添加协程任务，然后启动
    $scheduler->addTask((new SocketServerV2())->startServer((int)$port));
    $scheduler->addTask(test1());
    $scheduler->runAll();
}

function test1()
{
    foreach ([1, 2, 3, 4, 5] as $i) {
        echo $i . PHP_EOL;
        yield;
    }
}

//监听进程信号
pcntl_async_signals(true);
pcntl_signal(SIGINT, 'Scheduler::signalHandler');

main($argv);
