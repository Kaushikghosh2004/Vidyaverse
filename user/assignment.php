<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocasuid'] ?? '') == 0) {
    header('location:logout.php');
    exit();
}

$uid = $_SESSION['ocasuid'];
$batch_id = 0;

// 1. Get Student's Batch/Class ID
// We assume the student's 'batch_id' corresponds to the assignment's 'Cid' (Class ID)
try {
    $stmt = $dbh->prepare("SELECT batch_id FROM tbluser WHERE ID = :uid");
    $stmt->execute(['uid' => $uid]);
    $batch_id = $stmt->fetchColumn();
} catch (Exception $e) {}

// 2. Fetch Assignments Logic
// FIX: Changed 'tblassigment.BatchID' to 'tblassigment.Cid' based on error log and previous file structure.
$sql = "SELECT 
            tblassigment.ID as aid,
            tblassigment.AssignmentNumber,
            tblassigment.AssignmenttTitle,
            tblassigment.SubmissionDate,
            tblassigment.CreationDate,
            tblcourse.CourseName,
            tblcourse.BranchName,
            tblsubject.SubjectFullname,
            tblsubject.SubjectCode,
            tblteacher.FirstName,
            tblteacher.LastName,
            tbluploadass.ID as submission_id,
            tbluploadass.Marks
        FROM tblassigment
        JOIN tblcourse ON tblassigment.Cid = tblcourse.ID
        JOIN tblsubject ON tblassigment.Sid = tblsubject.ID
        JOIN tblteacher ON tblassigment.Tid = tblteacher.ID
        LEFT JOIN tbluploadass ON tblassigment.ID = tbluploadass.AssId AND tbluploadass.UserID = :uid
        WHERE tblassigment.Cid = :batch_id
        ORDER BY tblassigment.CreationDate DESC";

$query = $dbh->prepare($sql);
$query->execute(['uid' => $uid, 'batch_id' => $batch_id]);
$results = $query->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Assignments | VIDYAVERSE</title>
    
    <link href="../assets/css/lib/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/lib/themify-icons.css" rel="stylesheet">
    <link href="../assets/css/lib/bootstrap.min.css" rel="stylesheet">

    <style>
        /* --- GLOBAL DARK THEME --- */
        * { box-sizing: border-box; }
        body { 
            background-color: #0f172a; 
            font-family: 'Segoe UI', 'Roboto', sans-serif; 
            color: #f8fafc; 
            margin: 0; padding: 0; 
            overflow-x: hidden;
        }

        /* HEADER */
        .simple-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 80px;
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px);
            z-index: 1000; display: flex; align-items: center; justify-content: space-between;
            padding: 0 40px; border-bottom: 1px solid #334155;
        }
        .header-title { font-size: 20px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px; }
        .btn-back {
            background: #334155; color: #fff; padding: 8px 20px; border-radius: 6px;
            text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 8px;
        }
        .btn-back:hover { background: #475569; color: #fff; }

        /* CONTENT */
        .main-content {
            margin-top: 80px;
            padding: 40px;
            max-width: 1400px;
            margin-left: auto; margin-right: auto;
        }

        /* GRID SYSTEM */
        .assign-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        /* CARD STYLES */
        .assign-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
            display: flex; flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .assign-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            border-color: #3b82f6;
        }

        /* CARD HEADER */
        .card-top {
            padding: 20px;
            border-bottom: 1px solid #334155;
            display: flex; justify-content: space-between; align-items: flex-start;
        }
        .assign-no {
            font-size: 11px; font-weight: 700; text-transform: uppercase; 
            color: #94a3b8; display: block; margin-bottom: 5px; letter-spacing: 1px;
        }
        .assign-name {
            font-size: 18px; font-weight: 700; color: #fff; line-height: 1.3;
        }

        /* BADGES */
        .status-badge {
            padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase;
        }
        .bdg-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid #f59e0b; }
        .bdg-submitted { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid #10b981; }

        /* CARD BODY */
        .card-mid { padding: 20px; flex-grow: 1; }
        .meta-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 14px; color: #cbd5e1; }
        .meta-row i { color: #64748b; width: 20px; text-align: center; }
        
        .due-alert {
            margin-top: 15px; padding: 10px; border-radius: 6px; background: rgba(239, 68, 68, 0.1); 
            color: #fca5a5; font-size: 13px; display: flex; align-items: center; gap: 8px;
        }

        /* CARD FOOTER */
        .card-bot {
            padding: 15px 20px; background: #0f172a; border-top: 1px solid #334155;
            display: flex; justify-content: space-between; align-items: center;
        }
        .date-info { font-size: 12px; color: #64748b; }

        .btn-action {
            text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600;
            transition: 0.2s; display: inline-block;
        }
        .btn-upload { background: #3b82f6; color: white; }
        .btn-upload:hover { background: #2563eb; color: white; }
        
        .btn-view { background: #10b981; color: white; }
        .btn-view:hover { background: #059669; color: white; }

        /* EMPTY STATE */
        .empty-state {
            grid-column: 1 / -1; text-align: center; padding: 60px;
            background: #1e293b; border-radius: 16px; border: 2px dashed #334155; color: #94a3b8;
        }
    </style>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <i class="ti-book"></i> ASSIGNMENTS
        </div>
        <a href="dashboard.php" class="btn-back">
            <i class="ti-arrow-left"></i> Dashboard
        </a>
    </div>

    <div class="main-content">
        
        <div class="assign-grid">
            <?php 
            if($query->rowCount() > 0) {
                foreach($results as $row) {
                    // Logic to check status
                    $isSubmitted = !empty($row->submission_id);
                    $statusBadge = $isSubmitted 
                        ? '<span class="status-badge bdg-submitted">SUBMITTED</span>' 
                        : '<span class="status-badge bdg-pending">PENDING</span>';
                    
                    $btnHtml = $isSubmitted
                        ? '<a href="submit-assignment.php?sid='.htmlentities($row->aid).'" class="btn-action btn-view">View Submission</a>'
                        : '<a href="submit-assignment.php?sid='.htmlentities($row->aid).'" class="btn-action btn-upload">Upload Now</a>';
            ?>
            
            <div class="assign-card">
                <div class="card-top">
                    <div>
                        <span class="assign-no"><?php echo htmlentities($row->AssignmentNumber ?? 'N/A'); ?></span>
                        <div class="assign-name"><?php echo htmlentities($row->AssignmenttTitle ?? 'Untitled Assignment'); ?></div>
                    </div>
                    <?php echo $statusBadge; ?>
                </div>

                <div class="card-mid">
                    <div class="meta-row">
                        <i class="ti-book"></i>
                        <span><?php echo htmlentities($row->SubjectFullname); ?> (<?php echo htmlentities($row->SubjectCode); ?>)</span>
                    </div>
                    <div class="meta-row">
                        <i class="ti-user"></i>
                        <span>Prof. <?php echo htmlentities($row->FirstName . " " . $row->LastName); ?></span>
                    </div>
                    <div class="meta-row">
                        <i class="ti-calendar"></i>
                        <span>Posted: <?php echo date("d M Y", strtotime($row->CreationDate)); ?></span>
                    </div>

                    <?php if(!$isSubmitted): ?>
                    <div class="due-alert">
                        <i class="ti-time"></i> Due: <?php echo date("d M Y", strtotime($row->SubmissionDate)); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card-bot">
                    <div class="date-info">
                        <?php echo htmlentities($row->CourseName); ?>
                    </div>
                    <?php echo $btnHtml; ?>
                </div>
            </div>
            <?php 
                } 
            } else { 
            ?>
                <div class="empty-state">
                    <i class="ti-folder" style="font-size: 40px; margin-bottom: 20px; display:block;"></i>
                    <h3>No Assignments Found</h3>
                    <p>There are no assignments posted for your batch yet.</p>
                </div>
            <?php } ?>
        </div>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>

</body>
</html>