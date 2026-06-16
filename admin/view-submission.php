<?php
session_start();
include('includes/dbconnection.php');

// Security Check (Admin)
if (empty($_SESSION['admin_id'])) { header('location:logout.php'); exit; }

// ID Validation
$id = intval($_GET['id']);
if($id == 0) { header('location:dashboard.php'); exit; }

// Fetch Submission Details
$sql = "SELECT tbluploadass.*, 
               tbluser.FullName, tbluser.RollNumber, 
               tblassigment.AssignmenttTitle, tblassigment.AssigmentMarks,
               tblteacher.FirstName as T_FName, tblteacher.LastName as T_LName
        FROM tbluploadass 
        JOIN tbluser ON tbluploadass.UserID = tbluser.ID 
        JOIN tblassigment ON tbluploadass.AssId = tblassigment.ID
        JOIN tblteacher ON tblassigment.Tid = tblteacher.ID
        WHERE tbluploadass.ID = :id";

$query = $dbh->prepare($sql);
$query->execute([':id' => $id]);
$row = $query->fetch(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Submission Details | VidyaVerse</title>
    <link href="../assets/css/lib/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: 'Inter', sans-serif; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        
        .card { 
            background: #1e293b; border: 1px solid #334155; 
            border-radius: 16px; padding: 30px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
        }

        h2 { margin-top: 0; border-bottom: 1px solid #334155; padding-bottom: 15px; color: #fff; }
        
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .label { font-size: 12px; color: #94a3b8; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 5px; }
        .val { font-size: 15px; color: #e2e8f0; font-weight: 500; }
        
        .file-box {
            background: rgba(59, 130, 246, 0.1); border: 2px dashed #3b82f6;
            padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0;
        }
        .btn-dl {
            background: #3b82f6; color: white; text-decoration: none;
            padding: 10px 20px; border-radius: 6px; font-weight: 600; display: inline-block;
        }
        .btn-dl:hover { background: #2563eb; }

        .status-box { background: #0f172a; padding: 15px; border-radius: 8px; border: 1px solid #334155; }
        .btn-back { color: #94a3b8; text-decoration: none; margin-bottom: 15px; display: inline-block; }
    </style>
</head>
<body>

<div class="container">
    <a href="javascript:history.back()" class="btn-back">&larr; Back to Report</a>

    <?php if($row) { ?>
    <div class="card">
        <h2>Submission #<?php echo $row->ID; ?></h2>

        <div class="row">
            <div><span class="label">Student</span><span class="val"><?php echo htmlentities($row->FullName); ?> (<?php echo htmlentities($row->RollNumber); ?>)</span></div>
            <div><span class="label">Assignment</span><span class="val"><?php echo htmlentities($row->AssignmenttTitle); ?></span></div>
        </div>

        <div class="row">
            <div><span class="label">Assigned By</span><span class="val"><?php echo htmlentities($row->T_FName . ' ' . $row->T_LName); ?></span></div>
            <div><span class="label">Submitted On</span><span class="val"><?php echo date("d M Y, h:i A", strtotime($row->SubmitDate)); ?></span></div>
        </div>

        <div class="file-box">
            <div style="margin-bottom:10px; color:#cbd5e1;">File: <?php echo htmlentities($row->AnswerFile); ?></div>
            <a href="../user/assignanswer/<?php echo htmlentities($row->AnswerFile); ?>" target="_blank" class="btn-dl">
                <i class="ti-download"></i> View / Download File
            </a>
        </div>

        <div class="status-box">
            <span class="label">Result Status</span>
            <?php if($row->Marks != "") { ?>
                <div style="color:#10b981; font-weight:bold; font-size:18px; margin-top:5px;">
                    Score: <?php echo htmlentities($row->Marks); ?> / <?php echo htmlentities($row->AssigmentMarks); ?>
                </div>
                <div style="margin-top:10px; color:#94a3b8; font-style:italic;">
                    "<?php echo htmlentities($row->Remarks); ?>"
                </div>
            <?php } else { ?>
                <div style="color:#f59e0b; font-weight:bold; margin-top:5px;">Pending Grading</div>
            <?php } ?>
        </div>
    </div>
    <?php } else { echo "<h3 style='text-align:center; color:#ef4444;'>Record Not Found</h3>"; } ?>
</div>

</body>
</html>