<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\Browser;
use React\Promise\Promise;

class HttpServer
{
    private $jobs = [];

    public function __construct()
    {
        $http = new React\Http\HttpServer(function (ServerRequestInterface $request) {
            $params = $request->getQueryParams();
            $method = $request->getMethod();
            $body = $request->getBody();

            if ('insert_job' === $params['action']) {
                $data = json_decode($body, true);
                $sql = $data['sql'];
                $binds = $data['binds'];
                $job_id = $this->gen_id();
                $this->jobs[$job_id] = ['req' => $data];

                $promise = new Promise(function ($resolve, $reject) use ($body, $job_id) {
                    Loop::addTimer(0.001, function () use ($resolve, $body, $job_id) {

                        $client = new Browser();
                        $client->post(
                            'http://127.0.0.1:8081',
                            array(
                                'Content-Type' => 'application/json'
                            ),
                            json_encode_unescape([$this->jobs[$job_id]])
                        )->then(function (ResponseInterface $response) use($resolve, $job_id) {
                            $body = (string)$response->getBody();
                            unset($this->jobs[$job_id]);
                            echo 'count = ' . count($this->jobs) . PHP_EOL;
                            $resolve($body);

                        }, function (Exception $e) use($resolve, $job_id) {
                            $message = $e->getMessage();
                            echo $message . PHP_EOL;
                            $body = [];
                            unset($this->jobs[$job_id]);
                            echo 'count = ' . count($this->jobs) . PHP_EOL;
                            $resolve($message);
                        });
                    });
                });

                return $promise->then(function ($body) {
                    //var_dump($a);
                    return React\Http\Message\Response::plaintext(
                        $body . PHP_EOL
                    );
                });
            }

            return React\Http\Message\Response::plaintext(
                '404' . PHP_EOL
            );
        });

        $socket = new React\Socket\SocketServer('127.0.0.1:8080');
        $http->listen($socket);
    }

    private function gen_id()
    {
        $id = base_convert(time(), 10, 36) . '-';
        for ($i = 0; $i < 10; $i++)
        {
            $id .= base_convert(mt_rand(0, 35), 10, 36);
        }
        echo 'id=' . $id . PHP_EOL;
        return $id;
    }
}
