<?php
// ============================================================
// db.php - Database Connection File
// This file creates a PDO connection to the MySQL database.
// Include this file in any PHP page that needs the database.
// ============================================================

// Database settings - change these if your XAMPP setup is different
$host     = "localhost";   // MySQL server (XAMPP uses localhost)
$dbname   = "grad_collab"; // The database we created
$username = "root";         // Default XAMPP MySQL username
$password = "";             // Default XAMPP MySQL password (empty)

try {
    // Create a new PDO connection
    // PDO is safer than mysqli because it uses prepared statements easily
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Tell PDO to throw exceptions when a database error occurs
    // This makes it easier to catch and handle errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // If the connection fails, stop the script and show a message
    // We do NOT show $e->getMessage() to users because it exposes server details
    die("Database connection failed. Please check your XAMPP settings.");
}
?>
