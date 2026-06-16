<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// --- 1. START EXAM LOGIC ---
if(isset($_GET['startid'])) {
    $eid = intval($_GET['startid']);
    date_default_timezone_set('Asia/Kolkata'); 
    
    // Set ExamDate to Current Time (Makes it LIVE immediately)
    $currentTime = date("Y-m-d H:i:s");
    
    $sql = "UPDATE tblexams SET ExamDate = :now WHERE ID=:eid";
    $query = $dbh->prepare($sql);
    $query->execute([':now' => $currentTime, ':eid' => $eid]);
    
    echo "<script>window.location.href='manage-exams.php';</script>";
}

// --- 2. STOP EXAM LOGIC ---
if(isset($_GET['stopid'])) {
    $eid = intval($_GET['stopid']);
    date_default_timezone_set('Asia/Kolkata'); 
    
    // 1. Get Exam Start Time
    $stmt = $dbh->prepare("SELECT ExamDate FROM tblexams WHERE ID = :eid");
    $stmt->execute([':eid' => $eid]);
    $row = $stmt->fetch(PDO::FETCH_OBJ);
    
    if($row) {
        $startTime = strtotime($row->ExamDate);
        $currentTime = time();
        
        // 2. Calculate minutes passed since start
        // This effectively sets the duration to "Time Elapsed", ending it right now.
        $elapsedMins = floor(($currentTime - $startTime) / 60);
        if($elapsedMins < 0) $elapsedMins = 0; // Safety check
        
        $update = $dbh->prepare("UPDATE tblexams SET Duration = :dur WHERE ID=:eid");
        $update->execute([':dur' => $elapsedMins, ':eid' => $eid]);
    }
    
    echo "<script>window.location.href='manage-exams.php';</script>";
}

// --- 3. DELETE EXAM LOGIC ---
if(isset($_GET['delid'])) {
    $eid = intval($_GET['delid']);
    try {
        // Delete linked questions & sessions first to keep DB clean
        $dbh->prepare("DELETE FROM tblexam_questions WHERE ExamID=:eid")->execute([':eid'=>$eid]);
        $dbh->prepare("DELETE FROM tblexam_sessions WHERE ExamID=:eid")->execute([':eid'=>$eid]);
        // Delete the Exam
        $dbh->prepare("DELETE FROM tblexams WHERE ID=:eid")->execute([':eid'=>$eid]);
        
        echo "<script>alert('Exam Deleted Successfully'); window.location.href='manage-exams.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

include('includes/header.php');
?>

<div class="container-fluid">
    
    <style>
        /* PAGE STYLES */
        :root {
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --neon-blue: #3b82f6;
            --neon-green: #10b981;
            --neon-red: #ef4444;
            --neon-orange: #f59e0b;
        }

        body { 
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc;
        }

        /* GLASS CARD */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            margin-top: 30px;
        }

        .section-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 20px;
        }
        .header-title { font-size: 20px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }
        
        .btn-create {
            background: var(--neon-blue); color: white; text-decoration: none;
            padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600;
            display: flex; align-items: center; gap: 8px; transition: 0.2s;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .btn-create:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5); color: #fff; }

        /* TABLE */
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        
        .table th { 
            text-align: left; padding: 15px; 
            color: #94a3b8; font-size: 12px; text-transform: uppercase; font-weight: 600; 
            letter-spacing: 1px;
        }
        
        .table td { 
            padding: 15px; 
            background: rgba(30, 41, 59, 0.6); 
            color: #e2e8f0; font-size: 14px; vertical-align: middle;
            border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .table tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; border-left: 1px solid rgba(255,255,255,0.05); }
        .table tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; border-right: 1px solid rgba(255,255,255,0.05); }
        .table tr:hover td { background: rgba(59, 130, 246, 0.1); }

        /* BADGES */
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; }
        
        .status-live { 
            background: rgba(16, 185, 129, 0.15); color: var(--neon-green); 
            border: 1px solid rgba(16, 185, 129, 0.3); 
            animation: pulse 2s infinite;
        }
        .status-upcoming { background: rgba(245, 158, 11, 0.15); color: var(--neon-orange); border: 1px solid rgba(245, 158, 11, 0.3); }
        .status-closed { background: rgba(239, 68, 68, 0.15); color: var(--neon-red); border: 1px solid rgba(239, 68, 68, 0.3); }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .meta-info { font-size: 12px; color: #94a3b8; display: block; margin-top: 4px; }
        .unknown-data { color: #ef4444; font-style: italic; font-size: 11px; }
        
        /* ACTION BUTTONS */
        .btn-action { 
            padding: 8px 12px; border-radius: 8px; font-size: 16px; font-weight: 600; 
            text-decoration: none; margin-right: 5px; transition: 0.2s; display: inline-block; 
            border: 1px solid transparent;
        }
        
        /* Start Button (Green Play) */
        .btn-start { 
            background: rgba(16, 185, 129, 0.15); color: #10b981; border-color: rgba(16, 185, 129, 0.3); 
        }
        .btn-start:hover { background: #10b981; color: #fff; box-shadow: 0 0 15px rgba(16, 185, 129, 0.4); }

        /* Stop Button (Red Stop) */
        .btn-stop { 
            background: rgba(239, 68, 68, 0.15); color: #ef4444; border-color: rgba(239, 68, 68, 0.3); 
        }
        .btn-stop:hover { background: #ef4444; color: #fff; box-shadow: 0 0 15px rgba(239, 68, 68, 0.4); }

        /* Delete Button (Gray Trash) */
        .btn-delete { 
            background: rgba(148, 163, 184, 0.1); color: #94a3b8; border-color: rgba(148, 163, 184, 0.2); 
        }
        .btn-delete:hover { background: #ef4444; color: #fff; border-color: #ef4444; }

    </style>

    <div class="row">
        <div class="col-lg-12">
            <div class="glass-card">
                <div class="section-header">
                    <div>
                        <div class="header-title">Scheduled Examinations</div>
                        <div style="font-size:13px; color:#94a3b8; margin-top:5px;">Monitor & Control Exams.</div>
                    </div>
                    <a href="create-exam.php" class="btn-create"><i class="ti-plus"></i> Schedule New Exam</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Exam Title</th>
                                <th>Target Batch</th>
                                <th>Schedule</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th style="text-align:center;">Controls</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            date_default_timezone_set('Asia/Kolkata'); 
                            $current_time = time();

                            $sql = "SELECT e.*, c.CourseName, s.SubjectFullname, b.batch_name 
                                    FROM tblexams e
                                    LEFT JOIN tblcourse c ON e.CourseID = c.ID
                                    LEFT JOIN tblsubject s ON e.SubjectID = s.ID
                                    LEFT JOIN batches b ON e.BatchID = b.id 
                                    ORDER BY e.ExamDate DESC";
                            
                            $query = $dbh->prepare($sql);
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                            
                            $cnt = 1;
                            
                            if($query->rowCount() > 0) {
                                foreach($results as $row) {
                                    
                                    // A. Calculate Timings
                                    $start_ts = strtotime($row->ExamDate);
                                    $duration_min = $row->Duration;
                                    $end_ts = $start_ts + ($duration_min * 60);

                                    // B. Determine Status & Control Buttons
                                    $status_html = '';
                                    $control_btn = '';

                                    if ($current_time < $start_ts) {
                                        // UPCOMING
                                        $status_html = '<span class="badge status-upcoming"><i class="ti-time"></i> Upcoming</span>';
                                        // Button: START NOW
                                        $control_btn = '<a href="manage-exams.php?startid='.$row->ID.'" class="btn-action btn-start" title="Start Immediately" onclick="return confirm(\'Are you sure you want to START this exam right now?\')"><i class="ti-control-play"></i></a>';
                                    } 
                                    elseif ($current_time >= $start_ts && $current_time <= $end_ts) {
                                        // LIVE
                                        $status_html = '<span class="badge status-live"><i class="ti-pulse"></i> LIVE NOW</span>';
                                        // Button: STOP NOW
                                        $control_btn = '<a href="manage-exams.php?stopid='.$row->ID.'" class="btn-action btn-stop" title="Stop Exam" onclick="return confirm(\'WARNING: This will end the exam for all students immediately. Continue?\')"><i class="ti-control-stop"></i></a>';
                                    } 
                                    else {
                                        // CLOSED
                                        $status_html = '<span class="badge status-closed"><i class="ti-lock"></i> Completed</span>';
                                        // Button: RESTART (Start Again)
                                        $control_btn = '<a href="manage-exams.php?startid='.$row->ID.'" class="btn-action btn-start" title="Re-Open Exam" onclick="return confirm(\'Re-open this exam now?\')"><i class="ti-reload"></i></a>';
                                    }
                                    
                                    // C. Safe Display
                                    $title = htmlentities($row->ExamTitle ?? '');
                                    $subName = !empty($row->SubjectFullname) ? htmlentities($row->SubjectFullname) : '<span class="unknown-data">Subject Missing</span>';
                                    $batchName = ($row->BatchID == 0) ? "ALL BATCHES" : (!empty($row->batch_name) ? htmlentities($row->batch_name) : '<span class="unknown-data">Batch Deleted</span>');
                                    $courseName = htmlentities($row->CourseName ?? '');
                                    $startStr = date("d M, h:i A", $start_ts);
                                    ?>
                                    <tr>
                                        <td><?php echo $cnt;?></td>
                                        <td>
                                            <span style="color:#fff; font-weight:600; font-size:15px;"><?php echo $title;?></span><br>
                                            <span class="meta-info"><?php echo $subName;?></span>
                                        </td>
                                        <td>
                                            <span style="background:#1e293b; padding:4px 8px; border-radius:4px; font-size:12px; border:1px solid #334155; color:#3b82f6;">
                                                <?php echo $batchName;?>
                                            </span>
                                            <br>
                                            <span class="meta-info"><?php echo $courseName;?></span>
                                        </td>
                                        <td>
                                            <div style="font-size:13px; color:#94a3b8;">
                                                <?php echo $startStr;?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-weight:bold; color:#fff;"><?php echo htmlentities($duration_min);?></span> Mins
                                        </td>
                                        <td><?php echo $status_html; ?></td>
                                        <td style="text-align:center;">
                                            <?php echo $control_btn; ?>
                                            
                                            <a href="manage-exams.php?delid=<?php echo $row->ID;?>" class="btn-action btn-delete" onclick="return confirm('WARNING: Deleting this exam will remove all results. Continue?');" title="Delete Exam">
                                                <i class="ti-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php 
                                    $cnt++; 
                                }
                            } else { ?>
                                <tr><td colspan="7" style="text-align:center; padding:50px; color:#94a3b8;">No exams scheduled yet.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php');?>
</div>