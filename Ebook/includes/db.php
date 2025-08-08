<?php
$host = 'localhost'; // Change this if your database is hosted elsewhere
$dbname = 'ebook_management';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}