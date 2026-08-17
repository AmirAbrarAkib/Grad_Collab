<?php
// ============================================================
// generate_hash.php
// TEMPORARY FILE - Delete after use!
// Run this once to generate the correct hash for 'admin123'
// Visit: http://localhost/grad_collab/generate_hash.php
// ============================================================

$hash = password_hash('admin123', PASSWORD_DEFAULT);
echo "Hash for 'admin123': <br><br>";
echo "<strong>" . $hash . "</strong>";
echo "<br><br>Use this in your database.sql INSERT statement.";
echo "<br><br><strong>DELETE this file after you have copied the hash!</strong>";
?>
