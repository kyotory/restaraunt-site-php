<?php

require_once __DIR__ . '/bootstrap.php';

function db()
{
    static $mysqli = null;

    if ($mysqli instanceof mysqli) {
        return $mysqli;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $mysqli = new mysqli('127.0.0.1', 'root', '', 'restaraunt', 3306);
    $mysqli->set_charset('utf8mb4');

    bootstrap_database($mysqli);

    return $mysqli;
}
