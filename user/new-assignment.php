<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocasuid']==0)) { header('location:logout.php'); exit(); }
$uid = $_SESSION['ocasuid'];

// 1. Get Student Course ID
// FIX: Join tbluser with batches table to get CourseID, as tbluser only has batch_id
$stmt = $dbh->prepare("SELECT b.CourseID 
                       FROM tbluser u 
                       JOIN batches b ON u.batch_id = b.id 
                       WHERE u.ID = :uid");
$stmt->execute(['uid' => $uid]);
$course_id = $stmt->fetchColumn();

// 2. Pagination Logic
if (isset($_GET['page_no']) && $_GET['page_no']!="") {
    $page_no = $_GET['page_no'];
} else {
    $page_no = 1;
}

$no_of_records_per_page = 10;
$offset = ($page_no-1) * $no_of_records_per_page;
$previous_page = $page_no - 1;
$next_page = $page_no + 1;

// 3. Fetch Assignments (Pending Only)
// We filter by CourseID AND exclude assignments present in 'tbluploadass' for this user
$sql_count = "SELECT count(ID) as total_records FROM tblassigment 
              WHERE Cid=:cid 
              AND ID NOT IN (SELECT AssId FROM tbluploadass WHERE UserID=:uid)";
$stmt_count = $dbh->prepare($sql_count);
$stmt_count->execute(['cid' => $course_id, 'uid' => $uid]);
$total_records = $stmt_count->fetchColumn();
$total_no_of_pages = ceil($total_records / $no_of_records_per_page);

include('includes/header.php');
?>

<!-- === LAYOUT CONTAINER === -->
<div class="app-container">
    
    <!-- === CUSTOM DARK HEADER === -->
    <div class="simple-header">
        <div class="header-left">
            <a href="dashboard.php" class="back-btn">
                <i class="ti-arrow-left"></i> Back to Dashboard
            </a>
            <div class="welcome-info">
                <span class="welcome-msg">New Assignments</span>
                <span class="welcome-sub">Pending tasks for your course</span>
            </div>
        </div>
        <div class="header-right">
            <a href="logout.php" class="logout-link">
                <i class="ti-power-off"></i> <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- === MAIN CONTENT === -->
    <div class="content-wrap">
        <div class="main">
            <div class="container-fluid">
                
                <!-- Custom Styles -->
                <style>
                    /* GLOBAL RESET */
                    * { box-sizing: border-box; }
                    body { 
                        background-color: #0f172a; 
                        font-family: 'Inter', 'Segoe UI', sans-serif; 
                        margin: 0; padding: 0; 
                        overflow-x: hidden;
                        color: #f8fafc;
                    }

                    .header, .sidebar { display: none !important; }

                    /* VARIABLES */
                    :root {
                        --header-h: 80px;
                        --bg-dark: #0f172a;
                        --card-dark: #1e293b;
                        --accent: #8b5cf6; /* Purple for Assignments */
                        --text-muted: #94a3b8;
                    }

                    /* HEADER */
                    .simple-header {
                        position: fixed; top: 0; left: 0; width: 100%; height: var(--header-h);
                        background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px);
                        z-index: 999; display: flex; align-items: center; justify-content: space-between;
                        padding: 0 40px; border-bottom: 1px solid #334155;
                    }
                    .header-left { display: flex; align-items: center; gap: 20px; }
                    .welcome-info { border-left: 1px solid #334155; padding-left: 20px; }
                    .welcome-msg { font-size: 20px; font-weight: 700; color: #fff; display: block; }
                    .welcome-sub { font-size: 13px; color: var(--text-muted); }

                    /* BUTTONS */
                    .back-btn {
                        display: flex; align-items: center; gap: 8px;
                        background: rgba(255,255,255,0.05); color: #fff;
                        padding: 8px 16px; border-radius: 8px; text-decoration: none;
                        font-weight: 600; font-size: 14px; border: 1px solid #334155;
                        transition: all 0.2s;
                    }
                    .back-btn:hover { background: var(--accent); border-color: var(--accent); }

                    .logout-link {
                        background: #ef4444; color: #fff; padding: 8px 24px; border-radius: 6px;
                        text-decoration: none; font-weight: 600; font-size: 14px;
                        display: flex; align-items: center; gap: 8px; transition: 0.2s;
                    }
                    .logout-link:hover { background: #dc2626; transform: translateY(-1px); }

                    /* CONTENT */
                    .content-wrap {
                        margin-top: var(--header-h); padding: 40px; width: 100%; min-height: 100vh;
                    }

                    /* CARD & TABLE */
                    .card {
                        background: var(--card-dark); border: 1px solid #334155;
                        border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
                    }
                    .card-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #334155; }
                    .card-title { font-size: 18px; font-weight: 700; color: #fff; margin: 0; }

                    .table-responsive { overflow-x: auto; }
                    .table { width: 100%; border-collapse: collapse; }
                    .table th { 
                        text-align: left; padding: 15px; background: rgba(0,0,0,0.2); 
                        color: #cbd5e1; font-size: 12px; text-transform: uppercase; font-weight: 600; 
                        border-bottom: 1px solid #334155; 
                    }
                    .table td { 
                        padding: 15px; border-bottom: 1px solid #334155; 
                        color: var(--text-muted); font-size: 14px; 
                    }
                    .table tr:hover td { background: rgba(255,255,255,0.02); color: #fff; }

                    /* ACTIONS */
                    .btn-submit {
                        background: var(--accent); color: white; padding: 6px 16px; border-radius: 6px;
                        text-decoration: none; font-weight: 600; font-size: 13px; display: inline-block;
                    }
                    .btn-submit:hover { background: #7c3aed; }
                    
                    .status-expired {
                        color: #ef4444; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 5px;
                    }

                    /* PAGINATION */
                    .pagination { display: flex; gap: 5px; list-style: none; padding: 0; margin-top: 20px; justify-content: center; }
                    .pagination li a {
                        padding: 8px 14px; border-radius: 6px; background: #0f172a; border: 1px solid #334155;
                        color: var(--text-muted); text-decoration: none; font-size: 14px;
                    }
                    .pagination li.active a, .pagination li a:hover {
                        background: var(--accent); color: white; border-color: var(--accent);
                    }
                    .pagination li.disabled a { opacity: 0.5; pointer-events: none; }
                </style>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Pending Assignments List</h4>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Subject</th>
                                            <th>Assignment Title</th>
                                            <th>Teacher</th>
                                            <th>Post Date</th>
                                            <th>Deadline</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetch Assignments Details
                                        // Note: Assuming table names from your previous code: tblassigment, tblsubject, tblteacher
                                        $sql = "SELECT tblassigment.ID as aid, tblassigment.AssignmentNumber, tblassigment.AssignmenttTitle, 
                                                tblassigment.SubmissionDate, tblassigment.CreationDate, 
                                                tblsubject.SubjectFullname, tblsubject.SubjectCode,
                                                tblteacher.FirstName, tblteacher.LastName
                                                FROM tblassigment 
                                                JOIN tblsubject ON tblsubject.ID = tblassigment.Sid 
                                                JOIN tblteacher ON tblteacher.ID = tblassigment.Tid 
                                                WHERE tblassigment.Cid = :cid
                                                AND tblassigment.ID NOT IN (SELECT AssId FROM tbluploadass WHERE UserID = :uid)
                                                ORDER BY tblassigment.CreationDate DESC 
                                                LIMIT $offset, $no_of_records_per_page";
                                        
                                        $query = $dbh->prepare($sql);
                                        $query->execute(['cid' => $course_id, 'uid' => $uid]);
                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                        
                                        $cnt = 1 + $offset;
                                        
                                        if($query->rowCount() > 0) {
                                            foreach($results as $row) { 
                                                $submitDate = $row->SubmissionDate;
                                                $isExpired = (date('Y-m-d') > $submitDate);
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlentities($cnt);?></td>
                                                    <td style="color: #fff; font-weight: 600;">
                                                        <?php echo htmlentities($row->SubjectFullname);?> 
                                                        <span style="color: #64748b; font-size: 12px;">(<?php echo htmlentities($row->SubjectCode);?>)</span>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlentities($row->AssignmenttTitle);?><br>
                                                        <span style="font-size: 12px; color: #64748b;">#<?php echo htmlentities($row->AssignmentNumber);?></span>
                                                    </td>
                                                    <td><?php echo htmlentities($row->FirstName . " " . $row->LastName);?></td>
                                                    <td><?php echo htmlentities($row->CreationDate);?></td>
                                                    <td style="<?php echo $isExpired ? 'color:#ef4444' : 'color:#10b981'; ?>">
                                                        <?php echo htmlentities($submitDate);?>
                                                    </td>
                                                    <td>
                                                        <?php if(!$isExpired) { ?>
                                                            <a href="submit-assignment.php?assid=<?php echo htmlentities($row->aid);?>" class="btn-submit">
                                                                <i class="ti-upload"></i> Submit
                                                            </a>
                                                        <?php } else { ?>
                                                            <span class="status-expired"><i class="ti-close"></i> Expired</span>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                                <?php $cnt++; 
                                            }
                                        } else { ?>
                                            <tr>
                                                <td colspan="7" style="text-align:center; padding: 40px;">
                                                    <i class="ti-check-box" style="font-size: 30px; color: #10b981; margin-bottom: 10px; display:block;"></i>
                                                    Great job! No pending assignments.
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if($total_no_of_pages > 1) { ?>
                            <ul class="pagination">
                                <li class="<?php if($page_no <= 1){ echo 'disabled'; } ?>">
                                    <a href="<?php if($page_no > 1){ echo "?page_no=".($page_no-1); } ?>">Prev</a>
                                </li>
                                <?php for ($counter = 1; $counter <= $total_no_of_pages; $counter++) { ?>
                                    <li class="<?php if($page_no == $counter){ echo 'active'; } ?>">
                                        <a href="?page_no=<?php echo $counter; ?>"><?php echo $counter; ?></a>
                                    </li>
                                <?php } ?>
                                <li class="<?php if($page_no >= $total_no_of_pages){ echo 'disabled'; } ?>">
                                    <a href="<?php if($page_no < $total_no_of_pages) { echo "?page_no=".($page_no+1); } ?>">Next</a>
                                </li>
                            </ul>
                            <?php } ?>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12"><div class="footer"><p>2024 © VIDYAVERSE Student Portal.</p></div></div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php');?>