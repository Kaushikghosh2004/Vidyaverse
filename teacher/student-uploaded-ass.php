<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (empty($_SESSION['ocastid'])) { header('location:logout.php'); exit; }
$tid = $_SESSION['ocastid'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Assignment Checking Portal | VidyaVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* GLASS THEME */
        body { background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); font-family: 'Inter', sans-serif; color: #f8fafc; margin: 0; padding: 0; }
        .container { padding: 40px 20px; max-width: 1400px; margin: 0 auto; }
        .glass-card { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; padding: 30px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); }
        
        .header-title { font-size: 22px; font-weight: 700; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 25px; }

        /* ASSIGNMENT GRID */
        .assign-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        
        .assign-card {
            background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px; padding: 20px; transition: 0.3s; position: relative;
            display: flex; flex-direction: column; height: 100%;
        }
        .assign-card:hover { transform: translateY(-5px); border-color: #3b82f6; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }

        .ass-title { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 5px; }
        .ass-meta { font-size: 12px; color: #94a3b8; margin-bottom: 15px; }
        
        .stat-row { 
            display: flex; justify-content: space-between; margin-bottom: 15px; 
            background: rgba(255,255,255,0.03); padding: 10px; border-radius: 8px;
        }
        .stat-item { text-align: center; }
        .stat-val { display: block; font-weight: 700; font-size: 16px; }
        .stat-lbl { font-size: 10px; text-transform: uppercase; color: #64748b; }

        .btn-open {
            margin-top: auto; text-decoration: none; padding: 10px; border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;
            text-align: center; font-weight: 600; font-size: 13px; transition: 0.2s;
        }
        .btn-open:hover { box-shadow: 0 0 15px rgba(59, 130, 246, 0.5); color: #fff; }
    </style>
</head>
<body>
    <?php include_once('includes/header.php');?>

    <div class="container">
        <div class="glass-card">
            <div class="header-title">Assignment Checking Portal</div>
            
            <div class="assign-grid">
                <?php
                // Fetch Assignments + Count Submissions
                $sql = "SELECT a.ID, a.AssignmenttTitle, a.AssignmentNumber, a.AssigmentMarks,
                               s.SubjectFullname, c.CourseName, c.BranchName,
                               (SELECT COUNT(*) FROM tbluploadass u WHERE u.AssId = a.ID) as TotalSubs,
                               (SELECT COUNT(*) FROM tbluploadass u WHERE u.AssId = a.ID AND (u.Marks IS NULL OR u.Marks = '')) as Unchecked
                        FROM tblassigment a
                        JOIN tblsubject s ON a.Sid = s.ID
                        JOIN tblcourse c ON a.Cid = c.ID
                        WHERE a.Tid = :tid
                        ORDER BY a.CreationDate DESC";
                
                $query = $dbh->prepare($sql);
                $query->bindParam(':tid', $tid);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);

                if($query->rowCount() > 0) {
                    foreach($results as $row) {
                        $checked = $row->TotalSubs - $row->Unchecked;
                ?>
                <div class="assign-card">
                    <div class="ass-title"><?php echo htmlentities($row->AssignmenttTitle); ?></div>
                    <div class="ass-meta">
                        <?php echo htmlentities($row->SubjectFullname); ?><br>
                        <?php echo htmlentities($row->CourseName); ?> (<?php echo htmlentities($row->BranchName); ?>)
                    </div>

                    <div class="stat-row">
                        <div class="stat-item">
                            <span class="stat-val" style="color:#fff;"><?php echo $row->TotalSubs; ?></span>
                            <span class="stat-lbl">Total</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-val" style="color:#f59e0b;"><?php echo $row->Unchecked; ?></span>
                            <span class="stat-lbl">Pending</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-val" style="color:#10b981;"><?php echo $checked; ?></span>
                            <span class="stat-lbl">Checked</span>
                        </div>
                    </div>

                    <a href="view-submissions.php?assign_id=<?php echo $row->ID; ?>" class="btn-open">
                        Open Folder <i class="fas fa-folder-open ml-2"></i>
                    </a>
                </div>
                <?php 
                    }
                } else {
                    echo '<p style="color:#94a3b8; text-align:center; width:100%;">No assignments created yet.</p>';
                } 
                ?>
            </div>
        </div>
    </div>
    <?php include_once('includes/footer.php');?>
</body>
</html>