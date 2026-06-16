<?php
session_start();
include('includes/dbconnection.php');
header('Content-Type: application/json');

if (strlen($_SESSION['admin_id']==0)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

$json_data = file_get_contents('php://input');
$scheduleData = json_decode($json_data, true);

if (is_null($scheduleData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data received.']);
    exit();
}

$dbh->beginTransaction();
try {
    $dbh->exec("TRUNCATE TABLE timetable_schedule");
    $insert_sql = "INSERT INTO timetable_schedule (day_of_week, start_time, end_time, subject_id, batch_id, teacher_id, classroom_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $dbh->prepare($insert_sql);
    
    foreach ($scheduleData as $event) {
        $start_datetime = new DateTime($event['start']);
        $end_datetime = new DateTime($event['end']);
        $day_of_week = $start_datetime->format('l');
        $start_time = $start_datetime->format('H:i:s');
        $end_time = $end_datetime->format('H:i:s');
        
        $subject_id = $event['extendedProps']['subject_id'] ?? 0;
        $batch_id = 1; // Placeholder
        $teacher_id = 1; // Placeholder
        $classroom_id = 1; // Placeholder
        
        $stmt->execute([$day_of_week, $start_time, $end_time, $subject_id, $batch_id, $teacher_id, $classroom_id]);
    }
    $dbh->commit();
    echo json_encode(['success' => true, 'message' => 'Schedule saved successfully!']);
} catch (Exception $e) {
    $dbh->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>