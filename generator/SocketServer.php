<?php
declare(strict_types=1);

function startServer(int $port)
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
        getLogger()->info("startServer at %s:%d SOCK[#%d]", $host, $port, $socket);

        while (true) {
            yield $socket;
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
                addSchedulerTask(handleClient($conn));
            }
        }
    } catch (\Exception $e) {
        getLogger()->errorException($e);
    } finally {
        socket_close($socket);
        return;
    }
}


function handleClient($sock)
{
    try {
        $peer_addr = '';
        $peer_port = '';
        socket_getpeername($sock, $peer_addr, $peer_port);
        getLogger()->info('[accpet_connction] from %s:%s SOCK[#%d]', $peer_addr, $peer_port, $sock);
        socket_set_nonblock($sock);
        while (true) {
            yield $sock;
            $buf = socket_read($sock, 2048);
            getLogger()->errorIfSocketError();
            if ($buf === '') {
                getLogger()->info("Client has disconnected! %s:%s", $peer_addr, $peer_port);
                break;
            }
            if ($buf === false) {
                getLogger()->debug("Wait for client send data! %s:%s", $peer_addr, $peer_port);
                continue;
            }
            getLogger()->info("SOCKET[%d] RECV:%s", intval($sock), $buf);
            $resp = "OK\n";
            yield $sock;
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
