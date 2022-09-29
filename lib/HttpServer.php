<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\ChildProcess\Process;
use React\EventLoop\Loop;
use React\Http\Browser;
use React\Promise\Promise;

class HttpServer
{
    private $jobs = [];
    private $job_promises = [];

    public function __construct()
    {
        $file = realpath(__DIR__ . '/../cli/http_worker.php');

        for ($i = 1; $i <= 10; $i++)
        {
            $process = new Process('php ' . $file . ' ' . (6000 + $i));
            $process->start();

            $process->stdout->on('data', function ($chunk) {
                echo $chunk . PHP_EOL;
            });

            $process->stdout->on('end', function () {
                echo 'ended' . PHP_EOL;
            });

            $process->stdout->on('error', function (Exception $e) {
                echo 'error: ' . $e->getMessage() . PHP_EOL;
            });

            $process->stdout->on('close', function () {
                echo 'closed' . PHP_EOL;
            });
        }

        Loop::addPeriodicTimer(0.01, function () {
            $client = new Browser();

            if (empty($this->jobs)) {
                return;
            }

            $jobs = [];
            $job_promises = [];

            $batch_size = 0;
            foreach ($this->jobs as $job_id => $job) {
                $batch_size++;
                if ($batch_size > 50) {
                    break;
                }

                $jobs[$job_id] = $job;
                $job_promises[$job_id] = $this->job_promises[$job_id];

                unset($this->jobs[$job_id]);
                unset($this->job_promises[$job_id]);
            }

            $port = 6000;
            $port = mt_rand(6001, 6010);

            echo 'sending ' . count($jobs) . ' to port ' . $port . PHP_EOL;

            $client->post(
                'http://127.0.0.1:' . $port,
                array(
                    'Content-Type' => 'application/json'
                ),
                json_encode_unescape($jobs)
            )->then(function (ResponseInterface $response) use (&$jobs, &$job_promises) {
                $body = (string)$response->getBody();
                $results = json_decode($body, true);
                foreach ($results as $result) {
                    $job_id = $result['job_id'];
                    $resolve = $job_promises[$job_id];

                    unset($jobs[$job_id]);
                    unset($job_promises[$job_id]);

                    $resolve(json_encode_unescape($result));
                }

                foreach ($jobs as $job_id => $job) {
                    $resolve = $job_promises[$job_id];
                    $resolve('not_found');
                }

            }, function (Exception $e) use (&$jobs, &$job_promises) {
                $message = $e->getMessage();
                echo $message . PHP_EOL;
                foreach ($jobs as $job_id => $job) {
                    $resolve = $job_promises[$job_id];
                    $resolve($message);
                }
            });
        });

        $http = new React\Http\HttpServer(function (ServerRequestInterface $request) {
            $params = $request->getQueryParams();
            $method = $request->getMethod();
            $body = $request->getBody();

            if ('insert_job' === $params['action']) {
                $promise = new Promise(function ($resolve, $reject) use ($body) {
                    $data = json_decode($body, true);
                    $sql = $data['sql'];
                    $binds = $data['binds'];
                    $job_id = $this->gen_id();

                    $this->jobs[$job_id] = [
                        'job_id' => $job_id,
                        'req' => $data,
                    ];
                    $this->job_promises[$job_id] = $resolve;
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
        for ($i = 0; $i < 10; $i++) {
            $id .= base_convert(mt_rand(0, 35), 10, 36);
        }
        //echo 'id=' . $id . PHP_EOL;
        return $id;
    }
}
