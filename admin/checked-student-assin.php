<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// GET DATES or Subject ID (Logic handles both from previous context, here assumes Subject ID based on previous file flow)
$sid = $_POST['sid'] ?? '';

// If accessed directly without POST, handle gracefully
if(empty($sid) && isset($_GET['sid'])) {
    $sid = $_GET['sid'];
}

// --- PAGINATION LOGIC ---
if (isset($_GET['page_no']) && $_GET['page_no']!="") {
    $page_no = $_GET['page_no'];
} else {
    $page_no = 1;
}

$no_of_records_per_page = 6;
$offset = ($page_no-1) * $no_of_records_per_page;
$previous_page = $page_no - 1;
$next_page = $page_no + 1;

// Count Total for Pagination
$count_sql = "SELECT count(tbluploadass.ID) as total 
              FROM tbluploadass 
              JOIN tblassigment ON tbluploadass.AssId = tblassigment.ID
              WHERE tblassigment.Sid = :sid AND tbluploadass.Marks IS NOT NULL";
$q_count = $dbh->prepare($count_sql);
$q_count->execute(['sid' => $sid]);
$total_rows = $q_count->fetchColumn();
$total_no_of_pages = ceil($total_rows / $no_of_records_per_page);

// Fetch Data
$sql = "SELECT 
            tblcourse.CourseName,
            tblcourse.BranchName,
            tblsubject.SubjectFullname,
            tblsubject.SubjectCode,
            tblassigment.AssignmentNumber,
            tblassigment.AssignmenttTitle,
            tblteacher.FirstName,
            tblteacher.LastName,
            tbluploadass.ID as upid,
            tbluploadass.SubmitDate,
            tbluploadass.Marks,
            tbluser.FullName as StudentName,
            tbluser.RollNumber,
            tbluser.ID as uid,
            tblassigment.ID as assinid
        FROM tblassigment 
        JOIN tblcourse ON tblcourse.ID = tblassigment.Cid 
        JOIN tblsubject ON tblsubject.ID = tblassigment.Sid 
        JOIN tblteacher ON tblteacher.ID = tblassigment.Tid 
        JOIN tbluploadass ON tblassigment.ID = tbluploadass.AssId 
        JOIN tbluser ON tbluploadass.UserID = tbluser.ID 
        WHERE tblassigment.Sid = :sid AND tbluploadass.Marks IS NOT NULL
        ORDER BY tbluploadass.SubmitDate DESC
        LIMIT $offset, $no_of_records_per_page";

$query = $dbh->prepare($sql);
$query->execute(['sid' => $sid]);
$results = $query->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checked Assignments Report | VIDYAVERSE</title>
    
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

        /* REPORT HEADER */
        .report-info {
            background: #1e293b; border-left: 5px solid #3b82f6;
            padding: 20px; border-radius: 8px; margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .report-title { font-size: 18px; font-weight: 700; color: #fff; margin: 0; }
        .report-sub { color: #94a3b8; font-size: 14px; }

        /* CARD GRID */
        .result-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        /* RESULT CARD */
        .res-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
            display: flex; flex-direction: column;
            transition: transform 0.2s;
        }
        .res-card:hover { transform: translateY(-3px); border-color: #3b82f6; }

        /* CARD HEADER */
        .card-top {
            padding: 20px; border-bottom: 1px solid #334155;
            display: flex; justify-content: space-between; align-items: center;
        }
        .student-name { font-size: 16px; font-weight: 700; color: #fff; }
        .student-roll { font-size: 12px; color: #94a3b8; font-family: monospace; letter-spacing: 1px; }

        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .st-graded { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid #10b981; }

        /* CARD BODY */
        .card-mid { padding: 20px; flex-grow: 1; }
        
        .info-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 13px; color: #cbd5e1; }
        .info-row i { color: #64748b; width: 18px; text-align: center; }
        
        .assign-title { font-size: 15px; font-weight: 600; color: #fff; margin-bottom: 5px; }

        /* CARD FOOTER */
        .card-bot {
            padding: 15px 20px; background: #0f172a; border-top: 1px solid #334155;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .btn-view {
            text-decoration: none; background: #3b82f6; color: white;
            padding: 6px 15px; border-radius: 4px; font-size: 12px; font-weight: 600;
            transition: 0.2s;
        }
        .btn-view:hover { background: #2563eb; color: white; }

        /* PAGINATION */
        .pagination-container { margin-top: 40px; display: flex; justify-content: center; gap: 10px; }
        .page-btn {
            background: #1e293b; color: #fff; padding: 8px 15px; border-radius: 6px;
            text-decoration: none; border: 1px solid #334155; font-size: 14px;
        }
        .page-btn:hover { background: #334155; }
        .page-btn.active { background: #3b82f6; border-color: #3b82f6; }
        .page-btn.disabled { opacity: 0.5; pointer-events: none; }

        .empty-state { text-align: center; padding: 60px; color: #64748b; background: #1e293b; border-radius: 16px; border: 1px dashed #334155; grid-column: 1 / -1; }

    </style>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <i class="ti-check-box"></i> CHECKED ASSIGNMENTS
        </div>
        <a href="checked-student-assin.php" class="btn-back">
            <i class="ti-arrow-left"></i> Back to Filter
        </a>
    </div>

    <div class="main-content">
        
        <div class="report-info">
            <h3 class="report-title">Graded Submissions Report</h3>
            <span class="report-sub">Viewing checked assignments for selected subject</span>
        </div>

        <div class="result-grid">
            <?php 
            if($query->rowCount() > 0) {
                foreach($results as $row) {
                    $badge = '<span class="status-badge st-graded">CHECKED</span>';
            ?>
            
            <div class="res-card">
                <div class="card-top">
                    <div>
                        <div class="student-name"><?php echo htmlentities($row->StudentName); ?></div>
                        <span class="student-roll"><?php echo htmlentities($row->RollNumber); ?></span>
                    </div>
                    <?php echo $badge; ?>
                </div>

                <div class="card-mid">
                    <div class="assign-title"><?php echo htmlentities($row->AssignmenttTitle); ?></div>
                    <div class="info-row">
                        <i class="ti-book"></i> 
                        <span><?php echo htmlentities($row->SubjectFullname); ?> (<?php echo htmlentities($row->SubjectCode); ?>)</span>
                    </div>
                    <div class="info-row">
                        <i class="ti-id-badge"></i> 
                        <span><?php echo htmlentities($row->CourseName); ?></span>
                    </div>
                    <div class="info-row">
                        <i class="ti-calendar"></i> 
                        <span>Submitted: <?php echo date("d M Y", strtotime($row->SubmitDate)); ?></span>
                    </div>
                </div>

                <div class="card-bot">
                    <span style="font-size:12px; color:#64748b;">By: <?php echo htmlentities($row->FirstName . " " . $row->LastName); ?></span>
                    <a href="submit-assignment.php?assinid=<?php echo htmlentities($row->assinid);?>&uid=<?php echo htmlentities($row->uid);?>" class="btn-view">View Details</a> 
                </div>
            </div>
            <?php 
                }
            } else { 
            ?>
                <div class="empty-state">
                    <i class="ti-search" style="font-size: 40px; margin-bottom: 20px; display:block;"></i>
                    <h3>No Records Found</h3>
                    <p>No checked assignments found for this subject.</p>
                </div>
            <?php } ?>
        </div>

        <?php if($total_no_of_pages > 1): ?>
        <div class="pagination-container">
            <span style="color:#64748b; font-size:12px;">Showing Page <?php echo $page_no; ?> of <?php echo $total_no_of_pages; ?></span>
        </div>
        <?php endif; ?>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>

</body>
</html>