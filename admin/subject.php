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

// --- 1. ADD SUBJECT LOGIC ---
if(isset($_POST['submit'])) {
    $cid = $_POST['cid'];
    $semester = $_POST['semester']; // Capture Semester
    $sfname = $_POST['sfname'];
    $ssname = $_POST['ssname'];
    $subcode = $_POST['subcode'];

    if(empty($cid) || empty($semester) || empty($sfname) || empty($ssname) || empty($subcode)) {
        echo "<script>alert('Please fill all fields');</script>";
    } else {
        try {
            $sql = "INSERT INTO tblsubject(CourseID, Semester, SubjectFullname, SubjectShortname, SubjectCode) VALUES(:cid, :sem, :sfname, :ssname, :subcode)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':cid', $cid, PDO::PARAM_INT);
            $query->bindParam(':sem', $semester, PDO::PARAM_STR);
            $query->bindParam(':sfname', $sfname, PDO::PARAM_STR);
            $query->bindParam(':ssname', $ssname, PDO::PARAM_STR);
            $query->bindParam(':subcode', $subcode, PDO::PARAM_STR);
            $query->execute();

            $LastInsertId = $dbh->lastInsertId();
            if ($LastInsertId > 0) {
                // Direct refresh to show data
                header('location:subject.php');
                exit;
            } else {
                echo '<script>alert("Something Went Wrong. Please try again")</script>';
            }
        } catch (Exception $e) {
            echo '<script>alert("Database Error: ' . addslashes($e->getMessage()) . '");</script>';
        }
    }
}

// --- 2. DELETE SUBJECT LOGIC ---
if(isset($_GET['delid'])) {
    $rid = intval($_GET['delid']);
    try {
        $sql = "DELETE FROM tblsubject WHERE ID=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_STR);
        $query->execute();
        header('location:subject.php');
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Error deleting: Database dependency found.');</script>"; 
    }
}

// Set Page Titles
$pageTitle = "Manage Subjects";
$pageSubTitle = "Curriculum & Syllabus Configuration";
include('includes/header.php');
?>

<div class="container-fluid">
    
    <style>
        /* --- GLASS THEME STYLES --- */
        :root { 
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --accent: #3b82f6; /* Blue Theme for Subjects */
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
        .form-control:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        
        .btn-submit { 
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            color: white; padding: 12px; border: none; 
            border-radius: 12px; font-weight: 600; cursor: pointer; 
            width: 100%; transition: 0.2s; text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4); }

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
        
        .sem-badge {
            background: rgba(59, 130, 246, 0.15); color: #60a5fa;
            padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .btn-action { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 600; text-decoration: none; margin-right: 5px; }
        .btn-edit { background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3); }
        .btn-delete { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        
        .btn-edit:hover { background: #8b5cf6; color: white; }
        .btn-delete:hover { background: #ef4444; color: white; }
    </style>

    <div class="manage-grid">
        
        <div class="grid-col-left">
            <div class="glass-card">
                <div class="card-header">
                    <h4 class="card-title">Add New Subject</h4>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label>Course Name</label>
                        <select class="form-control" name="cid" required>
                            <option value="">Select Course...</option>
                            <?php
                            try {
                                $sql_courses="SELECT * from tblcourse"; 
                                $query_courses=$dbh->prepare($sql_courses); 
                                $query_courses->execute(); 
                                $results_courses=$query_courses->fetchAll(PDO::FETCH_OBJ); 
                                foreach($results_courses as $row_course){ 
                            ?>
                            <option style="background:#1e293b; color:#fff;" value="<?php echo htmlentities($row_course->ID);?>">
                                <?php echo htmlentities($row_course->CourseName);?> (<?php echo htmlentities($row_course->BranchName);?>)
                            </option>
                            <?php } } catch(Exception $e) {} ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Semester</label>
                        <select class="form-control" name="semester" required>
                            <option value="">Select Semester...</option>
                            <?php for($i=1; $i<=8; $i++) { echo "<option style='background:#1e293b; color:#fff;' value='$i'>Semester $i</option>"; } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Subject Full Name</label>
                        <input type="text" class="form-control" name="sfname" placeholder="e.g. Data Structures" required>
                    </div>
                    <div class="form-group">
                        <label>Subject Short Name</label>
                        <input type="text" class="form-control" name="ssname" placeholder="e.g. DSA" required>
                    </div>
                    <div class="form-group">
                        <label>Subject Code</label>
                        <input type="text" class="form-control" name="subcode" placeholder="e.g. CS-201" required>
                    </div>
                    <button type="submit" name="submit" class="btn-submit">Add Subject</button>
                </form>
            </div>
        </div>

        <div class="grid-col-right">
            <div class="glass-card">
                <div class="card-header">
                    <h4 class="card-title">Subject Curriculum</h4>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Course Details</th>
                                <th>Sem</th>
                                <th>Subject</th>
                                <th>Code</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $sql="SELECT tblcourse.CourseName, tblcourse.BranchName, tblsubject.SubjectFullname, tblsubject.SubjectShortname, tblsubject.SubjectCode, tblsubject.Semester, tblsubject.ID as sid 
                                      FROM tblsubject 
                                      JOIN tblcourse ON tblcourse.ID=tblsubject.CourseID 
                                      ORDER BY tblsubject.ID DESC";
                                $query = $dbh->prepare($sql);
                                $query->execute();
                                $results=$query->fetchAll(PDO::FETCH_OBJ);
                                $cnt=1;
                                
                                if($query->rowCount() > 0) {
                                    foreach($results as $row) { ?>
                                        <tr>
                                            <td><?php echo htmlentities($cnt);?></td>
                                            <td>
                                                <span style="color:#fff; font-weight:600;"><?php echo htmlentities($row->CourseName);?></span><br>
                                                <span style="font-size:12px; color:var(--text-muted);"><?php echo htmlentities($row->BranchName);?></span>
                                            </td>
                                            <td><span class="sem-badge">Sem <?php echo htmlentities($row->Semester);?></span></td>
                                            <td>
                                                <span style="color:#e2e8f0;"><?php echo htmlentities($row->SubjectFullname);?></span><br>
                                                <span style="font-size:11px; color:#94a3b8;"><?php echo htmlentities($row->SubjectShortname);?></span>
                                            </td>
                                            <td style="font-family:monospace; color:#cbd5e1;"><?php echo htmlentities($row->SubjectCode);?></td>
                                            <td>
                                                <a href="edit-subject.php?editid=<?php echo htmlentities($row->sid);?>" class="btn-action btn-edit">Edit</a>
                                                <a href="subject.php?delid=<?php echo htmlentities($row->sid);?>" class="btn-action btn-delete" onclick="return confirm('Delete subject?');">Del</a>
                                            </td>
                                        </tr>
                                        <?php $cnt++; 
                                    }
                                } else { ?>
                                    <tr><td colspan="6" style="text-align:center; padding: 20px;">No subjects found.</td></tr>
                                <?php } 
                            } catch (Exception $e) { ?>
                                <tr><td colspan="6" style="text-align:center; padding: 20px; color: #f87171;">Error loading data.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> 
</div>

<?php include('includes/footer.php');?>