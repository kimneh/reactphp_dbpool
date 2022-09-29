<?php

// telnet localhost 8080

require __DIR__ . '/../lib/inc.php';

$server_for_worker = new ServerForWorker();
$server_for_request = new ServerForRequest($server_for_worker);