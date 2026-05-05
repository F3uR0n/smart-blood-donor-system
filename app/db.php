<?php
$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);

$mysqli = new mysqli(
    $config['db']['host'],
    $config['db']['user'],
    $config['db']['pass'],
    $config['db']['name'],
    $config['db']['port']
);

if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset($config['db']['charset']);
