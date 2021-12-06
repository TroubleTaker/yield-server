<?php
declare(strict_types=1);

class IoPollScheduler extends Scheduler
{
    protected $write_socket_wait_list = [];
    protected $read_socket_wait_list = [];

    public function runAll()
    {
        getLogger()->info(__method__ . " start!");
        $this->addTask($this->ioPollTask());
        while ($this->running) {
            if (!$this->task_queue->isEmpty()) {
                $task   = $this->task_queue->dequeue();
                $retval = $task->run();
                if (is_callable($retval)) {
                    //有回调的，执行回调，不再加到自动调度队列
                    getLogger()->info("[TASK_CALLBACK] task:%s", $task);
                    $retval($this, $task);
                } else {
                    //自动调度
                    if (!$task->isFinished()) {
                        $this->schedule($task);
                    }
                }
                if ($task->isFinished()) {
                    getLogger()->info("task[%s] finished!", $task);
                }
            } else {
                getLogger()->info("task_queue empty! all task finished");
                break;
            }
            usleep($this->dispatch_interval);
        }
        $this->terminalAllTask();
    }

    public function ioPoll($timeout)
    {
        $r_sock = array_column($this->read_socket_wait_list, 'socket');
        $w_sock = array_column($this->write_socket_wait_list, 'socket');
        $e_sock = [];
        if (empty($r_sock) && empty($w_sock) && empty($e_sock)) {
            getLogger()->debug("all socket empty!");
            return;
        }
        $changed_sockets = socket_select($r_sock, $w_sock, $e_sock, $timeout, 0);
        if ($changed_sockets === false) {
            getLogger()->errorIfSocketError();
            return;
        } elseif ($changed_sockets == 0) {
            getLogger()->debug("timeout reached");
            return;
        }
        if ($r_sock) {
            foreach ($r_sock as $sock) {
                $sock_key = (int)$sock;
                foreach ($this->read_socket_wait_list[$sock_key]['tasks'] as $task) {
                    $this->schedule($task);
                }
                unset($this->read_socket_wait_list[$sock_key]);
            }
        }
        if ($w_sock) {
            foreach ($w_sock as $sock) {
                $sock_key = (int)$sock;
                foreach ($this->write_socket_wait_list[$sock_key]['tasks'] as $task) {
                    $this->schedule($task);
                }
                unset($this->write_socket_wait_list[$sock_key]);
            }
        }
    }

    public function ioPollTask()
    {
        while (true) {
            if ($this->task_queue->isEmpty()) {
                $this->ioPoll(null);
            } else {
                $this->ioPoll(0);
            }
            yield;
        }
    }

    public function waitForWrite($socket, Task $task)
    {
        $socket_key = (int)$socket;
        if (!isset($this->write_socket_wait_list[$socket_key])) {
            $this->write_socket_wait_list[$socket_key] = [
                'socket' => $socket,
                'tasks'  => [],
            ];
        }
        $this->write_socket_wait_list[$socket_key]['tasks'][] = $task;
        getLogger()->debug("[waitForWrite] task:%s socket:%d", $task, $socket);
    }

    public function waitForRead($socket, Task $task)
    {
        $socket_key = (int)$socket;
        if (!isset($this->read_socket_wait_list[$socket_key])) {
            $this->read_socket_wait_list[$socket_key] = [
                'socket' => $socket,
                'tasks'  => [],
            ];
        }
        $this->read_socket_wait_list[$socket_key]['tasks'][] = $task;
        getLogger()->debug("[waitForRead] task:%s socket:%d", $task, $socket);
    }
}

