<?php
declare(strict_types=1);

class Scheduler
{
    protected static $instance;
    protected $max_task_id;
    protected $task_queue;
    protected $running = true;
    protected $dispatch_interval = 1;

    private function __construct()
    {
        $this->max_task_id = 0;
        $this->task_queue  = new SplQueue();
    }

    public static function getDefault(): Scheduler
    {
        if (empty(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function addTask(Generator $coroutine)
    {
        if ($this->running) {
            $this->max_task_id++;
            $task = new Task($this->max_task_id, $coroutine);
            $this->schedule($task);
        } else {
            throw new \Exception("stop!");
        }
    }

    public function schedule(Task $task)
    {
        getLogger()->debug("[TASK_ENQUEUE] task:%s", $task);
        $this->task_queue->enqueue($task);
    }

    public function getCoroutineCount()
    {
        return count($this->task_queue) + 1;
    }

    public function setInterval(int $interval)
    {
        $this->dispatch_interval = $interval;
    }

    public function runAll()
    {
        while ($this->running) {
            if (!$this->task_queue->isEmpty()) {
                $task    = $this->task_queue->dequeue();
                $ret_val = $task->run();
                if ($ret_val instanceof Closure) {
                    $ret_val($this, $task);
                }
                if (!$task->isFinished()) {
                    $this->schedule($task);
                } else {
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

    public function stop()
    {
        getLogger()->info("scheduler is stoping...");
        $this->running = false;
    }

    public static function signalHandler($signo)
    {
        getLogger()->info("received signal %d", $signo);
        switch ($signo) {
            case SIGTERM:
            case SIGINT:
                static::getDefault()->stop();
                break;
            default:
                return;
        }
    }

    public function terminalAllTask()
    {
        while (!$this->task_queue->isEmpty()) {
            $task = $this->task_queue->dequeue();
            getLogger()->info("remove unfinished task: " . $task);
        }
        //正常退出
        exit(0);
    }
}

