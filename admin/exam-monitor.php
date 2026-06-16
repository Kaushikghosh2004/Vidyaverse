<?php
session_start();
// Enable Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// --- ACTION 1: TERMINATE STUDENT ---
if(isset($_POST['terminate'])) {
    $sid = $_POST['session_id'];
    $reason = "Admin Action: Disqualified due to suspicious behavior.";
    
    $stmt = $dbh->prepare("UPDATE tblexam_sessions SET Status='Terminated', AdminMessage=? WHERE ID=?");
    $stmt->execute([$reason, $sid]);
    
    echo "<script>alert('Student exam terminated.');</script>";
}

// --- ACTION 2: SEND WARNING ---
if(isset($_POST['warn'])) {
    $sid = $_POST['session_id'];
    $msg = "⚠️ WARNING: Movement or Tab Switching detected. Please focus on the screen.";
    
    $stmt = $dbh->prepare("UPDATE tblexam_sessions SET TeacherWarningMsg=? WHERE ID=?");
    $stmt->execute([$msg, $sid]);
    
    echo "<script>alert('Warning sent to student.');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>AI Surveillance | Monitor</title>
    <meta http-equiv="refresh" content="8"> 
    
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <style>
        /* --- DARK THEME --- */
        * { box-sizing: border-box; }
        body { 
            background-color: #0b1120; 
            font-family: 'Segoe UI', sans-serif; color: #e2e8f0; margin: 0; 
        }

        /* HEADER */
        .simple-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 60px;
            background: rgba(15, 23, 42, 0.95); border-bottom: 1px solid #334155;
            display: flex; align-items: center; justify-content: space-between; padding: 0 30px; z-index: 1000;
        }
        .live-dot { 
            height: 10px; width: 10px; background: #ef4444; border-radius: 50%; 
            display: inline-block; margin-right: 10px; box-shadow: 0 0 10px #ef4444; 
            animation: pulse 1s infinite;
        }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

        .content-wrap { margin-top: 80px; padding: 30px; }
        
        .grid { 
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; 
        }

        /* MONITOR CARD */
        .card {
            background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden;
            display: flex; flex-direction: column;
        }
        
        /* Risk Borders */
        .risk-low { border-top: 4px solid #10b981; }
        .risk-med { border-top: 4px solid #f59e0b; }
        .risk-high { border-top: 4px solid #ef4444; box-shadow: 0 0 20px rgba(239, 68, 68, 0.2); }

        .cam-feed {
            width: 100%; height: 200px; background: #000; position: relative;
        }
        .cam-feed img { width: 100%; height: 100%; object-fit: cover; opacity: 0.9; }
        
        .overlay {
            position: absolute; top: 10px; left: 10px; right: 10px; display: flex; justify-content: space-between;
        }
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; background: rgba(0,0,0,0.6); backdrop-filter: blur(2px); }
        .bg-danger { background: rgba(220, 38, 38, 0.9); }

        .card-body { padding: 15px; }
        .st-name { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 5px; }
        .exam-name { font-size: 12px; color: #94a3b8; margin-bottom: 15px; }

        .metrics { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 5px; margin-bottom: 15px; }
        .metric { background: #0f172a; padding: 8px; border-radius: 6px; text-align: center; }
        .m-val { display: block; font-size: 16px; font-weight: 700; color: #fff; }
        .m-lbl { font-size: 10px; color: #64748b; text-transform: uppercase; }
        .warn-text { color: #f87171; }

        .actions { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px; border-top: 1px solid #334155; padding: 15px; 
        }
        .btn { border: none; padding: 8px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; width: 100%; }
        .btn-warn { background: #f59e0b; color: #000; }
        .btn-kill { background: #7f1d1d; color: #fca5a5; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>

    <div class="simple-header">
        <div style="font-weight:700; font-size:18px; color:white;">
            <span class="live-dot"></span> AI PROCTOR HUB
        </div>
        <a href="dashboard.php" style="color:#94a3b8; text-decoration:none; font-size:14px;">Exit Monitor</a>
    </div>

    <div class="content-wrap">
        <div class="grid">
            <?php
            // Fetch Active Sessions
            // Matches columns in your ajax-monitor-update code (LastSnapshot, TabSwitchCount, MovementWarnings)
            $sql = "SELECT s.ID as SessID, s.LastSnapshot, s.TabSwitchCount, s.MovementWarnings,
                    u.FullName, u.RollNumber, e.ExamTitle 
                    FROM tblexam_sessions s
                    JOIN tbluser u ON s.StudentID = u.ID
                    JOIN tblexams e ON s.ExamID = e.ID
                    WHERE s.Status = 'Ongoing'
                    ORDER BY s.TabSwitchCount DESC"; 
            
            $query = $dbh->prepare($sql);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_OBJ);

            if($query->rowCount() > 0) {
                foreach($results as $row) {
                    
                    // 1. Image Path Logic
                    // Your AJAX saves to 'evidence/filename.jpg' inside user folder.
                    // Admin is in 'admin/', so path is '../user/evidence/filename.jpg'
                    $imgSrc = "../user/" . $row->LastSnapshot;
                    
                    if(empty($row->LastSnapshot) || !file_exists("../user/" . $row->LastSnapshot)) {
                        $imgSrc = "https://via.placeholder.com/400x250/000000/FFFFFF?text=Connecting...";
                    }

                    // 2. Risk Calculation
                    $tabs = intval($row->TabSwitchCount);
                    $move = intval($row->MovementWarnings);
                    $risk = ($tabs * 10) + ($move * 5); // Simple formula

                    // Determine CSS Class
                    $css = "risk-low";
                    if($risk > 20) $css = "risk-med";
                    if($risk > 50) $css = "risk-high";
            ?>
            
            <div class="card <?php echo $css; ?>">
                <div class="cam-feed">
                    <img src="<?php echo $imgSrc; ?>" alt="Live Feed">
                    <div class="overlay">
                        <span class="badge">ID: <?php echo htmlentities($row->RollNumber); ?></span>
                        <?php if($risk > 50) echo '<span class="badge bg-danger">HIGH RISK</span>'; ?>
                    </div>
                </div>

                <div class="card-body">
                    <div class="st-name"><?php echo htmlentities($row->FullName); ?></div>
                    <div class="exam-name"><?php echo htmlentities($row->ExamTitle); ?></div>

                    <div class="metrics">
                        <div class="metric">
                            <span class="m-val <?php echo ($tabs>0)?'warn-text':''; ?>"><?php echo $tabs; ?></span>
                            <span class="m-lbl">Switches</span>
                        </div>
                        <div class="metric">
                            <span class="m-val <?php echo ($move>0)?'warn-text':''; ?>"><?php echo $move; ?></span>
                            <span class="m-lbl">Movements</span>
                        </div>
                        <div class="metric">
                            <span class="m-val"><?php echo $risk; ?>%</span>
                            <span class="m-lbl">Risk Score</span>
                        </div>
                    </div>
                </div>

                <div class="actions">
                    <form method="POST">
                        <input type="hidden" name="session_id" value="<?php echo $row->SessID; ?>">
                        <button type="submit" name="warn" class="btn btn-warn">⚠ Send Warning</button>
                    </form>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure you want to terminate this student?');">
                        <input type="hidden" name="session_id" value="<?php echo $row->SessID; ?>">
                        <button type="submit" name="terminate" class="btn btn-kill">✖ Terminate</button>
                    </form>
                </div>
            </div>

            <?php 
                } 
            } else { 
                echo '<div style="grid-column:1/-1; text-align:center; padding:50px; color:#64748b;">
                        <h2>No Active Exams</h2>
                        <p>Waiting for students to begin...</p>
                      </div>';
            } 
            ?>
        </div>
    </div>
    <script>
    // Function to reload the grid without refreshing the whole page
    function loadFeeds() {
        var container = document.querySelector('.grid');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajax-get-feeds.php', true);
        xhr.onload = function() {
            if (this.status === 200) {
                container.innerHTML = this.responseText;
            }
        };
        xhr.send();
    }

    // Refresh every 3 seconds for near-live updates
    setInterval(loadFeeds, 3000);
    
    // Load immediately on open
    loadFeeds();
</script>

</body>
</html>