<?php

use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\Http\Browser;

require __DIR__ . '/../lib/inc.php';

ini_set('memory_limit','256M');

//truncate_table();

$client = new Browser();

$GLOBALS['time'] = microtime(true);
$GLOBALS['completed'] = 0;
function on_complete()
{
    $GLOBALS['completed']++;
    if ($GLOBALS['completed'] >= 500) {
        $lapsed = round(microtime(true) - $GLOBALS['time'], 3);
        echo 'lapsed = ' . $lapsed . PHP_EOL;
    }
}

for ($i = 1; $i <= 1500; $i++) {
    $id = random_id();
    $client->post(
        'http://127.0.0.1:8080?action=insert_job',
        array(
            'Content-Type' => 'application/json'
        ),
        json_encode_unescape([
            'sql' => 'insert into test_user (id, name, age) values (:id, :name, :age)',
            'binds' => ['name' => 'kim', 'id' => $id, 'age' => mt_rand(1, 100)],
        ])
    )->then(function (ResponseInterface $response)  {
        //echo (string)$response->getBody();
        on_complete();
    }, function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
        on_complete();
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