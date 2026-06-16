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

// --- ADD COURSE LOGIC ---
if(isset($_POST['submit'])) {
    $cname = trim($_POST['coursename']);
    $bname = trim($_POST['branchname']);

    if(empty($cname) || empty($bname)) {
        $_SESSION['toast_msg'] = "Sync Failed: Please fill all required fields.";
        $_SESSION['toast_type'] = "error";
    } else {
        try {
            $sql = "INSERT INTO tblcourse(BranchName, CourseName) VALUES(:branchname, :coursename)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':branchname', $bname);
            $query->bindParam(':coursename', $cname);
            $query->execute();

            if ($dbh->lastInsertId() > 0) {
                $_SESSION['toast_msg'] = "Data Synced Successfully: Course Added.";
                $_SESSION['toast_type'] = "success";
            } else {
                $_SESSION['toast_msg'] = "Sync Error: Could not verify data injection.";
                $_SESSION['toast_type'] = "error";
            }
        } catch (Exception $e) {
            $_SESSION['toast_msg'] = "System Error: " . htmlspecialchars($e->getMessage());
            $_SESSION['toast_type'] = "error";
        }
    }
    // Redirect to clear POST data and show the toast
    header("Location: course.php");
    exit;
}

// --- DELETE LOGIC ---
if(isset($_GET['delid'])) {
    $rid = intval($_GET['delid']);
    try {
        $sql = "DELETE FROM tblcourse WHERE ID=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid);
        $query->execute();
        
        $_SESSION['toast_msg'] = "Data Synced Successfully: Record Purged.";
        $_SESSION['toast_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['toast_msg'] = "Deletion Blocked: Active database dependencies exist.";
        $_SESSION['toast_type'] = "error";
    }
    header("Location: course.php");
    exit;
}

// Fetch Toast Data before clearing it
$toastMsg = $_SESSION['toast_msg'] ?? '';
$toastType = $_SESSION['toast_type'] ?? '';
unset($_SESSION['toast_msg'], $_SESSION['toast_type']);

$pageTitle = "Manage Courses";
$pageSubTitle = "Curriculum & Branch Management";
include('includes/header.php');
?>

<div class="container-fluid">
    
    <style>
        /* --- PAGE SPECIFIC STYLES --- */
        :root { 
            --glass-bg: rgba(9, 9, 11, 0.65);
            --glass-border: 1px solid rgba(255, 255, 255, 0.08);
            --sec-cyan: #06b6d4;
            --sec-purple: #8b5cf6;
            --sec-red: #ef4444;
            --sec-emerald: #10b981;
        }

        body { 
            background: #050505;
            background-image: radial-gradient(circle at 50% 0%, #1e293b 0%, #020617 80%);
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
        }

        /* LAYOUT GRID */
        .page-wrapper {
            display: grid;
            grid-template-columns: 350px 1fr; /* Fixed Width Form + Flexible Table */
            gap: 30px; margin-top: 30px;
        }
        @media(max-width: 992px) { .page-wrapper { grid-template-columns: 1fr; } }

        /* GLASS CARD */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border: var(--glass-border); border-radius: 20px; padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5), inset 0 1px 2px rgba(255, 255, 255, 0.05);
            height: 100%;
        }

        .section-header { border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; margin-bottom: 25px; }
        .header-title { font-family: 'Orbitron', sans-serif; font-size: 18px; font-weight: 700; color: #fff; letter-spacing: 1px; }

        /* FORM STYLES */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #a1a1aa; margin-bottom: 8px; font-weight: 700; }
        
        .form-control {
            width: 100%; background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255,255,255,0.1); color: #fff;
            padding: 14px; border-radius: 12px; font-size: 14px; transition: 0.3s;
            outline: none; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
        }
        .form-control:focus { border-color: var(--sec-cyan); box-shadow: 0 0 15px rgba(6, 182, 212, 0.2), inset 0 2px 4px rgba(0,0,0,0.5); }

        .btn-submit {
            background: linear-gradient(135deg, var(--sec-cyan), #0284c7);
            color: white; border: 1px solid rgba(6, 182, 212, 0.5); width: 100%; padding: 15px;
            border-radius: 12px; font-size: 13px; font-weight: 800; font-family: 'Orbitron', sans-serif;
            cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 2px;
            box-shadow: 0 10px 25px rgba(6, 182, 212, 0.3); margin-top: 10px;
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(6, 182, 212, 0.5); filter: brightness(1.2); }

        /* TABLE STYLES */
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        
        .table th { text-align: left; padding: 15px; color: #71717a; font-size: 11px; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; }
        .table td { 
            padding: 15px; background: rgba(24, 24, 27, 0.6); color: #e2e8f0; font-size: 13px; vertical-align: middle;
            border-top: 1px solid rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.02);
        }
        .table tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; border-left: 1px solid rgba(255,255,255,0.02); }
        .table tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; border-right: 1px solid rgba(255,255,255,0.02); }
        .table tr:hover td { background: rgba(6, 182, 212, 0.05); }

        .btn-action { padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-decoration: none; margin-right: 5px; transition: 0.2s; text-transform: uppercase; letter-spacing: 1px; border: 1px solid transparent; }
        .btn-edit { background: rgba(139, 92, 246, 0.15); color: var(--sec-purple); border-color: rgba(139, 92, 246, 0.3); }
        .btn-delete { background: rgba(239, 68, 68, 0.15); color: var(--sec-red); border-color: rgba(239, 68, 68, 0.3); }
        .btn-edit:hover { background: var(--sec-purple); color: white; box-shadow: 0 0 15px rgba(139, 92, 246, 0.4); }
        .btn-delete:hover { background: var(--sec-red); color: white; box-shadow: 0 0 15px rgba(239, 68, 68, 0.4); }

        /* --- IN-PAGE TOAST NOTIFICATION --- */
        .glass-toast {
            position: fixed; top: 90px; right: -400px; /* Hidden off-screen initially */
            background: rgba(9, 9, 11, 0.85); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1); border-left: 4px solid var(--sec-cyan);
            padding: 18px 25px; border-radius: 12px;
            display: flex; align-items: center; gap: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
            z-index: 9999;
            transition: right 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .glass-toast.show { right: 30px; }
        
        .toast-icon { font-size: 24px; }
        .toast-content h4 { margin: 0 0 4px; font-size: 14px; font-weight: 800; color: #fff; letter-spacing: 1px; text-transform: uppercase; }
        .toast-content p { margin: 0; font-size: 12px; color: #a1a1aa; }

        /* Success & Error Themes for Toast */
        .toast-success { border-left-color: var(--sec-emerald); }
        .toast-success .toast-icon { color: var(--sec-emerald); text-shadow: 0 0 15px var(--sec-emerald); }
        .toast-error { border-left-color: var(--sec-red); }
        .toast-error .toast-icon { color: var(--sec-red); text-shadow: 0 0 15px var(--sec-red); }
    </style>

    <div id="syncToast" class="glass-toast <?php echo ($toastType == 'success') ? 'toast-success' : 'toast-error'; ?>">
        <i class="fas <?php echo ($toastType == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> toast-icon"></i>
        <div class="toast-content">
            <h4><?php echo ($toastType == 'success') ? 'System Update' : 'System Alert'; ?></h4>
            <p><?php echo $toastMsg; ?></p>
        </div>
    </div>

    <div class="page-wrapper">
        
        <div class="glass-card">
            <div class="section-header">
                <div class="header-title">Create Data Node</div>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="coursename" class="form-control" placeholder="e.g. B.Tech" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label>Branch Identifier</label>
                    <input type="text" name="branchname" class="form-control" placeholder="e.g. Computer Science" required autocomplete="off">
                </div>

                <button type="submit" name="submit" class="btn-submit"><i class="fas fa-database"></i> Inject Data</button>
            </form>
        </div>

        <div class="glass-card">
            <div class="section-header">
                <div class="header-title">Active Database Registry</div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Course Name</th>
                            <th>Branch Identifier</th>
                            <th style="width: 150px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM tblcourse";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                        $cnt = 1;

                        if($query->rowCount() > 0) {
                            foreach($results as $row) {
                                ?>
                                <tr>
                                    <td><?php echo $cnt;?></td>
                                    <td style="font-weight:700; color:#fff;"><?php echo htmlentities($row->CourseName);?></td>
                                    <td style="color:#a1a1aa;"><?php echo htmlentities($row->BranchName);?></td>
                                    <td>
                                        <a href="edit-course.php?editid=<?php echo $row->ID;?>" class="btn-action btn-edit">Edit</a>
                                        <a href="course.php?delid=<?php echo $row->ID;?>" class="btn-action btn-delete" onclick="return confirm('WARNING: Purge this record from the database?');">Del</a>
                                    </td>
                                </tr>
                                <?php 
                                $cnt++;
                            }
                        } else { ?>
                            <tr><td colspan="4" style="text-align:center; padding:40px; color:#71717a; text-transform:uppercase; letter-spacing:1px; font-weight:700;">No active nodes found in registry.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    // Execute Toast Animation if a message exists
    document.addEventListener("DOMContentLoaded", function() {
        const toastMsg = "<?php echo addslashes($toastMsg); ?>";
        if (toastMsg.trim() !== "") {
            const toast = document.getElementById('syncToast');
            
            // Slide in
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);

            // Slide out after 3.5 seconds
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3500);
        }
    });
</script>

<?php include('includes/footer.php');?>