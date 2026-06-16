<?php
// 1. HEADERS (Good practice from your code)
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); 
date_default_timezone_set('Asia/Kolkata');

// 2. DATABASE CONNECTION
$con = mysqli_connect("localhost", "root", "", "lexclassroom");

if (mysqli_connect_errno()) {
    echo json_encode(["status" => "error", "message" => "Database Connection Failed"]);
    exit();
}

// 3. FETCH FROM KIOSK TABLE (Much Faster than sorting the main table)
$query = "SELECT * FROM tbl_kiosk_live WHERE id=1";
$result = mysqli_query($con, $query);

if ($row = mysqli_fetch_assoc($result)) {
    
    // Calculate seconds ago (Useful for frontend logic)
    $scanTime = strtotime($row['ScanTime']);
    $now = time();
    $seconds_ago = $now - $scanTime;

    // Append calculated data
    $row['seconds_ago'] = $seconds_ago;
    $row['status'] = "success";
    
    // Return the data
    echo json_encode($row);

} else {
    // Fallback if table is empty
    echo json_encode(["status" => "empty", "message" => "System initializing..."]);
}
?>