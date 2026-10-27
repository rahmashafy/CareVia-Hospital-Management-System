<?php

// Database connection settings
$host = "localhost";
$username = "root";
$password = "";
$database = "carevia_db";

// Create database connection
$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set character encoding
$conn->set_charset("utf8mb4");

?>