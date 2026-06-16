<?php
session_start();
// Hide HTML errors to ensure strict JSON output
error_reporting(0);
header('Content-Type: application/json');

include('includes/dbconnection.php');
date_default_timezone_set('Asia/Kolkata');

// Ensure this is a POST request and data was sent
if (!isset($_POST['qr_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Request.']);
    exit();
}

$qr_id = trim($_POST['qr_id']);
$action = $_POST['action']; // 'in' or 'out'
$current_time = date('H:i');
$today = date('Y-m-d');

// --- 1. FETCH SYSTEM SETTINGS (TIME MATRIX) ---
$settings = [];
try {
    $q_set = $dbh->query("SELECT setting_key, setting_value FROM system_settings");
    while($row = $q_set->fetch(PDO::FETCH_ASSOC)) { 
        $settings[$row['setting_key']] = $row['setting_value']; 
    }
} catch(Exception $e) {
    // If table doesn't exist yet, default to off
}

$enforce = $settings['enforce_time_windows'] ?? '0'; // Defaults to 0 (OFF)
$in_start = $settings['checkin_start'] ?? '08:00';
$in_end = $settings['checkin_end'] ?? '09:30';
$out_start = $settings['checkout_start'] ?? '16:00';
$out_end = $settings['checkout_end'] ?? '18:00';

// --- 2. ENFORCE TIME RULES (ONLY IF TOGGLE IS ON) ---
if ($enforce === '1') {
    if ($action === 'in') {
        if ($current_time < $in_start || $current_time > $in_end) {
            $formatted_start = date("h:i A", strtotime($in_start));
            $formatted_end = date("h:i A", strtotime($in_end));
            echo json_encode(['success' => false, 'message' => "CHECK-IN IS ONLY ALLOWED BETWEEN {$formatted_start} AND {$formatted_end}"]);
            exit();
        }
    } else if ($action === 'out') {
        if ($current_time < $out_start || $current_time > $out_end) {
            $formatted_start = date("h:i A", strtotime($out_start));
            $formatted_end = date("h:i A", strtotime($out_end));
            echo json_encode(['success' => false, 'message' => "CHECK-OUT IS ONLY ALLOWED BETWEEN {$formatted_start} AND {$formatted_end}"]);
            exit();
        }
    }
}

// --- 3. FIND THE FACULTY MEMBER ---
try {
    $teacher_sql = "SELECT ID, FirstName, LastName FROM tblteacher WHERE qr_code_identifier = :qr";
    $teacher_stmt = $dbh->prepare($teacher_sql);
    $teacher_stmt->execute([':qr' => $qr_id]);
    $teacher = $teacher_stmt->fetch(PDO::FETCH_OBJ);

    if (!$teacher) {
        echo json_encode(['success' => false, 'message' => 'INVALID QR: IDENTITY NOT FOUND.']);
        exit();
    }

    $teacher_id = $teacher->ID;
    $now_full = date('H:i:s');

    // --- 4. LOG ATTENDANCE ---
    // Check if record exists for today
    $check_sql = "SELECT id FROM teacher_attendance WHERE teacher_id = ? AND attendance_date = ?";
    $check_stmt = $dbh->prepare($check_sql);
    $check_stmt->execute([$teacher_id, $today]);
    $record = $check_stmt->fetch(PDO::FETCH_OBJ);

    if ($record) {
        // Record exists: Update it
        if ($action === 'in') {
            $upd = $dbh->prepare("UPDATE teacher_attendance SET check_in_time = ?, status = 'present' WHERE id = ?");
            $upd->execute([$now_full, $record->id]);
            $msg = "CHECK-IN UPDATED";
        } else {
            $upd = $dbh->prepare("UPDATE teacher_attendance SET check_out_time = ? WHERE id = ?");
            $upd->execute([$now_full, $record->id]);
            $msg = "CHECK-OUT LOGGED";
        }
    } else {
        // No record exists for today: Insert a new one
        if ($action === 'in') {
            $ins = $dbh->prepare("INSERT INTO teacher_attendance (teacher_id, attendance_date, check_in_time, status) VALUES (?, ?, ?, 'present')");
            $ins->execute([$teacher_id, $today, $now_full]);
            $msg = "CHECK-IN SUCCESSFUL";
        } else {
            $ins = $dbh->prepare("INSERT INTO teacher_attendance (teacher_id, attendance_date, check_out_time, status) VALUES (?, ?, ?, 'absent')");
            $ins->execute([$teacher_id, $today, $now_full]);
            $msg = "CHECK-OUT LOGGED (NO CHECK-IN FOUND)";
        }
    }

    // Send Success JSON
    echo json_encode([
        'success' => true,
        'teacher_name' => "Prof. " . $teacher->FirstName . ' ' . $teacher->LastName,
        'employee_id' => 'FACULTY ID: ' . $teacher->ID,
        'message' => $msg
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'SYSTEM DATABASE ERROR.']);
}
?>