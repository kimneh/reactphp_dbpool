<?php

use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;

class ServerForWorker
{
    public $port;
    /**
     * @var WorkerConnection[]
     */
    public $connections = [];

    public function __construct()
    {
        $this->port = 8081;
        $socket = new SocketServer('127.0.0.1:' . $this->port);

        $socket->on('connection', function (ConnectionInterface $connection) {
            new WorkerConnection($connection, $this);
        });

        $socket->on('error', function (Exception $e) {
            echo 'server error: ' . $e->getMessage() . PHP_EOL;
        });

        echo 'Listening on ' . $socket->getAddress() . PHP_EOL;
    }

    public function notify_work()
    {
        foreach ($this->connections as $connection) {
            $connection->send_sql_to_worker();
        }
    }
}