<?php
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    // Local XAMPP
    $host = 'localhost';
    $dbname = 'ebook_management';
    $username = 'root';
    $password = '';
} else {
    // InfinityFree Hosting
    $host = 'sql304.infinityfree.com';
    $dbname = 'if0_39481305_ora_db';
    $username = 'if0_39481305';
    $password = 'isur1978';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
