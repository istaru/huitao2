<?php
class Database {
    public static function pdo($dsn, $username, $password) {
        return new PDO($dsn,$username,$password);
    }
}