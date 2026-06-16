<?php
session_start();
include('includes/dbconnection.php');

if (empty($_SESSION['ocasuid'])) { header('location:logout.php'); exit; }

$sid = intval($_GET['sid']);

// Fetch Result Details
$sql = "SELECT s.Score, s.Status, e.ExamTitle, e.TotalMarks 
        FROM tblexam_sessions s
        JOIN tblexams e ON s.ExamID = e.ID
        WHERE s.ID = :sid";
$stmt = $dbh->prepare($sql);
$stmt->execute([':sid' => $sid]);
$res = $stmt->fetch(PDO::FETCH_OBJ);

if(!$res) { echo "Invalid Session"; exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Submitted</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0f172a; color: #fff; font-family: 'Segoe UI', sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; }
        .result-card { background: #1e293b; padding: 40px; border-radius: 16px; text-align: center; max-width: 500px; width: 100%; border: 1px solid #334155; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        .score-circle { width: 120px; height: 120px; border-radius: 50%; border: 8px solid #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; margin: 0 auto 20px; }
        .status-pending { color: #f59e0b; border-color: #f59e0b; }
        .status-done { color: #10b981; border-color: #10b981; }
        .btn-home { background: #3b82f6; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>

    <div class="result-card">
        <h2 class="mb-4">Exam Submitted Successfully!</h2>
        <p class="text-muted mb-4"><?php echo htmlentities($res->ExamTitle); ?></p>

        <?php if($res->Status == 'Completed') { ?>
            <div class="score-circle status-done">
                <?php echo $res->Score; ?>
            </div>
            <h4>Final Score</h4>
            <p style="color:#94a3b8;">Out of <?php echo $res->TotalMarks; ?></p>
        
        <?php } else { ?>
            <div class="score-circle status-pending">
                <i class="ti-time"></i> ?
            </div>
            <h4 style="color:#f59e0b;">Grading Pending</h4>
            <p style="color:#cbd5e1; font-size:14px; margin-top:15px; line-height:1.6;">
                Your MCQ score is <strong><?php echo $res->Score; ?></strong>.<br>
                However, your Theory answers have been submitted for <strong>AI & Manual Review</strong>. 
                Your final score will be updated once grading is complete.
            </p>
        <?php } ?>

        <a href="dashboard.php" class="btn-home">Return to Dashboard</a>
    </div>

</body>
</html>