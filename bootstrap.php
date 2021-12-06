<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'On');

require_once 'lib/Logger.php';
require_once 'lib/Container.php';

require_once 'generator/SocketServer.php';
require_once 'generator/SocketServerV2.php';
require_once 'generator/Timer.php';

require_once 'scheduler/Task.php';
require_once 'scheduler/Scheduler.php';
require_once 'scheduler/IoPollScheduler.php';

function getDI() {
    return Container::getInstance();
}

function get($key, $value = null) {
    return getDI()->get($key, $value);
}

function getLogger(): Logger {
    return get('logger', Logger::getDefault());
}

function getScheduler(): Scheduler {
    return get('scheduler', Scheduler::getDefault());
}

function addSchedulerTask(Generator $coroutine) {
    return getScheduler()->addTask($coroutine);
}
