<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['ocastid'])) {
    header('location:logout.php');
    exit;
}

// --- HANDLE GRADE SUBMISSION ---
if (isset($_POST['submit_grade'])) {
    $sub_id = $_GET['id'];
    $marks = $_POST['marks'];
    $remarks = $_POST['remarks'];

    $sql = "UPDATE tbluploadass SET Marks=:marks, Remarks=:remarks WHERE ID=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':marks', $marks, PDO::PARAM_STR);
    $query->bindParam(':remarks', $remarks, PDO::PARAM_STR);
    $query->bindParam(':id', $sub_id, PDO::PARAM_STR);
    $query->execute();

    echo "<script>alert('Grade Saved Successfully!'); window.location.href='grading-queue.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Assignment | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    
    <style>
        /* --- GIGANTIC DARK THEME --- */
        :root {
            --bg-dark: #0b1120;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --purple: #8b5cf6;
            --border: #334155;
            --input-bg: #0f172a;
        }

        body { 
            background-color: var(--bg-dark); 
            color: var(--text-main); 
            font-family: 'Segoe UI', sans-serif; 
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

        /* CONTAINER & LAYOUT */
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
            display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px;
        }

        /* CARD STYLES */
        .big-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        h2 { margin: 0 0 20px 0; color: #fff; font-size: 24px; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .meta-box { background: var(--input-bg); padding: 15px; border-radius: 10px; flex: 1; margin-right: 15px; border: 1px solid var(--border); }
        .meta-box:last-child { margin-right: 0; }
        .meta-lbl { font-size: 12px; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 5px; }
        .meta-val { font-size: 16px; font-weight: 700; color: #fff; }

        .desc-area {
            background: var(--input-bg); padding: 25px; border-radius: 10px;
            color: var(--text-muted); line-height: 1.6; border: 1px solid var(--border);
            min-height: 100px; margin-bottom: 30px;
        }

        /* FILE BUTTON */
        .btn-file {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            background: rgba(139, 92, 246, 0.1); color: var(--purple);
            border: 1px dashed var(--purple); padding: 20px; border-radius: 12px;
            text-decoration: none; font-weight: 700; font-size: 16px; transition: 0.3s;
        }
        .btn-file:hover { background: var(--purple); color: #fff; }

        /* FORM ELEMENTS */
        label { display: block; margin-bottom: 10px; color: #fff; font-weight: 600; margin-top: 20px; }
        
        input[type="number"], textarea {
            width: 100%; background: var(--input-bg); border: 1px solid var(--border);
            color: #fff; padding: 15px; border-radius: 10px; font-size: 16px;
            outline: none; transition: 0.3s;
        }
        input:focus, textarea:focus { border-color: var(--accent); }

        .btn-submit {
            background: var(--accent); color: white; border: none;
            padding: 15px 40px; border-radius: 10px; font-size: 18px; font-weight: 700;
            cursor: pointer; width: 100%; margin-top: 30px; transition: 0.3s;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5); }

    </style>
</head>
<body>

    <div class="top-header">
        <div class="page-title"><i class="ti-eye"></i> View Assignment</div>
        <a href="grading-queue.php" class="btn-back"><i class="ti-arrow-left"></i> Back to Queue</a>
    </div>

    <?php
    if(isset($_GET['id'])) {
        $sid = intval($_GET['id']);
        
        // Fetch Submission + Student Info + Assignment Title
        $sql = "SELECT tbluploadass.*, tbluser.FullName, tbluser.RollNumber, tblassigment.AssignmenttTitle, tblassigment.AssigmentMarks 
                FROM tbluploadass 
                JOIN tbluser ON tbluploadass.UserID = tbluser.ID 
                JOIN tblassigment ON tbluploadass.AssId = tblassigment.ID
                WHERE tbluploadass.ID = :id";
        
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $sid, PDO::PARAM_STR);
        $query->execute();
        $row = $query->fetch(PDO::FETCH_OBJ);

        if($row) {
    ?>

    <div class="container">
        
        <div class="big-card">
            <h2>Student Submission</h2>
            
            <div class="meta-row">
                <div class="meta-box">
                    <span class="meta-lbl">Student Name</span>
                    <span class="meta-val"><?php echo htmlentities($row->FullName); ?></span>
                </div>
                <div class="meta-box">
                    <span class="meta-lbl">Roll Number</span>
                    <span class="meta-val"><?php echo htmlentities($row->RollNumber); ?></span>
                </div>
            </div>

            <div class="meta-row">
                <div class="meta-box">
                    <span class="meta-lbl">Assignment Title</span>
                    <span class="meta-val" style="color:var(--purple);"><?php echo htmlentities($row->AssignmenttTitle); ?></span>
                </div>
                <div class="meta-box">
                    <span class="meta-lbl">Submitted On</span>
                    <span class="meta-val"><?php echo date("d M Y, h:i A", strtotime($row->SubmitDate)); ?></span>
                </div>
            </div>

            <label style="margin-top:0;">Student's Notes / Description:</label>
            <div class="desc-area">
                <?php echo !empty($row->AssDes) ? htmlentities($row->AssDes) : "No description provided by student."; ?>
            </div>

            <label>Attached File:</label>
            <a href="../user/assignanswer/<?php echo htmlentities($row->AnswerFile); ?>" target="_blank" class="btn-file">
                <i class="ti-download"></i> Download / View Answer File
            </a>
        </div>

        <div class="big-card">
            <h2>Evaluation</h2>
            <form method="post">
                
                <label>Marks (Max: <?php echo htmlentities($row->AssigmentMarks); ?>)</label>
                <input type="number" name="marks" value="<?php echo htmlentities($row->Marks); ?>" max="<?php echo htmlentities($row->AssigmentMarks); ?>" placeholder="Enter Marks" required>

                <label>Teacher's Remarks</label>
                <textarea name="remarks" rows="6" placeholder="Write your feedback here..."><?php echo htmlentities($row->Remarks); ?></textarea>

                <button type="submit" name="submit_grade" class="btn-submit">
                    <i class="ti-check"></i> Save Grade
                </button>

            </form>
        </div>

    </div>

    <?php 
        } else {
            echo "<div style='text-align:center; padding:100px; color:#94a3b8;'><h2>Submission Not Found</h2></div>";
        }
    } else {
        echo "<div style='text-align:center; padding:100px; color:#94a3b8;'><h2>Invalid Request</h2></div>";
    } 
    ?>

</body>
</html>