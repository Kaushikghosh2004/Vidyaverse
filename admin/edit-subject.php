<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check (Using consistent session name)
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// --- UPDATE LOGIC ---
if(isset($_POST['submit'])) {
    $cid = $_POST['cid'];
    $semester = $_POST['semester']; // NEW: Capture Semester
    $sfname = $_POST['sfname'];
    $ssname = $_POST['ssname'];
    $subcode = $_POST['subcode'];
    $eid = intval($_GET['editid']);

    try {
        // SQL Updated to include Semester
        $sql = "UPDATE tblsubject SET CourseID=:cid, Semester=:semester, SubjectFullname=:sfname, SubjectShortname=:ssname, SubjectCode=:subcode WHERE ID=:eid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':cid', $cid, PDO::PARAM_INT);
        $query->bindParam(':semester', $semester, PDO::PARAM_STR);
        $query->bindParam(':sfname', $sfname, PDO::PARAM_STR);
        $query->bindParam(':ssname', $ssname, PDO::PARAM_STR);
        $query->bindParam(':subcode', $subcode, PDO::PARAM_STR);
        $query->bindParam(':eid', $eid, PDO::PARAM_INT);
        $query->execute();

        // Direct Redirect (Matches other pages)
        header('location:subject.php');
        exit;
    } catch (Exception $e) {
        echo '<script>alert("Error: ' . addslashes($e->getMessage()) . '");</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Subject | VidyaVerse</title>
    <style>
        /* GLOBAL RESET */
        * { box-sizing: border-box; }
        body { 
            background-color: #0f172a; 
            font-family: 'Inter', 'Segoe UI', sans-serif; 
            color: #f8fafc; 
            margin: 0; padding: 0; 
        }

        /* VARIABLES */
        :root { 
            --header-h: 80px; 
            --bg-dark: #0f172a; 
            --card-dark: #1e293b; 
            --accent: #8b5cf6; 
            --text-muted: #94a3b8;
        }

        /* HEADER */
        .simple-header { 
            position: fixed; top: 0; left: 0; width: 100%; height: var(--header-h); 
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); 
            z-index: 999; display: flex; align-items: center; justify-content: space-between; 
            padding: 0 40px; border-bottom: 1px solid #334155; 
        }
        .header-left .welcome-msg { font-size: 20px; font-weight: 700; color: #fff; display: block; }
        .header-left .welcome-sub { font-size: 13px; color: var(--text-muted); }
        .back-link { 
            background: #334155; color: #fff; padding: 8px 24px; border-radius: 6px; 
            text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s;
        }
        .back-link:hover { background: #475569; }

        /* LAYOUT */
        .content-wrap { margin-top: var(--header-h); padding: 40px; width: 100%; min-height: 100vh; }
        
        .manage-grid {
            display: grid;
            grid-template-columns: 1fr; /* Centered Layout for Edit */
            max-width: 800px;
            margin: 0 auto;
        }

        /* CARD */
        .card { 
            background: var(--card-dark); border: 1px solid #334155; 
            border-radius: 12px; padding: 30px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
        }
        .card-header { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #334155; }
        .card-title { font-size: 20px; font-weight: 700; color: #fff; margin: 0; }

        /* FORM */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 8px; font-weight: 500; }
        .form-control { 
            width: 100%; background: #0f172a; border: 1px solid #334155; 
            color: #fff; padding: 12px 15px; border-radius: 8px; font-size: 14px; 
        }
        .form-control:focus { outline: none; border-color: var(--accent); }
        
        .btn-submit { 
            background: var(--accent); color: white; padding: 12px 20px; 
            border: none; border-radius: 8px; font-weight: 600; cursor: pointer; 
            width: 100%; font-size: 16px; margin-top: 10px;
        }
        .btn-submit:hover { background: #7c3aed; }
        
        .footer { text-align: center; margin-top: 40px; color: var(--text-muted); font-size: 12px; }
    </style>
</head>

<body>

    <div class="simple-header">
        <div class="header-left">
            <div class="welcome-info">
                <span class="welcome-msg">Edit Subject</span>
                <span class="welcome-sub">Update subject details</span>
            </div>
        </div>
        <div class="header-right">
            <a href="subject.php" class="back-link">
                &larr; Back to List
            </a>
        </div>
    </div>

    <div class="content-wrap">
        <div class="manage-grid">
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Update Information</h4>
                </div>

                <form method="post">
                    <?php
                    $eid = intval($_GET['editid']);
                    // Fetch existing data including Semester
                    $sql = "SELECT * FROM tblsubject WHERE ID=:eid";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':eid', $eid, PDO::PARAM_INT);
                    $query->execute();
                    $result = $query->fetch(PDO::FETCH_OBJ);

                    if($query->rowCount() > 0) {
                    ?>
                        
                        <div class="form-group">
                            <label>Course Name</label>
                            <select class="form-control" name="cid" required>
                                <option value="">Select Course</option>
                                <?php
                                $sql2 = "SELECT * FROM tblcourse";
                                $query2 = $dbh->prepare($sql2);
                                $query2->execute();
                                $courses = $query2->fetchAll(PDO::FETCH_OBJ);
                                
                                foreach($courses as $row_c) {
                                    $selected = ($row_c->ID == $result->CourseID) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo htmlentities($row_c->ID);?>" <?php echo $selected; ?>>
                                        <?php echo htmlentities($row_c->CourseName);?> (<?php echo htmlentities($row_c->BranchName);?>)
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Semester</label>
                            <select class="form-control" name="semester" required>
                                <option value="">Select Semester</option>
                                <?php 
                                for($i=1; $i<=8; $i++) { 
                                    // Check if this semester matches the database value
                                    $semSelected = ($i == $result->Semester) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $i; ?>" <?php echo $semSelected; ?>>Semester <?php echo $i; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Subject Full Name</label>
                            <input type="text" class="form-control" name="sfname" value="<?php echo htmlentities($result->SubjectFullname);?>" required>
                        </div>

                        <div class="form-group">
                            <label>Subject Short Name</label>
                            <input type="text" class="form-control" name="ssname" value="<?php echo htmlentities($result->SubjectShortname);?>" required>
                        </div>

                        <div class="form-group">
                            <label>Subject Code</label>
                            <input type="text" class="form-control" name="subcode" value="<?php echo htmlentities($result->SubjectCode);?>" required>
                        </div>

                        <button type="submit" name="submit" class="btn-submit">Update Subject</button>
                    
                    <?php } else { ?>
                        <div style="text-align:center; padding:20px; color:#ef4444;">Record not found.</div>
                    <?php } ?>
                </form>
            </div>

            <div class="footer">
                <?php include('includes/footer.php'); ?>
            </div>

        </div>
    </div>

</body>
</html>