<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

// --- SECURITY CHECK ---
if (empty($_SESSION['ocastid'])) {
    header('location:logout.php');
    exit;
}

$tid = $_SESSION['ocastid'];

// --- FETCH TEACHER DETAILS ---
$sqlT = "SELECT * FROM tblteacher WHERE ID = :tid";
$qT = $dbh->prepare($sqlT);
$qT->bindParam(':tid', $tid);
$qT->execute();
$teacher = $qT->fetch(PDO::FETCH_OBJ);

if(!$teacher) {
    die('<div style="color:white; text-align:center; margin-top:50px;">Error: Teacher profile not found. Please contact Admin.</div>');
}

// --- SUBMIT QUESTION LOGIC ---
if(isset($_POST['submit_q'])) {
    $subject_id = $_POST['subject_id']; 
    $q_type = $_POST['q_type'];
    $q_text = $_POST['q_text'];
    
    // ======================================================
    // FIX: HANDLE NULL VALUES FOR SHORT/LONG ANSWERS
    // ======================================================
    if ($q_type == 'MCQ') {
        // If MCQ, use the actual inputs from the form
        $opt_a = $_POST['opt_a'];
        $opt_b = $_POST['opt_b'];
        $opt_c = $_POST['opt_c'];
        $opt_d = $_POST['opt_d'];
        $correct = $_POST['correct_ans'];
    } else {
        // If Short/Long Answer, insert "N/A" so the Database doesn't crash
        // (Because your DB columns are likely set to NOT NULL)
        $opt_a = "N/A";
        $opt_b = "N/A";
        $opt_c = "N/A";
        $opt_d = "N/A";
        $correct = "Manual Review"; // Placeholder for the correct answer column
    }
    // ======================================================

    $is_approved = 0; 

    try {
        // 1. Get CourseID for the subject
        $cSql = "SELECT CourseID FROM tblsubject WHERE ID=:sid";
        $cq = $dbh->prepare($cSql);
        $cq->bindParam(':sid', $subject_id);
        $cq->execute();
        $courseObj = $cq->fetch(PDO::FETCH_OBJ);
        $course_id = $courseObj ? $courseObj->CourseID : 0;

        // 2. Insert Question
        $sql = "INSERT INTO tblquestions (SubjectID, CourseID, QuestionType, QuestionText, OptionA, OptionB, OptionC, OptionD, CorrectAnswer, TeacherID, IsApproved) 
                VALUES (:sid, :cid, :qtype, :txt, :oa, :ob, :oc, :od, :cor, :tid, :is_app)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $subject_id);
        $query->bindParam(':cid', $course_id);
        $query->bindParam(':qtype', $q_type);
        $query->bindParam(':txt', $q_text);
        $query->bindParam(':oa', $opt_a);
        $query->bindParam(':ob', $opt_b);
        $query->bindParam(':oc', $opt_c);
        $query->bindParam(':od', $opt_d);
        $query->bindParam(':cor', $correct);
        $query->bindParam(':tid', $tid);
        $query->bindParam(':is_app', $is_approved);
        $query->execute();
        
        echo "<script>alert('Question Submitted Successfully for Review!');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Question Maker System</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* GLOBAL RESET */
        * { box-sizing: border-box; }
        body { 
            background-color: #0f172a; color: #f8fafc; 
            font-family: 'Inter', sans-serif; margin: 0; padding-top: 80px; 
        }

        /* SECURE HEADER */
        .secure-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: #1e293b; border-bottom: 2px solid #ef4444;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 40px; z-index: 1000; box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand i { font-size: 22px; color: #ef4444; }
        .brand-text { font-size: 18px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }
        .confidential-badge {
            background: rgba(239, 68, 68, 0.15); color: #ef4444; font-size: 11px; font-weight: 700;
            text-transform: uppercase; padding: 4px 10px; border-radius: 4px;
            border: 1px solid rgba(239, 68, 68, 0.4); margin-left: 10px; letter-spacing: 1px;
        }

        .header-right { display: flex; align-items: center; gap: 20px; }
        .user-profile { text-align: right; font-size: 13px; color: #94a3b8; }
        .user-profile span { display: block; color: #fff; font-weight: 600; font-size: 14px; }
        .btn-logout {
            background: #ef4444; color: white; text-decoration: none; padding: 8px 20px;
            border-radius: 6px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px;
            transition: 0.2s;
        }
        .btn-logout:hover { background: #dc2626; }
        .back-link { color: #94a3b8; text-decoration: none; font-weight: 600; font-size: 14px; }
        .back-link:hover { color: #fff; }

        /* CONTENT CARD */
        .container { display: flex; justify-content: center; padding: 40px 20px; }
        .maker-card {
            background: #1e293b; width: 100%; max-width: 700px; padding: 40px;
            border-radius: 12px; border: 1px solid #334155; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #cbd5e1; margin-bottom: 8px; font-size: 13px; font-weight: 500; }
        
        .form-control { 
            width: 100%; background: #0f172a; border: 1px solid #334155; color: #fff; 
            padding: 12px; border-radius: 8px; font-size: 14px; transition: 0.2s; 
        }
        .form-control:focus { outline: none; border-color: #3b82f6; }
        textarea.form-control { resize: vertical; min-height: 120px; }

        .btn-submit {
            background: #3b82f6; color: white; width: 100%; padding: 14px; border: none;
            border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer; margin-top: 10px;
            transition: 0.2s; text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-submit:hover { background: #2563eb; }

        #mcq_box {
            background: rgba(15, 23, 42, 0.5); padding: 20px; border-radius: 8px;
            border: 1px solid #334155; margin-top: 10px; display: none;
        }
        .option-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
    </style>
</head>
<body>

    <header class="secure-header">
        <div class="brand">
            <i class="ti-lock"></i>
            <span class="brand-text">Question Maker System</span>
            <span class="confidential-badge">Highly Confidential</span>
        </div>
        <div class="header-right">
            <a href="dashboard.php" class="back-link">Dashboard</a>
            <div class="user-profile">Logged in as <span><?php echo htmlentities($teacher->FirstName . " " . $teacher->LastName);?></span></div>
            <a href="logout.php" class="btn-logout"><i class="ti-power-off"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="maker-card">
            <h2 style="margin-top:0; color:#fff;">Submit New Question</h2>
            <p style="color:#94a3b8; font-size:14px; margin-bottom:30px;">
                All questions submitted here are subject to admin approval.
            </p>
            
            <form method="post">
                
                <div class="form-group">
                    <label>Select Subject (From your assigned list)</label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">Choose Subject...</option>
                        <?php
                        $sqlS = "SELECT s.ID, s.SubjectFullname 
                                 FROM tblteacher_subjects ts
                                 JOIN tblsubject s ON ts.SubjectID = s.ID
                                 WHERE ts.TeacherID = :tid";
                        $qS = $dbh->prepare($sqlS);
                        $qS->bindParam(':tid', $tid);
                        $qS->execute();
                        
                        if($qS->rowCount() > 0) {
                            $subjects = $qS->fetchAll(PDO::FETCH_OBJ);
                            foreach($subjects as $sub) {
                                echo "<option value='".$sub->ID."'>".$sub->SubjectFullname."</option>";
                            }
                        } else {
                            echo "<option value='' disabled>No subjects assigned to you yet.</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Question Type</label>
                    <select name="q_type" id="q_type" class="form-control" onchange="toggleOptions()">
                        <option value="MCQ">Multiple Choice (MCQ)</option>
                        <option value="Short">Short Answer (Theory)</option>
                        <option value="Long">Long Answer (Theory)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Question Text</label>
                    <textarea name="q_text" class="form-control" placeholder="Enter the full question text here..." required></textarea>
                </div>

                <div id="mcq_box" style="display:block;"> 
                    <label style="color:#60a5fa; font-weight:600; margin-bottom:15px; display:block;">MCQ Configuration</label>
                    
                    <div class="option-grid">
                        <div><label>Option A</label><input type="text" name="opt_a" class="form-control" placeholder="Answer for A"></div>
                        <div><label>Option B</label><input type="text" name="opt_b" class="form-control" placeholder="Answer for B"></div>
                        <div><label>Option C</label><input type="text" name="opt_c" class="form-control" placeholder="Answer for C"></div>
                        <div><label>Option D</label><input type="text" name="opt_d" class="form-control" placeholder="Answer for D"></div>
                    </div>

                    <div class="form-group">
                        <label>Correct Answer</label>
                        <select name="correct_ans" class="form-control">
                            <option value="">Select Correct Option</option>
                            <option value="A">Option A</option>
                            <option value="B">Option B</option>
                            <option value="C">Option C</option>
                            <option value="D">Option D</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="submit_q" class="btn-submit">Submit to Question Bank</button>
            </form>
        </div>
    </div>

    <script>
        function toggleOptions() {
            var type = document.getElementById('q_type').value;
            var box = document.getElementById('mcq_box');
            
            if(type === 'MCQ') {
                box.style.display = 'block';
                // Add required attributes
                document.getElementsByName('opt_a')[0].required = true;
                document.getElementsByName('opt_b')[0].required = true;
                document.getElementsByName('correct_ans')[0].required = true;
            } else {
                box.style.display = 'none';
                // Remove required attributes
                document.getElementsByName('opt_a')[0].required = false;
                document.getElementsByName('opt_b')[0].required = false;
                document.getElementsByName('correct_ans')[0].required = false;
            }
        }
        // Run on load
        toggleOptions();
    </script>

</body>
</html>