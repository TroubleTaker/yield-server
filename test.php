<?php
function main()
{
    $t1 = test1();
    $t2 = test2();
    $queue = new SplQueue();
    $queue->enqueue(['is_start' => false, 'task' => $t1]);
    $queue->enqueue(['is_start' => false, 'task' => $t2]);
    while (true) {
        if ($queue->isEmpty()) {
            break;
        } else {
            $item = $queue->dequeue();
            if ($item['is_start']) {
                $item['task']->next();
            } else {
                $item['task']->rewind();
                $item['is_start'] = true;
            }
            if ($item['task']->valid()) {
                $queue->enqueue($item);
            }
        }
    }
    return true;
}

function test1()
{
    foreach ([1,2,3,4,5] as $i) {
        echo $i . PHP_EOL;
        yield;
    }
}

function test2()
{
    foreach ([10,20,30,40,50] as $i) {
        echo $i . PHP_EOL;
        yield;
    }
}

main();