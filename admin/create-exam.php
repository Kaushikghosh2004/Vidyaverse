<?php
session_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// =======================================================================
//  1. AJAX HANDLER: GENERATE PREVIEW (Returns HTML)
// =======================================================================
if(isset($_POST['action']) && $_POST['action'] == 'preview_paper') {
    $subject_id = $_POST['subject_id'];
    $sections = json_decode($_POST['sections'], true);
    
    // Preview Header (Matches your Screenshot)
    echo '<div style="font-family: \'Times New Roman\', serif; color:#000; padding:20px; background:#fff;">
            <div style="text-align:center; border-bottom:2px solid #000; padding-bottom:10px; margin-bottom:20px;">
                <h2 style="margin:0; text-transform:uppercase;">Techno International Batanagar</h2>
                <h4 style="margin:5px 0; font-weight:normal;">Examination Department</h4>
                <h3 style="margin:5px 0;">'.strtoupper($_POST['exam_title']).'</h3>
            </div>
            
            <div style="display:flex; justify-content:space-between; font-weight:bold; border-bottom:1px solid #000; padding-bottom:5px; margin-bottom:20px;">
                <span>Subject: '.$_POST['subject_name'].'</span>
                <span>Time: '.$_POST['duration'].' Mins</span>
                <span>Total Marks: '.$_POST['total_marks'].'</span>
            </div>';

    foreach($sections as $sec) {
        $grp = strtoupper($sec['group']);
        $type = strtoupper($sec['type']);
        $marks = $sec['marks'];
        $pool = $sec['pool'];     // How many to fetch (Print Qty)
        $attempt = $sec['attempt']; // How many student must answer
        $subtotal = $marks * $attempt;

        // Section Header
        echo "<div style='border:1px solid #000; padding:8px; text-align:center; font-weight:bold; background:#e0e0e0; margin-top:20px; margin-bottom:15px;'>
                $grp ($type TYPE) - ANSWER ANY $attempt - [ $marks x $attempt = $subtotal Marks ]
              </div>";

        // Fetch Random Questions for Preview
        $sql = "SELECT QuestionText, OptionA, OptionB, OptionC, OptionD FROM tblquestions 
                WHERE SubjectID = :sid AND QuestionType = :qtype 
                ORDER BY RAND() LIMIT $pool";
        $q = $dbh->prepare($sql);
        $q->execute([':sid' => $subject_id, ':qtype' => $sec['type']]);
        $questions = $q->fetchAll(PDO::FETCH_OBJ);

        if(count($questions) < $pool) {
            echo "<p style='color:red; text-align:center;'>[Error: You requested $pool questions, but the Question Bank only has ".count($questions)."]</p>";
        }

        echo "<ol>";
        foreach($questions as $row) {
            echo "<li style='margin-bottom:15px;'>".$row->QuestionText;
            if($sec['type'] == 'MCQ') {
                echo "<div style='display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:5px; font-size:14px; color:#444;'>
                        <span>(A) ".$row->OptionA."</span><span>(B) ".$row->OptionB."</span>
                        <span>(C) ".$row->OptionC."</span><span>(D) ".$row->OptionD."</span>
                      </div>";
            }
            echo "</li>";
        }
        echo "</ol>";
    }
    echo '</div>';
    exit; // Stop execution here for AJAX
}


// =======================================================================
//  2. HANDLE FINAL SUBMISSION (SAVE TO DATABASE)
// =======================================================================
if(isset($_POST['finalize_exam'])) {
    
    // Capture Inputs
    $exam_title = $_POST['exam_title'];
    $course_id = $_POST['course_id'];
    $subject_id = $_POST['subject_id'];
    
    // Logic: If batch_id is empty or 0, treat as "All Batches"
    $batch_id = !empty($_POST['batch_id']) ? $_POST['batch_id'] : 0; 
    
    $exam_date = $_POST['exam_date'];
    $duration = $_POST['duration'];
    $total_marks = $_POST['display_total_marks']; 
    
    // Arrays from Table
    $groups = $_POST['q_group'];
    $types = $_POST['q_type'];
    $marks = $_POST['q_marks'];
    $pools = $_POST['q_pool'];       
    $attempts = $_POST['q_attempt']; 
    
    // Total questions physically in the paper (Pool Sum)
    $total_questions_count = array_sum($pools);

    try {
        $dbh->beginTransaction();

        // A. Insert Exam Metadata
        $sqlExam = "INSERT INTO tblexams (ExamTitle, CourseID, SubjectID, BatchID, ExamDate, Duration, TotalMarks, TotalQuestions) 
                    VALUES (:title, :cid, :sid, :bid, :edate, :dur, :tm, :tq)";
        $stmt = $dbh->prepare($sqlExam);
        $stmt->execute([
            ':title' => $exam_title,
            ':cid' => $course_id,
            ':sid' => $subject_id,
            ':bid' => $batch_id,
            ':edate' => $exam_date,
            ':dur' => $duration,
            ':tm' => $total_marks,
            ':tq' => $total_questions_count
        ]);
        
        $exam_id = $dbh->lastInsertId();

        // B. Select Questions & Link
        for($i=0; $i < count($groups); $i++) {
            
            $grp_name = $groups[$i];
            $q_type = $types[$i];
            $q_mark = $marks[$i];
            $q_fetch = $pools[$i];       // Pool Size (Print Qty)
            $q_attempt = $attempts[$i];  // Instruction (Req)
            
            // Format Section Name for Student View: "Section A (Answer 5)"
            $section_display_name = "$grp_name (Answer $q_attempt)";

            // Fetch Random IDs
            $sqlSelect = "SELECT ID FROM tblquestions 
                          WHERE SubjectID = :sid AND QuestionType = :qtype 
                          ORDER BY RAND() LIMIT $q_fetch";
            
            $qSelect = $dbh->prepare($sqlSelect);
            $qSelect->execute([':sid' => $subject_id, ':qtype' => $q_type]);
            $selected_questions = $qSelect->fetchAll(PDO::FETCH_COLUMN);

            if(count($selected_questions) < $q_fetch) {
                throw new Exception("Error in '$grp_name': You need $q_fetch questions, but Bank only has " . count($selected_questions));
            }

            // Save to Link Table
            $sqlLink = "INSERT INTO tblexam_questions (ExamID, QuestionID, SectionName, QuestionMarks) VALUES (?, ?, ?, ?)";
            $stmtLink = $dbh->prepare($sqlLink);

            foreach($selected_questions as $qid) {
                $stmtLink->execute([$exam_id, $qid, $section_display_name, $q_mark]);
            }
        }

        $dbh->commit();
        echo "<script>alert('Exam Scheduled Successfully! ID: $exam_id'); window.location.href='manage-exams.php';</script>";

    } catch (Exception $e) {
        $dbh->rollBack();
        echo "<script>alert('Failed: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule Exam | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* --- GIGANTIC DARK THEME (From your code) --- */
        :root {
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --neon-blue: #3b82f6;
            --neon-green: #10b981;
            --neon-red: #ef4444;
        }
        body { 
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc; padding-top: 80px; margin: 0;
        }
        * { box-sizing: border-box; }

        .secure-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: #1e293b; border-bottom: 2px solid #ef4444;
            display: flex; align-items: center; justify-content: space-between; padding: 0 40px; z-index: 1000;
        }
        .header-brand { display: flex; align-items: center; gap: 12px; }
        .header-brand i { font-size: 20px; color: #ef4444; }
        .brand-text { font-size: 18px; font-weight: 700; color: #fff; }
        .header-right a { color: #94a3b8; text-decoration: none; font-weight: 600; margin-left: 20px; }

        .container { width: 100%; max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
        
        .glass-card {
            background: var(--glass-bg); backdrop-filter: blur(12px);
            border: var(--glass-border); border-radius: 20px; padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); margin-bottom: 30px;
        }

        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--neon-blue); border-left: 4px solid var(--neon-blue); padding-left: 15px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .form-group label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 8px; font-weight: 500; }
        .form-control { width: 100%; background: #0f172a; border: 1px solid #334155; color: #fff; padding: 12px; border-radius: 8px; }
        .form-control:focus { outline: none; border-color: var(--neon-blue); }

        /* TABLE */
        .smart-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .smart-table th { text-align: left; color: #94a3b8; padding: 10px; font-size: 13px; text-transform:uppercase; }
        .smart-table td { padding: 10px; background: rgba(30, 41, 59, 0.6); vertical-align: middle; border-top: 1px solid #334155; border-bottom: 1px solid #334155; }
        .smart-table tr td:first-child { border-left: 1px solid #334155; border-radius: 8px 0 0 8px; }
        .smart-table tr td:last-child { border-right: 1px solid #334155; border-radius: 0 8px 8px 0; }
        
        .btn-add { background: var(--neon-green); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight:600; }
        .btn-preview { background: var(--neon-blue); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-confirm { background: var(--neon-green); color: white; padding: 12px 25px; border: none; border-radius: 30px; font-weight: 600; cursor: pointer; }
        .btn-close { background: #ef4444; color: white; padding: 12px 25px; border: none; border-radius: 30px; cursor: pointer; }
        .btn-reshuffle { background: #f59e0b; color: white; padding: 12px 25px; border: none; border-radius: 30px; cursor: pointer; }

        .total-bar { background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; color: #60a5fa; padding: 15px; border-radius: 8px; text-align: center; margin-top: 20px; font-weight: 700; display: flex; justify-content: space-between; }

        /* MODAL */
        #previewModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2000; overflow-y: auto; padding: 40px 0; }
        .a4-paper { width: 210mm; min-height: 297mm; background: white; color: black; margin: 0 auto; padding: 20mm; }
        .preview-actions { position: fixed; bottom: 30px; right: 30px; display: flex; gap: 10px; }
    </style>
</head>
<body>

    <header class="secure-header">
        <div class="header-brand"><i class="ti-shield"></i> <span class="brand-text" style="margin-left:10px;">Smart Exam Controller</span></div>
        <div class="header-right">
            <a href="dashboard.php">Dashboard</a> &nbsp;|&nbsp; <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <form id="examForm" method="POST">
            
            <div class="glass-card">
                <div class="section-title">1. Exam Logistics</div>
                <div class="form-grid">
                    <div class="form-group"><label>Exam Title</label><input type="text" name="exam_title" id="exam_title" class="form-control" required></div>
                    
                    <div class="form-group"><label>1. Select Course</label>
                        <select name="course_id" id="course_id" class="form-control" onchange="fetchSubjectsAndBatches()" required>
                            <option value="">Select Course...</option>
                            <?php
                            $q = $dbh->query("SELECT * FROM tblcourse");
                            while($r = $q->fetch(PDO::FETCH_OBJ)) { echo "<option value='".$r->ID."'>".$r->CourseName."</option>"; }
                            ?>
                        </select>
                    </div>
                    <div class="form-group"><label>2. Select Semester</label>
                        <select name="semester" id="semester" class="form-control" onchange="fetchSubjectsAndBatches()" required>
                            <option value="">Select Semester...</option>
                            <?php for($i=1; $i<=8; $i++) echo "<option value='$i'>Semester $i</option>"; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>3. Select Subject (Filtered)</label><select name="subject_id" id="subject_id" class="form-control" required><option value="">Select Subject...</option></select></div>
                    
                    <div class="form-group"><label>4. Select Batch</label><select name="batch_id" id="batch_id" class="form-control"><option value="0">★ All Batches (Entire Semester)</option></select></div>

                    <div class="form-group"><label>Exam Date</label><input type="datetime-local" name="exam_date" class="form-control" required></div>
                    <div class="form-group"><label>Duration (Mins)</label><input type="number" name="duration" id="duration" class="form-control" placeholder="90" required></div>
                    <div class="form-group"><label>Total Marks (Auto)</label><input type="text" name="display_total_marks" id="display_total_marks" class="form-control" readonly value="0"></div>
                </div>
            </div>

            <div class="glass-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <div class="section-title" style="margin:0;">2. Question Paper Structure</div>
                    <button type="button" class="btn-add" onclick="addRow()">+ Add Section</button>
                </div>
                
                <table class="smart-table" id="structureTable">
                    <thead>
                        <tr>
                            <th width="20%">Section Name</th>
                            <th width="15%">Type</th>
                            <th width="10%">Marks Per Q</th>
                            <th width="15%">Pool (Print Qty)</th>
                            <th width="15%">Attempt (Req)</th>
                            <th width="15%">Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <div class="total-bar">
                    <span id="grandTotalQ">Total Questions (Pool): 0</span>
                    <span id="grandTotalMarks">Total Marks: 0</span>
                </div>
            </div>

            <div style="text-align: right; margin-bottom: 50px;">
                <button type="button" class="btn-preview" onclick="showPreview()">Review & Schedule</button>
            </div>

            <div id="previewModal">
                <div class="a4-paper" id="previewContent">Loading...</div>
                <div class="preview-actions">
                    <button type="button" class="btn-close" onclick="$('#previewModal').fadeOut()">Edit</button>
                    <button type="button" class="btn-reshuffle" onclick="showPreview()">Reshuffle</button>
                    <button type="submit" name="finalize_exam" class="btn-confirm">Confirm & Schedule</button>
                </div>
            </div>

        </form>
    </div>

    <script>
        // --- 1. DROPDOWN LOGIC ---
        function fetchSubjectsAndBatches() {
            var cid = $('#course_id').val();
            var sem = $('#semester').val();
            if(cid && sem) {
                // Fetch Subjects
                $.post('get_data.php', { action: 'get_subjects', course_id: cid, semester: sem }, function(data) { $('#subject_id').html(data); });
                // Fetch Batches (And append "All Batches" option)
                $.post('get_data.php', { action: 'get_batches', course_id: cid, semester: sem }, function(data) { 
                    $('#batch_id').html('<option value="0">★ All Batches (Entire Semester)</option>' + data); 
                });
            }
        }

        // --- 2. TABLE LOGIC ---
        function addRow() {
            var html = `<tr>
                <td><input type="text" name="q_group[]" class="form-control" value="Section " required></td>
                <td><select name="q_type[]" class="form-control" style="background:#253045;"><option value="MCQ">MCQ</option><option value="Short">Short Answer</option><option value="Long">Long Answer</option></select></td>
                <td><input type="number" name="q_marks[]" class="form-control" oninput="calc()" placeholder="Marks" required></td>
                <td><input type="number" name="q_pool[]" class="form-control" oninput="calc()" placeholder="Print Qty" required></td>
                <td><input type="number" name="q_attempt[]" class="form-control" oninput="calc()" placeholder="Req Attempt" required></td>
                <td><input type="text" class="form-control subtotal" readonly value="0"></td>
                <td style="text-align:center;"><button type="button" style="color:#ef4444; background:none; border:none; font-size:18px; cursor:pointer;" onclick="$(this).closest('tr').remove(); calc();">&times;</button></td>
            </tr>`;
            $('#structureTable tbody').append(html);
        }

        function calc() {
            let totalMarks = 0;
            let totalPool = 0;
            $('#structureTable tbody tr').each(function() {
                let m = parseFloat($(this).find('input[name="q_marks[]"]').val()) || 0;
                let pool = parseFloat($(this).find('input[name="q_pool[]"]').val()) || 0;
                let attempt = parseFloat($(this).find('input[name="q_attempt[]"]').val()) || 0;
                
                // Logic: Subtotal = Marks * Attempt
                let sub = m * attempt;
                
                $(this).find('.subtotal').val(sub);
                totalMarks += sub;
                totalPool += pool;
            });
            $('#display_total_marks').val(totalMarks);
            $('#grandTotalMarks').text("Total Marks: " + totalMarks);
            $('#grandTotalQ').text("Total Questions (Pool): " + totalPool);
        }

        $(document).ready(function() { addRow(); });

        // --- 3. PREVIEW LOGIC (AJAX) ---
        function showPreview() {
            if(!document.getElementById('examForm').checkValidity()) {
                document.getElementById('examForm').reportValidity();
                return;
            }

            $('#previewModal').fadeIn();
            
            let sectionsData = [];
            $('#structureTable tbody tr').each(function() {
                sectionsData.push({
                    group: $(this).find('input[name="q_group[]"]').val(),
                    type: $(this).find('select[name="q_type[]"]').val(),
                    marks: $(this).find('input[name="q_marks[]"]').val(),
                    pool: $(this).find('input[name="q_pool[]"]').val(),
                    attempt: $(this).find('input[name="q_attempt[]"]').val()
                });
            });

            $.ajax({
                url: '', 
                method: 'POST',
                data: { 
                    action: 'preview_paper', 
                    subject_id: $('#subject_id').val(),
                    subject_name: $("#subject_id option:selected").text(),
                    exam_title: $('#exam_title').val(),
                    duration: $('#duration').val(),
                    total_marks: $('#display_total_marks').val(),
                    sections: JSON.stringify(sectionsData) 
                },
                success: function(response) {
                    $('#previewContent').html(response);
                },
                error: function() {
                    $('#previewContent').html('<p style="color:red; text-align:center;">Error fetching questions. Ensure question bank has enough questions.</p>');
                }
            });
        }
    </script>
</body>
</html>