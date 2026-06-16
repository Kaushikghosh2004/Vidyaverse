<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocastid'] ?? '') == 0) {
    header('location:logout.php');
    exit;
}

$tid = $_SESSION['ocastid'];

// Pagination Logic
if (isset($_GET['page_no']) && $_GET['page_no'] != "") {
    $page_no = $_GET['page_no'];
} else {
    $page_no = 1;
}

$no_of_records_per_page = 20;
$offset = ($page_no - 1) * $no_of_records_per_page;
$previous_page = $page_no - 1;
$next_page = $page_no + 1;

// --- FIX 1: ROBUST TEACHER COURSE FETCHING ---
// We fetch all columns to be safe, then check for Cid or CourseID
$course_sql = "SELECT * FROM tblteacher WHERE ID = :tid";
$c_stmt = $dbh->prepare($course_sql);
$c_stmt->execute(['tid' => $tid]);
$teacher_data = $c_stmt->fetch(PDO::FETCH_ASSOC);

$cid = 0;
if ($teacher_data) {
    // Check common column names
    if (!empty($teacher_data['Cid'])) {
        $cid = $teacher_data['Cid'];
    } elseif (!empty($teacher_data['CourseID'])) {
        $cid = $teacher_data['CourseID'];
    }
}

// --- DEBUGGING (Optional: Remove if working) ---
// if($cid == 0) { echo "Debug: No Course ID found for Teacher ID $tid. Check tblteacher columns."; exit; }

// Count Total Records based on valid CID
$total_rows = 0;
if ($cid > 0) {
    $count_sql = "SELECT COUNT(*) FROM tbluser WHERE Cid = :cid";
    $count_query = $dbh->prepare($count_sql);
    $count_query->execute(['cid' => $cid]);
    $total_rows = $count_query->fetchColumn();
}

$total_no_of_pages = ceil($total_rows / $no_of_records_per_page);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Registered Students | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc;
        }

        /* --- LAYOUT --- */
        .container { 
            padding: 40px 20px;
            max-width: 1400px; margin: 0 auto;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px; padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        .section-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 20px;
        }
        .header-title { font-size: 20px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }
        .course-badge {
            background: rgba(16, 185, 129, 0.15); color: #34d399;
            padding: 5px 15px; border-radius: 20px; font-size: 13px; font-weight: 600;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        /* TABLE */
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        
        .table th { 
            text-align: left; padding: 15px; 
            color: #94a3b8; font-size: 12px; text-transform: uppercase; font-weight: 600; 
            letter-spacing: 1px;
        }
        
        .table td { 
            padding: 15px; background: rgba(30, 41, 59, 0.6); 
            color: #e2e8f0; font-size: 13px; vertical-align: middle;
            border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .table tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; border-left: 1px solid rgba(255,255,255,0.05); }
        .table tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; border-right: 1px solid rgba(255,255,255,0.05); }
        .table tr:hover td { background: rgba(59, 130, 246, 0.1); }

        .user-avatar {
            width: 35px; height: 35px; border-radius: 50%; background: #3b82f6; 
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px; margin-right: 10px; float: left;
        }

        /* PAGINATION */
        .pagination { display: flex; list-style: none; padding: 0; margin-top: 20px; justify-content: flex-end; }
        .pagination li { margin: 0 2px; }
        .pagination li a {
            color: #94a3b8; padding: 8px 12px; text-decoration: none; border-radius: 6px;
            background: rgba(255,255,255,0.05); font-size: 13px; transition: 0.2s;
        }
        .pagination li.active a { background: #3b82f6; color: white; }
        .pagination li a:hover:not(.active) { background: rgba(255,255,255,0.1); color: #fff; }
        .pagination li.disabled a { opacity: 0.5; cursor: not-allowed; }

    </style>
</head>
<body>

    <?php include_once('includes/header.php');?>

    <div class="container">
        <div class="glass-card">
            
            <div class="section-header">
                <div class="header-title">Enrolled Students</div>
                <?php
                if ($cid > 0) {
                    $cNameSql = "SELECT CourseName, BranchName FROM tblcourse WHERE ID = :cid";
                    $cNameStmt = $dbh->prepare($cNameSql);
                    $cNameStmt->execute(['cid' => $cid]);
                    $courseInfo = $cNameStmt->fetch(PDO::FETCH_OBJ);
                    
                    if($courseInfo) {
                        echo '<div class="course-badge">'.htmlentities($courseInfo->CourseName).' ('.htmlentities($courseInfo->BranchName).')</div>';
                    }
                } else {
                    echo '<div class="course-badge" style="background:rgba(239, 68, 68, 0.15); color:#ef4444; border-color:rgba(239, 68, 68, 0.3);">No Course Assigned</div>';
                }
                ?>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Course Info</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Roll Number</th>
                            <th>Reg. Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($cid > 0) {
                            $sql = "SELECT tblcourse.ID, tblcourse.BranchName, tblcourse.CourseName, 
                                           tbluser.ID, tbluser.FullName, tbluser.MobileNumber, 
                                           tbluser.Email, tbluser.Cid, tbluser.RollNumber, tbluser.RegDate 
                                    FROM tbluser 
                                    JOIN tblcourse ON tblcourse.ID = tbluser.Cid 
                                    WHERE tbluser.Cid = :cid
                                    ORDER BY tbluser.RegDate DESC
                                    LIMIT :offset, :limit";
                            
                            $query = $dbh->prepare($sql);
                            $query->bindValue(':cid', $cid, PDO::PARAM_INT);
                            $query->bindValue(':offset', $offset, PDO::PARAM_INT);
                            $query->bindValue(':limit', $no_of_records_per_page, PDO::PARAM_INT);
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                            $cnt = 1 + $offset;
                            if($query->rowCount() > 0) {
                                foreach($results as $row) {
                        ?>
                        <tr>
                            <td><?php echo $cnt;?></td>
                            <td>
                                <div class="user-avatar"><?php echo strtoupper(substr($row->FullName, 0, 1)); ?></div>
                                <span style="font-weight:600; color:#fff; line-height:35px;"><?php echo htmlentities($row->FullName);?></span>
                            </td>
                            <td>
                                <span style="color:#cbd5e1;"><?php echo htmlentities($row->CourseName);?></span>
                                <br><span style="font-size:11px; color:#64748b;"><?php echo htmlentities($row->BranchName);?></span>
                            </td>
                            <td style="font-family:monospace; color:#94a3b8;"><?php echo htmlentities($row->MobileNumber);?></td>
                            <td><?php echo htmlentities($row->Email);?></td>
                            <td style="font-weight:700; color:#e2e8f0;"><?php echo htmlentities($row->RollNumber);?></td>
                            <td style="font-size:11px; color:#64748b;"><?php echo htmlentities($row->RegDate);?></td>
                        </tr>
                        <?php 
                                $cnt++;
                                }
                            } else { ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:30px; color:#ef4444;">
                                    No students found for this course yet.
                                </td>
                            </tr>
                        <?php } 
                        } else { ?>
                             <tr>
                                <td colspan="7" style="text-align:center; padding:30px; color:#fbbf24;">
                                    <strong>Notice:</strong> You are not currently assigned to a valid Course ID in the system. <br>
                                    Please contact the Admin to assign a course to your Teacher Profile.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_no_of_pages > 1): ?>
            <ul class="pagination">
                <li <?php if($page_no <= 1){ echo "class='disabled'"; } ?>>
                    <a <?php if($page_no > 1){ echo "href='?page_no=$previous_page'"; } ?>>Previous</a>
                </li>
                
                <?php
                if ($total_no_of_pages <= 10){
                    for ($counter = 1; $counter <= $total_no_of_pages; $counter++){
                        if ($counter == $page_no) {
                            echo "<li class='active'><a>$counter</a></li>";
                        } else {
                            echo "<li><a href='?page_no=$counter'>$counter</a></li>";
                        }
                    }
                }
                ?>
                
                <li <?php if($page_no >= $total_no_of_pages){ echo "class='disabled'"; } ?>>
                    <a <?php if($page_no < $total_no_of_pages) { echo "href='?page_no=$next_page'"; } ?>>Next</a>
                </li>
            </ul>
            <?php endif; ?>

        </div>
    </div>

    <?php include('includes/footer.php');?>

</body>
</html>