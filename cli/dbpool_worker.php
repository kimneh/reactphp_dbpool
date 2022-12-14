<?php
require __DIR__ . '/../lib/inc.php';

$conn = DbConnection::connect();

$fp = fsockopen('127.0.0.1', 8081, $errno, $error, 3);
//stream_set_read_buffer($fp, 100);
if (!$fp) {
    echo "$error ($errno)\n";
    exit;
}

stream_set_blocking($fp, TRUE);
stream_set_timeout($fp, 1);

fputs($fp, uniqid() . PHP_EOL);

while (true) {
    read_line($fp, function ($fp, $line) {
        fputs($fp, 'get' . PHP_EOL);
        echo 'sent_get' . PHP_EOL;
    });

    $data = [];
    read_line($fp, function ($fp, $line) use ($conn, &$data) {
        $sql = trim($line);
        echo 'sql = [' . $sql . ']' . PHP_EOL;

        try {
            $sth = $conn->prepare($sql);
            $sth->execute();
            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
            $data['rows'] = $rows;
        } catch (Exception $e) {
            $data['exception']['code'] = $e->getCode();
            $data['exception']['message'] = $e->getMessage();
        } catch (Error $e) {
            $data['error']['code'] = $e->getCode();
            $data['error']['message'] = $e->getMessage();
        }
    });

    //usleep(10 * 1000);

    read_line($fp, function ($fp, $line) use (&$data) {
        fputs($fp, json_encode($data) . PHP_EOL);
        echo 'sent_sql_result' . PHP_EOL;
    });
}
