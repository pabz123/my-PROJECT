<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// php/db_connect.php
// Keep credentials outside webroot in production. This file is a simple local-dev example.
$DB_HOST = '127.0.0.1';
$DB_NAME = 'student_portal';
$DB_USER = 'root';
$DB_PASS = ''; // set your local root password if any
$DB_CHAR = 'utf8mb4';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHAR";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
  // In production, log error to file rather than echo
  echo 'Database connection failed.';
  error_log($e->getMessage());
  exit;
}
