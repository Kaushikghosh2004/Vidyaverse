<?php
session_start();
error_reporting(E_ALL); // Enable errors for debugging
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['ocasuid'] ?? 0) == 0) {
    header('location:logout.php');
    exit;
} else {
    
    // --- 1. GET ASSIGNMENT ID (Fixing the mismatch) ---
    // Check if URL has 'assid' OR 'sid'
    if (isset($_GET['assid'])) {
        $sid = intval($_GET['assid']);
    } elseif (isset($_GET['sid'])) {
        $sid = intval($_GET['sid']);
    } else {
        echo "<script>alert('Error: No Assignment ID provided in URL.'); window.location.href='dashboard.php';</script>";
        exit;
    }

    // --- 2. FORM SUBMISSION HANDLING ---
    if (isset($_POST['submit'])) {
        
        $userid = $_SESSION['ocasuid'];
        $assdes = $_POST['assdes'];
        $ansfile = $_FILES["ansfile"]["name"];
        $filesize = $_FILES["ansfile"]["size"]; 

        // Extension Validation
        $extension = strtolower(pathinfo($ansfile, PATHINFO_EXTENSION));
        $allowed_extensions = array("doc", "docx", "pdf"); 

        if ($filesize > 5242880) { // 5MB
            echo "<script>alert('Error: File size exceeds 5MB limit.');</script>";
        } 
        elseif (!in_array($extension, $allowed_extensions)) { 
            echo "<script>alert('Invalid Format! Only .doc, .docx, and .pdf allowed.');</script>";
        } 
        else {
            // Upload
            $newfilename = md5($ansfile) . time() . "." . $extension;
            move_uploaded_file($_FILES["ansfile"]["tmp_name"], "assignanswer/" . $newfilename);

            $sql = "INSERT INTO tbluploadass(UserID,AssId,AssDes,AnswerFile) VALUES(:userid,:asid,:assdes,:ansfile)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':userid', $userid, PDO::PARAM_STR);
            $query->bindParam(':asid', $sid, PDO::PARAM_STR); // Use the fixed $sid
            $query->bindParam(':assdes', $assdes, PDO::PARAM_STR);
            $query->bindParam(':ansfile', $newfilename, PDO::PARAM_STR);

            $query->execute();

            if ($dbh->lastInsertId() > 0) {
                echo '<script>alert("Assignment Submitted Successfully!"); window.location.href ="assignment.php";</script>';
            } else {
                echo '<script>alert("Something went wrong. Please try again.");</script>';
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Assignment | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    
    <style>
        /* --- GIGANTIC DARK THEME --- */
        :root {
            --bg-dark: #0b1120;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --border: #334155;
            --input-bg: #0f172a;
            --success: #10b981;
            --danger: #ef4444;
        }

        * { box-sizing: border-box; }

        body { 
            background-color: var(--bg-dark); 
            color: var(--text-main); 
            font-family: 'Segoe UI', 'Roboto', sans-serif; 
            margin: 0; padding: 0; 
            min-height: 100vh;
        }

        /* HEADER */
        .top-header {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid var(--border);
            padding: 20px 40px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100;
            backdrop-filter: blur(10px);
        }
        .page-title { font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 15px; }
        .btn-back {
            color: var(--text-muted); text-decoration: none; font-size: 14px; 
            border: 1px solid var(--border); padding: 8px 20px; border-radius: 30px;
            transition: 0.3s;
        }
        .btn-back:hover { background: var(--border); color: #fff; }

        /* LAYOUT */
        .container {
            max-width: 1600px;
            margin: 40px auto;
            padding: 0 40px;
            display: grid;
            grid-template-columns: 1fr 1fr; 
            gap: 40px;
        }
        @media (max-width: 992px) { .container { grid-template-columns: 1fr; } }

        /* CARDS */
        .big-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            padding: 40px;
            height: 100%;
        }

        .card-header-custom {
            border-bottom: 1px solid var(--border);
            padding-bottom: 20px; margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: start;
        }

        h2 { margin: 0; font-size: 28px; color: #fff; }
        h3 { font-size: 18px; color: var(--text-muted); font-weight: 500; margin-bottom: 5px; }
        
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .meta-item { background: var(--input-bg); padding: 15px; border-radius: 10px; border: 1px solid var(--border); }
        .meta-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px; }
        .meta-value { font-size: 16px; font-weight: 600; color: #fff; }

        .desc-box {
            background: var(--input-bg); padding: 25px; border-radius: 12px;
            color: var(--text-muted); line-height: 1.6; border: 1px solid var(--border);
            min-height: 150px;
        }

        .file-download {
            margin-top: 20px; display: block; text-decoration: none;
            background: rgba(59, 130, 246, 0.1); color: var(--accent);
            padding: 15px; border-radius: 10px; text-align: center; font-weight: 700;
            border: 1px dashed var(--accent); transition: 0.3s;
        }
        .file-download:hover { background: var(--accent); color: #fff; }

        /* FORM */
        label { display: block; margin-bottom: 10px; color: #fff; font-weight: 600; }
        
        textarea, input[type="file"] {
            width: 100%; background: var(--input-bg); border: 1px solid var(--border);
            color: #fff; padding: 15px; border-radius: 10px; font-size: 16px;
            margin-bottom: 25px; outline: none; transition: 0.3s;
        }
        textarea:focus, input:focus { border-color: var(--accent); }

        .btn-submit {
            background: var(--accent); color: white; border: none;
            padding: 15px 40px; border-radius: 10px; font-size: 18px; font-weight: 700;
            cursor: pointer; width: 100%; transition: 0.3s;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5); }

        /* STATUS */
        .marks-badge {
            background: var(--accent); color: white; padding: 5px 15px;
            border-radius: 20px; font-size: 14px; font-weight: 700;
        }
        .status-over {
            background: rgba(239, 68, 68, 0.1); color: var(--danger);
            padding: 20px; text-align: center; border-radius: 12px; border: 1px solid var(--danger);
            font-weight: 700; font-size: 18px;
        }
        
        .submitted-card {
            background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success);
            padding: 30px; border-radius: 15px; text-align: center;
        }
        .check-icon { font-size: 50px; color: var(--success); margin-bottom: 15px; display: block; }

    </style>
</head>
<body>

    <div class="top-header">
        <div class="page-title">
            <i class="ti-write"></i> Submit Assignment
        </div>
        <a href="dashboard.php" class="btn-back"><i class="ti-arrow-left"></i> Return to Dashboard</a>
    </div>

    <?php
    // FETCH ASSIGNMENT DETAILS (Using the safely retrieved $sid)
    $sql = "SELECT tblcourse.BranchName, tblcourse.CourseName, tblsubject.SubjectFullname, tblsubject.SubjectCode, 
            tblassigment.AssignmentNumber, tblassigment.AssignmenttTitle, tblassigment.SubmissionDate, 
            tblassigment.AssignmentDescription, tblassigment.AssigmentMarks, tblassigment.AssignmentFile 
            FROM tblassigment 
            JOIN tblcourse ON tblcourse.ID=tblassigment.Cid 
            JOIN tblsubject ON tblsubject.ID=tblassigment.Sid 
            WHERE tblassigment.ID=:sid";
            
    $query = $dbh->prepare($sql);
    $query->execute([':sid' => $sid]);
    $row = $query->fetch(PDO::FETCH_OBJ);

    if($row) {
    ?>

    <div class="container">
        
        <div class="big-card">
            <div class="card-header-custom">
                <div>
                    <h2><?php echo htmlentities($row->AssignmenttTitle); ?></h2>
                    <span style="color:var(--text-muted);">Assignment #<?php echo htmlentities($row->AssignmentNumber); ?></span>
                </div>
                <div class="marks-badge"><?php echo htmlentities($row->AssigmentMarks); ?> Marks</div>
            </div>

            <div class="meta-grid">
                <div class="meta-item">
                    <span class="meta-label">Course</span>
                    <span class="meta-value"><?php echo htmlentities($row->CourseName); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Subject</span>
                    <span class="meta-value"><?php echo htmlentities($row->SubjectFullname); ?> (<?php echo htmlentities($row->SubjectCode); ?>)</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Deadline</span>
                    <span class="meta-value" style="color: var(--danger);">
                        <?php echo date("d M Y", strtotime($row->SubmissionDate)); ?>
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Total Marks</span>
                    <span class="meta-value"><?php echo htmlentities($row->AssigmentMarks); ?></span>
                </div>
            </div>

            <h3>Instructions / Description:</h3>
            <div class="desc-box">
                <?php echo htmlentities($row->AssignmentDescription); ?>
            </div>

            <?php if(!empty($row->AssignmentFile)) { ?>
                <a href="../teacher/assignmentfile/<?php echo $row->AssignmentFile; ?>" target="_blank" class="file-download">
                    <i class="ti-download"></i> Download Question Paper / Resource
                </a>
            <?php } ?>
        </div>


        <div class="big-card">
            <div class="card-header-custom">
                <h2>Your Submission</h2>
            </div>

            <?php
            // CHECK IF ALREADY SUBMITTED
            $userid = $_SESSION['ocasuid'];
            $ret = "SELECT * FROM tbluploadass WHERE UserId=:userid && AssId=:asid";
            $q2 = $dbh->prepare($ret);
            $q2->bindParam(':userid', $userid, PDO::PARAM_STR);
            $q2->bindParam(':asid', $sid, PDO::PARAM_STR);
            $q2->execute();
            $subData = $q2->fetch(PDO::FETCH_OBJ);

            if($q2->rowCount() == 0) {
                // --- NOT SUBMITTED YET ---
                
                $cdate = date('Y-m-d');
                $lldate = date("Y-m-d", strtotime($row->SubmissionDate));

                if ($cdate <= $lldate) {
            ?>
                <form method="post" enctype="multipart/form-data">
                    <label>Description / Notes (Optional)</label>
                    <textarea name="assdes" placeholder="Enter any notes for the teacher..." rows="6"></textarea>

                    <label>Upload Answer File (PDF, DOCX) - Max 5MB</label>
                    <input type="file" name="ansfile" required>
                    <p style="font-size:12px; color:var(--text-muted); margin-top:-15px; margin-bottom:20px;">
                        Allowed formats: .pdf, .doc, .docx
                    </p>

                    <button type="submit" name="submit" class="btn-submit">
                        <i class="ti-upload"></i> Submit Assignment
                    </button>
                </form>

            <?php } else { ?>
                <div class="status-over">
                    <i class="ti-lock"></i> Submission Deadline Passed
                    <div style="font-size:14px; margin-top:5px; font-weight:400; color:#fff;">
                        You can no longer submit this assignment.
                    </div>
                </div>
            <?php } 
            
            } else { ?>
                
                <div class="submitted-card">
                    <i class="ti-check-box check-icon"></i>
                    <h3 style="color:#fff;">Submitted Successfully</h3>
                    <p style="color:var(--success);">Turned in on <?php echo date("d M Y, h:i A", strtotime($subData->SubmitDate)); ?></p>
                </div>

                <div class="desc-box" style="margin-top:30px;">
                    <strong style="color:#fff;">Your Notes:</strong><br>
                    <?php echo htmlentities($subData->AssDes); ?>
                    <br><br>
                    <strong style="color:#fff;">File:</strong><br>
                    <a href="assignanswer/<?php echo $subData->AnswerFile; ?>" target="_blank" style="color:var(--accent);">View Uploaded File</a>
                </div>

                <div class="meta-grid" style="margin-top:20px;">
                    <div class="meta-item">
                        <span class="meta-label">Marks Obtained</span>
                        <span class="meta-value">
                            <?php echo ($subData->Marks == "") ? "Pending" : htmlentities($subData->Marks); ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Teacher Remarks</span>
                        <span class="meta-value">
                            <?php echo ($subData->Remarks == "") ? "Pending" : htmlentities($subData->Remarks); ?>
                        </span>
                    </div>
                </div>

            <?php } ?>

        </div>
    </div>

    <?php } else { ?>
        <div class="container" style="display:block; text-align:center; padding-top:100px;">
            <i class="ti-face-sad" style="font-size:50px; color:#94a3b8;"></i>
            <h2 style="color:#fff; margin-top:20px;">Assignment Not Found</h2>
            <p style="color:#94a3b8;">The assignment ID provided is invalid or has been deleted.</p>
        </div>
    <?php } ?>

</body>
</html>
<?php } ?>