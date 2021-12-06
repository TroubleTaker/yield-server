<?php
declare(strict_types=1);

class Timer
{
    public static function newTimer(int $duration, Closure $callback)
    {
        addSchedulerTask(
            self::after($duration, $callback)
        );
    }

    public static function newTicker(int $duration, Closure $callback)
    {
        addSchedulerTask(
            self::ticker($duration, $callback)
        );
    }

    public static function after(int $duration, Closure $callback)
    {
        getLogger()->debug("[timer.after.install] callback will executed after %d seconds!", $duration);
        try {
            $start_ts = time();
            while (true) {
                if (time() - $start_ts >= $duration) {
                    $callback();
                    return;
                }
                yield;
            }
        } catch (\Exception $e) {
            getLogger()->errorException($e);
            return;
        }
    }

    public static function ticker(int $duration, Closure $callback)
    {
        getLogger()->debug("[timer.ticker.install] callback will executed every %d seconds!", $duration);
        try {
            $last_ts = time();
            while (true) {
                if (time() - $last_ts >= $duration) {
                    $callback();
                    $last_ts = time();
                }
                yield;
            }
        } catch (\Exception $e) {
            getLogger()->errorException($e);
        }
    }
}
