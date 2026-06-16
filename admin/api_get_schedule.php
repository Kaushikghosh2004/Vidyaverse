<?php
session_start();
include('includes/dbconnection.php');
header('Content-Type: application/json');

if (strlen($_SESSION['admin_id']==0)) {
    echo json_encode(['error' => 'Authentication required']);
    exit();
}
try {
    $sql = "SELECT tt.id, tt.start_time, tt.end_time, tt.day_of_week, s.ID as subject_id, s.SubjectFullname as title
            FROM timetable_schedule tt
            JOIN tblsubject s ON tt.subject_id = s.ID";
    $query = $dbh->prepare($sql);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);

    $events = [];
    foreach ($results as $row) {
        $start_date = date('Y-m-d', strtotime($row['day_of_week'] . ' this week'));
        $end_date = date('Y-m-d', strtotime($row['day_of_week'] . ' this week'));
        $events[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'start' => $start_date . 'T' . $row['start_time'],
            'end' => $end_date . 'T' . $row['end_time'],
            'extendedProps' => [ 'subject_id' => $row['subject_id'] ]
        ];
    }
    echo json_encode($events);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>