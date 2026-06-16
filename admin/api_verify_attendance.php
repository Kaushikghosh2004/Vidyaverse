<?php
session_start();
// Keep HTML errors off so JSON doesn't break, but capture exceptions
error_reporting(0);
header('Content-Type: application/json');

include('includes/dbconnection.php');
date_default_timezone_set('Asia/Kolkata');

// --- 0. SELF-HEALING DATABASE CHECK ---
// Automatically create the student_attendance table if it is missing
try {
    $dbh->query("CREATE TABLE IF NOT EXISTS `student_attendance` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `student_id` int(11) NOT NULL,
      `attendance_date` date NOT NULL,
      `check_in_time` time DEFAULT NULL,
      `check_out_time` time DEFAULT NULL,
      `status` varchar(20) DEFAULT 'absent',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch(Exception $e) {
    // Ignore if user lacks permissions to create tables
}

// 1. Validate Input
if (!isset($_POST['qr_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'INVALID NEURAL SIGNAL (No Data)']);
    exit();
}

$qr_id = trim($_POST['qr_id']);
$action = $_POST['action']; // 'in' or 'out'
$current_time = date('H:i');
$current_day = date('l'); 
$today = date('Y-m-d');
$now_full = date('H:i:s');

try {
    // 2. Fetch Global Settings Matrix
    $settings = [];
    try {
        $q_set = $dbh->query("SELECT setting_key, setting_value FROM system_settings");
        while($row = $q_set->fetch(PDO::FETCH_ASSOC)) { $settings[$row['setting_key']] = $row['setting_value']; }
    } catch(Exception $e) {} // Ignore if settings table is missing

    $mode = $settings['stu_enforce_mode'] ?? 'timetable';
    $in_start = $settings['stu_checkin_start'] ?? '08:00';
    $in_end = $settings['stu_checkin_end'] ?? '10:00';
    $out_start = $settings['stu_checkout_start'] ?? '15:00';
    $out_end = $settings['stu_checkout_end'] ?? '17:00';

    // 3. Find the Student 
    // FIX: Using SELECT * to prevent "column not found" crashes if batch_id is missing
    $stu_sql = "SELECT * FROM tbluser WHERE qr_code_identifier = :qr";
    $stu_stmt = $dbh->prepare($stu_sql);
    $stu_stmt->execute([':qr' => $qr_id]);
    $student = $stu_stmt->fetch(PDO::FETCH_OBJ);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'INVALID QR: IDENTITY NOT FOUND']);
        exit();
    }
    
    $student_id = $student->ID;
    $batch_id = isset($student->batch_id) ? $student->batch_id : null;

    // 4. Enforce Security Modes
    if ($mode === 'manual_window') {
        if ($action === 'in') {
            if ($current_time < $in_start || $current_time > $in_end) {
                echo json_encode(['success' => false, 'message' => "CHECK-IN CLOSED. ALLOWED: " . date("h:i A", strtotime($in_start)) . " TO " . date("h:i A", strtotime($in_end))]);
                exit();
            }
        } else if ($action === 'out') {
            if ($current_time < $out_start || $current_time > $out_end) {
                echo json_encode(['success' => false, 'message' => "CHECK-OUT CLOSED. ALLOWED: " . date("h:i A", strtotime($out_start)) . " TO " . date("h:i A", strtotime($out_end))]);
                exit();
            }
        }
    } 
    else if ($mode === 'timetable') {
        if (empty($batch_id)) {
            echo json_encode(['success' => false, 'message' => 'NO BATCH ASSIGNED. CANNOT VERIFY SCHEDULE.']);
            exit();
        }

        $class_sql = "SELECT id FROM timetable_schedule WHERE batch_id = ? AND day_of_week = ? AND start_time <= ? AND end_time >= ? LIMIT 1";
        $class_stmt = $dbh->prepare($class_sql);
        $class_stmt->execute([$batch_id, $current_day, $current_time, $current_time]);
        $active_class = $class_stmt->fetch(PDO::FETCH_OBJ);

        if (!$active_class) {
            echo json_encode(['success' => false, 'message' => 'ACCESS DENIED: NO ACTIVE CLASS RIGHT NOW.']);
            exit();
        }
    }

    // 5. Log Attendance to Database
    $check_stmt = $dbh->prepare("SELECT id FROM student_attendance WHERE student_id = ? AND attendance_date = ?");
    $check_stmt->execute([$student_id, $today]);
    $record = $check_stmt->fetch(PDO::FETCH_OBJ);

    if ($record) {
        // Update existing record
        if ($action === 'in') {
            $dbh->prepare("UPDATE student_attendance SET check_in_time = ?, status = 'present' WHERE id = ?")->execute([$now_full, $record->id]);
            $msg = "CHECK-IN LOG UPDATED";
        } else {
            $dbh->prepare("UPDATE student_attendance SET check_out_time = ? WHERE id = ?")->execute([$now_full, $record->id]);
            $msg = "CHECK-OUT LOGGED";
        }
    } else {
        // Create new record
        if ($action === 'in') {
            $dbh->prepare("INSERT INTO student_attendance (student_id, attendance_date, check_in_time, status) VALUES (?, ?, ?, 'present')")->execute([$student_id, $today, $now_full]);
            $msg = "CHECK-IN SUCCESSFUL";
        } else {
            $dbh->prepare("INSERT INTO student_attendance (student_id, attendance_date, check_out_time, status) VALUES (?, ?, ?, 'absent')")->execute([$student_id, $today, $now_full]);
            $msg = "CHECK-OUT LOGGED (NO CHECK-IN FOUND)";
        }
    }

    // 6. Return Success
    echo json_encode([
        'success' => true,
        'student_name' => $student->FullName ?? 'Verified Student',
        'roll_number' => 'ID: ' . ($student->RollNumber ?? 'Pending'),
        'message' => $msg
    ]);

} catch (PDOException $e) {
    // FIX: This will now print the EXACT SQL Error to your scanner screen!
    echo json_encode([
        'success' => false, 
        'message' => 'DB ERROR: ' . $e->getMessage() 
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'SYSTEM ERROR: ' . $e->getMessage() 
    ]);
}
?>