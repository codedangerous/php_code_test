<?php

// Database credentials
$host = "localhost";
$username = "root";
$password = "root";
$dbname = "php_code_test";

try {
    // Establish connection to MySQL localhost
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Turn on exceptions for errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
    ];

    $pdo = new PDO($dsn, $username, $password, $options);

    $selectSql = "SELECT orderid, comments FROM sweetwater_test ORDER BY orderid";
    $stmt = $pdo->query($selectSql);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle any connection or query errors safely
    die("Database Error: " . $e->getMessage());
}

// 5. Close connection (Optional, as PHP closes it automatically when script ends)
$pdo = null;