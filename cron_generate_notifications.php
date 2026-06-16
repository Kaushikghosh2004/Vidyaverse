<?php
// cron_generate_notifications.php
// Purpose: Checks the timetable and generates alerts 1 hour before class.
// Usage: Run via Cron Job (server) OR include in dashboard header.

// 1. Set Timezone (Critical for accurate alerts)
date_default_timezone_set('Asia/Kolkata'); 

// 2. Database Connection
// NOTE: Adjust the path if this file is in a different folder (e.g., 'admin/includes/...')
if (file_exists('includes/dbconnection.php')) {
    include('includes/dbconnection.php');
} elseif (file_exists('../includes/dbconnection.php')) {
    include('../includes/dbconnection.php'); // If file is inside a subfolder
} else {
    // Fallback or Error
    die("Database connection file not found.");
}

// --- CONFIGURATION ---
$notification_window_minutes = 60; // Notify 1 hour before class starts
$duplicate_check_window = 2;       // Don't send same alert if sent in last 2 hours

// --- TIME CALCULATION ---
$current_day = date('l'); // e.g., "Monday"
$time_now = date('H:i:00');
$time_future = date('H:i:00', strtotime("+$notification_window_minutes minutes"));

try {
    // 3. FETCH UPCOMING CLASSES (Optimized Single Query)
    // We join all necessary tables (Subject, Room, Batch, Teacher) to avoid loops
    $sql = "SELECT ts.id as schedule_id, ts.start_time, ts.teacher_id, ts.batch_id,
                   s.SubjectFullname, c.room_name_or_number, b.batch_name,
                   t.FirstName, t.LastName
            FROM timetable_schedule ts
            JOIN tblsubject s ON ts.subject_id = s.ID
            JOIN classrooms c ON ts.classroom_id = c.id
            JOIN batches b ON ts.batch_id = b.id
            JOIN tblteacher t ON ts.teacher_id = t.ID
            WHERE ts.day_of_week = :day 
            AND ts.start_time BETWEEN :now AND :future";

    $query = $dbh->prepare($sql);
    $query->execute([
        ':day'    => $current_day,
        ':now'    => $time_now,
        ':future' => $time_future
    ]);
    
    $upcoming_classes = $query->fetchAll(PDO::FETCH_OBJ);
    $alerts_sent = 0;

    foreach ($upcoming_classes as $class) {
        $start_time_formatted = date('h:i A', strtotime($class->start_time));
        
        // --- A. TEACHER ALERT ---
        $msg_teacher = "Reminder: Your <strong>{$class->SubjectFullname}</strong> class for batch <strong>{$class->batch_name}</strong> in Room <strong>{$class->room_name_or_number}</strong> starts at {$start_time_formatted}.";

        // Check if we already alerted this teacher recently
        if (!hasNotificationRecently($dbh, $class->teacher_id, $msg_teacher, $duplicate_check_window)) {
            createNotification($dbh, $class->teacher_id, 'teacher', $msg_teacher);
            $alerts_sent++;
        }

        // --- B. STUDENT ALERT (Batch Wise) ---
        // Fetch all students belonging to this batch
        $stu_sql = "SELECT ID FROM tbluser WHERE batch_id = :bid"; 
        $stu_query = $dbh->prepare($stu_sql);
        $stu_query->execute([':bid' => $class->batch_id]);
        $students = $stu_query->fetchAll(PDO::FETCH_OBJ);

        $msg_student = "Upcoming: <strong>{$class->SubjectFullname}</strong> with Prof. {$class->FirstName} {$class->LastName} in Room <strong>{$class->room_name_or_number}</strong> at {$start_time_formatted}.";

        foreach ($students as $student) {
            // Check if we already alerted this student recently
            if (!hasNotificationRecently($dbh, $student->ID, $msg_student, $duplicate_check_window)) {
                createNotification($dbh, $student->ID, 'student', $msg_student);
            }
        }
    }

    // Optional: Output status for debugging (visible if run manually)
    // echo "Status: Checked at " . date('H:i:s') . ". Alerts generated: " . $alerts_sent;

} catch (Exception $e) {
    // Log error silently or echo
    // echo "Error: " . $e->getMessage();
}

// --- HELPER FUNCTIONS ---

/**
 * Check if a specific notification was already sent to a user in the last X hours
 */
function hasNotificationRecently($dbh, $user_id, $message, $hours) {
    $sql = "SELECT id FROM notifications 
            WHERE user_id = :uid 
            AND message = :msg 
            AND created_at > DATE_SUB(NOW(), INTERVAL :hrs HOUR)";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':uid' => $user_id, ':msg' => $message, ':hrs' => $hours]);
    return $stmt->rowCount() > 0;
}

/**
 * Insert a new notification
 */
function createNotification($dbh, $user_id, $type, $message) {
    $sql = "INSERT INTO notifications (user_id, user_type, message, is_read, created_at) 
            VALUES (:uid, :type, :msg, 0, NOW())";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':uid' => $user_id, ':type' => $type, ':msg' => $message]);
}
?>