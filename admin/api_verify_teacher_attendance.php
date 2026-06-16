<?php
include('includes/dbconnection.php');
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');

if (!isset($_POST['qr_id'])) {
    echo json_encode(['success' => false, 'message' => 'No QR Code provided.']);
    exit();
}

$qr_id = $_POST['qr_id'];

// 1. Find the teacher with this QR code
$teacher_sql = "SELECT ID, FirstName, LastName FROM tblteacher WHERE qr_code_identifier = ?";
$teacher_stmt = $dbh->prepare($teacher_sql);
$teacher_stmt->execute([$qr_id]);
$teacher = $teacher_stmt->fetch(PDO::FETCH_OBJ);

if (!$teacher) {
    echo json_encode(['success' => false, 'message' => 'Invalid QR Code. Teacher not found.']);
    exit();
}

// 2. Check if already marked for today
$today = date('Y-m-d');
$check_sql = "SELECT id FROM teacher_attendance WHERE teacher_id = ? AND attendance_date = ?";
$check_stmt = $dbh->prepare($check_sql);
$check_stmt->execute([$teacher->ID, $today]);

if ($check_stmt->rowCount() > 0) {
    $message = htmlspecialchars($teacher->FirstName) . ", your attendance has already been marked for today.";
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// 3. Save the new attendance record
try {
    $insert_sql = "INSERT INTO teacher_attendance (teacher_id, attendance_date, status, check_in_time) VALUES (?, ?, 'present', ?)";
    $insert_stmt = $dbh->prepare($insert_sql);
    $insert_stmt->execute([$teacher->ID, $today, date('H:i:s')]);
    
    $message = "Welcome, " . htmlspecialchars($teacher->FirstName) . "! Your attendance has been marked for " . $today . ".";
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>