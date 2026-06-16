<?php
include('includes/dbconnection.php');

if(!empty($_GET['cid'])) {
    $cid = $_GET['cid'];

    // If "ALL" is selected, fetch every subject
    if($cid == "ALL") {
        $sql = "SELECT * FROM tblsubject ORDER BY SubjectFullname ASC";
        $query = $dbh->prepare($sql);
    } 
    // Otherwise, fetch subjects only for that specific CourseID
    else {
        // IMPORTANT: Ensure your tblsubject table has a 'CourseID' column
        $sql = "SELECT * FROM tblsubject WHERE CourseID=:cid ORDER BY SubjectFullname ASC";
        $query = $dbh->prepare($sql);
        $query->bindParam(':cid', $cid);
    }

    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);

    // Generate the <option> list
    if($query->rowCount() > 0) {
        echo '<option value="" style="background:#1e293b; color:#fff;">Select Subject...</option>';
        foreach($results as $row) {
            echo '<option style="background:#1e293b; color:#fff;" value="'.$row->ID.'">'.$row->SubjectFullname.' ('.$row->SubjectCode.')</option>';
        }
    } else {
        echo '<option value="">No subjects found for this course</option>';
    }
}
?>