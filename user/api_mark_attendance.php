<?php
session_start();
include('includes/dbconnection.php');
header('Content-Type: application/json');

if (strlen($_SESSION['ocasuid'] ?? '') == 0) {
    echo json_encode(['success' => false, 'message' => 'Error: You must be logged in.']);
    exit();
}
if (!isset($_POST['qr_data'])) {
    echo json_encode(['success' => false, 'message' => 'Error: No QR data received.']);
    exit();
}

$student_id = $_SESSION['ocasuid'];
$timetable_schedule_id = intval($_POST['qr_data']); // Data from teacher's QR

try {
    // Check if attendance was already marked
    $check_sql = "SELECT id FROM student_attendance WHERE timetable_schedule_id = ? AND student_id = ?";
    $check_stmt = $dbh->prepare($check_sql);
    $check_stmt->execute([$timetable_schedule_id, $student_id]);

    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Attendance has already been marked for this class.']);
        exit();
    }

    // Insert the new attendance record
    $insert_sql = "INSERT INTO student_attendance (timetable_schedule_id, student_id, status, attendance_time) VALUES (?, ?, 'present', NOW())";
    $insert_stmt = $dbh->prepare($insert_sql);
    
    if ($insert_stmt->execute([$timetable_schedule_id, $student_id])) {
        echo json_encode(['success' => true, 'message' => 'Attendance marked successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark attendance.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>