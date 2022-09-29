<?php
require __DIR__ . '/../lib/inc.php';

$fp = fsockopen('127.0.0.1', 8080, $errno, $error, 3);
//stream_set_read_buffer($fp, 100);
if (!$fp) {
    echo "$error ($errno)\n";
} else {
    stream_set_blocking($fp, TRUE);
    stream_set_timeout($fp, 1);

    for ($i = 1; $i <= 100; $i++) {
        $sql = 'select give_id, ' .  $i. ' As c from give offset 0 limit 1' . PHP_EOL;
        echo $sql;
        fputs($fp, $sql);

        read_line($fp, function ($fp, $line) {
        });

        read_line($fp, function ($fp, $line) {
            echo $line . PHP_EOL;
        });

        //sleep(1);
    }
}