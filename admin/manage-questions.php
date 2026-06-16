<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

// --- SECURITY CHECK ---
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// --- 1. ADD QUESTION LOGIC (ADMIN) ---
if(isset($_POST['add_q'])) {
    $subject_id = $_POST['subject_id'];
    $q_type = $_POST['q_type'];
    $q_text = $_POST['q_text'];
    
    // ======================================================
    // FIX: PREVENT NULL VALUES FOR LONG/SHORT ANSWERS
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
        $opt_a = "N/A";
        $opt_b = "N/A";
        $opt_c = "N/A";
        $opt_d = "N/A";
        $correct = "Subjective"; // Placeholder for correct answer column
    }
    // ======================================================

    // Default values for Admin
    $teacher_id = 0; // 0 denotes Admin
    $is_approved = 1; // Admin questions are always approved automatically

    try {
        // Step A: Fetch CourseID based on SubjectID
        $courseSql = "SELECT CourseID FROM tblsubject WHERE ID = :sid";
        $cQuery = $dbh->prepare($courseSql);
        $cQuery->bindParam(':sid', $subject_id);
        $cQuery->execute();
        $courseRow = $cQuery->fetch(PDO::FETCH_OBJ);
        $course_id = $courseRow ? $courseRow->CourseID : 0;

        // Step B: Insert Question
        $sql = "INSERT INTO tblquestions 
                (SubjectID, CourseID, QuestionType, QuestionText, OptionA, OptionB, OptionC, OptionD, CorrectAnswer, TeacherID, IsApproved) 
                VALUES 
                (:sid, :cid, :qtype, :txt, :oa, :ob, :oc, :od, :cor, :tid, :is_app)";
        
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
        $query->bindParam(':tid', $teacher_id);
        $query->bindParam(':is_app', $is_approved);
        
        $query->execute();
        
        // Redirect to prevent form resubmission
        echo "<script>alert('Question added successfully!'); window.location.href='manage-questions.php';</script>";
        exit;

    } catch (Exception $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// --- 2. DELETE QUESTION LOGIC ---
if(isset($_GET['delid'])) {
    $id = intval($_GET['delid']);
    try {
        $sql = "DELETE FROM tblquestions WHERE ID=:id";
        $q = $dbh->prepare($sql);
        $q->bindParam(':id', $id, PDO::PARAM_INT);
        $q->execute();
        echo "<script>alert('Question deleted.'); window.location.href='manage-questions.php';</script>";
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Error deleting question.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Questions | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    
    <style>
        /* GLOBAL STYLES */
        * { box-sizing: border-box; }
        body { background-color: #0f172a; color: #f8fafc; font-family: 'Inter', sans-serif; margin: 0; }
        
        /* HEADER */
        .simple-header { 
            position: fixed; top: 0; left: 0; width: 100%; height: 80px; 
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); 
            z-index: 999; display: flex; align-items: center; justify-content: space-between; 
            padding: 0 40px; border-bottom: 1px solid #334155; 
        }
        .header-left .welcome-msg { font-size: 20px; font-weight: 700; color: #fff; display: block; }
        .header-left .welcome-sub { font-size: 13px; color: #94a3b8; }
        
        .logout-link { 
            background: #ef4444; color: #fff; padding: 8px 24px; border-radius: 6px; 
            text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; font-size: 14px; 
        }

        /* LAYOUT */
        .content-wrap { margin-top: 80px; padding: 40px; }
        .manage-grid { 
            display: grid; grid-template-columns: 400px 1fr; gap: 30px; 
            max-width: 1600px; margin: 0 auto; 
        }
        @media (max-width: 992px) { .manage-grid { grid-template-columns: 1fr; } }

        /* CARDS */
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 25px; height: 100%; }
        .card-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #334155; }
        .card-title { font-size: 18px; font-weight: 700; color: #fff; margin: 0; }

        /* FORMS */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 8px; font-weight: 500; }
        .form-control { 
            width: 100%; background: #0f172a; border: 1px solid #334155; color: #fff; 
            padding: 10px 15px; border-radius: 8px; font-size: 14px; 
        }
        .form-control:focus { outline: none; border-color: #8b5cf6; }
        textarea.form-control { resize: vertical; }

        .btn-submit { 
            background: #8b5cf6; color: white; padding: 10px 20px; border: none; 
            border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; 
        }
        .btn-submit:hover { background: #7c3aed; }

        /* TABLE */
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { 
            text-align: left; padding: 12px 15px; background: rgba(0,0,0,0.2); 
            color: #cbd5e1; font-size: 12px; text-transform: uppercase; font-weight: 600; 
            border-bottom: 1px solid #334155; 
        }
        .table td { 
            padding: 12px 15px; border-bottom: 1px solid #334155; 
            color: #94a3b8; font-size: 14px; vertical-align: top; 
        }
        .table tr:hover td { background: rgba(255,255,255,0.02); color: #fff; }

        .badge-type { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-mcq { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
        .badge-theory { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        
        .badge-author { font-size:11px; padding:2px 6px; border-radius:4px; background: #334155; color: #cbd5e1; }
        .badge-admin { background: #f59e0b; color: #000; font-weight:bold; }

        .btn-delete { 
            padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; 
            text-decoration: none; display: inline-block; background: rgba(239, 68, 68, 0.15); color: #f87171; 
        }
        .btn-delete:hover { background: #ef4444; color: white; }
    </style>
</head>
<body>

<div class="app-container">
    
    <div class="simple-header">
        <div class="header-left">
            <div class="welcome-info">
                <span class="welcome-msg">Question Bank</span>
                <span class="welcome-sub">Manage Admin & Teacher Questions</span>
            </div>
        </div>
        <div class="header-right" style="display:flex; align-items:center; gap:15px;">
            
            <form method="get" style="margin:0;">
                <select name="author_filter" onchange="this.form.submit()" style="background:#1e293b; color:#fff; border:1px solid #334155; padding:8px; border-radius:6px; font-size:13px;">
                    <option value="">Filter by Author (All)</option>
                    <option value="admin" <?php if(isset($_GET['author_filter']) && $_GET['author_filter']=='admin') echo 'selected';?>>Admin Only</option>
                    <?php
                    // Fetch Teachers for Filter
                    try {
                        $sqlT = "SELECT * FROM tblteacher";
                        $qT = $dbh->prepare($sqlT); $qT->execute();
                        foreach($qT->fetchAll(PDO::FETCH_OBJ) as $t) {
                            $sel = (isset($_GET['author_filter']) && $_GET['author_filter'] == $t->ID) ? 'selected' : '';
                            echo "<option value='".$t->ID."' $sel>".$t->FirstName . " " . $t->LastName."</option>";
                        }
                    } catch(Exception $e) {}
                    ?>
                </select>
            </form>

            <a href="logout.php" class="logout-link">
                <i class="ti-power-off"></i> Logout
            </a>
        </div>
    </div>

    <div class="content-wrap">
        <div class="manage-grid">
            
            <div class="grid-col-left">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Add New Question</h4>
                    </div>
                    <form method="post">
                        <div class="form-group">
                            <label>Select Subject</label>
                            <select name="subject_id" class="form-control" required>
                                <option value="">Choose Subject...</option>
                                <?php
                                // Fetch All Subjects
                                $sql_sub = "SELECT * FROM tblsubject ORDER BY SubjectFullname ASC";
                                $q_sub = $dbh->prepare($sql_sub);
                                $q_sub->execute();
                                foreach($q_sub->fetchAll(PDO::FETCH_OBJ) as $s) {
                                    echo "<option value='".$s->ID."'>".$s->SubjectFullname." (".$s->SubjectCode.")</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Question Type</label>
                            <select name="q_type" id="q_type" class="form-control" onchange="toggleOptions()" required>
                                <option value="Long">Long Answer (Theory)</option>
                                <option value="Short">Short Answer (Theory)</option>
                                <option value="MCQ">Multiple Choice (MCQ)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Question Text</label>
                            <textarea name="q_text" class="form-control" rows="4" placeholder="Enter question..." required></textarea>
                        </div>

                        <div id="mcq_box" style="display:none; background:#0f172a; padding:15px; border:1px solid #334155; border-radius:8px; margin-bottom:20px;">
                            <label style="color:#a78bfa; margin-bottom:10px;">MCQ Options</label>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:15px;">
                                <input type="text" name="opt_a" class="form-control" placeholder="Option A">
                                <input type="text" name="opt_b" class="form-control" placeholder="Option B">
                                <input type="text" name="opt_c" class="form-control" placeholder="Option C">
                                <input type="text" name="opt_d" class="form-control" placeholder="Option D">
                            </div>
                            <label>Correct Answer</label>
                            <select name="correct_ans" class="form-control">
                                <option value="">Select Correct Option</option>
                                <option value="A">Option A</option>
                                <option value="B">Option B</option>
                                <option value="C">Option C</option>
                                <option value="D">Option D</option>
                            </select>
                        </div>

                        <button type="submit" name="add_q" class="btn-submit">Add to Question Bank</button>
                    </form>
                </div>
            </div>

            <div class="grid-col-right">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Existing Questions</h4>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width:50px;">#</th>
                                    <th>Subject / Type</th>
                                    <th>Question</th>
                                    <th>Added By</th>
                                    <th style="width:80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // --- FILTER QUERY ---
                                $filterSql = "";
                                if(isset($_GET['author_filter'])) {
                                    if($_GET['author_filter'] == 'admin') {
                                        $filterSql = " AND (q.TeacherID = 0 OR q.TeacherID IS NULL)";
                                    } elseif(!empty($_GET['author_filter'])) {
                                        $tid = intval($_GET['author_filter']);
                                        $filterSql = " AND q.TeacherID = $tid";
                                    }
                                }

                                // --- FETCH QUESTIONS ---
                                $sql_list = "SELECT q.*, s.SubjectFullname, t.FirstName, t.LastName 
                                             FROM tblquestions q 
                                             JOIN tblsubject s ON q.SubjectID = s.ID 
                                             LEFT JOIN tblteacher t ON q.TeacherID = t.ID
                                             WHERE 1=1 $filterSql
                                             ORDER BY q.ID DESC LIMIT 50";
                                
                                $q_list = $dbh->prepare($sql_list);
                                $q_list->execute();
                                $questions = $q_list->fetchAll(PDO::FETCH_OBJ);
                                $cnt = 1;

                                if($q_list->rowCount() > 0) {
                                    foreach($questions as $row) {
                                        $badgeClass = ($row->QuestionType == 'MCQ') ? 'badge-mcq' : 'badge-theory';
                                        
                                        // Author Logic
                                        if($row->FirstName) {
                                            $authorBadge = '<span class="badge-author">'.$row->FirstName.' '.$row->LastName.'</span>';
                                        } else {
                                            $authorBadge = '<span class="badge-author badge-admin">ADMIN</span>';
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $cnt;?></td>
                                            <td>
                                                <span style="color:#fff; font-weight:600; font-size:13px;"><?php echo htmlentities($row->SubjectFullname);?></span><br>
                                                <span class="badge-type <?php echo $badgeClass; ?>"><?php echo htmlentities($row->QuestionType);?></span>
                                            </td>
                                            <td>
                                                <?php echo htmlentities($row->QuestionText);?>
                                                <?php if($row->QuestionType == 'MCQ') { ?>
                                                    <br><small style="color:#64748b;">
                                                        (A) <?php echo htmlentities($row->OptionA);?> ... 
                                                        <strong style="color:#10b981;">[Ans: <?php echo htmlentities($row->CorrectAnswer);?>]</strong>
                                                    </small>
                                                <?php } ?>
                                            </td>
                                            <td><?php echo $authorBadge; ?></td>
                                            <td>
                                                <a href="manage-questions.php?delid=<?php echo $row->ID;?>" class="btn-delete" onclick="return confirm('Delete this question?');">Delete</a>
                                            </td>
                                        </tr>
                                        <?php $cnt++; 
                                    }
                                } else { ?>
                                    <tr><td colspan="5" style="text-align:center; padding:20px;">No questions found.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function toggleOptions() {
        var type = document.getElementById('q_type').value;
        var box = document.getElementById('mcq_box');
        
        if(type === 'MCQ') {
            box.style.display = 'block';
            document.getElementsByName('opt_a')[0].required = true;
            document.getElementsByName('opt_b')[0].required = true;
            document.getElementsByName('correct_ans')[0].required = true;
        } else {
            box.style.display = 'none';
            document.getElementsByName('opt_a')[0].required = false;
            document.getElementsByName('opt_b')[0].required = false;
            document.getElementsByName('correct_ans')[0].required = false;
        }
    }
</script>

</body>
</html>