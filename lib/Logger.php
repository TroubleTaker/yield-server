<?php
declare(strict_types=1);

class Logger
{
    public $log_level = 1;
    public $all_level = [
        'debug' => 0,
        'info'  => 1,
        'error' => 2,
        'fatal' => 3,
    ];
    private static $instance;
    private $coroutine;

    public static function getDefault(): Logger
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->coroutine = $this->writeLog();
    }

    public function setLevel(string $str_level)
    {
        $this->log_level = $this->getIntLevel($str_level);
    }

    public function getIntLevel(string $str_level): int
    {
        return $this->all_level[$str_level] ?? 0;
    }

    public function log(string $level, string $format, ...$args)
    {
        if ($this->getIntLevel($level) < $this->log_level) {
            return;
        }
        $message = sprintf("[pid:%s]\t[%s]\t[%s]\t%s\n", posix_getpid(), (new \DateTime())->format("Y-m-d H:i:s.u"), strtoupper($level), sprintf($format, ...$args));
        $this->coroutine->send($message);
    }

    private function writeLog()
    {
        while (true) {
            fwrite(STDOUT, yield);
        }
    }

    public function __call(string $name, $args)
    {
        return $this->log($name, ...$args);
    }

    public function fatalIfSocketError($sock = null)
    {
        if (null === $sock) {
            $last_error = socket_last_error();
        } else {
            $last_error = socket_last_error($sock);
        }
        if ($last_error !== 0) {
            $this->fatal("socket_error:#%d(%s)", $last_error, socket_strerror($last_error));
            exit(1);
        }
    }

    public function errorIfSocketError($sock = null)
    {
        if (null === $sock) {
            $last_error = socket_last_error();
        } else {
            $last_error = socket_last_error($sock);
        }
        if ($last_error !== 0) {
            $this->error("socket_error:#%d(%s)", $last_error, socket_strerror($last_error));
        }
    }

    public function errorException(Exception $e)
    {
        $this->error("catch exception " . $e->getMessage());
    }
}
