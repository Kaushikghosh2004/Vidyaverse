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

// --- ADD NEWS LOGIC ---
if(isset($_POST['submit'])) {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);

    if(empty($title) || empty($desc)) {
        $_SESSION['toast_msg'] = "Sync Failed: Please fill all required fields.";
        $_SESSION['toast_type'] = "error";
    } else {
        try {
            $sql = "INSERT INTO tblnews(Title, Description) VALUES(:title, :desc)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':title', $title, PDO::PARAM_STR);
            $query->bindParam(':desc', $desc, PDO::PARAM_STR);
            $query->execute();

            $LastInsertId = $dbh->lastInsertId();
            if ($LastInsertId > 0) {
                $_SESSION['toast_msg'] = "Data Synced: Announcement Broadcasted.";
                $_SESSION['toast_type'] = "success";
            } else {
                $_SESSION['toast_msg'] = "Sync Error: Could not post announcement.";
                $_SESSION['toast_type'] = "error";
            }
        } catch (Exception $e) {
            $_SESSION['toast_msg'] = "System Error: " . htmlspecialchars($e->getMessage());
            $_SESSION['toast_type'] = "error";
        }
    }
    // Redirect to prevent form resubmission and trigger toast
    header("Location: newsorannouncement.php");
    exit;
}

// --- DELETE LOGIC ---
if(isset($_GET['delid'])) {
    $rid = intval($_GET['delid']);
    try {
        $sql = "DELETE FROM tblnews WHERE ID=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_STR);
        $query->execute();
        
        $_SESSION['toast_msg'] = "Data Synced: Record Purged.";
        $_SESSION['toast_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['toast_msg'] = "Deletion Blocked: System error occurred.";
        $_SESSION['toast_type'] = "error";
    }
    header("Location: newsorannouncement.php");
    exit;
}

// Fetch Toast Data before clearing it
$toastMsg = $_SESSION['toast_msg'] ?? '';
$toastType = $_SESSION['toast_type'] ?? '';
unset($_SESSION['toast_msg'], $_SESSION['toast_type']);

$pageTitle = "Announcements";
$pageSubTitle = "Manage News & Updates";
include('includes/header.php');
?>

<div class="container-fluid">
    
    <style>
        /* PAGE SPECIFIC STYLES */
        :root {
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --neon-blue: #3b82f6;
            --neon-purple: #8b5cf6;
            --neon-red: #ef4444;
            --sec-cyan: #06b6d4;
            --sec-emerald: #10b981;
        }

        body { 
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
        }

        /* LAYOUT GRID */
        .page-wrapper {
            display: grid;
            grid-template-columns: 350px 1fr; /* Fixed Width Form + Flexible Table */
            gap: 30px;
            margin-top: 30px;
        }
        @media(max-width: 992px) { .page-wrapper { grid-template-columns: 1fr; } }

        /* GLASS CARD */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            height: 100%;
        }

        .section-header {
            border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 25px;
        }
        .header-title { font-size: 18px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }

        /* FORM STYLES */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 8px; font-weight: 500; }
        
        .form-control {
            width: 100%; background: rgba(15, 23, 42, 0.6);
            border: 1px solid #334155; color: #fff;
            padding: 12px; border-radius: 12px; font-size: 14px; transition: 0.3s;
            outline: none;
        }
        .form-control:focus { border-color: var(--neon-blue); box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        textarea.form-control { min-height: 120px; resize: vertical; }

        .btn-submit {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white; border: none; width: 100%; padding: 14px;
            border-radius: 12px; font-size: 16px; font-weight: 700;
            cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4); }

        /* TABLE STYLES */
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        
        .table th { 
            text-align: left; padding: 15px; 
            color: #94a3b8; font-size: 12px; text-transform: uppercase; font-weight: 600; 
            letter-spacing: 1px;
        }
        
        .table td { 
            padding: 15px; 
            background: rgba(30, 41, 59, 0.6); 
            color: #e2e8f0; font-size: 14px; vertical-align: top;
            border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .table tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; border-left: 1px solid rgba(255,255,255,0.05); }
        .table tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; border-right: 1px solid rgba(255,255,255,0.05); }
        .table tr:hover td { background: rgba(59, 130, 246, 0.1); }

        .news-title { font-weight: 700; color: #fff; font-size: 15px; display: block; margin-bottom: 5px; }
        .news-date { font-size: 11px; color: var(--neon-blue); background: rgba(59, 130, 246, 0.1); padding: 3px 8px; border-radius: 4px; display: inline-block; margin-bottom: 8px; }
        .news-desc { color: #94a3b8; line-height: 1.5; font-size: 13px; }

        .btn-delete {
            background: rgba(239, 68, 68, 0.15); color: var(--neon-red);
            padding: 8px 12px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 5px; border: 1px solid rgba(239, 68, 68, 0.3);
            transition: 0.2s;
        }
        .btn-delete:hover { background: var(--neon-red); color: white; }

        /* --- IN-PAGE TOAST NOTIFICATION --- */
        .glass-toast {
            position: fixed; top: 90px; right: -400px; /* Hidden off-screen initially */
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
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
        .toast-success .toast-icon { color: var(--sec-emerald); text-shadow: 0 0 15px rgba(16, 185, 129, 0.5); }
        .toast-error { border-left-color: var(--neon-red); }
        .toast-error .toast-icon { color: var(--neon-red); text-shadow: 0 0 15px rgba(239, 68, 68, 0.5); }
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
                <div class="header-title"><i class="ti-pencil-alt"></i> Create Post</div>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label>Announcement Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Mid-Sem Exams Postponed" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label>Description / Details</label>
                    <textarea name="description" class="form-control" placeholder="Enter full details here..." required></textarea>
                </div>

                <button type="submit" name="submit" class="btn-submit">Publish Update</button>
            </form>
        </div>

        <div class="glass-card">
            <div class="section-header">
                <div class="header-title"><i class="ti-announcement"></i> Latest Updates</div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Announcement Details</th>
                            <th style="width: 100px; text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM tblnews ORDER BY CreationDate DESC";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                        $cnt = 1;

                        if($query->rowCount() > 0) {
                            foreach($results as $row) {
                                ?>
                                <tr>
                                    <td style="font-weight:bold; color:#64748b;"><?php echo $cnt;?></td>
                                    <td>
                                        <span class="news-date">
                                            <i class="ti-calendar"></i> <?php echo date("d M Y, h:i A", strtotime($row->CreationDate)); ?>
                                        </span>
                                        <span class="news-title"><?php echo htmlentities($row->Title);?></span>
                                        <div class="news-desc"><?php echo htmlentities($row->Description);?></div>
                                    </td>
                                    <td style="text-align:right; vertical-align:middle;">
                                        <a href="newsorannouncement.php?delid=<?php echo $row->ID;?>" class="btn-delete" onclick="return confirm('WARNING: Purge this announcement from the network?');">
                                            <i class="ti-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                $cnt++;
                            }
                        } else { ?>
                            <tr><td colspan="3" style="text-align:center; padding:30px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; font-weight:600;">No announcements posted yet.</td></tr>
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