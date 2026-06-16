<?php
session_start();
include('includes/dbconnection.php');

if (empty($_SESSION['admin_id'])) { header('location:logout.php'); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grading Queue | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: sans-serif; padding: 30px; }
        .card { background: #1e293b; padding: 25px; border-radius: 12px; border: 1px solid #334155; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; color: #94a3b8; border-bottom: 1px solid #334155; }
        td { padding: 15px; border-bottom: 1px solid #334155; }
        .btn-grade { background: #f59e0b; color: #000; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .btn-grade:hover { background: #d97706; color: white; }
    </style>
</head>
<body>

    <h2 style="border-bottom: 1px solid #334155; padding-bottom: 15px;">
        <i class="ti-pencil-alt"></i> Pending Grading Queue
    </h2>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Exam Title</th>
                    <th>Submission Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch sessions marked as 'Pending Review'
                $sql = "SELECT s.ID as SessID, s.EndTime, u.FullName, e.ExamTitle 
                        FROM tblexam_sessions s
                        JOIN tbluser u ON s.StudentID = u.ID
                        JOIN tblexams e ON s.ExamID = e.ID
                        WHERE s.Status = 'Pending Review'
                        ORDER BY s.EndTime ASC";
                
                $query = $dbh->prepare($sql);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);

                if($query->rowCount() > 0) {
                    foreach($results as $row) {
                        echo "<tr>
                                <td>{$row->FullName}</td>
                                <td>{$row->ExamTitle}</td>
                                <td>".date("d M Y, h:i A", strtotime($row->EndTime))."</td>
                                <td style='color:#f59e0b;'>Needs Grading</td>
                                <td><a href='grade-paper.php?sid={$row->SessID}' class='btn-grade'>Grade Now</a></td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#64748b;'>All caught up! No pending papers.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>