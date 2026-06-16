<?php
session_start();
error_reporting(0); // Hide HTML errors for strict JSON output
header('Content-Type: application/json');

include('includes/dbconnection.php');
date_default_timezone_set('Asia/Kolkata');

// --- 0. SELF-HEALING DATABASE ---
// Creates the dedicated classroom attendance table if it doesn't exist
try {
    $dbh->query("CREATE TABLE IF NOT EXISTS `class_attendance` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `student_id` int(11) NOT NULL,
      `timetable_id` int(11) NOT NULL,
      `attendance_date` date NOT NULL,
      `scan_time` time NOT NULL,
      `status` varchar(20) DEFAULT 'present',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch(Exception $e) {}

// Ensure this is a POST request
if (!isset($_POST['qr_id'])) {
    echo json_encode(['success' => false, 'message' => 'INVALID SIGNAL (No Data)']);
    exit();
}

$qr_id = trim($_POST['qr_id']);
$today = date('Y-m-d');
$current_time = date('H:i');
$current_day = date('l'); // e.g., 'Monday'
$now_full = date('H:i:s');

try {
    // --- 1. FIND THE STUDENT ---
    $stu_sql = "SELECT ID, FullName, RollNumber, batch_id FROM tbluser WHERE qr_code_identifier = :qr";
    $stu_stmt = $dbh->prepare($stu_sql);
    $stu_stmt->execute([':qr' => $qr_id]);
    $student = $stu_stmt->fetch(PDO::FETCH_OBJ);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'INVALID QR: STUDENT NOT FOUND']);
        exit();
    }
    
    $student_id = $student->ID;
    $batch_id = $student->batch_id;

    // --- 2. CAMPUS ENTRY CHECK (THE MAIN GATE) ---
    // Has the student scanned at the Admin Auto Check-In Node today?
    $gate_stmt = $dbh->prepare("SELECT id FROM student_attendance WHERE student_id = ? AND attendance_date = ? AND check_in_time IS NOT NULL");
    $gate_stmt->execute([$student_id, $today]);
    if (!$gate_stmt->fetch(PDO::FETCH_OBJ)) {
        // KICK THEM OUT: They haven't entered the campus!
        echo json_encode([
            'success' => false, 
            'message' => "ACCESS DENIED: MAIN CAMPUS ENTRY REQUIRED FIRST."
        ]);
        exit();
    }

    // --- 3. TIMETABLE SYNC ---
    // Does this student have a class right now?
    if (empty($batch_id)) {
        echo json_encode(['success' => false, 'message' => 'STUDENT HAS NO BATCH. CANNOT VERIFY CLASS.']);
        exit();
    }

    $class_sql = "SELECT id, start_time, end_time FROM timetable_schedule WHERE batch_id = ? AND day_of_week = ? AND ? BETWEEN start_time AND end_time LIMIT 1";
    $class_stmt = $dbh->prepare($class_sql);
    $class_stmt->execute([$batch_id, $current_day, $current_time]);
    $active_class = $class_stmt->fetch(PDO::FETCH_OBJ);

    if (!$active_class) {
        echo json_encode(['success' => false, 'message' => 'NO ACTIVE CLASS SCHEDULED RIGHT NOW.']);
        exit();
    }

    $timetable_id = $active_class->id;

    // --- 4. ANTI-PROXY / DUPLICATE CLASS SCAN CHECK ---
    // Did they already scan for this specific class period?
    $check_stmt = $dbh->prepare("SELECT id, scan_time FROM class_attendance WHERE student_id = ? AND timetable_id = ? AND attendance_date = ?");
    $check_stmt->execute([$student_id, $timetable_id, $today]);
    $record = $check_stmt->fetch(PDO::FETCH_OBJ);

    if ($record) {
        $time_scanned = date("h:i A", strtotime($record->scan_time));
        echo json_encode([
            'success' => false, 
            'message' => "PROXY ALERT: ALREADY SCANNED FOR THIS CLASS AT " . $time_scanned
        ]);
        exit();
    } 

    // --- 5. LOG CLASS ATTENDANCE ---
    $ins = $dbh->prepare("INSERT INTO class_attendance (student_id, timetable_id, attendance_date, scan_time, status) VALUES (?, ?, ?, ?, 'present')");
    $ins->execute([$student_id, $timetable_id, $today, $now_full]);

    // Return Success
    echo json_encode([
        'success' => true,
        'student_name' => $student->FullName,
        'roll_number' => 'ROLL NO: ' . ($student->RollNumber ?? 'Verified'),
        'message' => 'CLASS ATTENDANCE LOGGED'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB ERROR: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'SYSTEM ERROR.']);
}
?>