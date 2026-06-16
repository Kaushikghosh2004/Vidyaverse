<?php
session_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocasuid'] ?? '') == 0) {
    header('location:logout.php');
    exit();
}

$uid = $_SESSION['ocasuid'];
$cid = $_SESSION['ocasucid'] ?? 0; // Fallback if not set

// --- PAGINATION LOGIC ---
if (isset($_GET['page_no']) && $_GET['page_no']!="") {
    $page_no = $_GET['page_no'];
} else {
    $page_no = 1;
}

$no_of_records_per_page = 6; // Show 6 cards per page
$offset = ($page_no-1) * $no_of_records_per_page;
$previous_page = $page_no - 1;
$next_page = $page_no + 1;

// Count Total Records
$sql_count = "SELECT count(tbluploadass.ID) as total 
              FROM tbluploadass 
              JOIN tblassigment ON tbluploadass.AssId = tblassigment.ID
              WHERE tbluploadass.UserID=:uid";
$q_count = $dbh->prepare($sql_count);
$q_count->execute(['uid' => $uid]);
$total_rows = $q_count->fetchColumn();
$total_no_of_pages = ceil($total_rows / $no_of_records_per_page);

// Fetch Data
// Note: Preserved table name 'tblassigment' (typo in DB schema) based on your previous code
$sql = "SELECT 
            tblcourse.CourseName,
            tblcourse.BranchName,
            tblsubject.SubjectFullname,
            tblsubject.SubjectCode,
            tblassigment.AssignmentNumber,
            tblassigment.AssignmenttTitle,
            tblassigment.SubmissionDate as DueDate,
            tblteacher.FirstName,
            tblteacher.LastName,
            tblassigment.ID as aid,
            tbluploadass.Marks, 
            tbluploadass.SubmitDate,
            tbluploadass.Remarks
        FROM tblassigment 
        JOIN tblcourse ON tblcourse.ID = tblassigment.Cid 
        JOIN tblsubject ON tblsubject.ID = tblassigment.Sid 
        JOIN tblteacher ON tblteacher.ID = tblassigment.Tid 
        JOIN tbluploadass ON tbluploadass.AssId = tblassigment.ID 
        WHERE tbluploadass.UserID = :uid 
        ORDER BY tbluploadass.SubmitDate DESC
        LIMIT $offset, $no_of_records_per_page";

$query = $dbh->prepare($sql);
$query->execute(['uid' => $uid]);
$results = $query->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Submission History | VIDYAVERSE</title>
    
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

        /* CARD GRID */
        .history-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        /* ASSIGNMENT CARD */
        .assign-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex; flex-direction: column;
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
        .assign-num {
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
            color: #94a3b8; margin-bottom: 5px; display: block;
        }
        .assign-title {
            font-size: 18px; font-weight: 700; color: #fff; line-height: 1.3;
        }
        
        /* STATUS BADGE */
        .status-badge {
            padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;
        }
        .st-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid #f59e0b; }
        .st-graded { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid #10b981; }

        /* CARD BODY */
        .card-mid { padding: 20px; flex-grow: 1; }
        
        .info-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-size: 14px; color: #cbd5e1; }
        .info-row i { color: #64748b; width: 20px; text-align: center; }

        .marks-box {
            background: #0f172a; border-radius: 8px; padding: 15px; margin-top: 15px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .score-val { font-size: 20px; font-weight: 800; color: #fff; }
        .score-lbl { font-size: 12px; color: #64748b; text-transform: uppercase; }

        /* CARD FOOTER */
        .card-bot {
            padding: 15px 20px; background: #0f172a; border-top: 1px solid #334155;
            display: flex; justify-content: space-between; align-items: center;
        }
        .sub-date { font-size: 12px; color: #64748b; }
        
        .btn-view {
            text-decoration: none; background: #3b82f6; color: white;
            padding: 6px 15px; border-radius: 4px; font-size: 13px; font-weight: 600;
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

        .empty-state { text-align: center; padding: 60px; color: #64748b; background: #1e293b; border-radius: 16px; border: 1px dashed #334155; }

    </style>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <i class="ti-cloud-up"></i> SUBMISSION HISTORY
        </div>
        <a href="dashboard.php" class="btn-back">
            <i class="ti-arrow-left"></i> Dashboard
        </a>
    </div>

    <div class="main-content">

        <?php if($query->rowCount() > 0): ?>
            
            <div class="history-grid">
                <?php foreach($results as $row): 
                    // Determine Status
                    $isGraded = ($row->Marks != "");
                    $statusBadge = $isGraded 
                        ? '<span class="status-badge st-graded">GRADED</span>' 
                        : '<span class="status-badge st-pending">PENDING REVIEW</span>';
                    
                    $scoreDisplay = $isGraded ? $row->Marks : "--";
                ?>
                
                <div class="assign-card">
                    <div class="card-top">
                        <div>
                            <span class="assign-num"><?php echo htmlentities($row->AssignmentNumber); ?></span>
                            <div class="assign-title"><?php echo htmlentities($row->AssignmenttTitle ?? 'Assignment'); ?></div>
                        </div>
                        <?php echo $statusBadge; ?>
                    </div>

                    <div class="card-mid">
                        <div class="info-row">
                            <i class="ti-book"></i> 
                            <span><?php echo htmlentities($row->SubjectFullname); ?> (<?php echo htmlentities($row->SubjectCode); ?>)</span>
                        </div>
                        <div class="info-row">
                            <i class="ti-user"></i> 
                            <span>Prof. <?php echo htmlentities($row->FirstName . " " . $row->LastName); ?></span>
                        </div>

                        <div class="marks-box">
                            <div>
                                <div class="score-lbl">Score Obtained</div>
                                <div class="score-val"><?php echo htmlentities($scoreDisplay); ?></div>
                            </div>
                            <?php if($isGraded): ?>
                                <i class="ti-check-box" style="color:#10b981; font-size:24px;"></i>
                            <?php else: ?>
                                <i class="ti-time" style="color:#f59e0b; font-size:24px;"></i>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-bot">
                        <div class="sub-date">
                            Submitted: <?php echo date("d M Y", strtotime($row->SubmitDate)); ?>
                        </div>
                        <a href="submit-assignment.php?sid=<?php echo htmlentities($row->aid);?>" class="btn-view">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="pagination-container">
                <a href="<?php echo ($page_no <= 1) ? '#' : '?page_no='.$previous_page; ?>" 
                   class="page-btn <?php echo ($page_no <= 1) ? 'disabled' : ''; ?>">Previous</a>
                
                <span class="page-btn active"><?php echo $page_no; ?> / <?php echo $total_no_of_pages; ?></span>

                <a href="<?php echo ($page_no >= $total_no_of_pages) ? '#' : '?page_no='.$next_page; ?>" 
                   class="page-btn <?php echo ($page_no >= $total_no_of_pages) ? 'disabled' : ''; ?>">Next</a>
            </div>

        <?php else: ?>
            
            <div class="empty-state">
                <i class="ti-folder" style="font-size:40px; margin-bottom:15px; display:block;"></i>
                <h3>No Submissions Found</h3>
                <p>You haven't submitted any assignments yet.</p>
                <a href="new-assignment.php" class="btn-view" style="margin-top:10px; display:inline-block;">Go to New Assignments</a>
            </div>

        <?php endif; ?>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>

</body>
</html>