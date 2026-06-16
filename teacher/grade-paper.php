<?php
session_start();
include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['ocastid'])) { header('location:logout.php'); exit; }

$sid = intval($_GET['sid']);

// --- SUBMIT GRADES ---
if(isset($_POST['finalize_grades'])) {
    
    $theory_score = 0;
    $mcq_score = floatval($_POST['mcq_score']); 
    
    // Process Theory Marks
    if(isset($_POST['marks'])) {
        foreach($_POST['marks'] as $ans_id => $mark) {
            $mark = floatval($mark);
            $theory_score += $mark;
            
            // Update individual answer
            $upd = $dbh->prepare("UPDATE tblexam_answers SET MarksObtained=:m, IsCorrect=1 WHERE ID=:aid");
            $upd->execute([':m'=>$mark, ':aid'=>$ans_id]);
        }
    }

    $final_total = $mcq_score + $theory_score;

    // Update Main Session
    $stmt = $dbh->prepare("UPDATE tblexam_sessions SET Score=:score, Status='Completed' WHERE ID=:sid");
    $stmt->execute([':score'=>$final_total, ':sid'=>$sid]);

    echo "<script>alert('Paper Graded Successfully! Student Final Score: $final_total'); window.location.href='grading-queue.php';</script>";
}

// --- DATA FETCHING ---
// 1. Exam & Student Info
$sess = $dbh->query("SELECT u.FullName, e.ExamTitle, s.Score as CurrentScore 
                     FROM tblexam_sessions s 
                     JOIN tbluser u ON s.StudentID=u.ID 
                     JOIN tblexams e ON s.ExamID=e.ID 
                     WHERE s.ID=$sid")->fetch(PDO::FETCH_OBJ);

// 2. Fetch Pending Answers (IsCorrect IS NULL)
$ansSql = "SELECT a.ID, a.StudentAnswer, q.QuestionText, q.Answer as ModelAnswer, q.Marks as MaxMarks 
           FROM tblexam_answers a 
           JOIN tblquestions q ON a.QuestionID = q.ID 
           WHERE a.SessionID = $sid AND a.IsCorrect IS NULL";
$answers = $dbh->query($ansSql)->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grade Paper | <?php echo $sess->FullName; ?></title>
    <style>
        /* Light Theme for Teachers */
        body { background-color: #f8fafc; color: #334155; font-family: 'Segoe UI', sans-serif; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        
        .header-panel { 
            background: #fff; padding: 25px; border-radius: 12px; margin-bottom: 30px; 
            border: 1px solid #e2e8f0; border-left: 5px solid #3b82f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
        }
        
        .question-card { 
            background: #fff; padding: 30px; border-radius: 12px; margin-bottom: 25px; 
            border: 1px solid #e2e8f0; position: relative;
        }
        
        .q-title { font-weight: 700; font-size: 16px; color: #0f172a; margin-bottom: 15px; display: block; }
        
        .ans-box { 
            background: #f1f5f9; padding: 15px; border-radius: 8px; 
            border-left: 3px solid #64748b; margin-bottom: 15px; font-family: 'Courier New', monospace;
        }
        
        .ref-box { 
            background: #ecfdf5; color: #047857; padding: 10px; border-radius: 6px; 
            font-size: 13px; margin-bottom: 20px; border: 1px solid #d1fae5;
        }
        
        .grading-area { 
            display: flex; justify-content: flex-end; align-items: center; gap: 10px; 
            padding-top: 15px; border-top: 1px solid #f1f5f9; 
        }
        .grading-area input { 
            padding: 8px; border: 2px solid #3b82f6; border-radius: 6px; 
            width: 80px; text-align: center; font-weight: bold; font-size: 16px;
        }

        .btn-finish { 
            background: #10b981; color: white; border: none; padding: 15px 40px; 
            border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; 
            float: right; transition: 0.2s; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
        }
        .btn-finish:hover { background: #059669; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="container">
    <div class="header-panel">
        <h2 style="margin:0; color:#1e293b;"><?php echo htmlentities($sess->FullName); ?></h2>
        <p style="margin:5px 0; color:#64748b;"><?php echo htmlentities($sess->ExamTitle); ?></p>
        <div style="margin-top:15px; display:inline-block; background:#eff6ff; color:#1d4ed8; padding:5px 10px; border-radius:6px; font-weight:bold; font-size:14px;">
            MCQ Score (Auto): <?php echo $sess->CurrentScore; ?>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="mcq_score" value="<?php echo $sess->CurrentScore; ?>">

        <?php if(count($answers) > 0) { 
            foreach($answers as $row) { ?>
            
            <div class="question-card">
                <span class="q-title">Q: <?php echo htmlentities($row->QuestionText); ?></span>
                
                <div class="ans-box">
                    <strong>Student's Response:</strong><br>
                    <?php echo nl2br(htmlentities($row->StudentAnswer)); ?>
                </div>

                <div class="ref-box">
                    <strong>Expected Answer:</strong> <?php echo htmlentities($row->ModelAnswer); ?>
                </div>

                <div class="grading-area">
                    <label style="font-weight:600; color:#475569;">Marks Awarded (Max <?php echo $row->MaxMarks; ?>):</label>
                    <input type="number" step="0.5" min="0" max="<?php echo $row->MaxMarks; ?>" 
                           name="marks[<?php echo $row->ID; ?>]" required>
                </div>
            </div>

        <?php } ?>
        
        <div style="overflow:hidden; padding-bottom:50px;">
            <button type="submit" name="finalize_grades" class="btn-finish">Save & Publish Results</button>
        </div>
        
        <?php } else { ?>
            <div style="text-align:center; padding:50px; background:white; border-radius:12px;">
                <h3>No questions require manual grading.</h3>
                <p>This exam consists entirely of auto-graded questions.</p>
                <button type="submit" name="finalize_grades" class="btn-finish">Confirm Completion</button>
            </div>
        <?php } ?>
    </form>
</div>

</body>
</html>