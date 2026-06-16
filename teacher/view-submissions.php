<?php
session_start();
include('includes/dbconnection.php');
if (empty($_SESSION['ocastid'])) { header('location:logout.php'); exit; }

$assign_id = intval($_GET['assign_id']);

// Fetch Assignment Details
$sqlA = "SELECT AssignmenttTitle, AssigmentMarks FROM tblassigment WHERE ID=:aid";
$qA = $dbh->prepare($sqlA);
$qA->execute([':aid' => $assign_id]);
$assignInfo = $qA->fetch(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Submissions List | VidyaVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: 'Inter', sans-serif; }
        .container { padding: 40px 20px; max-width: 1200px; margin: 0 auto; }
        .glass-card { background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; padding: 30px; }
        
        .list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .list-title { font-size: 20px; font-weight: 700; color: #fff; }
        .back-btn { color: #94a3b8; text-decoration: none; font-size: 14px; }
        .back-btn:hover { color: #fff; }

        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 15px; color: #94a3b8; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; vertical-align: middle; }
        
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .status-checked { background: rgba(16, 185, 129, 0.15); color: #34d399; }

        .btn-grade { background: #3b82f6; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; }
        .btn-grade:hover { background: #2563eb; }
    </style>
</head>
<body>
    <?php include_once('includes/header.php');?>
    <div class="container">
        <div class="glass-card">
            <div class="list-header">
                <div>
                    <div class="list-title"><?php echo htmlentities($assignInfo->AssignmenttTitle); ?></div>
                    <small style="color:#94a3b8;">Max Marks: <?php echo htmlentities($assignInfo->AssigmentMarks); ?></small>
                </div>
                <a href="student-uploaded-ass.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Folders</a>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Roll No</th>
                        <th>Submitted On</th>
                        <th>Marks</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT u.ID as UploadID, u.SubmitDate, u.Marks, usr.FullName, usr.RollNumber 
                            FROM tbluploadass u 
                            JOIN tbluser usr ON u.UserID = usr.ID 
                            WHERE u.AssId = :aid ORDER BY u.SubmitDate ASC";
                    $query = $dbh->prepare($sql);
                    $query->execute([':aid' => $assign_id]);
                    $subs = $query->fetchAll(PDO::FETCH_OBJ);

                    if($query->rowCount() > 0) {
                        foreach($subs as $row) {
                            $isChecked = ($row->Marks != "" && $row->Marks !== NULL);
                    ?>
                    <tr>
                        <td style="color:#fff; font-weight:600;"><?php echo htmlentities($row->FullName); ?></td>
                        <td style="color:#94a3b8;"><?php echo htmlentities($row->RollNumber); ?></td>
                        <td><?php echo date("d M, h:i A", strtotime($row->SubmitDate)); ?></td>
                        <td><?php echo $isChecked ? htmlentities($row->Marks) : '-'; ?></td>
                        <td>
                            <?php if($isChecked) { ?>
                                <span class="status-badge status-checked">Checked</span>
                            <?php } else { ?>
                                <span class="status-badge status-pending">Unchecked</span>
                            <?php } ?>
                        </td>
                        <td>
                            <a href="grade-submission.php?upload_id=<?php echo $row->UploadID; ?>" class="btn-grade">
                                <?php echo $isChecked ? '<i class="fas fa-eye"></i> View' : '<i class="fas fa-pencil-alt"></i> Grade'; ?>
                            </a>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo '<tr><td colspan="6" style="text-align:center; padding:30px; color:#ef4444;">No students have submitted this assignment yet.</td></tr>';
                    } 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include_once('includes/footer.php');?>
</body>
</html>