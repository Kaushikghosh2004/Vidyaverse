<?php
session_start();
include('includes/dbconnection.php');

if(isset($_POST['sid'])) {
    $sid = $_POST['sid'];
    $img = $_POST['image']; // Base64 Image
    $tabSwitches = $_POST['tab_switches'];
    $movementAlert = $_POST['movement_alert']; // 0 or 1

    // 1. Process Image (Save to server)
    $filename = "";
    if($img != "") {
        $img = str_replace('data:image/jpeg;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $filename = 'evidence_' . $sid . '_' . time() . '.jpg';
        file_put_contents('../user/' . $filename, $data);
    }

    // 2. Update Database
    // Note: You must ensure your database table 'tblexam_sessions' has columns: 
    // LastSnapshot, TabSwitchCount, IsMoving (INT)
    
    $sql = "UPDATE tblexam_sessions SET 
            LastSnapshot = :img, 
            TabSwitchCount = :tabs,
            MovementWarnings = MovementWarnings + :move
            WHERE ID = :sid";
            
    $stmt = $dbh->prepare($sql);
    $stmt->execute([
        ':img' => $filename,
        ':tabs' => $tabSwitches,
        ':move' => $movementAlert,
        ':sid' => $sid
    ]);
}
?>