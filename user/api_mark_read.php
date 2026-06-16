<?php
session_start();
include('includes/dbconnection.php');
$user_id = $_SESSION['ocasuid']; // Correct session ID for students
$user_type = 'student';
$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = ?";
$dbh->prepare($sql)->execute([$user_id, $user_type]);
?>