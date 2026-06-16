<?php
// doctor.php - Session Diagnosis Tool
session_start();

echo "<h1>Session Diagnosis</h1>";
echo "<p><strong>Current Session ID:</strong> " . session_id() . "</p>";

echo "<h3>Stored Variables:</h3>";
if (!empty($_SESSION)) {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<p style='color:red; font-weight:bold;'>SESSION IS EMPTY! (Login failed or session lost)</p>";
}

echo "<h3>Database Check:</h3>";
include('includes/dbconnection.php');
if ($dbh) {
    echo "<p style='color:green;'>Database Connected Successfully.</p>";
} else {
    echo "<p style='color:red;'>Database Connection FAILED.</p>";
}

echo "<br><a href='login.php'>Go back to Login</a>";
?>