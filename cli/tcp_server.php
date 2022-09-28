<?php

// telnet localhost 8080

use React\Socket\ConnectionInterface;

require __DIR__ . '/../vendor/autoload.php';

function print_socket(ConnectionInterface $connection, $message)
{
    echo 'connection[' . $connection->getRemoteAddress() . '] ' . $message . PHP_EOL;
}

$socket = new React\Socket\SocketServer('127.0.0.1:8080');

$socket->on('connection', function (ConnectionInterface $connection) {
    print_socket($connection, 'on_connection');

    $connection->on('data', function ($data) use ($connection) {
        print_socket($connection, 'on_data ' . $data);
        $connection->write("server received: " . $data . PHP_EOL);
    });

    $connection->on('close', function () use ($connection) {
        print_socket($connection, 'on_close');
    });
});

$socket->on('error', function (Exception $e) {
    echo 'server error: ' . $e->getMessage() . PHP_EOL;
});

echo 'Listening on ' . $socket->getAddress() . PHP_EOL;