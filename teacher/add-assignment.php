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

// --- ADD ASSIGNMENT LOGIC ---
if(isset($_POST['submit'])) {
    $tid = $_SESSION['ocastid'];
    $cid = $_POST['cid'];
    $subdata = $_POST['sid']; // "SubjectID-SubjectCode"
    
    // Parse Subject Data
    $data = explode("-", $subdata);
    $sid = $data[0];
    $subcode = isset($data[1]) ? $data[1] : 'GEN';

    $asstitle = $_POST['asstitle'];
    $assdesc = $_POST['assdesc'];
    $lsdate = $_POST['lsdate'];
    $assmarks = $_POST['assmarks'];
    
    // Generate Assignment Number
    $assignno = mt_rand(10000, 99999);
    $asgnnumber = $subcode . "-" . $assignno;

    // File Upload
    $file = $_FILES["assfile"]["name"];
    $file_final_name = "";

    if(!empty($file)) {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $allowed_extensions = array("doc", "docx", "pdf", "png", "jpg", "jpeg");
        
        if(!in_array($extension, $allowed_extensions)) {
            echo "<script>alert('Invalid file format. Only doc, docx, pdf, png, jpg allowed.');</script>";
        } else {
            $file_final_name = md5($file) . time() . "." . $extension;
            move_uploaded_file($_FILES["assfile"]["tmp_name"], "assignmentfile/" . $file_final_name);
        }
    }

    // Insert into DB
    try {
        $sql = "INSERT INTO tblassigment(Tid, Cid, Sid, AssignmentNumber, AssignmenttTitle, AssignmentDescription, SubmissionDate, AssigmentMarks, AssignmentFile) 
                VALUES(:tid, :cid, :sid, :asgnnumber, :asstitle, :assdesc, :lsdate, :assmarks, :file)";
        
        $query = $dbh->prepare($sql);
        $query->bindParam(':tid', $tid);
        $query->bindParam(':cid', $cid);
        $query->bindParam(':sid', $sid);
        $query->bindParam(':asgnnumber', $asgnnumber);
        $query->bindParam(':asstitle', $asstitle);
        $query->bindParam(':assdesc', $assdesc);
        $query->bindParam(':lsdate', $lsdate);
        $query->bindParam(':assmarks', $assmarks);
        $query->bindParam(':file', $file_final_name);
        
        $query->execute();
        $lastInsertId = $dbh->lastInsertId();

        if ($lastInsertId > 0) {
            echo '<script>alert("Assignment created successfully.")</script>';
            echo "<script>window.location.href ='add-assignment.php'</script>";
        } else {
            echo '<script>alert("Something went wrong. Please try again.")</script>';
        }
    } catch (Exception $e) {
        echo '<script>alert("Error: ' . addslashes($e->getMessage()) . '")</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Assignment | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc;
            /* Header height offset handled by global header CSS */
        }

        /* --- LAYOUT --- */
        .container { max-width: 900px; margin: 0 auto; padding: 40px 20px; }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px; padding: 40px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        .section-label {
            font-size: 14px; text-transform: uppercase; letter-spacing: 2px;
            color: #3b82f6; margin-bottom: 25px; font-weight: 700;
            border-bottom: 1px solid rgba(59, 130, 246, 0.3); padding-bottom: 10px;
        }

        /* --- FORM ELEMENTS --- */
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 8px; font-weight: 500; }

        .modern-input {
            width: 100%; background: rgba(15, 23, 42, 0.8);
            border: 1px solid #334155; color: #fff;
            padding: 14px; border-radius: 12px; font-size: 14px; transition: 0.3s;
        }
        .modern-input:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        
        textarea.modern-input { resize: vertical; min-height: 120px; }

        /* Dropdown Fix */
        select.modern-input {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat; background-position: right 15px center; background-size: 16px;
            appearance: none; color: #fff !important;
        }
        select.modern-input option { background-color: #1e293b; color: #fff; padding: 10px; }

        .btn-glow {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white; border: none; width: 100%; padding: 16px;
            border-radius: 12px; font-size: 16px; font-weight: 700;
            letter-spacing: 1px; cursor: pointer; text-transform: uppercase;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4); margin-top: 20px;
            transition: 0.3s;
        }
        .btn-glow:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(139, 92, 246, 0.6); }

        .btn-reset {
            background: rgba(255, 255, 255, 0.05); color: #94a3b8;
            border: 1px solid #334155; width: 100%; padding: 16px;
            border-radius: 12px; font-size: 14px; font-weight: 600;
            cursor: pointer; margin-top: 15px; transition: 0.3s;
        }
        .btn-reset:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }
    </style>
</head>
<body>

    <?php include_once('includes/header.php');?>

    <div class="container">
        <div class="glass-card">
            
            <div class="section-label">Create New Assignment</div>

            <form method="post" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>Course Name</label>
                    <select class="modern-input" name="cid" required>
                        <option value="">Select Course...</option>
                        <?php
                        $tid = $_SESSION['ocastid'];
                        
                        // NEW: Join via tblteacher_subjects -> tblsubject -> tblcourse
                        // DISTINCT avoids duplicates if teacher has multiple subjects in the same course
                        $sql = "SELECT DISTINCT c.ID as cid, c.BranchName, c.CourseName 
                                FROM tblteacher_subjects ts
                                JOIN tblsubject s ON ts.SubjectID = s.ID
                                JOIN tblcourse c ON s.CourseID = c.ID
                                WHERE ts.TeacherID = :tid";
                        
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':tid', $tid);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                        if($query->rowCount() > 0) {
                            foreach($results as $row) {
                                echo "<option value='".$row->cid."'>".htmlentities($row->CourseName)." (".htmlentities($row->BranchName).")</option>";
                            }
                        } else {
                            // Fallback: Check old single column method just in case
                            $sqlFallback = "SELECT c.ID as cid, c.BranchName, c.CourseName 
                                            FROM tblteacher t 
                                            JOIN tblcourse c ON c.ID = t.CourseID 
                                            WHERE t.ID = :tid";
                            $qFallback = $dbh->prepare($sqlFallback);
                            $qFallback->bindParam(':tid', $tid);
                            $qFallback->execute();
                            if($qFallback->rowCount() > 0) {
                                $resFallback = $qFallback->fetchAll(PDO::FETCH_OBJ);
                                foreach($resFallback as $row) {
                                    echo "<option value='".$row->cid."'>".htmlentities($row->CourseName)." (".htmlentities($row->BranchName).")</option>";
                                }
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Subject</label>
                    <select class="modern-input" name="sid" required>
                        <option value="">Select Subject...</option>
                        <?php
                        // Fetch subjects explicitly assigned to this teacher
                        $sqlSub = "SELECT s.ID, s.SubjectFullname, s.SubjectCode 
                                   FROM tblteacher_subjects ts
                                   JOIN tblsubject s ON ts.SubjectID = s.ID
                                   WHERE ts.TeacherID = :tid";
                        $qSub = $dbh->prepare($sqlSub);
                        $qSub->bindParam(':tid', $tid);
                        $qSub->execute();
                        $subResults = $qSub->fetchAll(PDO::FETCH_OBJ);
                        
                        if($qSub->rowCount() > 0) {
                            foreach($subResults as $sub) {
                                echo "<option value='".$sub->ID."-".$sub->SubjectCode."'>".htmlentities($sub->SubjectFullname)."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="row" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="form-group">
                        <label>Assignment Title</label>
                        <input type="text" name="asstitle" class="modern-input" placeholder="e.g. Lab Report 1" required>
                    </div>
                    <div class="form-group">
                        <label>Total Marks</label>
                        <input type="number" name="assmarks" class="modern-input" placeholder="e.g. 20" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description / Instructions</label>
                    <textarea name="assdesc" class="modern-input" placeholder="Enter detailed instructions here..." required></textarea>
                </div>

                <div class="row" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="form-group">
                        <label>Submission Deadline</label>
                        <input type="date" name="lsdate" class="modern-input" required>
                    </div>
                    <div class="form-group">
                        <label>Upload File (PDF/Doc/Img) - Optional</label>
                        <input type="file" name="assfile" class="modern-input" style="padding-top:10px;">
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-glow">Post Assignment</button>
                <button type="reset" class="btn-reset">Reset Form</button>

            </form>
        </div>
    </div>

    <?php include('includes/footer.php');?>

</body>
</html>