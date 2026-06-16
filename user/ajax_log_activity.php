<?php
include('includes/dbconnection.php');

if(isset($_POST['session_id'])) {
    $sid = $_POST['session_id'];
    
    // Increment the TabSwitchCount
    $sql = "UPDATE tblexam_sessions SET TabSwitchCount = TabSwitchCount + 1 WHERE ID = :sid";
    $dbh->prepare($sql)->execute(['sid'=>$sid]);
    
    echo "Logged";
}
?>