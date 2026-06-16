<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$experiment_id = $data['experiment_id'] ?? 0;
$status = $data['status'] ?? 'started';

// Check if activity already exists
$check_sql = "SELECT id FROM vlabs_activities WHERE user_id = ? AND experiment_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $user_id, $experiment_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Update existing activity
    $update_sql = "UPDATE vlabs_activities SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND experiment_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sii", $status, $user_id, $experiment_id);
    $update_stmt->execute();
} else {
    // Insert new activity
    $insert_sql = "INSERT INTO vlabs_activities (user_id, experiment_id, status) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iis", $user_id, $experiment_id, $status);
    $insert_stmt->execute();
}

echo json_encode(['success' => true]);