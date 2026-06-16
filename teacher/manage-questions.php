<?php
session_start();
include('includes/dbconnection.php');
if (strlen($_SESSION['ocastid']==0)) { header('location:logout.php'); }

$tid = $_SESSION['ocastid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Questions</title>
    <link href="../assets/css/lib/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include_once('includes/sidebar.php');?>
    <?php include_once('includes/header.php');?>

    <div class="content-wrap">
        <div class="main">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header"><h4>My Submitted Questions</h4></div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT q.*, c.CourseName FROM tblquestions q 
                                        JOIN tblcourse c ON q.CourseID = c.ID 
                                        WHERE q.TeacherID = :tid ORDER BY q.ID DESC";
                                $query = $dbh->prepare($sql);
                                $query->execute(['tid'=>$tid]);
                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                
                                if($query->rowCount() > 0) {
                                    foreach($results as $row) {
                                        $status = ($row->IsApproved == 1) 
                                            ? '<span class="badge badge-success">Approved</span>' 
                                            : '<span class="badge badge-warning">Pending</span>';
                                ?>
                                <tr>
                                    <td><?php echo htmlentities($row->QuestionText); ?></td>
                                    <td><?php echo htmlentities($row->CourseName); ?></td>
                                    <td><?php echo $status; ?></td>
                                </tr>
                                <?php }} else { ?>
                                <tr><td colspan="3">No questions submitted yet.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>
    <script src="../assets/js/lib/menubar/sidebar.js"></script>
</body>
</html>