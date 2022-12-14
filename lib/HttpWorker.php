<?php

use Psr\Http\Message\ServerRequestInterface;

class HttpWorker
{
    private $sqls = [];
    private $conn;

    public function __construct()
    {
        $this->conn = DbConnection::connect();

        $http = new React\Http\HttpServer(function (ServerRequestInterface $request) {
            $body = $request->getBody();
            $jobs = json_decode($body, true);

            //var_dump($jobs);

            $this->conn->beginTransaction();

            if (0) {
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

                        if (0) {
                            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                            $res['rows'] = $rows;
                        }

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
            } else {
                $sqls = [];
                foreach ($jobs as $i => $job) {
                    $req = $job['req'];
                    $sql = $req['sql'];
                    $binds = $req['binds'];
                    $sql = prepare_sql($this->conn, $sql, $binds);
                    $sqls[] = $sql;

                    $jobs[$i]['res'] = $sql;
                }

                $str = implode(';', $sqls) . ';';
                //$str .= "insert into test_user (id, name, age) values ('1', 'kim', '65');";
                //echo $str;
                try {
                    $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
                    $this->conn->query($str);
                }catch (Exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }

            $this->conn->commit();

            return React\Http\Message\Response::plaintext(
                json_encode_unescape($jobs) . PHP_EOL
            );
        });

        $port = 6000;
        if (isset($GLOBALS['argv'][1])) {
            $port = $GLOBALS['argv'][1];
        }

        $socket = new React\Socket\SocketServer('127.0.0.1:' . $port);
        $http->listen($socket);
    }
}
