<?php
// ============================================================
// logout.php - Logout Handler
// Destroys the user session and redirects to the login page
// ============================================================

// Start the session so we can access and destroy it
session_start();

// Remove all session variables (clears user_id, name, email, role)
session_unset();

// Destroy the session completely on the server
session_destroy();

// Send the user back to the login page
header("Location: login.php");
exit();
?>
