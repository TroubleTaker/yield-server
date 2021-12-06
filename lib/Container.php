<?php

class Container
{
    private static $instance;
    public $map;

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set($key, $value)
    {
        $this->map[$key] = $value;
        return $this->map[$key];
    }

    public function get($key, $default)
    {
        return $this->map[$key] ?? $this->set($key, $default);
    }
}
