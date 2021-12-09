<?php
declare(strict_types=1);

class SocketServerV2
{
    public function startServer(int $port)
    {
        $host   = '127.0.0.1';
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        try {
            getLogger()->fatalIfSocketError();
            socket_bind($socket, $host, $port);
            getLogger()->fatalIfSocketError();
            socket_listen($socket, 128);
            getLogger()->fatalIfSocketError();
            socket_set_nonblock($socket);
            getLogger()->info(__method__ . " at %s:%d SOCKET[#%d]", $host, $port, $socket);

            while (true) {
                //yield 一个闭包，主要是为了把 $socket 传给外部的调度器
                yield $this->waitForRead($socket);
                $conn = socket_accept($socket);
                if (false === $conn) {
                    if (SOCKET_EAGAIN === socket_last_error()) {
                        //当前没有请求到来
                        getLogger()->debug("socket_accept no connection...");
                        continue;
                    } else {
                        getLogger()->errorIfSocketError();
                        continue;
                    }
                } else {
                    addSchedulerTask($this->handleClient($conn));
                }
            }
        } catch (\Exception $e) {
            getLogger()->errorException($e);
        } finally {
            socket_close($socket);
            return;
        }
    }

    public function handleClient($sock)
    {
        try {
            $peer_addr = '';
            $peer_port = '';
            socket_getpeername($sock, $peer_addr, $peer_port);
            getLogger()->info('[accpet_connction] from %s:%s SOCK[#%d]', $peer_addr, $peer_port, $sock);
            socket_set_nonblock($sock);
            while (true) {
                yield $this->waitForRead($sock);
                $buf = socket_read($sock, 2048);
                getLogger()->errorIfSocketError();
                if ($buf === '') {
                    getLogger()->info("Client has disconnected! %s:%s", $peer_addr, $peer_port);
                    break;
                }
                getLogger()->info("SOCKET[%d] RECV:%s", intval($sock), $buf);
                $resp = "OK\n";
                yield $this->waitForWrite($sock);
                socket_write($sock, $resp, strlen($resp));
                getLogger()->errorIfSocketError();
            }
        } catch (\Exception $e) {
            getLogger()->errorException($e);
        } finally {
            socket_close($sock);
            return;
        }
    }

    /**
     * 把 $socket 添加到读等待队列
     * @param $socket
     * @return Closure
     */
    private function waitForRead($socket)
    {
        return function (Scheduler $scheduler, Task $task) use ($socket) {
            $scheduler->waitForRead($socket, $task);
        };
    }

    /**
     * 把 $socket 添加到写等待队列
     * @param $socket
     * @return Closure
     */
    private function waitForWrite($socket)
    {
        return function (Scheduler $scheduler, Task $task) use ($socket) {
            $scheduler->waitForWrite($socket, $task);
        };
    }
}
