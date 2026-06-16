<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) { header('location:logout.php'); exit; }

// --- FIX 1: CHECK IF SID EXISTS ---
if (!isset($_GET['sid']) || empty($_GET['sid'])) {
    // If no ID provided, go back to the queue
    header('location:grading-queue.php');
    exit;
}
$sid = intval($_GET['sid']);

// --- HANDLE SUBMISSION ---
if(isset($_POST['finalize_grades'])) {
    
    $theory_score = 0;
    $mcq_score = floatval($_POST['mcq_score']); 
    
    // Loop through submitted marks
    if(isset($_POST['marks'])) {
        foreach($_POST['marks'] as $ans_id => $mark) {
            $mark = floatval($mark);
            $theory_score += $mark;
            
            // Update individual answer record
            $upd = $dbh->prepare("UPDATE tblexam_answers SET MarksObtained=:m, IsCorrect=1 WHERE ID=:aid");
            $upd->execute([':m'=>$mark, ':aid'=>$ans_id]);
        }
    }

    $final_total = $mcq_score + $theory_score;

    // Update Session Status to COMPLETED
    $stmt = $dbh->prepare("UPDATE tblexam_sessions SET Score=:score, Status='Completed' WHERE ID=:sid");
    $stmt->execute([':score'=>$final_total, ':sid'=>$sid]);

    echo "<script>alert('Grading Complete! Final Score: $final_total'); window.location.href='grading-queue.php';</script>";
}

// --- FETCH DATA ---
try {
    // 1. Session Info
    $sessSql = "SELECT u.FullName, e.ExamTitle, s.Score as CurrentScore 
                FROM tblexam_sessions s 
                JOIN tbluser u ON s.StudentID=u.ID 
                JOIN tblexams e ON s.ExamID=e.ID 
                WHERE s.ID = :sid";
    $sessStmt = $dbh->prepare($sessSql);
    $sessStmt->execute([':sid' => $sid]);
    $sess = $sessStmt->fetch(PDO::FETCH_OBJ);

    if(!$sess) {
        die("Error: Session not found or invalid ID.");
    }

    // 2. Fetch Theory Answers 
    // Uses 'StudentAnswer' column. ensure Step 1 SQL is run.
    $ansSql = "SELECT a.ID, a.StudentAnswer, q.QuestionText, q.Answer as ModelAnswer, q.Marks as MaxMarks 
               FROM tblexam_answers a 
               JOIN tblquestions q ON a.QuestionID = q.ID 
               WHERE a.SessionID = :sid AND (a.IsCorrect IS NULL OR a.IsCorrect = 0)";
    
    $ansStmt = $dbh->prepare($ansSql);
    $ansStmt->execute([':sid' => $sid]);
    $answers = $ansStmt->fetchAll(PDO::FETCH_OBJ);

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grade Paper</title>
    <style>
        body { background-color: #0f172a; color: #e2e8f0; font-family: sans-serif; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        
        .header-card { background: #1e293b; padding: 20px; border-radius: 10px; margin-bottom: 30px; border-left: 5px solid #3b82f6; }
        
        .q-box { background: #1e293b; padding: 25px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #334155; }
        .q-text { font-weight: bold; color: #fff; margin-bottom: 10px; display: block; }
        
        .student-ans { background: #0f172a; padding: 15px; border-radius: 6px; border: 1px dashed #475569; margin-bottom: 15px; color: #cbd5e1; }
        .model-ans { font-size: 13px; color: #10b981; margin-bottom: 15px; }
        
        .mark-input { 
            background: #0f172a; border: 1px solid #3b82f6; color: white; padding: 8px; width: 80px; 
            border-radius: 5px; font-weight: bold; text-align: center;
        }
        
        .btn-submit { background: #10b981; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; float: right; }
        .btn-submit:hover { background: #059669; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-card">
        <h2 style="margin:0;"><?php echo htmlentities($sess->FullName); ?></h2>
        <p style="margin:5px 0; color:#94a3b8;"><?php echo htmlentities($sess->ExamTitle); ?></p>
        <p style="color:#3b82f6;"><strong>Auto-Graded MCQ Score: <?php echo htmlentities($sess->CurrentScore); ?></strong></p>
    </div>

    <form method="POST">
        <input type="hidden" name="mcq_score" value="<?php echo $sess->CurrentScore; ?>">

        <?php if(count($answers) > 0) { 
            foreach($answers as $row) { ?>
            
            <div class="q-box">
                <span class="q-text">Q: <?php echo htmlentities($row->QuestionText); ?></span>
                
                <div class="student-ans">
                    <strong>Student Answer:</strong><br>
                    <?php echo nl2br(htmlentities($row->StudentAnswer)); ?>
                </div>

                <div class="model-ans">
                    <strong>Reference Answer:</strong> <?php echo htmlentities($row->ModelAnswer); ?>
                </div>

                <div style="display:flex; justify-content:flex-end; align-items:center; gap:10px;">
                    <label>Marks (Max: <?php echo $row->MaxMarks; ?>):</label>
                    <input type="number" step="0.5" min="0" max="<?php echo $row->MaxMarks; ?>" 
                           name="marks[<?php echo $row->ID; ?>]" class="mark-input" required>
                </div>
            </div>

        <?php } ?>
        
        <button type="submit" name="finalize_grades" class="btn-submit">Finalize & Publish Results</button>
        
        <?php } else { ?>
            <div style="text-align:center; padding:50px; background:#1e293b; border-radius:10px;">
                <p>No pending theory questions found for this session.</p>
                <button type="submit" name="finalize_grades" class="btn-submit">Confirm Completion</button>
            </div>
        <?php } ?>
    </form>
</div>

</body>
</html>