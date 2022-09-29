<?php
require __DIR__ . '/../lib/inc.php';

ini_set('memory_limit', '256M');

$GLOBALS['stats'] = [
    'run_same_prepared_statement_every_time' => [],
    'run_unprepared_statement_every_time' => [],
    'run_new_prepared_statement_every_time' => [],
    'run_new_connection_every_time' => [],
];
foreach ($GLOBALS['stats'] as $name => $stat) {
    $GLOBALS['stats'][$name] = [
        'lapsed' => 0,
        'count' => 0,
        'affected_rows' => 0,
    ];
}
function run_same_prepared_statement_every_time($sql)
{
    $conn = truncate_table();
    $sth = $conn->prepare($sql);

    $time = microtime(true);
    $affected_rows = 0;
    for ($i = 1; $i <= 500; $i++) {
        $binds = ['name' => 'kim', 'id' => $i, 'age' => mt_rand(1, 100)];

        $sth->execute($binds);
        $affected_rows += $sth->rowCount();
    }
    $lapsed = microtime(true) - $time;

    $GLOBALS['stats']['run_same_prepared_statement_every_time']['lapsed'] += $lapsed;
    $GLOBALS['stats']['run_same_prepared_statement_every_time']['count']++;
    $GLOBALS['stats']['run_same_prepared_statement_every_time']['affected_rows'] += $affected_rows;
}

function run_unprepared_statement_every_time($sql)
{
    $conn = truncate_table();
    $sth = $conn->prepare($sql);

    $time = microtime(true);
    $affected_rows = 0;
    for ($i = 1; $i <= 500; $i++) {
        $conn->query(sprintf("insert into test_user (id, name, age) values (%s, '%s', %s)", $i, 'kim', mt_rand(1, 100)));
        $affected_rows++;
    }
    $lapsed = microtime(true) - $time;

    $GLOBALS['stats']['run_unprepared_statement_every_time']['lapsed'] += $lapsed;
    $GLOBALS['stats']['run_unprepared_statement_every_time']['count']++;
    $GLOBALS['stats']['run_unprepared_statement_every_time']['affected_rows'] += $affected_rows;
}

function run_new_prepared_statement_every_time($sql)
{
    $conn = truncate_table();

    $time = microtime(true);
    $affected_rows = 0;
    for ($i = 1; $i <= 500; $i++) {
        $binds = ['name' => 'kim', 'id' => $i, 'age' => mt_rand(1, 100)];

        $sth = $conn->prepare($sql);
        $sth->execute($binds);
        $affected_rows += $sth->rowCount();
    }
    $lapsed = microtime(true) - $time;

    $GLOBALS['stats']['run_new_prepared_statement_every_time']['lapsed'] += $lapsed;
    $GLOBALS['stats']['run_new_prepared_statement_every_time']['count']++;
    $GLOBALS['stats']['run_new_prepared_statement_every_time']['affected_rows'] += $affected_rows;
}

function run_new_connection_every_time($sql)
{
    $conn = truncate_table();

    $time = microtime(true);
    $affected_rows = 0;
    for ($i = 1; $i <= 500; $i++) {
        $binds = ['name' => 'kim', 'id' => $i, 'age' => mt_rand(1, 100)];

        $conn = DbConnection::connect();
        $sth = $conn->prepare($sql);
        $sth->execute($binds);
        $affected_rows += $sth->rowCount();
    }
    $lapsed = microtime(true) - $time;

    $GLOBALS['stats']['run_new_connection_every_time']['lapsed'] += $lapsed;
    $GLOBALS['stats']['run_new_connection_every_time']['count']++;
    $GLOBALS['stats']['run_new_connection_every_time']['affected_rows'] += $affected_rows;
}


$sql = 'insert into test_user (id, name, age) values (:id, :name, :age)';

for ($i = 0; $i < 100; $i++) {
    run_same_prepared_statement_every_time($sql);
    run_unprepared_statement_every_time($sql);
    run_new_prepared_statement_every_time($sql);
    //run_new_connection_every_time($sql);

    foreach ($GLOBALS['stats'] as $name => $stat) {

        if (!$stat['count']) {
            continue;
        }

        $avg_lapsed = $stat['lapsed'] / $stat['count'];

        echo str_pad($name, 40, ' ') . ' : ';
        echo ' avg_lapsed = ' . str_pad(number_format($avg_lapsed, 3), 8, ' ', STR_PAD_LEFT) . 's';
        echo ' affected_rows = ' . number_format($stat['affected_rows']);
        echo PHP_EOL;
    }

    echo PHP_EOL;
}