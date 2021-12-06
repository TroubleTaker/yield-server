<?php
declare(strict_types=1);

class Task
{
    protected $name;
    protected $id;
    protected $coroutine;
    protected $is_start = false;

    public function __construct(int $id, Generator $coroutine)
    {
        $this->coroutine = $coroutine;
        $this->id        = $id;
        $this->initTaskName();
    }

    public function run()
    {
        getLogger()->debug('task:%s start', $this);
        if (!$this->is_start) {
            $this->coroutine->rewind();
            $this->is_start = true;
        } else {
            $this->coroutine->next();
        }
        getLogger()->debug('task:%s end', $this);
        return $this->coroutine->current();
    }

    public function isFinished()
    {
        return !$this->coroutine->valid();
    }

    public function getCoroutine()
    {
        return $this->coroutine;
    }

    private function initTaskName()
    {
        $reflect    = new ReflectionGenerator($this->coroutine);
        $ref_func   = $reflect->getFunction();
        $this->name = sprintf('Task{id=%d,name=%s}', $this->id, $ref_func->getName());
    }

    public function __toString(): string
    {
        return (string)$this->name;
    }
}
