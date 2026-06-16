<?php
session_start();
include('includes/dbconnection.php');
header('Content-Type: application/json');

$user_id = $_SESSION['ocasuid']; // Correct session ID for students
$user_type = 'student';

$count_sql = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND user_type = ? AND is_read = 0";
$count_query = $dbh->prepare($count_sql);
$count_query->execute([$user_id, $user_type]);
$unread_count = $count_query->fetch(PDO::FETCH_OBJ)->unread;

$notif_sql = "SELECT message FROM notifications WHERE user_id = ? AND user_type = ? ORDER BY created_at DESC LIMIT 5";
$notif_query = $dbh->prepare($notif_sql);
$notif_query->execute([$user_id, $user_type]);
$notifications = $notif_query->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['unread_count' => $unread_count, 'notifications' => $notifications]);
?>