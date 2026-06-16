<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocastid'] ?? '') == 0) {
    header('location:logout.php');
    exit;
}

$tid = $_SESSION['ocastid'];

// --- FETCH PENDING SUBMISSIONS ---
$sqlPending = "SELECT u.ID as UploadID, u.SubmitDate, u.AnswerFile, 
                      a.AssignmentNumber, a.AssignmenttTitle, 
                      usr.FullName, usr.RollNumber, c.CourseName, c.BranchName, s.SubjectFullname
               FROM tbluploadass u
               JOIN tblassigment a ON u.AssId = a.ID
               JOIN tbluser usr ON u.UserID = usr.ID
               JOIN tblcourse c ON a.Cid = c.ID
               JOIN tblsubject s ON a.Sid = s.ID
               WHERE a.Tid = :tid AND (u.Marks IS NULL OR u.Marks = '')
               ORDER BY u.SubmitDate ASC";
$qPending = $dbh->prepare($sqlPending);
$qPending->execute(['tid' => $tid]);
$pending_list = $qPending->fetchAll(PDO::FETCH_OBJ);

// --- FETCH CHECKED SUBMISSIONS ---
$sqlChecked = "SELECT u.ID as UploadID, u.SubmitDate, u.Marks, u.Remarks,
                      a.AssignmentNumber, a.AssignmenttTitle, 
                      usr.FullName, usr.RollNumber, s.SubjectFullname
               FROM tbluploadass u
               JOIN tblassigment a ON u.AssId = a.ID
               JOIN tbluser usr ON u.UserID = usr.ID
               JOIN tblsubject s ON a.Sid = s.ID
               WHERE a.Tid = :tid AND (u.Marks IS NOT NULL AND u.Marks != '')
               ORDER BY u.SubmitDate DESC";
$qChecked = $dbh->prepare($sqlChecked);
$qChecked->execute(['tid' => $tid]);
$checked_list = $qChecked->fetchAll(PDO::FETCH_OBJ);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Submissions | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
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
        .container { padding: 40px 20px; max-width: 1400px; margin: 0 auto; }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px; padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            margin-bottom: 30px;
        }

        /* TABS STYLING */
        .nav-tabs {
            display: flex; gap: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 25px;
        }
        .tab-btn {
            background: transparent; border: none; color: #94a3b8;
            padding: 10px 20px; font-size: 14px; font-weight: 600; cursor: pointer;
            border-bottom: 3px solid transparent; transition: 0.3s;
        }
        .tab-btn:hover { color: #fff; }
        .tab-btn.active {
            color: #3b82f6; border-bottom-color: #3b82f6;
        }
        
        .tab-content { display: none; animation: fadeIn 0.3s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* TABLE STYLING */
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

        /* BADGES & BUTTONS */
        .badge-new { background: rgba(245, 158, 11, 0.15); color: #fbbf24; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
        .badge-done { background: rgba(16, 185, 129, 0.15); color: #34d399; padding: 3px 8px; border-radius: 4px; font-size: 11px; }

        .btn-action {
            display: inline-flex; align-items: center; gap: 5px;
            text-decoration: none; padding: 8px 16px; border-radius: 8px;
            font-size: 12px; font-weight: 700; text-transform: uppercase; transition: 0.2s;
        }
        .btn-grade { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-grade:hover { box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4); transform: translateY(-2px); color: white; }
        
        .btn-view { background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .btn-view:hover { background: #3b82f6; color: white; }

        .count-badge { background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; position: relative; top: -10px; left: -5px; }
    </style>
</head>
<body>

    <?php include_once('includes/header.php');?>

    <div class="container">
        <div class="glass-card">
            
            <div style="margin-bottom: 20px;">
                <h2 style="margin:0; color:#fff; font-size:24px;">Submissions Portal</h2>
                <p style="color:#94a3b8; font-size:14px;">Review and grade student assignments.</p>
            </div>

            <div class="nav-tabs">
                <button class="tab-btn active" onclick="openTab(event, 'pending')">
                    Unchecked Assignments 
                    <?php if(count($pending_list) > 0) { echo '<span class="count-badge">'.count($pending_list).'</span>'; } ?>
                </button>
                <button class="tab-btn" onclick="openTab(event, 'checked')">Checked History</button>
            </div>

            <div id="pending" class="tab-content active">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Assignment Info</th>
                                <th>Student</th>
                                <th>Course / Batch</th>
                                <th>Submission Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($pending_list) > 0) { foreach($pending_list as $row) { ?>
                            <tr>
                                <td>
                                    <span class="badge-new">New</span><br>
                                    <span style="color:#fff; font-weight:600;"><?php echo htmlentities($row->AssignmenttTitle);?></span>
                                    <div style="font-size:11px; color:#64748b;">Code: <?php echo htmlentities($row->AssignmentNumber);?></div>
                                </td>
                                <td>
                                    <span style="color:#e2e8f0;"><?php echo htmlentities($row->FullName);?></span>
                                    <div style="font-size:11px; color:#94a3b8;">Roll: <?php echo htmlentities($row->RollNumber);?></div>
                                </td>
                                <td>
                                    <?php echo htmlentities($row->CourseName);?><br>
                                    <span style="font-size:11px; color:#64748b;"><?php echo htmlentities($row->BranchName);?></span>
                                </td>
                                <td style="color:#f59e0b;"><?php echo date("d M, h:i A", strtotime($row->SubmitDate));?></td>
                                <td>
                                    <a href="submit-assignment.php?assinid=<?php echo $row->UploadID; ?>" class="btn-action btn-grade">
                                        <i class="ti-check-box"></i> Grade Now
                                    </a>
                                </td>
                            </tr>
                            <?php } } else { ?>
                                <tr><td colspan="5" style="text-align:center; padding:40px; color:#10b981;">No pending assignments! You're all caught up.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="checked" class="tab-content">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Assignment Info</th>
                                <th>Student</th>
                                <th>Marks Awarded</th>
                                <th>Checked On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($checked_list) > 0) { foreach($checked_list as $row) { ?>
                            <tr>
                                <td>
                                    <span class="badge-done">Graded</span><br>
                                    <span style="color:#fff; font-weight:600;"><?php echo htmlentities($row->AssignmenttTitle);?></span>
                                </td>
                                <td>
                                    <?php echo htmlentities($row->FullName);?> (<?php echo htmlentities($row->RollNumber);?>)
                                </td>
                                <td>
                                    <span style="font-size:14px; font-weight:700; color:#34d399;"><?php echo htmlentities($row->Marks);?></span>
                                </td>
                                <td style="color:#94a3b8;"><?php echo date("d M Y", strtotime($row->SubmitDate));?></td> <td>
                                    <a href="submit-assignment.php?assinid=<?php echo $row->UploadID; ?>" class="btn-action btn-view">
                                        <i class="ti-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php } } else { ?>
                                <tr><td colspan="5" style="text-align:center; padding:40px; color:#64748b;">No graded assignments found.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <?php include('includes/footer.php');?>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            
            // Hide all contents
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            
            // Deactivate all buttons
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            
            // Show target
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>

</body>
</html>