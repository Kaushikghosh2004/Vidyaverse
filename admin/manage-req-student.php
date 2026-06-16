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

// --- 1. HANDLE APPROVAL LOGIC (Your Logic) ---
if(isset($_GET['approve_reset_id'])) {
    $rid = $_GET['approve_reset_id']; // The ID from tblreset_requests
    
    // Fetch User Details linked to this request
    $sql = "SELECT u.ID, u.RollNumber, u.DOB, u.MobileNumber 
            FROM tbluser u 
            JOIN tblreset_requests r ON u.ID = r.UserID 
            WHERE r.ID = :rid";
    $query = $dbh->prepare($sql);
    $query->execute(['rid' => $rid]);
    $user = $query->fetch(PDO::FETCH_OBJ);
    
    if($user) {
        // --- PASSWORD GENERATION LOGIC ---
        // Format DOB to remove dashes (e.g., 2000-01-01 -> 20000101)
        $dob_clean = str_replace("-", "", $user->DOB); 
        
        // If DOB is empty in DB, fallback to Mobile Number
        if(empty($dob_clean)) {
            $default_pass_str = $user->RollNumber . $user->MobileNumber; 
        } else {
            $default_pass_str = $user->RollNumber . $dob_clean; 
        }

        // Apply MD5
        $new_password = md5($default_pass_str);
        
        // Update User Password
        $upd = $dbh->prepare("UPDATE tbluser SET Password=:pass WHERE ID=:uid");
        $upd->execute(['pass' => $new_password, 'uid' => $user->ID]);
        
        // Mark Request as Completed
        $del = $dbh->prepare("UPDATE tblreset_requests SET Status='Completed' WHERE ID=:rid");
        $del->execute(['rid' => $rid]);
        
        // Alert Admin
        echo "<script>
            alert('Password Reset Successful!\\n\\nNew Password: " . $default_pass_str . "');
            window.location.href='manage-student-req.php';
        </script>";
    }
}

// --- 2. HANDLE REJECTION LOGIC ---
if(isset($_GET['reject_id'])) {
    $rid = $_GET['reject_id'];
    $del = $dbh->prepare("UPDATE tblreset_requests SET Status='Rejected' WHERE ID=:rid");
    $del->execute(['rid' => $rid]);
    echo "<script>alert('Request Rejected.'); window.location.href='manage-student-req.php';</script>";
}

include('includes/header.php'); 
?>

<div class="app-container">
    
    <div class="simple-header">
        <div class="header-left">
            <div class="welcome-info">
                <span class="welcome-msg">Manage Requests</span>
                <span class="welcome-sub">Student Password Resets</span>
            </div>
        </div>
        <div class="header-right">
            <a href="dashboard.php" class="back-link">
                <i class="ti-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="content-wrap">
        <div class="main">
            <div class="container-fluid">
                
                <style>
                    /* GLOBAL RESET */
                    * { box-sizing: border-box; }
                    body { background-color: #0f172a; font-family: 'Inter', 'Segoe UI', sans-serif; color: #f8fafc; margin: 0; padding: 0; }
                    .header, .sidebar { display: none !important; }

                    /* HEADER */
                    .simple-header {
                        position: fixed; top: 0; left: 0; width: 100%; height: 80px;
                        background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px);
                        z-index: 999; display: flex; align-items: center; justify-content: space-between;
                        padding: 0 40px; border-bottom: 1px solid #334155;
                    }
                    .welcome-msg { font-size: 20px; font-weight: 700; color: #fff; display: block; }
                    .welcome-sub { font-size: 13px; color: #94a3b8; }

                    .back-link {
                        background: #334155; color: #fff; padding: 8px 24px; border-radius: 6px;
                        text-decoration: none; font-weight: 600; font-size: 14px;
                        display: flex; align-items: center; gap: 8px; transition: 0.2s;
                    }
                    .back-link:hover { background: #475569; }

                    /* CONTENT */
                    .content-wrap { margin-top: 80px; padding: 40px; width: 100%; min-height: 100vh; }

                    /* REQUEST GRID */
                    .req-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                        gap: 25px;
                    }

                    /* TICKET CARD */
                    .req-card {
                        background: #1e293b; border: 1px solid #334155;
                        border-radius: 12px; overflow: hidden;
                        display: flex; flex-direction: column;
                        transition: transform 0.2s;
                        position: relative;
                    }
                    .req-card:hover { transform: translateY(-3px); border-color: #3b82f6; }
                    
                    /* Status Strip */
                    .status-strip { height: 4px; width: 100%; background: #f59e0b; box-shadow: 0 0 10px #f59e0b; }

                    .card-body { padding: 25px; flex-grow: 1; }
                    
                    .student-name { font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 5px; }
                    .student-roll { font-size: 13px; color: #94a3b8; font-family: monospace; letter-spacing: 1px; background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px; }

                    .info-row { margin-top: 20px; display: flex; flex-direction: column; gap: 10px; }
                    .info-item { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #cbd5e1; }
                    .info-item i { color: #64748b; width: 20px; text-align: center; }

                    .card-actions {
                        padding: 15px 25px; background: #0f172a; border-top: 1px solid #334155;
                        display: flex; gap: 10px;
                    }

                    .btn {
                        flex: 1; padding: 10px; border-radius: 6px; font-size: 13px; font-weight: 600;
                        text-align: center; text-decoration: none; cursor: pointer; border: none;
                        transition: 0.2s;
                    }
                    .btn-approve { background: #10b981; color: white; }
                    .btn-approve:hover { background: #059669; }
                    
                    .btn-reject { background: transparent; border: 1px solid #ef4444; color: #ef4444; }
                    .btn-reject:hover { background: #ef4444; color: white; }

                    .empty-state {
                        grid-column: 1 / -1; text-align: center; padding: 60px;
                        background: #1e293b; border-radius: 12px; border: 2px dashed #334155;
                        color: #94a3b8;
                    }
                </style>

                <div class="req-grid">
                    <?php
                    // Fetch Pending Requests
                    try {
                        $sql = "SELECT r.ID as rid, r.RequestDate, u.FullName, u.RollNumber, u.Email, u.MobileNumber, u.DOB
                                FROM tblreset_requests r
                                JOIN tbluser u ON r.UserID = u.ID
                                WHERE r.Status = 'Pending'
                                ORDER BY r.RequestDate DESC";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                        if($query->rowCount() > 0) {
                            foreach($results as $row) {
                    ?>
                        <div class="req-card">
                            <div class="status-strip"></div>
                            <div class="card-body">
                                <div style="display:flex; justify-content:space-between; align-items:start;">
                                    <div>
                                        <div class="student-name"><?php echo htmlentities($row->FullName); ?></div>
                                        <span class="student-roll"><?php echo htmlentities($row->RollNumber); ?></span>
                                    </div>
                                    <i class="ti-lock" style="font-size:24px; color:#f59e0b;"></i>
                                </div>

                                <div class="info-row">
                                    <div class="info-item">
                                        <i class="ti-email"></i> <?php echo htmlentities($row->Email); ?>
                                    </div>
                                    <div class="info-item">
                                        <i class="ti-mobile"></i> <?php echo htmlentities($row->MobileNumber); ?>
                                    </div>
                                    <div class="info-item">
                                        <i class="ti-calendar"></i> DOB: <?php echo htmlentities($row->DOB); ?>
                                    </div>
                                    <div class="info-item" style="color:#f59e0b;">
                                        <i class="ti-time"></i> Requested: <?php echo date("d M Y h:i A", strtotime($row->RequestDate)); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-actions">
                                <a href="manage-student-req.php?approve_reset_id=<?php echo $row->rid; ?>" class="btn btn-approve" onclick="return confirm('Reset password to Default?');">Approve Reset</a>
                                <a href="manage-student-req.php?reject_id=<?php echo $row->rid; ?>" class="btn btn-reject" onclick="return confirm('Reject this request?');">Reject</a>
                            </div>
                        </div>

                    <?php 
                            }
                        } else {
                    ?>
                        <div class="empty-state">
                            <i class="ti-check-box" style="font-size:40px; margin-bottom:15px; display:block; color:#10b981;"></i>
                            <h3>All Clear!</h3>
                            <p>No pending password reset requests at this time.</p>
                        </div>
                    <?php 
                        }
                    } catch (Exception $e) {
                        echo "<div class='empty-state'>Error fetching requests: " . $e->getMessage() . "</div>";
                    }
                    ?>
                </div>

            </div>
        </div>
    </div>

</body>
</html>