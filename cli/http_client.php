<?php

use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\Http\Browser;

require __DIR__ . '/../lib/inc.php';

ini_set('memory_limit','256M');

$client = new Browser();

for ($i = 1; $i <= 500; $i++) {
    $client->post(
        'http://127.0.0.1:8080?action=insert_job',
        array(
            'Content-Type' => 'application/json'
        ),
        json_encode_unescape([
            'sql' => 'insert into test_user (id, name, age) values (:id, :name, :age)',
            'binds' => ['name' => 'kim', 'id' => $i, 'age' => mt_rand(1, 100)],
        ])
    )->then(function (ResponseInterface $response) {
        echo (string)$response->getBody();
    }, function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    });
}

if (0) {
    Loop::addTimer(0.5, function () {
        $client = new Browser();
        for ($i = 1; $i <= 100; $i++) {
            $client->post(
                'http://127.0.0.1:8080?action=insert_job',
                array(
                    'Content-Type' => 'application/json'
                ),
                json_encode_unescape([
                    'sql' => 'select * from test_user where id = :id',
                    'binds' => ['id' => $i],
                ])
            )->then(function (ResponseInterface $response) {
                echo (string)$response->getBody();
            }, function (Exception $e) {
                echo 'Error: ' . $e->getMessage() . PHP_EOL;
            });
        }
    });
}