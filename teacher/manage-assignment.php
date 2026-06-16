<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['ocastid'])) {
    header('location:logout.php');
    exit;
}

$tid = $_SESSION['ocastid'];

// --- DELETE LOGIC ---
if(isset($_GET['delid'])) {
    $rid = intval($_GET['delid']);
    try {
        $sql = "DELETE FROM tblassigment WHERE ID=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_STR);
        $query->execute();
        echo "<script>alert('Assignment deleted successfully.'); window.location.href = 'manage-assignment.php';</script>"; 
    } catch (Exception $e) {
        echo "<script>alert('Error deleting assignment.');</script>";
    }
}

// Pagination Logic
if (isset($_GET['page_no']) && $_GET['page_no'] != "") {
    $page_no = $_GET['page_no'];
} else {
    $page_no = 1;
}

$no_of_records_per_page = 15;
$offset = ($page_no - 1) * $no_of_records_per_page;
$previous_page = $page_no - 1;
$next_page = $page_no + 1;

// Count Total Records
$count_sql = "SELECT COUNT(*) FROM tblassigment WHERE Tid = :tid";
$count_query = $dbh->prepare($count_sql);
$count_query->execute(['tid' => $tid]);
$total_rows = $count_query->fetchColumn();
$total_no_of_pages = ceil($total_rows / $no_of_records_per_page);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Assignments | VidyaVerse</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        
        .create-btn {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white; padding: 10px 20px; border-radius: 10px;
            text-decoration: none; font-size: 13px; font-weight: 600;
            transition: 0.3s; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            display: flex; align-items: center; gap: 8px;
        }
        .create-btn:hover { transform: translateY(-2px); color: white; box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6); }

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

        /* ACTION BUTTONS (FIXED) */
        .action-group { display: flex; gap: 8px; }
        
        .btn-icon {
            width: 36px; height: 36px; border-radius: 8px; 
            display: flex; align-items: center; justify-content: center; 
            transition: 0.2s; text-decoration: none; border: 1px solid transparent;
        }
        
        /* Edit Button - Yellow */
        .btn-edit { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: rgba(245, 158, 11, 0.3); }
        .btn-edit:hover { background: #f59e0b; color: #fff; transform: translateY(-2px); }
        
        /* Delete Button - Red */
        .btn-delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.3); }
        .btn-delete:hover { background: #ef4444; color: #fff; transform: translateY(-2px); }

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
                <div class="header-title">My Posted Assignments</div>
                <a href="add-assignment.php" class="create-btn"><i class="fas fa-plus"></i> Post Assignment</a>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Course / Branch</th>
                            <th>Subject</th>
                            <th>Assignment Title</th>
                            <th>Closing Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // List assignments created by this teacher
                        $sql = "SELECT tblassigment.*, tblcourse.CourseName, tblcourse.BranchName, tblsubject.SubjectFullname 
                                FROM tblassigment 
                                JOIN tblcourse ON tblcourse.ID = tblassigment.Cid 
                                JOIN tblsubject ON tblsubject.ID = tblassigment.Sid 
                                WHERE tblassigment.Tid = :tid 
                                ORDER BY tblassigment.CreationDate DESC
                                LIMIT :offset, :limit";
                        
                        $query = $dbh->prepare($sql);
                        $query->bindValue(':tid', $tid, PDO::PARAM_INT);
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
                            <td style="color:#fff; font-weight:600;"><?php echo htmlentities($row->AssignmentNumber);?></td>
                            <td>
                                <?php echo htmlentities($row->CourseName);?>
                                <br><span style="font-size:11px; color:#64748b;"><?php echo htmlentities($row->BranchName);?></span>
                            </td>
                            <td><?php echo htmlentities($row->SubjectFullname);?></td>
                            <td style="color:#e2e8f0;"><?php echo htmlentities($row->AssignmenttTitle);?></td>
                            <td style="color:#f59e0b; font-weight:500;">
                                <?php echo date("d M Y", strtotime($row->SubmissionDate));?>
                            </td>
                            <td>
                                <div class="action-group">
                                    <a href="edit-assignment-detail.php?editid=<?php echo $row->ID;?>" class="btn-icon btn-edit" title="Edit Date/Details">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="manage-assignment.php?delid=<?php echo $row->ID;?>" class="btn-icon btn-delete" onclick="return confirm('Are you sure? This will remove the assignment for all students.');" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                                $cnt++;
                            }
                        } else { ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:30px; color:#94a3b8;">
                                    No assignments posted yet.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <?php if($total_no_of_pages > 1): ?>
            <ul class="pagination">
                <li <?php if($page_no <= 1){ echo "class='disabled'"; } ?>>
                    <a <?php if($page_no > 1){ echo "href='?page_no=$previous_page'"; } ?>>Previous</a>
                </li>
                
                <?php
                for ($counter = 1; $counter <= $total_no_of_pages; $counter++){
                    if ($counter == $page_no) {
                        echo "<li class='active'><a>$counter</a></li>";
                    } else {
                        echo "<li><a href='?page_no=$counter'>$counter</a></li>";
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