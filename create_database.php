<?php
/**
 * Script to create SkulSoft database
 */

$host = '127.0.0.1';
$port = 8889;
$username = 'root';
$password = 'root';
$database = 'SkulSoft';

try {
    // Connect to MySQL without selecting a database
    $conn = new PDO("mysql:host=$host;port=$port", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sql);
    
    echo "✅ Database '$database' created successfully (or already exists)!\n";
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$database'");
    if ($result->rowCount() > 0) {
        echo "✅ Confirmed: Database '$database' exists\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
