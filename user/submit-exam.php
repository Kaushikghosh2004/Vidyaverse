<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (strlen($_SESSION['ocasuid'] ?? '') == 0) {
    header('location:logout.php');
    exit();
}
$uid = $_SESSION['ocasuid'];

if(isset($_POST['exam_id'])) {
    
    $exam_id = $_POST['exam_id'];
    $session_id = $_POST['session_id'];
    $answers = $_POST['ans'] ?? []; 

    $mcq_score = 0;
    $has_theory = false;

    // 1. PROCESS ANSWERS
    foreach($answers as $qid => $user_ans) {
        $user_ans = trim($user_ans);
        
        // Step A: Get Question Info
        // (We removed 'Marks' because your DB doesn't have it)
        $sql = "SELECT QuestionType, CorrectAnswer FROM tblquestions WHERE ID=:qid";
        $qStmt = $dbh->prepare($sql);
        $qStmt->execute([':qid' => $qid]);
        $qData = $qStmt->fetch(PDO::FETCH_OBJ);
        
        if($qData) {
            $is_correct = 0; 
            $question_weight = 1; // Default 1 mark per question
            $marks_awarded = 0;
            $db_check_status = 0; 

            // LOGIC A: MCQ
            if($qData->QuestionType == 'MCQ') {
                if(strtoupper($user_ans) == strtoupper(trim($qData->CorrectAnswer))) {
                    $marks_awarded = $question_weight;
                    $mcq_score += $marks_awarded;
                    $is_correct = 1;
                    $db_check_status = 1;
                } else {
                    $db_check_status = 0;
                }
            } 
            // LOGIC B: THEORY
            else {
                $has_theory = true;
                $db_check_status = NULL;
            }

            // Step B: Save Answer to Database
            try {
                // TRYING TO INSERT INTO 'IsCorrect'
                $ansSql = "INSERT INTO tblexam_answers (SessionID, QuestionID, StudentAnswer, IsCorrect, MarksObtained) 
                           VALUES (:sid, :qid, :ans, :iscorr, :marks)";
                $dbh->prepare($ansSql)->execute([
                    ':sid' => $session_id,
                    ':qid' => $qid,
                    ':ans' => $user_ans,
                    ':iscorr' => $db_check_status,
                    ':marks' => $marks_awarded
                ]);
            } catch (PDOException $e) {
                // --- AUTO-DIAGNOSTIC FOR tblexam_answers ---
                if ($e->getCode() == '42S22') { // Column not found
                    echo "<div style='background:#000; color:#ff3333; padding:20px; font-family:monospace;'>";
                    echo "<h1>⚠️ DATABASE ERROR: tblexam_answers</h1>";
                    echo "<h3>The script cannot find the column 'IsCorrect'.</h3>";
                    echo "<p>Here are the <b>REAL</b> columns in your 'tblexam_answers' table:</p>";
                    echo "<hr>";
                    
                    // Fetch valid columns for answers table
                    $descQ = $dbh->query("DESCRIBE tblexam_answers");
                    $columns = $descQ->fetchAll(PDO::FETCH_COLUMN);
                    
                    echo "<ul>";
                    foreach($columns as $col) {
                        echo "<li>" . $col . "</li>";
                    }
                    echo "</ul>";
                    echo "<hr>";
                    echo "<h3>HOW TO FIX:</h3>";
                    echo "<p><b>Option 1:</b> If you see a column named 'Status', 'Result', or 'Correct' in the list above, change 'IsCorrect' in line 60 to that name.</p>";
                    echo "<p><b>Option 2:</b> If the column is missing completely, run this SQL in phpMyAdmin:</p>";
                    echo "<code style='background:#333; color:#fff; padding:5px;'>ALTER TABLE tblexam_answers ADD COLUMN IsCorrect INT DEFAULT 0;</code>";
                    echo "</div>";
                    exit(); 
                } else {
                    throw $e;
                }
            }
        }
    }

    // 2. FINALIZE SESSION
    $final_status = ($has_theory) ? 'Pending Review' : 'Completed';

    $updateSql = "UPDATE tblexam_sessions SET 
                  Status = :status, 
                  Score = :score, 
                  EndTime = NOW() 
                  WHERE ID = :sid";
    
    $stmt = $dbh->prepare($updateSql);
    $stmt->execute([
        ':status' => $final_status,
        ':score' => $mcq_score,
        ':sid' => $session_id
    ]);

    // 3. REDIRECT
    header("Location: exam-complete.php?sid=$session_id");
    exit();

} else {
    header('location:dashboard.php');
}
?>