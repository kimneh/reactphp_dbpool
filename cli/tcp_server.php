<?php

// telnet localhost 8080

use React\Socket\ConnectionInterface;

require __DIR__ . '/../vendor/autoload.php';

function print_socket(ConnectionInterface $connection, $message)
{
    echo 'connection[' . $connection->getRemoteAddress() . '] ' . $message . PHP_EOL;
}

$ports = [8080, 8081];
foreach ($ports as $port)
{
    $socket = new React\Socket\SocketServer('127.0.0.1:' . $port);

    $socket->on('connection', function (ConnectionInterface $connection) {
        print_socket($connection, 'on_connection');

        $connection->write("hi" . PHP_EOL);

        $connection->on('data', function ($data) use ($connection) {
            print_socket($connection, 'on_data ' . $data);
            $connection->write("server received: " . $data . PHP_EOL);

            if ($data === ('quit' . "\r\n")) {
                $connection->write("server want to end" . PHP_EOL);
                $connection->end();
            }
        });

        $connection->on('end', function () use ($connection) {
            print_socket($connection, 'on_end');
        });

        $connection->on('error', function (Exception $e) use ($connection) {
            print_socket($connection, 'on_error');
        });

        $connection->on('close', function () use ($connection) {
            print_socket($connection, 'on_close');
        });
    });

    $socket->on('error', function (Exception $e) {
        echo 'server error: ' . $e->getMessage() . PHP_EOL;
    });

    echo 'Listening on ' . $socket->getAddress() . PHP_EOL;
}