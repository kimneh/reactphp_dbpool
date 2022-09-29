<?php
/*
$fp = fopen("php://stdin","r");
stream_set_read_buffer($fp, 100);
stream_set_timeout($fp, 1);
while(true) {
    $strChar = stream_get_contents($fp, 1);
    echo("\nYou typed: ".$strChar."\n");
}

*/

$fp = fsockopen('127.0.0.1', 8080, $errno, $error, 3);
//stream_set_read_buffer($fp, 100);
if (!$fp) {
    echo "$error ($errno)\n";
} else {
    stream_set_blocking($fp, TRUE);
    stream_set_timeout($fp, 5);

    fputs($fp, 'hi i am client');

    while ((!feof($fp))) {
        $data = fgets($fp, 4096);
        $info = stream_get_meta_data($fp);
        $is_timeout = $info['timed_out'];
        echo $data . PHP_EOL;
        echo json_encode($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}