<?php
session_start();
include('../includes/dbconnection.php');
if (empty($_SESSION['ocastid'])) { header('location:logout.php'); exit; }

// Fetch Courses for Dropdown
$courses = [];
try {
    $tid = $_SESSION['ocastid'];
    // Fetch Course Name AND Branch Name
    $q = $dbh->query("SELECT ID, CourseName, BranchName FROM tblcourse ORDER BY CourseName ASC");
    $courses = $q->fetchAll(PDO::FETCH_OBJ);
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Live Mission Control | VidyaVerse</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- CORE VARIABLES --- */
        :root {
            --bg-deep: #050505;
            --bg-panel: #0f172a;
            --bg-card: rgba(30, 41, 59, 0.6); /* Glassy */
            --accent-primary: #3b82f6; /* Neon Blue */
            --accent-success: #10b981; /* Neon Green */
            --accent-danger: #ef4444; /* Neon Red */
            --text-main: #ffffff;
            --text-muted: #94a3b8;
            --font-main: 'Outfit', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --glass-border: 1px solid rgba(255, 255, 255, 0.08);
            --glow-blue: 0 0 20px rgba(59, 130, 246, 0.3);
            --glow-red: 0 0 20px rgba(239, 68, 68, 0.3);
        }

        * { box-sizing: border-box; }

        body { 
            background-color: var(--bg-deep);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.1), transparent 20%), 
                radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.05), transparent 20%);
            color: var(--text-main); 
            font-family: var(--font-main); 
            margin: 0; height: 100vh; display: flex; overflow: hidden;
        }

        /* --- SIDEBAR (CONTROL DECK) --- */
        .sidebar-controls {
            width: 360px; 
            background: rgba(15, 23, 42, 0.95);
            border-right: var(--glass-border);
            backdrop-filter: blur(10px);
            padding: 40px 30px; 
            display: flex; flex-direction: column; justify-content: space-between;
            z-index: 50; 
            box-shadow: 10px 0 50px rgba(0,0,0,0.5);
        }

        .brand-title {
            font-size: 24px; font-weight: 800; letter-spacing: -0.5px;
            margin-bottom: 5px; background: linear-gradient(90deg, #fff, #94a3b8);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .status-badge {
            background: rgba(255,255,255,0.05); color: var(--text-muted); padding: 8px 16px; 
            border-radius: 40px; font-size: 12px; font-weight: 700; border: var(--glass-border);
            display: inline-flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px;
            transition: 0.3s;
        }
        .status-badge.live { 
            background: rgba(16, 185, 129, 0.15); color: var(--accent-success); 
            border-color: rgba(16, 185, 129, 0.3); box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
        }
        .dot { width: 8px; height: 8px; background: var(--text-muted); border-radius: 50%; }
        .live .dot { background: var(--accent-success); animation: blink 1s infinite; }

        .control-group { margin-top: 40px; }
        
        label { 
            color: var(--text-muted); font-size: 11px; text-transform: uppercase; 
            font-weight: 700; letter-spacing: 1.5px; margin-bottom: 12px; display: block; 
        }
        
        /* Modern Select */
        .select-wrapper { position: relative; margin-bottom: 25px; }
        .select-wrapper::after {
            content: '\e64b'; font-family: 'themify'; position: absolute; right: 15px; top: 50%;
            transform: translateY(-50%); color: var(--text-muted); pointer-events: none;
        }
        select {
            width: 100%; background: rgba(0,0,0,0.3); border: 1px solid #334155; 
            color: white; padding: 18px; border-radius: 12px; font-size: 14px; font-family: inherit;
            outline: none; cursor: pointer; appearance: none; transition: 0.3s;
        }
        select:focus { border-color: var(--accent-primary); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        select:disabled { opacity: 0.5; cursor: not-allowed; background: #0f172a; }
        option { background: #0f172a; color: white; padding: 10px; }

        /* Buttons */
        .btn-launch {
            width: 100%; background: var(--accent-primary); color: white; border: none;
            padding: 20px; border-radius: 14px; font-weight: 700; font-size: 16px;
            cursor: pointer; transition: all 0.3s ease; 
            display: flex; align-items: center; justify-content: center; gap: 12px;
            box-shadow: var(--glow-blue); letter-spacing: 0.5px;
        }
        .btn-launch:hover { transform: translateY(-2px); box-shadow: 0 5px 30px rgba(59, 130, 246, 0.5); }
        
        .btn-stop {
            width: 100%; background: rgba(239, 68, 68, 0.1); color: var(--accent-danger); 
            border: 1px solid var(--accent-danger);
            padding: 20px; border-radius: 14px; font-weight: 700; font-size: 16px;
            cursor: pointer; display: none; transition: 0.3s;
            display: none; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-stop:hover { background: var(--accent-danger); color: white; box-shadow: var(--glow-red); }

        .return-link {
            color: var(--text-muted); text-decoration: none; font-size: 13px; font-weight: 500;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: 0.2s; opacity: 0.7;
        }
        .return-link:hover { opacity: 1; color: white; }

        /* --- MAIN MONITOR AREA --- */
        .monitor-area { 
            flex-grow: 1; padding: 40px; position: relative; 
            background: radial-gradient(circle at 50% 50%, #1e293b 0%, #0b1120 100%);
            perspective: 1000px;
        }
        
        /* LOCK SCREEN */
        .lock-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(5, 5, 5, 0.8); backdrop-filter: blur(20px);
            z-index: 40; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            color: var(--text-muted); text-align: center;
            animation: fadeIn 0.5s;
        }
        .lock-circle {
            width: 120px; height: 120px; border-radius: 50%;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 30px;
        }
        .lock-icon { font-size: 40px; color: var(--text-muted); }

        /* DASHBOARD GRID */
        .dashboard-grid { 
            display: grid; grid-template-columns: 2fr 1fr; gap: 30px; 
            height: 100%; max-width: 1600px; margin: 0 auto;
        }

        .card { 
            background: var(--bg-card); border: var(--glass-border); border-radius: 24px; 
            padding: 30px; overflow: hidden; display: flex; flex-direction: column; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: relative;
        }
        
        .card-header { 
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .card-title { 
            font-size: 18px; font-weight: 700; color: #fff; 
            display: flex; align-items: center; gap: 10px; letter-spacing: 0.5px;
        }
        .live-indicator {
            font-size: 11px; color: var(--accent-primary); font-weight: 600; text-transform: uppercase;
            animation: pulseText 2s infinite;
        }

        /* DOUBT FEED */
        .doubt-list { flex-grow: 1; overflow-y: auto; padding-right: 10px; scroll-behavior: smooth; }
        .doubt-list::-webkit-scrollbar { width: 6px; }
        .doubt-list::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }

        .doubt-item { 
            background: linear-gradient(145deg, #1e293b, #0f172a); 
            padding: 20px; margin-bottom: 15px; 
            border-radius: 16px; border: 1px solid rgba(255,255,255,0.05);
            animation: slideInUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transition: all 0.3s; position: relative; overflow: hidden;
        }
        .doubt-item::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
            background: var(--accent-primary);
        }
        .doubt-item:hover { transform: translateX(5px); border-color: rgba(59, 130, 246, 0.3); }

        .doubt-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .student-label { font-size: 12px; font-weight: 800; color: var(--accent-primary); text-transform: uppercase; letter-spacing: 1px; }
        .time-label { font-family: var(--font-mono); font-size: 12px; color: var(--text-muted); opacity: 0.7; }
        
        .doubt-msg { font-size: 16px; line-height: 1.6; color: #e2e8f0; font-weight: 400; }

        .btn-solve { 
            position: absolute; right: 20px; bottom: 20px;
            background: rgba(16, 185, 129, 0.1); border: 1px solid var(--accent-success); 
            color: var(--accent-success); padding: 6px 14px; 
            border-radius: 8px; font-size: 11px; font-weight: 700; cursor: pointer; 
            transition: 0.2s; text-transform: uppercase;
        }
        .btn-solve:hover { background: var(--accent-success); color: #fff; }

        /* BREAK COUNTER */
        .break-wrapper { 
            flex-grow: 1; display: flex; flex-direction: column; 
            align-items: center; justify-content: center; position: relative; 
        }
        .break-display { 
            font-size: 160px; font-weight: 800; color: #334155; 
            line-height: 1; transition: 0.3s; font-variant-numeric: tabular-nums;
        }
        .break-display.active { color: var(--accent-success); text-shadow: 0 0 50px rgba(16, 185, 129, 0.3); }
        .break-display.warning { color: var(--accent-danger); animation: shake 0.5s; text-shadow: 0 0 50px rgba(239, 68, 68, 0.4); }
        
        .waiting-text { color: var(--text-muted); font-size: 14px; margin-top: -10px; text-transform: uppercase; letter-spacing: 2px; }

        .btn-grant {
            width: 100%; padding: 20px; background: var(--accent-danger); color: white;
            border: none; border-radius: 12px; font-weight: 800; font-size: 14px; 
            cursor: pointer; margin-top: 30px; letter-spacing: 1px; display: none;
            box-shadow: var(--glow-red); animation: popIn 0.3s;
        }

        /* ANIMATIONS */
        @keyframes blink { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.8); } }
        @keyframes slideInUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-8px); } 75% { transform: translateX(8px); } }
        @keyframes pulseText { 0%, 100% { opacity: 0.5; } 50% { opacity: 1; } }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    </style>
</head>
<body>

    <audio id="notifSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

    <div class="sidebar-controls">
        <div>
            <div class="brand-title">VidyaVerse</div>
            <div style="font-size:12px; color:#64748b; margin-bottom:30px;">Live Teacher Console v2.0</div>
            
            <div id="statusBadge" class="status-badge"><div class="dot"></div> System Offline</div>
            
            <div class="control-group">
                <label>Target Audience</label>
                <div class="select-wrapper">
                    <select id="courseSelect">
                        <option value="" disabled selected>Select Batch...</option>
                        <option value="0" style="color:var(--accent-success); font-weight:bold;">🌍 Global Session (All Students)</option>
                        <?php foreach($courses as $c) { ?>
                            <option value="<?php echo $c->ID; ?>">
                                <?php echo htmlentities($c->CourseName . " (" . $c->BranchName . ")"); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <button id="btnStart" class="btn-launch" onclick="startClass()">
                    <i class='bx bx-broadcast'></i> GO LIVE NOW
                </button>

                <button id="btnStop" class="btn-stop" style="display:none;" onclick="stopClass()">
                    <i class='bx bx-stop-circle'></i> END SESSION
                </button>

                <div style="text-align:center; margin-top:20px;">
                    <a href="#" onclick="forceReset()" style="color:#475569; font-size:11px; text-decoration:underline; opacity:0.5; transition:0.3s;">Console Unresponsive? Reset</a>
                </div>
            </div>
        </div>

        <a href="dashboard.php" class="return-link"><i class='bx bx-left-arrow-alt'></i> Return to Dashboard</a>
    </div>

    <div class="monitor-area">
        
        <div id="lockScreen" class="lock-overlay">
            <div class="lock-circle"><i class='bx bx-lock-alt lock-icon'></i></div>
            <h2 style="margin:0; font-size:28px; color:white;">Console Locked</h2>
            <p style="margin-top:10px; max-width:300px;">Select a batch on the left and initialize the live stream to begin monitoring.</p>
        </div>

        <div class="dashboard-grid">
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class='bx bx-message-square-dots' style="color:var(--accent-primary);"></i> Incoming Doubts</div>
                    <div class="live-indicator">● Listening</div>
                </div>
                <div id="doubtContainer" class="doubt-list">
                    </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class='bx bx-timer' style="color:var(--accent-success);"></i> Break Requests</div>
                </div>
                <div class="break-wrapper">
                    <div id="breakCount" class="break-display">0</div>
                    <div class="waiting-text">Students Waiting</div>
                    <button id="grantBtn" class="btn-grant" onclick="grantBreak()">GRANT 5 MIN BREAK</button>
                </div>
            </div>

        </div>
    </div>

    <script>
        let sessionID = null;
        let poller = null;
        let previousDoubtCount = 0;

        // 1. INIT
        $(document).ready(function() {
            $.post('../includes/live-updates.php', { action: 'check_active_session' }, function(resp) {
                if(resp.status === 'active') {
                    sessionID = resp.session_id;
                    if(resp.course_id !== undefined && resp.course_id !== null) {
                        $('#courseSelect').val(resp.course_id);
                    }
                    activateUI(true);
                }
            }, 'json');
        });

        // 2. START
        function startClass() {
            let cid = $('#courseSelect').val();
            if(cid === null) { alert("Please select a target batch first."); return; }

            $('#btnStart').html('<i class="bx bx-loader-alt bx-spin"></i> Initializing...');

            $.post('../includes/live-updates.php', { action: 'start_session', course_id: cid }, function(resp) {
                if(resp.status === 'success') {
                    sessionID = resp.session_id;
                    activateUI(true);
                } else {
                    alert('Error: ' + resp.message);
                    resetButtons();
                }
            }, 'json').fail(function() { alert("Connection failed."); resetButtons(); });
        }

        // 3. STOP
        function stopClass() {
            if(!confirm("Are you sure you want to end this session?")) return;
            $.post('../includes/live-updates.php', { action: 'end_session' }, function() {
                location.reload(); 
            });
        }

        // 4. RESET
        function forceReset() {
            if(!confirm("Force reset will kill any active session. Proceed?")) return;
            $.post('../includes/live-updates.php', { action: 'end_session' }, function() {
                location.reload();
            });
        }

        // --- UI LOGIC ---
        function activateUI(isLive) {
            if(isLive) {
                $('#lockScreen').fadeOut(500);
                $('#btnStart').hide();
                $('#btnStop').css('display', 'flex'); // Flex to keep icon aligned
                $('#courseSelect').prop('disabled', true); 
                $('#statusBadge').addClass('live').html('<div class="dot"></div> System Online');
                
                if(poller) clearInterval(poller);
                poller = setInterval(fetchUpdates, 3000);
                fetchUpdates();
            }
        }

        function resetButtons() {
            $('#btnStart').html('<i class="bx bx-broadcast"></i> GO LIVE NOW');
        }

        // --- DATA FETCHING ---
        function fetchUpdates() {
            if(!sessionID) return;

            $.post('../includes/live-updates.php', { action: 'fetch_teacher_data', session_id: sessionID }, function(data) {
                
                // Breaks
                let breaks = parseInt(data.breaks || 0);
                let bDisplay = $('#breakCount');
                bDisplay.text(breaks);

                if(breaks > 0) bDisplay.addClass('active');
                if(breaks >= 5) {
                    bDisplay.addClass('warning');
                    $('#grantBtn').slideDown();
                } else {
                    bDisplay.removeClass('warning');
                    $('#grantBtn').slideUp();
                }

                // Doubts
                if(data.doubts && data.doubts.length > 0) {
                    // Play sound if new doubt arrived
                    if(data.doubts.length > previousDoubtCount) {
                        try { document.getElementById('notifSound').play(); } catch(e){}
                    }
                    previousDoubtCount = data.doubts.length;

                    let html = "";
                    data.doubts.forEach(d => {
                        let timePart = d.Timestamp.split(' ')[1]; 
                        html += `
                            <div class="doubt-item" id="doubt-${d.ID}">
                                <div class="doubt-header">
                                    <span class="student-label">Anonymous Student</span>
                                    <span class="time-label">${timePart}</span>
                                </div>
                                <div class="doubt-msg">${d.Message}</div>
                                <button class="btn-solve" onclick="solveDoubt(${d.ID})">
                                    <i class='bx bx-check'></i> Mark Solved
                                </button>
                            </div>`;
                    });
                    $('#doubtContainer').html(html);
                } else {
                    previousDoubtCount = 0;
                    $('#doubtContainer').html(`
                        <div style="text-align:center; color:var(--text-muted); margin-top:100px; opacity:0.5;">
                            <i class='bx bx-message-square-x' style="font-size:48px; margin-bottom:10px;"></i><br>
                            No active doubts
                        </div>`);
                }

            }, 'json');
        }

        function solveDoubt(id) {
            $('#doubt-' + id).css('transform', 'translateX(100%)').css('opacity', '0');
            setTimeout(() => { $('#doubt-' + id).remove(); }, 300);
            $.post('../includes/live-updates.php', { action: 'solve_doubt', doubt_id: id });
        }

        function grantBreak() {
            $.post('../includes/live-updates.php', { action: 'clear_breaks', session_id: sessionID }, function() {
                $('#breakCount').text('0').removeClass('active warning');
                $('#grantBtn').slideUp();
            });
        }
    </script>
</body>
</html>