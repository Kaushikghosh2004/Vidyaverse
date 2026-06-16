<?php
session_start();
include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['ocastid'])) { header('location:logout.php'); exit; }

// --- 1. UNIVERSAL ID DETECTION (The Fix) ---
$upload_id = 0;

if (isset($_GET['id'])) {
    $upload_id = intval($_GET['id']);
} elseif (isset($_GET['upload_id'])) {
    $upload_id = intval($_GET['upload_id']);
} elseif (isset($_GET['uid'])) {
    $upload_id = intval($_GET['uid']);
} elseif (isset($_GET['sid'])) {
    $upload_id = intval($_GET['sid']);
}

// If still 0, THEN redirect
if ($upload_id == 0) {
    echo "<script>alert('Error: No Assignment ID found. Please try again.'); window.location.href='grading-queue.php';</script>";
    exit;
}

// --- 2. SUBMIT MARKS ---
if(isset($_POST['submit_marks'])) {
    $marks = $_POST['marks'];
    $remarks = $_POST['remarks'];
    
    $sql = "UPDATE tbluploadass SET Marks=:m, Remarks=:r WHERE ID=:uid";
    $query = $dbh->prepare($sql);
    $query->execute([':m' => $marks, ':r' => $remarks, ':uid' => $upload_id]);
    
    echo "<script>alert('Graded Successfully!'); window.location.href='grading-queue.php';</script>";
}

// --- 3. FETCH DATA ---
$sql = "SELECT u.*, usr.FullName, usr.RollNumber, a.AssignmenttTitle, a.AssigmentMarks 
        FROM tbluploadass u 
        JOIN tbluser usr ON u.UserID = usr.ID 
        JOIN tblassigment a ON u.AssId = a.ID 
        WHERE u.ID = :uid";
$query = $dbh->prepare($sql);
$query->execute([':uid' => $upload_id]);
$data = $query->fetch(PDO::FETCH_OBJ);

if(!$data) {
    echo "<script>alert('Submission not found in database.'); window.location.href='grading-queue.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Grade Submission | VidyaVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #0b1120; color: #f8fafc; font-family: 'Inter', sans-serif; padding: 30px; }
        .container { max-width: 900px; margin: 0 auto; }
        
        .glass-card { 
            background: #1e293b; 
            border: 1px solid #334155; 
            border-radius: 20px; 
            padding: 40px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
        }
        
        h2 { color: #fff; border-bottom: 1px solid #334155; padding-bottom: 15px; margin-top: 0; }
        p { color: #94a3b8; margin: 5px 0; }
        strong { color: #fff; }

        .file-box { 
            background: rgba(59, 130, 246, 0.1); 
            border: 2px dashed #3b82f6; 
            padding: 20px; 
            border-radius: 12px; 
            margin: 30px 0; 
            text-align: center; 
        }
        
        .file-link { 
            display: inline-block;
            background: #3b82f6; color: white; 
            text-decoration: none; font-weight: 600; 
            padding: 10px 20px; border-radius: 6px;
            margin-top: 10px;
        }
        .file-link:hover { background: #2563eb; }

        .form-group { margin-bottom: 20px; }
        .form-label { display: block; color: #cbd5e1; margin-bottom: 8px; font-weight: 600; }
        
        .form-control { 
            width: 100%; background: #0f172a; 
            border: 1px solid #334155; 
            color: #fff; padding: 15px; 
            border-radius: 8px; font-size: 16px;
        }
        
        .btn-save { 
            background: #10b981; color: white; width: 100%; 
            padding: 15px; border: none; border-radius: 8px; 
            font-weight: 700; font-size: 18px; cursor: pointer; 
            transition: 0.3s;
        }
        .btn-save:hover { background: #059669; }
        
        .back-btn { color: #94a3b8; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        .back-btn:hover { color: #fff; }
    </style>
</head>
<body>

    <div class="container">
        <a href="grading-queue.php" class="back-btn">← Back to Queue</a>

        <div class="glass-card">
            <h2>Grade Submission</h2>
            <p>Student: <strong><?php echo htmlentities($data->FullName); ?></strong> (<?php echo htmlentities($data->RollNumber); ?>)</p>
            <p>Assignment: <strong><?php echo htmlentities($data->AssignmenttTitle); ?></strong></p>
            <p>Max Marks: <strong><?php echo htmlentities($data->AssigmentMarks); ?></strong></p>

            <div class="file-box">
                <i class="fas fa-file-pdf fa-3x" style="color:#60a5fa; margin-bottom:10px;"></i>
                <div style="color:#e2e8f0; margin-bottom:10px;">
                    File: <?php echo htmlentities($data->AnswerFile); ?>
                </div>
                
                <a href="../user/assignanswer/<?php echo htmlentities($data->AnswerFile); ?>" target="_blank" class="file-link">
                    <i class="fas fa-download"></i> View / Download File
                </a>
            </div>

            <?php if(!empty($data->AssDes)) { ?>
            <div style="background:#0f172a; padding:15px; border-radius:8px; border:1px solid #334155; margin-bottom:20px;">
                <label style="color:#64748b; font-size:12px; font-weight:bold; text-transform:uppercase;">Student Notes:</label>
                <div style="color:#e2e8f0; margin-top:5px;"><?php echo htmlentities($data->AssDes); ?></div>
            </div>
            <?php } ?>

            <form method="post">
                <div class="form-group">
                    <label class="form-label">Marks Obtained</label>
                    <input type="number" name="marks" class="form-control" max="<?php echo htmlentities($data->AssigmentMarks); ?>" value="<?php echo htmlentities($data->Marks); ?>" placeholder="Enter marks..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">Teacher Remarks</label>
                    <textarea name="remarks" class="form-control" rows="4" placeholder="Write feedback here..."><?php echo htmlentities($data->Remarks); ?></textarea>
                </div>
                <button type="submit" name="submit_marks" class="btn-save">
                    <i class="fas fa-check-circle"></i> Save Grade
                </button>
            </form>
        </div>
    </div>

</body>
</html>