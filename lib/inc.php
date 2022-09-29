<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set('memory_limit','256M');

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/ServerForWorker.php';
require_once __DIR__ . '/WorkerConnection.php';
require_once __DIR__ . '/ServerForRequest.php';
require_once __DIR__ . '/RequestConnection.php';
require_once __DIR__ . '/HttpServer.php';
require_once __DIR__ . '/HttpWorker.php';
require_once __DIR__ . '/DbConnection.php';

function json_encode_unescape($data)
{
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function read_line($fp, $callback)
{
    $buffer = [];
    while (!feof($fp)) {
        $line = fgets($fp, 4096);
        $buffer[] = $line;
        if (str_contains($line, PHP_EOL)) {
            $line = implode('', $buffer);
            $callback($fp, $line);
            $buffer = null;
            unset($buffer);
            return;
        }

        $info = stream_get_meta_data($fp);
        $is_timeout = $info['timed_out'];
        if ($is_timeout) {
            //echo json_encode($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
    }

    exit;
}

function truncate_table()
{
    $conn = DbConnection::connect();
    $sql = 'truncate test_user';
    $conn->query($sql);
    return $conn;
}