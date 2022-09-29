<?php

use React\Socket\ConnectionInterface;

class WorkerConnection
{
    const _STATUS_WAITING_FOR_ID = 1;
    const _STATUS_WAITING_FOR_GET = 2;
    const _STATUS_WAITING_FOR_GET_RESULT = 3;

    private string $conn_id;
    private ConnectionInterface $connection_worker;
    private ?ConnectionInterface $connection_request = null;
    private ServerForWorker $server_for_worker;
    private ?string $client_id = null;
    private int $status = self::_STATUS_WAITING_FOR_ID;

    public function __construct(ConnectionInterface $connection_worker, ServerForWorker $server_for_worker)
    {
        $this->conn_id = uniqid();
        $this->connection_worker = $connection_worker;
        $this->server_for_worker = $server_for_worker;

        $this->server_for_worker->connections[$this->conn_id] = $this;

        $buffer = [];

        $connection_worker->on('data', function ($data) use ($connection_worker, &$buffer) {
            //$this->print_socket('on_data ' . $data);
            //$connection->write("server received: " . $data . PHP_EOL);

            $buffer[] = $data;
            $line = implode('', $buffer);

            echo 'line = ' . json_encode($line) . PHP_EOL;

            if (self::_STATUS_WAITING_FOR_ID === $this->status) {
                if (str_contains($line, PHP_EOL)) {
                    $buffer = [];
                    $this->client_id = trim($line);
                    echo 'client_id: ' . $this->client_id . PHP_EOL;
                    $this->status = self::_STATUS_WAITING_FOR_GET;
                    $this->connection_worker->write(PHP_EOL);
                }
            } else if (self::_STATUS_WAITING_FOR_GET === $this->status) {
                if (str_contains($line, 'get' . PHP_EOL)) {
                    $buffer = [];
                    $this->send_sql_to_worker();
                }
            } else if (self::_STATUS_WAITING_FOR_GET_RESULT === $this->status) {
                if (str_contains($line, PHP_EOL)) {
                    $buffer = [];
                    $this->connection_request->write($line);
                    $this->status = self::_STATUS_WAITING_FOR_GET;
                    $this->connection_worker->write(PHP_EOL);
                }
            }
        });

        $connection_worker->on('end', function () use ($connection_worker) {
            $this->print_socket('on_end');
        });

        $connection_worker->on('error', function (Exception $e) use ($connection_worker) {
            $this->print_socket('on_error');
        });

        $connection_worker->on('close', function () use ($connection_worker) {
            $this->print_socket('on_close');
            unset($this->server_for_worker->connections[$this->conn_id]);
        });
    }

    public function send_sql_to_worker()
    {
        if (self::_STATUS_WAITING_FOR_GET !== $this->status) {
            return false;
        }

        if (empty($GLOBALS['sqls'])) {
            return false;
        }

        $sql_data = array_shift($GLOBALS['sqls']);
        $sql = $sql_data[0];
        $this->connection_request = $sql_data[1];

        $this->status = self::_STATUS_WAITING_FOR_GET_RESULT;

        echo 'send_sql_to_worker:  ' . $sql . PHP_EOL;
        $this->connection_worker->write($sql . PHP_EOL);
        return true;
    }

    private function print_socket($message)
    {
        echo $this->server_for_worker->port . ' connection[' . $this->connection_worker->getRemoteAddress() . '] ' . $message . PHP_EOL;
    }
}