<?php
session_start();
include('includes/dbconnection.php');
if (strlen($_SESSION['ocasuid']==0)) { header('location:logout.php'); }

$uid = $_SESSION['ocasuid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Exam History</title>
    <link href="../assets/css/lib/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/lib/unix.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include_once('includes/sidebar.php');?>
    <?php include_once('includes/header.php');?>

    <div class="content-wrap">
        <div class="main">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="page-header">
                            <div class="page-title">
                                <h1>Exam History & Results</h1>
                            </div>
                        </div>
                    </div>
                </div>
                
                <section id="main-content">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card alert">
                                <div class="card-header">
                                    <h4>Past Exams</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Exam Title</th>
                                                    <th>Date Taken</th>
                                                    <th>Score</th>
                                                    <th>Total Questions</th>
                                                    <th>Percentage</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $sql = "SELECT e.ExamTitle, e.TotalQuestions, s.Score, s.EndTime, s.Status 
                                                    FROM tblexam_sessions s
                                                    JOIN tblexams e ON s.ExamID = e.ID
                                                    WHERE s.StudentID = :uid AND (s.Status = 'Completed' OR s.Status = 'Terminated')
                                                    ORDER BY s.EndTime DESC";
                                            $query = $dbh->prepare($sql);
                                            $query->execute(['uid' => $uid]);
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                                            if($query->rowCount() > 0) {
                                                foreach($results as $row) {
                                                    $percentage = ($row->TotalQuestions > 0) ? round(($row->Score / $row->TotalQuestions) * 100, 2) : 0;
                                                    
                                                    $statusBadge = ($row->Status == 'Terminated') 
                                                        ? '<span class="badge badge-danger">Disqualified</span>' 
                                                        : '<span class="badge badge-success">Completed</span>';
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlentities($row->ExamTitle); ?></td>
                                                    <td><?php echo date("d-M-Y h:i A", strtotime($row->EndTime)); ?></td>
                                                    <td><?php echo htmlentities($row->Score); ?> / <?php echo htmlentities($row->TotalQuestions); ?></td>
                                                    <td><?php echo htmlentities($row->TotalQuestions); ?></td>
                                                    <td><?php echo $percentage; ?>%</td>
                                                    <td><?php echo $statusBadge; ?></td>
                                                </tr>
                                            <?php 
                                                }
                                            } else {
                                                echo "<tr><td colspan='6' class='text-center'>No past exam records found.</td></tr>";
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php include_once('includes/footer.php');?>
                </section>
            </div>
        </div>
    </div>
    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>
    <script src="../assets/js/lib/menubar/sidebar.js"></script>
</body>
</html>