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

// --- 1. DELETE BATCH LOGIC ---
if(isset($_GET['delid'])) {
    $rid = intval($_GET['delid']);
    try {
        $sql = "DELETE FROM batches WHERE id=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_STR);
        $query->execute();
        header('location:manage-batches.php');
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Error deleting: Database dependency found.');</script>"; 
    }
}

// --- 2. ADD BATCH LOGIC ---
if(isset($_POST['submit'])) {
    $course_id = $_POST['course_id'];
    $semester = $_POST['semester'];
    $section = $_POST['section'];

    // Auto-generate name: "Sem 5 - Sec A"
    $batch_name = "Sem " . $semester . " - Sec " . $section;
    
    if(empty($course_id) || empty($semester) || empty($section)) {
        echo "<script>alert('Please select Course, Semester and Section');</script>";
    } else {
        try {
            // Check duplicates
            $checkSql = "SELECT id FROM batches WHERE batch_name=:batch_name AND CourseID=:course_id";
            $checkQuery = $dbh->prepare($checkSql);
            $checkQuery->bindParam(':batch_name', $batch_name, PDO::PARAM_STR);
            $checkQuery->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $checkQuery->execute();

            if($checkQuery->rowCount() > 0){
                echo "<script>alert('This Batch (Semester + Section) already exists for the selected course.');</script>";
            } else {
                // Insert
                $sql = "INSERT INTO batches(batch_name, CourseID) VALUES(:batch_name, :course_id)";
                $query = $dbh->prepare($sql);
                $query->bindParam(':batch_name', $batch_name, PDO::PARAM_STR);
                $query->bindParam(':course_id', $course_id, PDO::PARAM_INT);
                $query->execute();
                
                if ($dbh->lastInsertId() > 0) {
                    header('location:manage-batches.php');
                    exit;
                } else {
                    echo '<script>alert("Something went wrong. Please try again.")</script>';
                }
            }
        } catch (Exception $e) {
            echo '<script>alert("Error: ' . addslashes($e->getMessage()) . '");</script>';
        }
    }
}

// Set Page Titles for the Global Header
$pageTitle = "Manage Batches";
$pageSubTitle = "Create and organize student groups";
include('includes/header.php');
?>

<div class="container-fluid">
    
    <style>
        /* --- PAGE STYLES --- */
        :root { 
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --accent: #8b5cf6; /* Purple Theme for Batches */
            --text-muted: #94a3b8;
        }

        body { 
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc;
        }

        .manage-grid {
            display: grid; grid-template-columns: 350px 1fr; gap: 30px;
            margin-top: 30px;
        }
        @media (max-width: 992px) { .manage-grid { grid-template-columns: 1fr; } }

        /* GLASS CARD */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            height: 100%;
        }

        .card-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px; margin-bottom: 25px;
        }
        .card-title { font-size: 18px; font-weight: 700; color: #fff; margin: 0; }

        /* FORM ELEMENTS */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 8px; font-weight: 500; }
        
        .form-control { 
            width: 100%; background: rgba(15, 23, 42, 0.6);
            border: 1px solid #334155; color: #fff; 
            padding: 12px; border-radius: 12px; font-size: 14px; transition: 0.2s; 
        }
        .form-control:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 10px rgba(139, 92, 246, 0.2); }
        
        .btn-submit { 
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white; padding: 12px; border: none; 
            border-radius: 12px; font-weight: 600; cursor: pointer; 
            width: 100%; transition: 0.2s; text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4); }

        /* TABLE */
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        
        .table th { 
            text-align: left; padding: 12px 15px; 
            color: #94a3b8; font-size: 12px; text-transform: uppercase; font-weight: 600; 
        }
        .table td { 
            padding: 15px; background: rgba(30, 41, 59, 0.6); 
            color: #e2e8f0; font-size: 14px; border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .table tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; border-left: 1px solid rgba(255,255,255,0.05); }
        .table tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; border-right: 1px solid rgba(255,255,255,0.05); }
        
        .batch-badge {
            background: rgba(139, 92, 246, 0.15); color: #a78bfa;
            padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .btn-delete { 
            padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; 
            background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);
            transition: 0.2s;
        }
        .btn-delete:hover { background: #ef4444; color: white; }
    </style>

    <div class="manage-grid">
        
        <div class="grid-col-left">
            <div class="glass-card">
                <div class="card-header">
                    <h4 class="card-title">Create New Batch</h4>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label>1. Select Course/Branch</label>
                        <select class="form-control" name="course_id" required>
                            <option value="">Choose Course...</option>
                            <?php
                            try {
                                $sql_courses="SELECT * from tblcourse"; 
                                $query_courses=$dbh->prepare($sql_courses); 
                                $query_courses->execute(); 
                                $results_courses=$query_courses->fetchAll(PDO::FETCH_OBJ); 
                                foreach($results_courses as $row_course){ 
                                    $cID = $row_course->ID;
                                    $cName = $row_course->CourseName;
                                    $bName = $row_course->BranchName;
                            ?>
                            <option style="background:#1e293b; color:#fff;" value="<?php echo htmlentities($cID);?>">
                                <?php echo htmlentities($cName . " - " . $bName);?>
                            </option>
                            <?php 
                                }
                            } catch(Exception $e) {} 
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>2. Select Semester</label>
                        <select class="form-control" name="semester" required>
                            <option value="">Choose Semester...</option>
                            <?php
                            for($i=1; $i<=8; $i++) {
                                echo "<option style='background:#1e293b; color:#fff;' value='$i'>Semester $i</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>3. Select Section</label>
                        <select class="form-control" name="section" required>
                            <option value="">Choose Section...</option>
                            <option style="background:#1e293b; color:#fff;" value="A">Section A</option>
                            <option style="background:#1e293b; color:#fff;" value="B">Section B</option>
                            <option style="background:#1e293b; color:#fff;" value="C">Section C</option>
                            <option style="background:#1e293b; color:#fff;" value="D">Section D</option>
                            <option style="background:#1e293b; color:#fff;" value="E">Section E</option>
                            <option style="background:#1e293b; color:#fff;" value="Morning">Morning Shift</option>
                            <option style="background:#1e293b; color:#fff;" value="Evening">Evening Shift</option>
                        </select>
                    </div>

                    <button type="submit" name="submit" class="btn-submit">Create Batch</button>
                </form>
            </div>
        </div>

        <div class="grid-col-right">
            <div class="glass-card">
                <div class="card-header">
                    <h4 class="card-title">Existing Batches</h4>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Course (Branch)</th>
                                <th>Batch Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $sql="SELECT b.*, c.CourseName, c.BranchName from batches b JOIN tblcourse c ON b.CourseID = c.ID ORDER BY b.id DESC";
                                $query = $dbh->prepare($sql);
                                $query->execute();
                                $results=$query->fetchAll(PDO::FETCH_OBJ);
                                $cnt=1;
                                
                                if($query->rowCount() > 0) {
                                    foreach($results as $row) { 
                                        $ID = isset($row->id) ? $row->id : $row->ID;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlentities($cnt);?></td>
                                            <td style="color:#fff;"><?php echo htmlentities($row->CourseName);?> <br> <span style="font-size:12px; color:var(--text-muted);"><?php echo htmlentities($row->BranchName);?></span></td>
                                            <td>
                                                <span class="batch-badge"><?php echo htmlentities($row->batch_name);?></span>
                                            </td>
                                            <td>
                                                <a href="manage-batches.php?delid=<?php echo htmlentities($ID);?>" class="btn-delete" onclick="return confirm('Delete this batch?');">Delete</a>
                                            </td>
                                        </tr>
                                        <?php $cnt++; 
                                    }
                                } else { ?>
                                    <tr><td colspan="4" style="text-align:center; padding: 20px; color:#94a3b8;">No batches found.</td></tr>
                                <?php } 
                            } catch (Exception $e) { ?>
                                <tr><td colspan="4" style="text-align:center; padding: 20px; color: #f87171;">Error loading batches.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> 
</div>

<?php include('includes/footer.php');?>