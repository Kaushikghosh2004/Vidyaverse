<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$exp_id = $_GET['id'] ?? 0;

$sql = "SELECT * FROM vlabs_experiments WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exp_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $experiment = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode($experiment);
} else {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Experiment not found']);
}