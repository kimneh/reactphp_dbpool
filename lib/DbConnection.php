<?php

class DbConnection
{
    public static function connect()
    {
        $host = '127.0.0.1';
        $port = 5432;
        $database = 'hpoint_db';
        $username = 'hpoint_user';
        $password = 'this_is_point_secret_2022';
        $schema = 'hpoint_schema';

        $dsn = 'pgsql:host=' . $host . ';port=' . $port . ';dbname=' . $database . ';';
        $conn = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        if (!$conn) {
            echo 'fail to connect to db' . PHP_EOL;
            exit;
        }
        $conn->exec("set schema '" . $schema . "';");
        return $conn;
    }
}