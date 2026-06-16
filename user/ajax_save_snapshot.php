<?php
include('includes/dbconnection.php');

if(isset($_POST['imgBase64']) && isset($_POST['session_id'])) {
    $session_id = $_POST['session_id'];
    $img = $_POST['imgBase64'];

    // 1. Check if exam is already terminated
    $sql_status = "SELECT Status FROM tblexam_sessions WHERE ID=:sid";
    $query = $dbh->prepare($sql_status);
    $query->execute(['sid'=>$session_id]);
    $status = $query->fetchColumn();

    if($status == 'Terminated') {
        echo 'TERMINATED';
        exit;
    }

    // 2. Process and Save Image
    $img = str_replace('data:image/jpeg;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    
    // Generate unique filename
    $filename = "snapshots/sess_" . $session_id . "_" . time() . ".jpg";

    // Ensure folder exists
    if (!file_exists('snapshots')) { 
        mkdir('snapshots', 0777, true); 
    }

    // Save file
    file_put_contents($filename, $data);

    // 3. Update Database with latest image path
    $sql = "UPDATE tblexam_sessions SET LastSnapshot = :path WHERE ID = :sid";
    $dbh->prepare($sql)->execute(['path'=>$filename, 'sid'=>$session_id]);

    echo "OK";
}
?>