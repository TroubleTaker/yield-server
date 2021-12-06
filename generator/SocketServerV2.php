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
                //让出cpu，等有链接来了再往下执行
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
                $buf      = '';
                $recv_len = socket_recv($sock, $buf, 409600, MSG_DONTWAIT);
                if (false === $recv_len) {
                    if (SOCKET_EAGAIN === socket_last_error()) {
                        //没有数据到来
                        getLogger()->debug("socket recv empty on %s:%d!", $peer_addr, $peer_port);
                        continue;
                    } else {
                        getLogger()->errorIfSocketError();
                        break;
                    }
                }
                getLogger()->info("[SOCKET_RECV] socket:%d content:%s", intval($sock), $buf);
                $resp = "OK\n";
                yield $this->waitForWrite($sock);
                $send_len = socket_send($sock, $resp, strlen($resp), 0);
                if (false === $send_len) {
                    if (SOCKET_EAGAIN === socket_last_error()) {
                        getLogger()->debug("socket send failed! %s:%d", $peer_addr, $peer_port);
                    } else {
                        getLogger()->errorIfSocketError();
                        break;
                    }
                }
                getLogger()->info("[SOCKET_SEND] socket:%d content:%s", intval($sock), $buf);
            }
        } catch (\Exception $e) {
            getLogger()->errorException($e);
        } finally {
            socket_close($sock);
            return;
        }
    }

    private function waitForRead($socket)
    {
        return function (Scheduler $scheduler, Task $task) use ($socket) {
            $scheduler->waitForRead($socket, $task);
        };
    }

    private function waitForWrite($socket)
    {
        return function (Scheduler $scheduler, Task $task) use ($socket) {
            $scheduler->waitForWrite($socket, $task);
        };
    }
}
