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
        $message = str_replace("\r", "^I", $message);
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

    public function fatal(...$args)
    {
        $this->log('fatal', ...$args);
        exit(127);
    }

    public function fatalIf(bool $flag, ...$args)
    {
        if ($flag) {
            $this->fatal(...$args);
        }
    }

    public function fatalIfSocketError($sock = null)
    {
        if (null === $sock) {
            $last_error = socket_last_error();
        } else {
            $last_error = socket_last_error($sock);
        }
        return $this->fatalIf($last_error !== 0, "socket_error:#%d(%s)", $last_error, socket_strerror($last_error));
    }

    public function errorIf(bool $flag, ...$args)
    {
        if ($flag) {
            $this->error(...$args);
        }
    }

    public function errorIfSocketError($sock = null)
    {
        if (null === $sock) {
            $last_error = socket_last_error();
        } else {
            $last_error = socket_last_error($sock);
        }
        return $this->errorIf($last_error !== 0, "socket_error:#%d(%s)", $last_error, socket_strerror($last_error));
    }

    public function infoException(Exception $e)
    {
        $this->log('info', "catch exception " . $e->getMessage());
    }

    public function errorException(Exception $e)
    {
        $this->log('error', "catch exception " . $e->getMessage());
    }
}
