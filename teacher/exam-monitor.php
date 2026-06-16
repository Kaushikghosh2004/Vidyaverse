<?php
session_start();
include('includes/dbconnection.php');

// --- 1. API MODE: Live Data Feed ---
if (isset($_GET['mode']) && $_GET['mode'] == 'live_data') {
    header('Content-Type: application/json');
    
    // Fetch Ongoing Sessions
    $sql = "SELECT s.ID as SessID, s.LastSnapshot, s.TabSwitchCount, s.MovementWarnings, 
                   u.FullName, u.RollNumber
            FROM tblexam_sessions s
            JOIN tbluser u ON s.StudentID = u.ID
            WHERE s.Status = 'Ongoing'";
    $query = $dbh->prepare($sql);
    $query->execute();
    $sessions = $query->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach($sessions as $row) {
        // Risk Calculation: (Tab * 20) + (Move * 10)
        $risk = ($row['TabSwitchCount'] * 20) + ($row['MovementWarnings'] * 10);
        
        $data[] = [
            'id' => $row['SessID'],
            'image' => $row['LastSnapshot'] ? "../user/" . $row['LastSnapshot'] : "",
            'name' => $row['FullName'],
            'roll' => $row['RollNumber'],
            'tab' => $row['TabSwitchCount'],
            'move' => $row['MovementWarnings'],
            'risk' => $risk
        ];
    }
    echo json_encode($data);
    exit; 
}

// --- 2. HTML MODE ---
if (empty($_SESSION['ocastid'])) { header('location:logout.php'); exit; }

// Handle Teacher Actions
if(isset($_POST['action_type'])) {
    $sid = $_POST['session_id'];
    $type = $_POST['action_type'];

    if($type == 'warn') {
        $dbh->prepare("UPDATE tblexam_sessions SET TeacherWarningMsg='WARNING: Focus on screen! Your risk level is rising.' WHERE ID=?")->execute([$sid]);
    } elseif($type == 'terminate') {
        $stmt = $dbh->prepare("UPDATE tblexam_sessions SET Status='Terminated', AdminMessage=? WHERE ID=?");
        $stmt->execute(['Revoked by Teacher due to High Risk Score.', $sid]);
    }
    header("Location: exam-monitor.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Live Proctor View | VidyaVerse</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- CRITICAL LAYOUT FIX --- */
        * { box-sizing: border-box; } 

        body { 
            background-color: #0f172a; 
            font-family: 'Inter', sans-serif; 
            color: #e2e8f0; 
            margin: 0; padding: 0; 
            overflow-x: hidden; /* Prevent horizontal scroll */
        }
        
        /* --- HEADER (FIXED & RESPONSIVE) --- */
        .header {
            position: fixed; top: 0; left: 0; right: 0;
            height: 70px;
            background: rgba(15, 23, 42, 0.95); 
            border-bottom: 1px solid #334155;
            display: flex; align-items: center; justify-content: space-between; 
            padding: 0 25px; 
            z-index: 1000;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }
        
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand i { color: #3b82f6; font-size: 24px; }
        .brand h2 { color: #fff; font-size: 20px; font-weight: 700; margin: 0; letter-spacing: 0.5px; }
        
        .live-indicator { 
            display: flex; align-items: center; gap: 8px; 
            background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4);
            padding: 6px 15px; border-radius: 30px; 
        }
        .dot { width: 8px; height: 8px; background: #ef4444; border-radius: 50%; animation: pulse 1.5s infinite; }
        .status-text { color: #f87171; font-size: 12px; font-weight: 700; letter-spacing: 1px; }
        
        @keyframes pulse { 0% { opacity: 1; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { opacity: 1; box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); } 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }

        /* --- GRID LAYOUT --- */
        .monitor-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px; 
            padding: 100px 25px 40px 25px; /* Top padding clears the fixed header */
            max-width: 1600px; margin: 0 auto;
        }

        /* STUDENT CARD */
        .student-card {
            background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden;
            transition: all 0.3s ease; position: relative; display: flex; flex-direction: column;
        }
        
        /* DYNAMIC BORDERS */
        .student-card.safe { border-color: #334155; }
        .student-card.warn { border-color: #f59e0b; box-shadow: 0 0 15px rgba(245, 158, 11, 0.15); }
        .student-card.danger { border-color: #ef4444; box-shadow: 0 0 20px rgba(239, 68, 68, 0.25); transform: scale(1.02); z-index: 10; }

        /* VIDEO AREA */
        .video-box { width: 100%; height: 200px; background: #000; position: relative; }
        .video-box img { width: 100%; height: 100%; object-fit: cover; opacity: 0.9; }
        .overlay { 
            position: absolute; bottom: 0; left: 0; right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
            padding: 12px; color: white; font-weight: 600; font-size: 14px;
            display: flex; justify-content: space-between; align-items: flex-end;
        }

        /* METRICS PANEL */
        .stats-panel { 
            padding: 15px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;
            background: #0f172a; border-bottom: 1px solid #334155;
        }
        .stat { text-align: center; }
        .stat span { display: block; font-size: 18px; font-weight: 700; color: #fff; }
        .stat label { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* CONTROLS */
        .controls { padding: 15px; display: flex; gap: 10px; background: #1e293b; }
        .btn { 
            flex: 1; padding: 10px; border: none; border-radius: 8px; cursor: pointer; 
            font-size: 12px; font-weight: 700; color: white; text-transform: uppercase; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        
        .btn-warn { background: #3b82f6; }
        .btn-warn:hover { background: #2563eb; transform: translateY(-2px); }
        
        .btn-revoke { background: #ef4444; animation: flashRed 2s infinite; }
        .btn-revoke:hover { background: #dc2626; transform: scale(1.05); }
        @keyframes flashRed { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }

        .btn-locked { 
            background: #334155; color: #64748b; cursor: not-allowed; 
            border: 1px dashed #475569;
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1; text-align: center; padding: 60px;
            color: #64748b; font-size: 16px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="brand">
            <i class="fas fa-shield-alt"></i>
            <h2>Proctor Guard</h2>
        </div>
        
        <div style="display:flex; align-items:center; gap:20px;">
            <div style="font-size:12px; color:#94a3b8; display:none; sm:block;">
                <i class="fas fa-sync fa-spin"></i> Syncing...
            </div>
            <div class="live-indicator">
                <div class="dot"></div>
                <span class="status-text">LIVE FEED</span>
            </div>
        </div>
    </div>

    <div class="monitor-grid" id="student-grid">
        <div class="empty-state">
            <i class="fas fa-circle-notch fa-spin fa-2x"></i><br><br>
            Connecting to secure feeds...
        </div>
    </div>

    <script>
        function updateDashboard() {
            fetch('exam-monitor.php?mode=live_data')
            .then(response => response.json())
            .then(data => {
                const grid = document.getElementById('student-grid');
                
                if(data.length === 0) {
                    grid.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-users-slash fa-3x" style="margin-bottom:15px; color:#334155;"></i>
                            <p>No students are currently taking an exam.</p>
                        </div>`;
                    return;
                }

                let html = '';
                
                data.forEach(student => {
                    // Logic: Risk Calculation
                    let cardClass = 'safe';
                    let riskColor = '#10b981'; // Green

                    if(student.risk > 55) {
                        cardClass = 'danger';
                        riskColor = '#ef4444'; // Red
                    } else if(student.risk > 45) {
                        cardClass = 'warn';
                        riskColor = '#f59e0b'; // Orange
                    }

                    // Logic: Unlock Button
                    let btnHtml = '';
                    if(student.risk >= 55) {
                        btnHtml = `
                            <form method="post" style="flex:1;">
                                <input type="hidden" name="session_id" value="${student.id}">
                                <input type="hidden" name="action_type" value="terminate">
                                <button class="btn btn-revoke">
                                    <i class="fas fa-ban"></i> REVOKE
                                </button>
                            </form>`;
                    } else {
                        btnHtml = `<button class="btn btn-locked"><i class="fas fa-lock"></i> LOCKED</button>`;
                    }

                    // Cache busting for image
                    let imgSource = student.image ? `${student.image}?t=${new Date().getTime()}` : 'https://via.placeholder.com/300x200/0f172a/ffffff?text=No+Signal';

                    html += `
                    <div class="student-card ${cardClass}">
                        <div class="video-box">
                            <img src="${imgSource}">
                            <div class="overlay">
                                <span>${student.name}</span>
                                <span style="font-size:11px; opacity:0.8;">${student.roll}</span>
                            </div>
                        </div>
                        
                        <div class="stats-panel">
                            <div class="stat">
                                <span>${student.tab}</span>
                                <label>Tabs</label>
                            </div>
                            <div class="stat">
                                <span>${student.move}</span>
                                <label>Motion</label>
                            </div>
                            <div class="stat">
                                <span style="color:${riskColor}">${student.risk}%</span>
                                <label>Risk</label>
                            </div>
                        </div>

                        <div class="controls">
                            <form method="post" style="flex:1;">
                                <input type="hidden" name="session_id" value="${student.id}">
                                <input type="hidden" name="action_type" value="warn">
                                <button class="btn btn-warn"><i class="fas fa-bell"></i> WARN</button>
                            </form>
                            <div style="flex:1;">
                                ${btnHtml}
                            </div>
                        </div>
                    </div>
                    `;
                });

                grid.innerHTML = html;
            })
            .catch(err => console.error('Stream Error:', err));
        }

        // Poll every 1.5 seconds
        setInterval(updateDashboard, 1500);
        updateDashboard();
    </script>

</body>
</html>