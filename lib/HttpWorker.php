<?php

use Psr\Http\Message\ServerRequestInterface;

class HttpWorker
{
    private $sqls = [];
    private $conn;

    public function __construct()
    {
        $host = '127.0.0.1';
        $port = 5432;
        $database = 'hpoint_db';
        $username = 'hpoint_user';
        $password = 'this_is_point_secret_2022';
        $schema = 'hpoint_schema';

        $dsn = 'pgsql:host=' . $host . ';port=' . $port . ';dbname=' . $database . ';';
        $conn = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        if (!$conn) {
            echo 'fail to connect to db' . PHP_EOL;
            exit;
        }
        $conn->exec("set schema '" . $schema . "';");

        $this->conn = $conn;

        $http = new React\Http\HttpServer(function (ServerRequestInterface $request) {
            $body = $request->getBody();
            $jobs = json_decode($body, true);

            foreach ($jobs as $i => $job) {
                $req = $job['req'];
                $sql = $req['sql'];
                $binds = $req['binds'];
                $res = [];

                if (!isset($this->sqls[$sql])) {
                    $this->sqls[$sql] = $this->conn->prepare($sql);
                }

                $sth = $this->sqls[$sql];

                try {
                    $sth->execute($binds);
                    $affected_rows = $sth->rowCount();

                    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                    $res['rows'] = $rows;
                    $res['affected_rows'] = $affected_rows;
                } catch (Exception $e) {
                    $res['exception']['code'] = $e->getCode();
                    $res['exception']['message'] = $e->getMessage();
                } catch (Error $e) {
                    $res['error']['code'] = $e->getCode();
                    $res['error']['message'] = $e->getMessage();
                }

                $jobs[$i]['res'] = $res;
            }

            return React\Http\Message\Response::plaintext(
                json_encode_unescape($jobs) . PHP_EOL
            );
        });

        $socket = new React\Socket\SocketServer('127.0.0.1:8081');
        $http->listen($socket);
    }
}
