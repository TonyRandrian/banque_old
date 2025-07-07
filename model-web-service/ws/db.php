<?php
function getDB()
{
    $host = 'localhost';
    $dbname = 'model_ws';
    $username = 'root';
    $password = 'p@ssw0rd';

    try {
        return new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (PDOException $e) {
        die(json_encode(['error' => $e->getMessage()]));
    }
}
