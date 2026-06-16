<?php
ob_start(); 
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

$message = "";
$msgType = ""; 

// ==========================================
// AJAX HANDLER: Get Batches based on Course
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'get_batches') {
    ob_clean(); 
    header('Content-Type: application/json');
    $course_id = intval($_GET['course_id']);
    
    try {
        $stmt = $dbh->prepare("SELECT ID as id, BatchName as batch_name FROM tblbatch WHERE CourseID = :cid");
        $stmt->execute(['cid' => $course_id]);
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        try {
            $stmt = $dbh->prepare("SELECT id, batch_name FROM batches WHERE CourseID = :cid");
            $stmt->execute(['cid' => $course_id]);
            $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            $batches = []; 
        }
    }
    
    echo json_encode($batches);
    exit; 
}

// ==========================================
// STRICT ALGORITHM: GENERATE TIMETABLE
// ==========================================
if (isset($_POST['generate'])) {
    $course_id = $_POST['course'];
    $batch_id = $_POST['batch'];

    if (empty($batch_id) || empty($course_id)) {
        $message = "SYSTEM HALT: Missing Course or Batch parameters.";
        $msgType = "error";
    } else {
        try {
            // 1. Get Subjects for the Course
            $sub_sql = "SELECT ID FROM tblsubject WHERE CourseID = :cid";
            $sub_stmt = $dbh->prepare($sub_sql);
            $sub_stmt->execute(['cid' => $course_id]);
            $subjects = $sub_stmt->fetchAll(PDO::FETCH_OBJ);

            // 2. Cross-Reference with Authorized Teachers
            $valid_pairs = [];
            foreach ($subjects as $sub) {
                // Look up which teachers are actually assigned to this specific subject
                $map_sql = "SELECT TeacherID FROM tblteacher_subjects WHERE SubjectID = :sid";
                $map_stmt = $dbh->prepare($map_sql);
                $map_stmt->execute(['sid' => $sub->ID]);
                $assigned_teachers = $map_stmt->fetchAll(PDO::FETCH_OBJ);
                
                // If a teacher is found, create a strictly paired link
                if (count($assigned_teachers) > 0) {
                    foreach ($assigned_teachers as $auth_teacher) {
                        $valid_pairs[] = [
                            'subject_id' => $sub->ID,
                            'teacher_id' => $auth_teacher->TeacherID
                        ];
                    }
                }
            }

            // 3. Get a Classroom (Fault-Tolerant)
            $classroom_id = 1; // Default
            try {
                $room_sql = "SELECT ID as id FROM tblclass LIMIT 1";
                $r_stmt = $dbh->query($room_sql);
                if ($classroom = $r_stmt->fetch(PDO::FETCH_OBJ)) $classroom_id = $classroom->id;
            } catch(Exception $e) {
                try {
                    $room_sql = "SELECT id FROM classrooms LIMIT 1";
                    $r_stmt = $dbh->query($room_sql);
                    if ($classroom = $r_stmt->fetch(PDO::FETCH_OBJ)) $classroom_id = $classroom->id;
                } catch(Exception $e2) {}
            }

            // 4. Build the Matrix ONLY if valid authorized pairs exist
            if (count($valid_pairs) > 0) {
                
                // Clear existing corrupted timetable for this batch
                $delete_sql = "DELETE FROM timetable_schedule WHERE batch_id = :batch_id";
                $del_stmt = $dbh->prepare($delete_sql);
                $del_stmt->execute(['batch_id' => $batch_id]);

                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                $time_slots = [
                    ['09:00:00', '10:00:00'],
                    ['10:00:00', '11:00:00'],
                    ['11:00:00', '12:00:00'],
                    ['12:00:00', '13:00:00'], 
                    ['14:00:00', '15:00:00'],
                    ['15:00:00', '16:00:00']
                ];

                $pair_index = 0;

                foreach ($days as $day) {
                    foreach ($time_slots as $slot) {
                        // Loop through strictly authorized Subject-Teacher pairs
                        $current_pair = $valid_pairs[$pair_index % count($valid_pairs)];

                        $insert_sql = "INSERT INTO timetable_schedule (batch_id, day_of_week, start_time, end_time, subject_id, teacher_id, classroom_id) 
                                       VALUES (:bid, :day, :start, :end, :sid, :tid, :cid)";
                        $ins_stmt = $dbh->prepare($insert_sql);
                        $ins_stmt->execute([
                            'bid' => $batch_id,
                            'day' => $day,
                            'start' => $slot[0],
                            'end' => $slot[1],
                            'sid' => $current_pair['subject_id'],
                            'tid' => $current_pair['teacher_id'],
                            'cid' => $classroom_id
                        ]);

                        $pair_index++;
                    }
                }
                $message = "Algorithm Complete: Strict Timetable Matrix deployed.";
                $msgType = "success";
            } else {
                $message = "SYSTEM HALT: No teachers are mapped to the subjects in this course. Please assign subjects to teachers first.";
                $msgType = "error";
            }

        } catch (PDOException $e) {
            $message = "CORE ERROR: " . $e->getMessage();
            $msgType = "error";
        }
    }
}

$pageTitle = "VidyaVerse | Auto-Scheduler Node";
include('includes/header.php');
?>

<div class="container-fluid content-wrapper">
    
    <style>
        :root {
            var(--stealth-bg): rgba(9, 9, 11, 0.65);
            var(--stealth-border-light): rgba(255, 255, 255, 0.08);
            var(--stealth-border-dark): rgba(0, 0, 0, 0.8);
            --sec-cyan: #06b6d4;
            --sec-emerald: #10b981;
            --sec-red: #ef4444;
            --sec-purple: #8b5cf6;
        }

        body {
            background-color: #050505;
            background-image: radial-gradient(circle at 50% 0%, #1e293b 0%, #020617 80%);
            background-attachment: fixed;
            font-family: 'Inter', sans-serif; color: #e2e8f0;
        }

        .container-center { display: flex; justify-content: center; margin-top: 60px; padding: 20px; }

        .glass-card {
            background: rgba(9, 9, 11, 0.65);
            backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            border-left: 1px solid rgba(255, 255, 255, 0.08);
            border-right: 1px solid rgba(0, 0, 0, 0.8);
            border-bottom: 1px solid rgba(0, 0, 0, 0.8);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7), inset 0 1px 2px rgba(255,255,255,0.05);
            width: 100%; max-width: 550px;
        }

        .section-header { text-align: center; margin-bottom: 35px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 20px; }
        .header-title { font-family: 'Orbitron', sans-serif; font-size: 22px; font-weight: 800; color: #fff; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .header-title i { color: var(--sec-purple); text-shadow: 0 0 15px var(--sec-purple); }
        .header-desc { font-size: 13px; color: #71717a; margin-top: 8px; text-transform: uppercase; letter-spacing: 1px; }

        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #a1a1aa; margin-bottom: 8px; font-weight: 700; }
        
        .form-control {
            width: 100%; background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255,255,255,0.1); color: #fff;
            padding: 14px; border-radius: 12px; font-size: 14px; transition: 0.3s;
            font-family: 'Inter', sans-serif; outline: none;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
        }
        .form-control:focus { border-color: var(--sec-cyan); box-shadow: 0 0 15px rgba(6, 182, 212, 0.2), inset 0 2px 4px rgba(0,0,0,0.5); }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2306b6d4' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat; background-position: right 15px center; background-size: 16px;
        }
        select.form-control option { background-color: #09090b; color: #fff; padding: 10px; }

        .btn-generate {
            background: linear-gradient(135deg, var(--sec-purple), #6d28d9);
            color: white; border: 1px solid rgba(139, 92, 246, 0.5); width: 100%; padding: 15px;
            border-radius: 12px; font-size: 13px; font-weight: 800; font-family: 'Orbitron', sans-serif;
            cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 2px;
            margin-top: 10px; box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);
            display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-generate:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(139, 92, 246, 0.5); filter: brightness(1.2); }

        .msg-box { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 10px; border: 1px solid transparent; letter-spacing: 0.5px; }
        .msg-success { background: rgba(16, 185, 129, 0.15); color: var(--sec-emerald); border-color: rgba(16, 185, 129, 0.3); }
        .msg-error { background: rgba(239, 68, 68, 0.15); color: var(--sec-red); border-color: rgba(239, 68, 68, 0.3); animation: shake 0.4s; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
    </style>

    <div class="container-center">
        <div class="glass-card">
            <div class="section-header">
                <div class="header-title"><i class="fas fa-microchip"></i> Algorithm Node</div>
                <div class="header-desc">Deploy Automated Weekly Timetable</div>
            </div>

            <?php if(!empty($message)): ?>
                <div class="msg-box <?php echo ($msgType == 'success') ? 'msg-success' : 'msg-error'; ?>">
                    <i class="fas <?php echo ($msgType == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label><i class="fas fa-layer-group" style="margin-right:5px; color:var(--sec-cyan);"></i> Target Course Data</label>
                    <select class="form-control" name="course" id="course_select" required>
                        <option value="">-- Initialize Course Selection --</option>
                        <?php
                        try {
                            $sqlC = "SELECT * FROM tblcourse";
                            $queryC = $dbh->query($sqlC);
                            $courses = $queryC->fetchAll(PDO::FETCH_OBJ);
                            foreach($courses as $row) {
                                echo "<option value='".$row->ID."'>".$row->CourseName." (".$row->BranchName.")</option>";
                            }
                        } catch(Exception $e) {
                            echo "<option value=''>Database Error: Could not load courses</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-users-cog" style="margin-right:5px; color:var(--sec-cyan);"></i> Target Batch Node</label>
                    <select class="form-control" name="batch" id="batch_select" required>
                        <option value="">-- Awaiting Course Input --</option>
                    </select>
                </div>

                <button type="submit" name="generate" class="btn-generate">
                    Execute Algorithm <i class="fas fa-bolt"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#course_select').change(function() {
            var courseId = $(this).val();
            if (courseId) {
                $('#batch_select').html('<option value="">Scanning Database...</option>');
                
                $.ajax({
                    url: 'generate-timetable.php',
                    type: 'GET',
                    data: { action: 'get_batches', course_id: courseId },
                    success: function(response) {
                        try {
                            var batches = typeof response === 'string' ? JSON.parse(response) : response;
                            var batchSelect = $('#batch_select');
                            batchSelect.empty();
                            batchSelect.append('<option value="">-- Select Deployment Batch --</option>');
                            
                            if(batches.length > 0) {
                                $.each(batches, function(index, batch) {
                                    batchSelect.append('<option value="' + batch.id + '">' + batch.batch_name + '</option>');
                                });
                            } else {
                                batchSelect.append('<option value="">No active batches found</option>');
                            }
                        } catch(e) {
                            $('#batch_select').html('<option value="">System Error: Invalid Data Stream</option>');
                        }
                    },
                    error: function() {
                        $('#batch_select').html('<option value="">Network Failure</option>');
                    }
                });
            } else {
                $('#batch_select').html('<option value="">-- Awaiting Course Input --</option>');
            }
        });
    });
</script>

<?php include('includes/footer.php');?>