<?php
session_start();
// Adjust path to dbconnection as needed (e.g., ../includes/ or includes/)
include('includes/dbconnection.php'); 

header('Content-Type: application/json');

// 1. Security Check
if (strlen($_SESSION['ocastid'] ?? '') == 0) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['ocastid']; 
$user_type = 'teacher';

// --- 2. TRIGGER ALERT GENERATOR (LAZY LOAD) ---
// This includes the script we created earlier to check the timetable 
// and generate alerts if a class is starting soon.
// Adjust the path below if your cron file is in the root folder (e.g., '../cron_generate_notifications.php')
if (file_exists('cron_generate_notifications.php')) {
    include('cron_generate_notifications.php');
} elseif (file_exists('../cron_generate_notifications.php')) {
    include('../cron_generate_notifications.php');
}
// ----------------------------------------------

try {
    // 3. Get Unread Count
    $count_sql = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND user_type = ? AND is_read = 0";
    $count_query = $dbh->prepare($count_sql);
    $count_query->execute([$user_id, $user_type]);
    $unread_count = $count_query->fetch(PDO::FETCH_OBJ)->unread;

    // 4. Get Recent Notifications
    $notif_sql = "SELECT message, created_at FROM notifications WHERE user_id = ? AND user_type = ? ORDER BY created_at DESC LIMIT 5";
    $notif_query = $dbh->prepare($notif_sql);
    $notif_query->execute([$user_id, $user_type]);
    $notifications = $notif_query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['unread_count' => $unread_count, 'notifications' => $notifications]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>