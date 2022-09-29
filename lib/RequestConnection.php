<?php

use React\Socket\ConnectionInterface;

class RequestConnection
{
    public ConnectionInterface $connection;
    private ServerForRequest $server_for_request;
    private ServerForWorker $server_for_worker;

    public function __construct(ConnectionInterface $connection, ServerForRequest $server_for_request, ServerForWorker $server_for_worker)
    {
        $this->connection = $connection;
        $this->server_for_request = $server_for_request;
        $this->server_for_worker = $server_for_worker;

        $buffer = [];

        $connection->on('data', function ($data) use ($connection, &$buffer) {
            //$this->print_socket('on_data ' . $data);
            //$connection->write("server received: " . $data . PHP_EOL);

            $buffer[] = $data;
            $line = implode('', $buffer);
            if (str_contains($line, PHP_EOL)) {
                echo 'received_job: ' . $line;
                $buffer = [];
                $GLOBALS['sqls'][] = [$data, $connection];
                $connection->write(PHP_EOL);
                $this->server_for_worker->notify_work();
            }
        });

        $connection->on('end', function () use ($connection) {
            $this->print_socket('on_end');
        });

        $connection->on('error', function (Exception $e) use ($connection) {
            $this->print_socket('on_error');
        });

        $connection->on('close', function () use ($connection) {
            $this->print_socket('on_close');
        });
    }

    private function print_socket($message)
    {
        echo $this->server_for_request->port . ' connection[' . $this->connection->getRemoteAddress() . '] ' . $message . PHP_EOL;
    }
}