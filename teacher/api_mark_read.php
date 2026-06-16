<?php
session_start();
// Include database connection
include('includes/dbconnection.php');

// Set header for JSON response (good practice for APIs)
header('Content-Type: application/json');

// 1. Security Check: Ensure Teacher is logged in
if (empty($_SESSION['ocastid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['ocastid']; 
$user_type = 'teacher';

try {
    // 2. Mark all notifications for this user as read
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND user_type = :utype";
    $query = $dbh->prepare($sql);
    $query->bindParam(':uid', $user_id, PDO::PARAM_INT);
    $query->bindParam(':utype', $user_type, PDO::PARAM_STR);
    $query->execute();

    // 3. Return success response
    echo json_encode(['status' => 'success', 'message' => 'Notifications marked as read']);

} catch (Exception $e) {
    // Handle database errors gracefully
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>