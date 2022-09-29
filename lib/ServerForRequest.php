<?php

use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;

class ServerForRequest
{
    private ServerForWorker $server_for_worker;
    public $port;

    public function __construct(ServerForWorker $server_for_worker)
    {
        $this->server_for_worker = $server_for_worker;
        $this->port = 8080;
        $socket = new SocketServer('127.0.0.1:' . $this->port);

        $socket->on('connection', function (ConnectionInterface $connection) {
            $conn = new RequestConnection($connection, $this, $this->server_for_worker);
        });

        $socket->on('error', function (Exception $e) {
            echo 'server error: ' . $e->getMessage() . PHP_EOL;
        });

        echo 'Listening on ' . $socket->getAddress() . PHP_EOL;
    }
}