<?php
$con = mysqli_connect("localhost", "root", "", "lexclassroom");

// STRICT CHECK: Only show data if updated in the last 2 seconds
// This ensures the popup disappears immediately when the student steps back
$q = mysqli_query($con, "SELECT StudentName, ScanTime FROM tbl_kiosk_live WHERE ScanTime > (NOW() - INTERVAL 2 SECOND)");

if($row = mysqli_fetch_assoc($q)) {
    echo json_encode(["status" => "found", "name" => $row['StudentName'], "time" => $row['ScanTime']]);
} else {
    echo json_encode(["status" => "waiting"]);
}
?>