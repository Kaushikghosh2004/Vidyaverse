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

// --- HANDLE BATCH ALLOCATION (UPDATE) ---
if(isset($_POST['update_batch'])) {
    $sid = intval($_POST['student_id']);
    $bid = intval($_POST['batch_id']);
    
    try {
        $sql = "UPDATE tbluser SET batch_id = :bid WHERE ID = :sid";
        $query = $dbh->prepare($sql);
        $query->execute([':bid' => $bid, ':sid' => $sid]);
        
        $_SESSION['toast_msg'] = "System Update: Batch allocated successfully.";
        $_SESSION['toast_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['toast_msg'] = "System Error: Could not allocate batch.";
        $_SESSION['toast_type'] = "error";
    }
    header("Location: manage-students.php");
    exit;
}

// --- PASSWORD RESET LOGIC ---
if(isset($_POST['reset_pass'])) {
    $student_id = intval($_POST['student_id']);
    $new_password = $_POST['new_password'];
    
    $hashed_password = md5($new_password); 

    try {
        $sql = "UPDATE tbluser SET Password=:pass WHERE ID=:sid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':pass', $hashed_password);
        $query->bindParam(':sid', $student_id);
        $query->execute();

        $_SESSION['toast_msg'] = "Security Override: Password updated successfully.";
        $_SESSION['toast_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['toast_msg'] = "System Error: Could not reset password.";
        $_SESSION['toast_type'] = "error";
    }
    header("Location: manage-students.php");
    exit;
}

// --- DELETE STUDENT LOGIC ---
if(isset($_GET['delid'])) {
    $sid = intval($_GET['delid']);
    try {
        $sql = "DELETE FROM tbluser WHERE ID=:sid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $sid);
        $query->execute();
        
        $_SESSION['toast_msg'] = "Data Purged: Student record permanently deleted.";
        $_SESSION['toast_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['toast_msg'] = "Deletion Blocked: Active dependencies exist.";
        $_SESSION['toast_type'] = "error";
    }
    header("Location: manage-students.php");
    exit;
}

// Fetch Toast Data
$toastMsg = $_SESSION['toast_msg'] ?? '';
$toastType = $_SESSION['toast_type'] ?? '';
unset($_SESSION['toast_msg'], $_SESSION['toast_type']);

$pageTitle = "Manage Students";
$pageSubTitle = "Student Database & Security Control";
include('includes/header.php');
?>

<div class="container-fluid content-wrapper">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">

    <style>
        /* --- DARK PRESTIGE THEME --- */
        :root {
            --bg-dark: #020617; 
            --text-main: #f8fafc;
            --text-muted: #cbd5e1;
            --admin-gold: #fbbf24;
            --border-dark: #1e293b;
        }

        body { 
            background-color: var(--bg-dark);
            background-image: radial-gradient(circle at 50% 0%, #0f172a 0%, #020617 100%);
            background-attachment: fixed;
            font-family: 'Inter', sans-serif; 
            color: var(--text-main);
        }

        /* --- PRESTIGE BANNER --- */
        .admin-banner {
            background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
            border: 1px solid var(--border-dark);
            border-left: 4px solid var(--admin-gold);
            border-radius: 16px; padding: 35px 40px;
            display: flex; align-items: center; justify-content: space-between;
            margin: 20px 0 40px 0; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            position: relative; overflow: hidden;
        }
        .admin-banner::after {
            content: ''; position: absolute; right: -50px; top: -50px; height: 250px; width: 250px;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.05) 0%, transparent 70%);
            border-radius: 50%; pointer-events: none;
        }
        .banner-text h1 { margin: 0; font-size: 28px; color: #ffffff; font-family: 'Playfair Display', serif; font-weight: 700; }
        .banner-text h1 span { color: var(--admin-gold); font-style: italic; }
        .banner-text p { margin: 8px 0 0; color: #94a3b8; font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; }

        /* --- TABLE STYLES --- */
        .glass-card {
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(15px);
            border: 1px solid var(--border-dark); border-radius: 16px; padding: 25px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5); overflow-x: auto;
        }
        
        .table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .table th { text-align: left; padding: 15px; color: #94a3b8; font-size: 12px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; border-bottom: 1px solid var(--border-dark); }
        .table td { padding: 15px; background: rgba(0, 0, 0, 0.3); color: #e2e8f0; font-size: 14px; vertical-align: middle; border-top: 1px solid rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.02); }
        .table tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; border-left: 1px solid rgba(255,255,255,0.02); }
        .table tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; border-right: 1px solid rgba(255,255,255,0.02); }
        .table tr:hover td { background: rgba(251, 191, 36, 0.03); border-color: rgba(251, 191, 36, 0.1); }

        /* User Avatar */
        .stu-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-dark); }
        .stu-details { display: flex; align-items: center; gap: 15px; }
        .stu-name { font-weight: 700; color: #fff; display: block; }
        .stu-roll { font-size: 12px; color: var(--admin-gold); font-family: monospace; letter-spacing: 1px; }

        /* Badges */
        .status-badge { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border: 1px solid transparent; display: inline-block; margin-bottom: 4px; }
        .badge-na { background: rgba(239, 68, 68, 0.15); color: #ef4444; border-color: rgba(239, 68, 68, 0.3); }
        .badge-ok { background: rgba(16, 185, 129, 0.15); color: #10b981; border-color: rgba(16, 185, 129, 0.3); }

        /* Action Buttons */
        .action-group { display: flex; gap: 8px; justify-content: flex-end; }
        .btn-action { padding: 8px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: 0.3s; cursor: pointer; border: none; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .btn-allocate { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .btn-allocate:hover { background: #10b981; color: #000; box-shadow: 0 0 15px rgba(16, 185, 129, 0.4); }

        .btn-reset { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }
        .btn-reset:hover { background: #3b82f6; color: #fff; box-shadow: 0 0 15px rgba(59, 130, 246, 0.4); }
        
        .btn-delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-delete:hover { background: #ef4444; color: #fff; box-shadow: 0 0 15px rgba(239, 68, 68, 0.4); }

        /* --- MODALS --- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(2, 6, 23, 0.85); backdrop-filter: blur(10px);
            z-index: 9999; display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden; transition: 0.3s;
        }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        
        .modal-card {
            background: #0f172a; border-radius: 20px; padding: 40px; width: 400px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5); transform: scale(0.95); transition: 0.3s;
            position: relative; border: 1px solid var(--border-dark);
        }
        .modal-overlay.active .modal-card { transform: scale(1); }
        
        .border-gold { border-top: 2px solid var(--admin-gold); }
        .border-emerald { border-top: 2px solid #10b981; }
        
        .close-modal { position: absolute; top: 20px; right: 20px; font-size: 24px; color: #64748b; cursor: pointer; transition: 0.2s; }
        .close-modal:hover { color: #ef4444; }

        .form-group { margin-top: 25px; text-align: left; }
        .form-group label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 8px; }
        .modern-input { width: 100%; background: rgba(0,0,0,0.4); border: 1px solid var(--border-dark); padding: 14px; border-radius: 10px; color: #fff; font-family: 'Inter', sans-serif; transition: 0.3s; outline: none; }
        .modern-input:focus { border-color: var(--admin-gold); box-shadow: 0 0 15px rgba(251, 191, 36, 0.1); }
        
        select.modern-input { appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23cbd5e1' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 15px center; background-size: 16px; }
        select.modern-input option { background: #0f172a; color: #fff; }

        .btn-submit { width: 100%; color: #000; border: none; padding: 14px; border-radius: 10px; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.3s; margin-top: 25px; }
        .btn-submit-gold { background: var(--admin-gold); }
        .btn-submit-gold:hover { background: #f59e0b; box-shadow: 0 10px 20px rgba(251, 191, 36, 0.2); transform: translateY(-2px); }
        .btn-submit-emerald { background: #10b981; color: #fff; }
        .btn-submit-emerald:hover { background: #059669; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2); transform: translateY(-2px); }

        /* --- TOAST NOTIFICATION --- */
        .glass-toast { position: fixed; top: 90px; right: -400px; background: rgba(15, 23, 42, 0.95); border: 1px solid var(--border-dark); border-left: 4px solid var(--admin-gold); padding: 18px 25px; border-radius: 12px; display: flex; align-items: center; gap: 15px; box-shadow: 0 15px 35px rgba(0,0,0,0.6); z-index: 10000; transition: right 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .glass-toast.show { right: 30px; }
        .toast-icon { font-size: 24px; }
        .toast-success { border-left-color: #10b981; } .toast-success .toast-icon { color: #10b981; }
        .toast-error { border-left-color: #ef4444; } .toast-error .toast-icon { color: #ef4444; }
    </style>

    <div id="syncToast" class="glass-toast <?php echo ($toastType == 'success') ? 'toast-success' : 'toast-error'; ?>">
        <i class="fas <?php echo ($toastType == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> toast-icon"></i>
        <div>
            <h4 style="margin:0 0 4px; font-size:14px; font-weight:800; color:#fff; text-transform:uppercase;"><?php echo ($toastType == 'success') ? 'System Update' : 'System Alert'; ?></h4>
            <p style="margin:0; font-size:12px; color:#a1a1aa;"><?php echo $toastMsg; ?></p>
        </div>
    </div>

    <div class="admin-banner">
        <div class="banner-text">
            <h1>Student <span>Database</span></h1>
            <p>Admin Security Override & Record Management</p>
        </div>
        <i class="fas fa-users-cog" style="font-size: 60px; color: var(--border-dark); opacity: 0.5;"></i>
    </div>

    <div class="glass-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Student Identity</th>
                    <th>Course & Allocation</th>
                    <th>Contact Info</th>
                    <th>Registration Date</th>
                    <th style="text-align: right;">Security Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch Students with both Batch and Course Info
                $sql = "SELECT u.ID, u.FullName, u.Email, u.MobileNumber, u.RegDate, u.UserImage, u.RollNumber, u.batch_id, 
                               b.batch_name, c.CourseName, c.BranchName 
                        FROM tbluser u 
                        LEFT JOIN batches b ON u.batch_id = b.id 
                        LEFT JOIN tblcourse c ON b.CourseID = c.ID 
                        ORDER BY u.RegDate DESC";
                $query = $dbh->prepare($sql);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);

                if($query->rowCount() > 0) {
                    foreach($results as $row) {
                        $imgSrc = (!empty($row->UserImage)) ? "../user/images/".$row->UserImage : "../user/images/default.png";
                        
                        // Allocation Badge Logic
                        if(empty($row->batch_id) || $row->batch_id == 0) {
                            $batchInfo = '<span class="status-badge badge-na">Not Allocated</span>';
                            $detail = 'Pending Assignment';
                        } else {
                            $batchInfo = '<span class="status-badge badge-ok">'.htmlentities($row->batch_name).'</span>';
                            $detail = htmlentities($row->CourseName . ' (' . $row->BranchName . ')');
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="stu-details">
                                    <img src="<?php echo $imgSrc; ?>" class="stu-avatar" alt="Avatar">
                                    <div>
                                        <span class="stu-name"><?php echo htmlentities($row->FullName); ?></span>
                                        <span class="stu-roll">ID: <?php echo htmlentities($row->RollNumber); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo $batchInfo; ?>
                                <div style="font-size:12px; color:#94a3b8; font-weight:500; margin-top: 4px;"><?php echo $detail; ?></div>
                            </td>
                            <td>
                                <div><i class="fas fa-envelope" style="color:#64748b; margin-right:5px; font-size:11px;"></i> <?php echo htmlentities($row->Email); ?></div>
                                <div style="margin-top:4px;"><i class="fas fa-phone" style="color:#64748b; margin-right:5px; font-size:11px;"></i> <?php echo htmlentities($row->MobileNumber); ?></div>
                            </td>
                            <td style="color:#94a3b8; font-size:13px; font-weight:500;">
                                <?php echo date("d M Y", strtotime($row->RegDate)); ?>
                            </td>
                            <td>
                                <div class="action-group">
                                    <button class="btn-action btn-allocate" onclick="openBatchModal(<?php echo $row->ID; ?>, '<?php echo $row->batch_id; ?>', '<?php echo addslashes($row->FullName); ?>')">
                                        <i class="fas fa-layer-group"></i> Allocate
                                    </button>

                                    <button class="btn-action btn-reset" onclick="openResetModal(<?php echo $row->ID; ?>, '<?php echo addslashes($row->FullName); ?>')">
                                        <i class="fas fa-key"></i> Reset
                                    </button>
                                    
                                    <a href="manage-students.php?delid=<?php echo $row->ID; ?>" class="btn-action btn-delete" onclick="return confirm('WARNING: Are you sure you want to permanently delete this student record?');">
                                        <i class="fas fa-trash"></i> Purge
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                    }
                } else { ?>
                    <tr><td colspan="5" style="text-align:center; padding:40px; color:#64748b; text-transform:uppercase; letter-spacing:1px; font-weight:700;">No Students Registered in Database</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="batchModal">
        <div class="modal-card border-emerald">
            <i class="fas fa-times close-modal" onclick="closeBatchModal()"></i>
            
            <i class="fas fa-layer-group" style="font-size: 40px; color: #10b981; margin-bottom: 15px;"></i>
            <h3 style="font-family:'Playfair Display', serif; margin: 0 0 5px; color:#fff; font-size:22px; font-weight:700;">Allocate Batch</h3>
            <p style="font-size:12px; color:#94a3b8; margin: 0;">Assign <strong id="modalBatchStuName" style="color:#fff;">Student</strong> to a specific class parameter.</p>
            
            <form method="post" action="manage-students.php">
                <input type="hidden" name="student_id" id="modalBatchStuId">
                
                <div class="form-group">
                    <label>Select Target Batch</label>
                    <select name="batch_id" id="modalBatchId" class="modern-input" required>
                        <option value="">-- Choose Batch --</option>
                        <?php
                        $bSql = "SELECT b.id, b.batch_name, c.CourseName, c.BranchName 
                                 FROM batches b 
                                 JOIN tblcourse c ON b.CourseID = c.ID 
                                 ORDER BY c.CourseName ASC";
                        $bQuery = $dbh->prepare($bSql);
                        $bQuery->execute();
                        $batches = $bQuery->fetchAll(PDO::FETCH_OBJ);
                        foreach($batches as $b) {
                            $display = $b->CourseName . " (" . $b->BranchName . ") - " . $b->batch_name;
                            echo "<option value='".$b->id."'>".$display."</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <button type="submit" name="update_batch" class="btn-submit btn-submit-emerald">
                    <i class="fas fa-save"></i> Save Allocation
                </button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="resetModal">
        <div class="modal-card border-gold">
            <i class="fas fa-times close-modal" onclick="closeResetModal()"></i>
            
            <i class="fas fa-shield-alt" style="font-size: 40px; color: var(--admin-gold); margin-bottom: 15px;"></i>
            <h3 style="font-family:'Playfair Display', serif; margin: 0 0 5px; color:#fff; font-size:22px; font-weight:700;">Security Override</h3>
            <p style="font-size:12px; color:#94a3b8; margin: 0;">Reset password for <strong id="modalResetStuName" style="color:#fff;">Student</strong>.</p>
            
            <form method="post" action="manage-students.php">
                <input type="hidden" name="student_id" id="modalResetStuId">
                
                <div class="form-group">
                    <label>New Master Password</label>
                    <input type="text" name="new_password" class="modern-input" placeholder="Enter new password" required autocomplete="off">
                </div>
                
                <button type="submit" name="reset_pass" class="btn-submit btn-submit-gold">
                    <i class="fas fa-lock"></i> Force Update
                </button>
            </form>
        </div>
    </div>

    <script>
        // --- TOAST NOTIFICATION ---
        document.addEventListener("DOMContentLoaded", function() {
            const toastMsg = "<?php echo addslashes($toastMsg); ?>";
            if (toastMsg.trim() !== "") {
                const toast = document.getElementById('syncToast');
                setTimeout(() => { toast.classList.add('show'); }, 100);
                setTimeout(() => { toast.classList.remove('show'); }, 3500);
            }
        });

        // --- BATCH MODAL LOGIC ---
        function openBatchModal(studentId, currentBatchId, studentName) {
            document.getElementById('modalBatchStuId').value = studentId;
            document.getElementById('modalBatchStuName').innerText = studentName;
            document.getElementById('modalBatchId').value = currentBatchId; 
            document.getElementById('batchModal').classList.add('active');
        }
        function closeBatchModal() { document.getElementById('batchModal').classList.remove('active'); }

        // --- RESET MODAL LOGIC ---
        function openResetModal(studentId, studentName) {
            document.getElementById('modalResetStuId').value = studentId;
            document.getElementById('modalResetStuName').innerText = studentName;
            document.getElementById('resetModal').classList.add('active');
        }
        function closeResetModal() { document.getElementById('resetModal').classList.remove('active'); }

        // --- CLOSE MODALS ON OUTSIDE CLICK ---
        window.addEventListener('click', function(e) {
            if (e.target == document.getElementById('resetModal')) closeResetModal();
            if (e.target == document.getElementById('batchModal')) closeBatchModal();
        });
    </script>

</div>

<?php include('includes/footer.php');?>