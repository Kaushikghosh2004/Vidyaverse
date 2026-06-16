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

// --- DELETE LOGIC ---
if(isset($_GET['delid'])) {
    $rid = intval($_GET['delid']);
    try {
        // 1. Delete subject links first (Foreign Key safety)
        $sql1 = "DELETE FROM tblteacher_subjects WHERE TeacherID=:rid";
        $query1 = $dbh->prepare($sql1);
        $query1->bindParam(':rid', $rid, PDO::PARAM_INT);
        $query1->execute();

        // 2. Delete Teacher from MAIN TABLE (tblteacher)
        $sql = "DELETE FROM tblteacher WHERE ID=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_INT);
        $query->execute();
        
        echo "<script>alert('Teacher record deleted successfully.'); window.location.href='manage-teacher.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error deleting record: " . addslashes($e->getMessage()) . "');</script>"; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    <title>Manage Faculty | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- 1. GLOBAL DARK THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
            min-height: 100vh;
            padding-top: 80px; /* Space for fixed header */
        }

        /* --- 2. GLASS HEADER (Fixes Upper Part) --- */
        .glass-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 40px; z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        }
        .brand { font-size: 18px; font-weight: 700; color: #fff; letter-spacing: 1px; display: flex; align-items: center; gap: 10px; }
        .brand i { color: #10b981; font-size: 20px; } 
        
        .nav-actions { display: flex; gap: 20px; }
        .nav-btn { 
            text-decoration: none; color: #94a3b8; font-size: 14px; font-weight: 600; 
            display: flex; align-items: center; gap: 8px; transition: 0.3s;
        }
        .nav-btn:hover { color: #fff; }
        .logout-btn { color: #ef4444; }
        .logout-btn:hover { color: #fca5a5; }

        /* --- 3. PAGE CONTENT --- */
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }

        /* Card Style */
        .glass-card {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px; padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
        }
        .card-title { font-size: 20px; font-weight: 700; color: #fff; margin: 0; }

        .btn-add-new {
            background: #10b981; color: white; text-decoration: none;
            padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600;
            display: flex; align-items: center; gap: 8px; transition: 0.2s;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .btn-add-new:hover { background: #059669; transform: translateY(-2px); }

        /* --- 4. TABLE STYLES --- */
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        
        .table th { 
            text-align: left; padding: 15px; 
            color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;
            font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .table td { 
            padding: 15px; 
            background: rgba(30, 41, 59, 0.6); 
            color: #e2e8f0; font-size: 14px; vertical-align: middle;
            border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .table tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; border-left: 1px solid rgba(255,255,255,0.05); }
        .table tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; border-right: 1px solid rgba(255,255,255,0.05); }
        .table tr:hover td { background: rgba(30, 41, 59, 0.9); }

        /* Subject Badges */
        .subject-tag {
            display: inline-block;
            background: rgba(59, 130, 246, 0.15); color: #60a5fa;
            padding: 4px 10px; border-radius: 20px; font-size: 11px; margin: 2px;
            border: 1px solid rgba(59, 130, 246, 0.3); font-weight: 600;
        }

        /* Avatar */
        .avatar-circle {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white; display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 14px; box-shadow: 0 0 10px rgba(139, 92, 246, 0.4);
        }
        .avatar-img {
            width: 40px; height: 40px; border-radius: 50%; object-fit: cover;
            border: 2px solid #8b5cf6;
        }

        /* Action Buttons */
        .btn-action { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; margin-right: 5px; transition: 0.2s; }
        .btn-edit { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .btn-delete { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-edit:hover { background: #3b82f6; color: white; }
        .btn-delete:hover { background: #ef4444; color: white; }

    </style>
</head>
<body>

    <header class="glass-header">
        <div class="brand">
            <i class="ti-user"></i> Faculty Management
        </div>
        <div class="nav-actions">
            <a href="dashboard.php" class="nav-btn"><i class="ti-arrow-left"></i> Dashboard</a>
            <a href="add-teacher.php" class="nav-btn"><i class="ti-plus"></i> Add New</a>
            <a href="logout.php" class="nav-btn logout-btn"><i class="ti-power-off"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="glass-card">
            <div class="card-header">
                <h4 class="card-title">All Registered Faculty</h4>
                <a href="add-teacher.php" class="btn-add-new"><i class="ti-plus"></i> Register Teacher</a>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Profile</th>
                            <th>Contact Info</th>
                            <th style="width: 35%;">Assigned Subjects</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // --- ROBUST SQL QUERY ---
                        // 1. Select from tblteacher (singular)
                        // 2. Join with tblteacher_subjects to get links
                        // 3. Join with tblsubject to get names
                        // 4. GROUP BY teacher ID to combine multiple rows into one
                        $sql = "SELECT t.*, 
                                       GROUP_CONCAT(s.SubjectFullname SEPARATOR '||') as SubjectNames,
                                       GROUP_CONCAT(s.SubjectCode SEPARATOR '||') as SubjectCodes
                                FROM tblteacher t
                                LEFT JOIN tblteacher_subjects ts ON t.ID = ts.TeacherID
                                LEFT JOIN tblsubject s ON ts.SubjectID = s.ID
                                GROUP BY t.ID
                                ORDER BY t.ID DESC";
                                
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                        $cnt = 1;
                        
                        if($query->rowCount() > 0) {
                            foreach($results as $row) {
                                
                                // Name Logic
                                $fullName = trim($row->FirstName . ' ' . $row->LastName);
                                if(empty($fullName)) $fullName = $row->TeacherName; // Fallback

                                // Avatar Logic
                                $avatarHTML = "";
                                if(!empty($row->ProfilePic)) {
                                    $avatarHTML = '<img src="images/'.htmlentities($row->ProfilePic).'" class="avatar-img">';
                                } else {
                                    $initial = strtoupper(substr($fullName, 0, 1));
                                    $avatarHTML = '<div class="avatar-circle">'.$initial.'</div>';
                                }

                                // Subject Logic
                                $subjDisplay = "";
                                if(!empty($row->SubjectNames)) {
                                    $subNames = explode('||', $row->SubjectNames);
                                    $subCodes = explode('||', $row->SubjectCodes);
                                    
                                    for($i=0; $i<count($subNames); $i++) {
                                        $displayTxt = $subNames[$i];
                                        // Append code if exists
                                        if(!empty($subCodes[$i])) {
                                            $displayTxt .= " (" . $subCodes[$i] . ")";
                                        }
                                        $subjDisplay .= '<span class="subject-tag">'.htmlentities($displayTxt).'</span>';
                                    }
                                } else {
                                    $subjDisplay = '<span style="color:#64748b; font-style:italic; font-size:12px;">No subjects assigned yet</span>';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlentities($cnt);?></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <?php echo $avatarHTML; ?>
                                            <div>
                                                <span style="color:#fff; font-weight:600; font-size:14px;"><?php echo htmlentities($fullName);?></span><br>
                                                <span style="font-size:11px; color:#10b981; background:rgba(16,185,129,0.1); padding:2px 6px; border-radius:4px;">ID: <?php echo htmlentities($row->EmpID);?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; flex-direction:column; gap:3px;">
                                            <span style="font-size:13px;"><i class="ti-mobile" style="color:#94a3b8; width:15px;"></i> <?php echo htmlentities($row->MobileNumber);?></span>
                                            <span style="font-size:13px;"><i class="ti-email" style="color:#94a3b8; width:15px;"></i> <?php echo htmlentities($row->Email);?></span>
                                        </div>
                                    </td>
                                    
                                    <td><?php echo $subjDisplay; ?></td>
                                    
                                    <td style="color:#94a3b8; font-size:13px;"><?php echo htmlentities(substr($row->RegDate, 0, 10));?></td>
                                    <td>
                                        <div style="display:flex;">
                                            <a href="edit-teacher-info.php?editid=<?php echo htmlentities($row->ID);?>" class="btn-action btn-edit">Edit</a>
                                            <a href="manage-teacher.php?delid=<?php echo htmlentities($row->ID);?>" class="btn-action btn-delete" onclick="return confirm('Permanently delete this teacher profile?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php $cnt++; 
                            }
                        } else { ?>
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:#94a3b8;">No faculty members found. Click "Add New" to begin.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>