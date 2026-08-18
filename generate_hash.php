<?php


$hash = password_hash('admin123', PASSWORD_DEFAULT);
echo "Hash for 'admin123': <br><br>";
echo "<strong>" . $hash . "</strong>";
echo "<br><br>Use this in your database.sql INSERT statement.";
echo "<br><br><strong>DELETE this file after you have copied the hash!</strong>";
?>
