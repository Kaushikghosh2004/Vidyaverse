<?php
session_start();
// Enable error reporting to find issues easily
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Set Timezone (Critical for Exam Start/Stop logic)
date_default_timezone_set('Asia/Kolkata'); 

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocasuid']==0)) { header('location:logout.php'); exit(); }
$uid = $_SESSION['ocasuid'];

// 2. GET STUDENT DETAILS (Fixing the Fatal Error)
// We join 'tbluser' with 'batches' to find the CourseID.
// NOTE: Make sure your batch table is named 'batches' or 'tblbatches' in your database.
// Based on your previous screenshots, I am using 'batches'.
$sqlUser = "SELECT u.FullName, u.batch_id, b.CourseID 
            FROM tbluser u
            LEFT JOIN batches b ON u.batch_id = b.id 
            WHERE u.ID = :uid";

$qUser = $dbh->prepare($sqlUser);
$qUser->execute(['uid' => $uid]);
$user = $qUser->fetch(PDO::FETCH_OBJ);

// Safe Fallbacks (Prevents errors if student has no batch)
$my_name = $user->FullName ?? 'Student';
$my_batch = $user->batch_id ?? 0;
$my_course = $user->CourseID ?? 0;

include('includes/header.php');
?>

<div class="app-container">
    
    <div class="simple-header">
        <div class="header-left">
            <a href="dashboard.php" class="back-btn">
                <i class="ti-arrow-left"></i> Dashboard
            </a>
            <div class="welcome-info">
                <span class="welcome-msg">Exam Hall</span>
                <span class="welcome-sub">Welcome, <?php echo htmlentities($my_name); ?></span>
            </div>
        </div>
        <div class="header-right">
            <a href="logout.php" class="logout-link"><i class="ti-power-off"></i> Logout</a>
        </div>
    </div>

    <div class="content-wrap">
        <div class="main">
            <div class="container-fluid">
                
                <style>
                    /* Styles from your previous code */
                    * { box-sizing: border-box; }
                    body { background-color: #0f172a; font-family: 'Inter', sans-serif; color: #f8fafc; }
                    .header, .sidebar { display: none !important; }

                    .simple-header {
                        position: fixed; top: 0; left: 0; width: 100%; height: 80px;
                        background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px);
                        z-index: 999; display: flex; align-items: center; justify-content: space-between;
                        padding: 0 40px; border-bottom: 1px solid #334155;
                    }
                    .welcome-msg { font-size: 20px; font-weight: 700; color: #fff; }
                    .welcome-sub { font-size: 13px; color: #94a3b8; }
                    .back-btn { background: rgba(255,255,255,0.1); color: #fff; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; }
                    .logout-link { background: #ef4444; color: #fff; padding: 8px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; }

                    .content-wrap { margin-top: 80px; padding: 40px; }
                    .page-header { margin-bottom: 30px; }
                    .page-header h1 { font-size: 32px; font-weight: 800; margin: 0; color: #fff; }
                    
                    /* Alert Box for Missing Batch */
                    .alert-box {
                        background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171;
                        padding: 20px; border-radius: 12px; text-align: center; margin-top: 20px;
                    }

                    .exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 30px; }
                    
                    .exam-card {
                        background: #1e293b; border: 1px solid #334155; border-radius: 16px;
                        overflow: hidden; position: relative; transition: 0.3s;
                        display: flex; flex-direction: column; height: 100%;
                    }
                    .exam-card:hover { transform: translateY(-5px); border-color: #3b82f6; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }

                    .status-badge {
                        position: absolute; top: 15px; right: 15px; padding: 5px 12px;
                        border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase;
                    }
                    .bs-live { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; animation: pulse 2s infinite; }
                    .bs-wait { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid #f59e0b; }
                    .bs-closed { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }

                    .card-body { padding: 25px; flex: 1; }
                    .exam-title { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 5px; line-height: 1.4; }
                    .subject-name { color: #3b82f6; font-size: 13px; font-weight: 600; margin-bottom: 15px; text-transform: uppercase; }
                    .meta-info { color: #94a3b8; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
                    
                    .card-footer { padding: 20px; border-top: 1px solid #334155; background: rgba(0,0,0,0.2); }
                    .btn-action { display: block; width: 100%; padding: 14px; border-radius: 10px; font-weight: 700; text-transform: uppercase; text-decoration: none; font-size: 14px; text-align:center; border:none; cursor:pointer; }
                    .btn-launch { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
                    .btn-wait { background: #334155; color: #94a3b8; cursor: not-allowed; }
                    .btn-missed { background: transparent; border: 1px solid #ef4444; color: #ef4444; cursor: not-allowed; }
                    
                    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
                </style>

                <div class="page-header">
                    <h1>Examination Hall</h1>
                    <p style="color:#94a3b8;">Server Time: <?php echo date('d M Y, h:i A'); ?></p>
                </div>

                <div class="exam-grid">
                    <?php
                    // --- CHECK: IS BATCH ALLOCATED? ---
                    if(empty($my_batch) || $my_batch == 0) {
                        // User has no batch, so we can't determine their course
                        echo '<div style="grid-column:1/-1;">
                                <div class="alert-box">
                                    <i class="ti-alert" style="font-size:40px; display:block; margin-bottom:10px;"></i>
                                    <strong>Access Denied: Batch Not Allocated</strong><br>
                                    Your profile is not assigned to any Class/Batch yet.<br>
                                    Please contact your Department HOD or Admin to allocate your batch so you can view exams.
                                </div>
                              </div>';
                    } 
                    else {
                        // --- FETCH EXAMS ---
                        try {
                            // Rule: Show exams matching Student's CourseID AND (Student's BatchID OR BatchID=0 for All)
                            $sql = "SELECT e.*, s.SubjectFullname 
                                    FROM tblexams e
                                    LEFT JOIN tblsubject s ON e.SubjectID = s.ID
                                    WHERE e.CourseID = :cid 
                                    AND (e.BatchID = :bid OR e.BatchID = 0)
                                    ORDER BY e.ExamDate ASC";
                            
                            $query = $dbh->prepare($sql);
                            $query->execute([
                                ':cid' => $my_course,
                                ':bid' => $my_batch
                            ]);
                            $exams = $query->fetchAll(PDO::FETCH_OBJ);

                            if($query->rowCount() > 0) {
                                foreach($exams as $exam) {
                                    
                                    // Check Status
                                    $chk = $dbh->prepare("SELECT Status, Score FROM tblexam_sessions WHERE ExamID=:eid AND StudentID=:sid");
                                    $chk->execute(['eid' => $exam->ID, 'sid' => $uid]);
                                    $session = $chk->fetch(PDO::FETCH_OBJ);

                                    $examTime = strtotime($exam->ExamDate);
                                    $currentTime = time();
                                    $endTime = $examTime + ((int)$exam->Duration * 60);
                                    
                                    $badge = ''; $btn = '';

                                    // Logic for Buttons/Badges
                                    if($session) {
                                        if($session->Status == 'Completed') {
                                            $badge = '<span class="status-badge bs-closed">Completed</span>';
                                            $btn = '<button class="btn-action btn-missed">Score: '.$session->Score.'</button>';
                                        } elseif($session->Status == 'Terminated') {
                                            $badge = '<span class="status-badge bs-closed">Terminated</span>';
                                            $btn = '<button class="btn-action btn-missed">Disqualified</button>';
                                        } else {
                                            if($currentTime < $endTime) {
                                                $badge = '<span class="status-badge bs-live">Running</span>';
                                                $btn = '<a href="take-exam.php?exam_id='.$exam->ID.'" class="btn-action btn-launch" style="background:#f59e0b; color:black;">Resume Exam</a>';
                                            } else {
                                                $badge = '<span class="status-badge bs-closed">Time Up</span>';
                                                $btn = '<button class="btn-action btn-missed">Not Submitted</button>';
                                            }
                                        }
                                    } else {
                                        if ($currentTime < $examTime) {
                                            $badge = '<span class="status-badge bs-wait">Upcoming</span>';
                                            $mins = ceil(($examTime - $currentTime) / 60);
                                            $waitTxt = ($mins > 60) ? date("M d, h:i A", $examTime) : "Starts in $mins mins";
                                            $btn = '<button class="btn-action btn-wait">'.$waitTxt.'</button>';
                                        } elseif ($currentTime >= $examTime && $currentTime <= $endTime) {
                                            $badge = '<span class="status-badge bs-live">● LIVE</span>';
                                            $btn = '<a href="take-exam.php?exam_id='.$exam->ID.'" class="btn-action btn-launch">ENTER EXAM HALL</a>';
                                        } else {
                                            $badge = '<span class="status-badge bs-closed">Expired</span>';
                                            $btn = '<button class="btn-action btn-missed">Exam Missed</button>';
                                        }
                                    }
                                    ?>
                                    <div class="exam-card">
                                        <?php echo $badge; ?>
                                        <div class="card-body">
                                            <div class="subject-name"><?php echo htmlentities($exam->SubjectFullname ?? 'General'); ?></div>
                                            <div class="exam-title"><?php echo htmlentities($exam->ExamTitle); ?></div>
                                            <div class="meta-info"><i class="ti-calendar"></i> <?php echo date("d M Y", $examTime); ?></div>
                                            <div class="meta-info"><i class="ti-time"></i> <?php echo date("h:i A", $examTime); ?> (<?php echo htmlentities($exam->Duration); ?> mins)</div>
                                        </div>
                                        <div class="card-footer"><?php echo $btn; ?></div>
                                    </div>
                                    <?php 
                                }
                            } else { 
                                echo '<div style="grid-column: 1/-1; color:#94a3b8; text-align:center; padding:50px; border:1px dashed #334155; border-radius:10px;">
                                        <i class="ti-folder" style="font-size:40px; margin-bottom:15px; display:block;"></i>
                                        No exams scheduled for your class yet.
                                      </div>'; 
                            } 
                        } catch (Exception $e) {
                            echo '<div style="color:#ef4444;">System Error: ' . $e->getMessage() . '</div>';
                        }
                    }
                    ?>
                </div>

                <div class="row">
                    <div class="col-lg-12"><div class="footer" style="text-align:center; padding:20px; color:#64748b; margin-top:40px;">2024 © VIDYAVERSE Student Portal.</div></div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php');?>