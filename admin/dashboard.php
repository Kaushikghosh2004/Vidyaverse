<?php
// 1. TURN ON ERROR REPORTING (To find out exactly why it won't open)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. ADVANCED SECURE SESSION START
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// 3. DATABASE CONNECTION
include('includes/dbconnection.php');

// --- MAINTENANCE MODE AJAX HANDLER ---
if (isset($_POST['action']) && $_POST['action'] == 'toggle_maintenance') {
    header('Content-Type: application/json');
    $status = ($_POST['status'] === 'true') ? '1' : '0';
    try {
        $dbh->query("CREATE TABLE IF NOT EXISTS `system_settings` (`setting_key` varchar(50) NOT NULL, `setting_value` varchar(100) NOT NULL, PRIMARY KEY (`setting_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $dbh->query("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('maintenance_mode', '0')");
        
        $stmt = $dbh->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'maintenance_mode'");
        $stmt->execute([$status]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit; 
}

// 4. STRICT SECURITY & ANOMALY DETECTION CHECK
if (empty($_SESSION['admin_id'])) {
    header('location:login.php');
    exit;
}

$current_ip = $_SERVER['REMOTE_ADDR'];
$current_ua = $_SERVER['HTTP_USER_AGENT'];

if (!isset($_SESSION['bound_ip'])) {
    $_SESSION['bound_ip'] = $current_ip;
    $_SESSION['bound_ua'] = $current_ua;
} else {
    if ($_SESSION['bound_ip'] !== $current_ip || $_SESSION['bound_ua'] !== $current_ua) {
        session_unset();
        session_destroy();
        header('location:login.php?error=unauthorized_device');
        exit;
    }
}

// 5. FETCH ADMIN NAME
$adminName = "Admin"; 
$aid = $_SESSION['admin_id'];
try {
    $sql = "SELECT AdminName FROM tbladmin WHERE ID=:aid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':aid', $aid, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);
    if($result) { $adminName = $result->AdminName; }
} catch (Exception $e) {}

// 6. THREAT INTELLIGENCE
$threatLevel = "SECURE";
$failedLogins = 0;
$systemStatusColor = "var(--tech-emerald)"; 

try {
    $stmt = $dbh->query("SELECT count(*) FROM tblaudit_logs WHERE action='failed_login' AND timestamp >= (NOW() - INTERVAL 1 DAY)");
    if($stmt) { $failedLogins = $stmt->fetchColumn(); }
    if ($failedLogins > 15) { $threatLevel = "CRITICAL THREAT"; $systemStatusColor = "var(--tech-red)"; } 
    elseif ($failedLogins > 5) { $threatLevel = "ELEVATED"; $systemStatusColor = "var(--tech-orange)"; }
} catch (Exception $e) {}

// 7. FAULT-TOLERANT STATS FETCHING
$count_students = 0; $count_teachers = 0; $count_exams = 0; $count_classes = 0; $cnt_pending = 0;
try { $count_students = $dbh->query("SELECT count(*) FROM tbluser")->fetchColumn(); } catch (Exception $e) {}
try { $count_teachers = $dbh->query("SELECT count(*) FROM tblteacher")->fetchColumn(); } catch (Exception $e) {}
try { $count_exams = $dbh->query("SELECT count(*) FROM tblexam")->fetchColumn(); } catch (Exception $e) {}
try { $count_classes = $dbh->query("SELECT count(*) FROM tblcourse")->fetchColumn(); } catch (Exception $e) {} 
try { $cnt_pending = $dbh->query("SELECT count(*) FROM tblgrading_queue WHERE status='pending'")->fetchColumn(); } catch (Exception $e) {}

// 8. FETCH CURRENT MAINTENANCE STATUS
$is_maintenance = '0';
try {
    $stmt = $dbh->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $is_maintenance = $row['setting_value']; }
} catch (Exception $e) {}

$pageTitle = "VidyaVerse | Central Security Unit";
include('includes/header.php');
?>

<div class="container-fluid content-wrapper">
    <style>
        :root {
            --aero-bg: rgba(10, 15, 25, 0.75); --aero-border-light: rgba(0, 229, 255, 0.4); --aero-border-dark: rgba(0, 0, 0, 0.9);
            --metal-gradient: linear-gradient(135deg, #ffffff 0%, #a5f3fc 50%, #0891b2 100%); --gloss-highlight: inset 0 1px 2px rgba(255, 255, 255, 0.3);
            --tech-blue: rgba(14, 165, 233, 0.9); --tech-cyan: rgba(0, 229, 255, 0.9); --tech-purple: rgba(139, 92, 246, 0.9);
            --tech-pink: rgba(236, 72, 153, 0.9); --tech-orange: rgba(245, 158, 11, 0.9); --tech-red: rgba(239, 68, 68, 0.9); --tech-emerald: rgba(16, 185, 129, 0.9);
        }
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; color: #e2e8f0; background-color: #020617; overflow-x: hidden; }
        .ai-cyber-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -2; overflow: hidden; background: url('data:image/svg+xml;utf8,<svg width="60" height="103.923" xmlns="http://www.w3.org/2000/svg"><path d="M 60 25.98 L 30 8.66 L 0 25.98 L 0 60.62 L 30 77.94 L 60 60.62 Z" fill="none" stroke="rgba(0, 229, 255, 0.05)" stroke-width="2"/></svg>'), linear-gradient(to bottom, rgba(3, 7, 18, 0.92), rgba(8, 145, 178, 0.25)), url('https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed; background-size: auto, cover, cover; }
        .mini-robot { position: absolute; z-index: -1; display: flex; flex-direction: column; align-items: center; filter: drop-shadow(0 0 10px currentColor); opacity: 0.8; }
        .mini-robot i { font-size: 28px; }
        .thruster { width: 8px; height: 15px; background: #fff; border-radius: 50%; margin-top: -5px; animation: thrust 0.5s infinite alternate; }
        @keyframes thrust { 0% { transform: scale(1); opacity: 0.8; } 100% { transform: scale(1.5) translateY(5px); opacity: 0.3; } }
        .bot-1 { color: #00e5ff; animation: glide-1 25s ease-in-out infinite; } .bot-1 .thruster { box-shadow: 0 0 15px 5px #00e5ff; background: #a5f3fc; }
        @keyframes glide-1 { 0% { top: 30%; left: -10%; transform: rotate(15deg); } 50% { top: 40%; left: 80%; transform: rotate(-5deg); } 100% { top: 30%; left: 110%; transform: rotate(10deg); } }
        .bot-2 { color: #a855f7; font-size: 20px; animation: glide-2 35s linear infinite; } .bot-2 .thruster { box-shadow: 0 0 15px 5px #a855f7; background: #e9d5ff; height: 10px; }
        @keyframes glide-2 { 0% { top: 10%; right: -10%; transform: scaleX(-1) rotate(5deg); } 30% { top: 15%; right: 40%; transform: scaleX(-1) rotate(-10deg); } 70% { top: 8%; right: 70%; transform: scaleX(-1) rotate(5deg); } 100% { top: 12%; right: 110%; transform: scaleX(-1) rotate(0deg); } }
        .bot-3 { color: #ec4899; font-size: 22px; animation: glide-3 18s ease-in-out infinite alternate; } .bot-3 .thruster { box-shadow: 0 0 15px 5px #ec4899; background: #fbcfe8; }
        @keyframes glide-3 { 0% { bottom: 10%; left: 10%; transform: rotate(5deg) translateY(0); } 25% { bottom: 15%; left: 30%; transform: rotate(-15deg) translateY(-20px); } 50% { bottom: 8%; left: 60%; transform: rotate(10deg) translateY(10px); } 75% { bottom: 20%; left: 80%; transform: rotate(-5deg) translateY(-15px); } 100% { bottom: 12%; left: 95%; transform: rotate(0deg) translateY(0); } }
        .ai-cyber-bg::after { content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to bottom, transparent 50%, rgba(0, 229, 255, 0.05) 51%, transparent 51%); background-size: 100% 4px; z-index: -1; pointer-events: none; animation: scanline 8s linear infinite; }
        @keyframes scanline { 0% { background-position: 0 0; } 100% { background-position: 0 100vh; } }
        .content-wrapper { position: relative; z-index: 1; padding: 25px; }
        .module-card, .stat-card, .welcome-banner, .security-matrix { background: var(--aero-bg); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-top: 1px solid var(--aero-border-light); border-left: 1px solid rgba(255, 255, 255, 0.1); border-right: 1px solid var(--aero-border-dark); border-bottom: 1px solid var(--aero-border-dark); border-radius: 18px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8), var(--gloss-highlight); transition: all 0.4s ease; position: relative; overflow: hidden; }
        .module-card:hover, .stat-card:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 30px 60px rgba(0, 0, 0, 0.9), inset 0 2px 15px rgba(0, 229, 255, 0.2); border-top: 1px solid #00e5ff; }
        .welcome-text h1, .stat-info h3, .section-header { background: var(--metal-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 0px 4px 10px rgba(0,229,255,0.3); font-weight: 800; }
        
        .welcome-banner { padding: 35px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; background: linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(8, 145, 178, 0.25)); }
        .welcome-text h1 { margin: 0; font-size: 34px; letter-spacing: 1px; }
        .welcome-text p { margin: 8px 0 0; color: #a5f3fc; font-size: 15px; font-weight: 500; letter-spacing: 1.5px; text-transform: uppercase; }
        .date-badge { background: rgba(2, 6, 23, 0.6); padding: 12px 25px; border-radius: 12px; border-top: 1px solid var(--aero-border-light); box-shadow: var(--gloss-highlight), 0 0 20px rgba(0,229,255,0.2); font-weight: 800; color: #00e5ff; text-transform: uppercase; letter-spacing: 2px; }

        /* --- MAINTENANCE TOGGLE UI --- */
        .maint-controls { display: flex; flex-direction: column; align-items: flex-end; gap: 15px; }
        .maintenance-toggle-box { background: rgba(2, 6, 23, 0.8); padding: 10px 20px; border-radius: 12px; border: 1px solid var(--tech-red); box-shadow: 0 0 15px rgba(239, 68, 68, 0.2); display: flex; align-items: center; gap: 15px; transition: 0.3s; }
        .m-label { font-size: 12px; font-weight: 800; color: #fca5a5; text-transform: uppercase; letter-spacing: 2px; }
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.1); border: 1px solid #334155; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: #94a3b8; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--tech-red); border-color: var(--tech-red); box-shadow: 0 0 15px rgba(239, 68, 68, 0.5); }
        input:checked + .slider:before { transform: translateX(24px); background-color: #fff; }

        .security-matrix { padding: 20px 35px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid <?php echo $systemStatusColor; ?>; background: linear-gradient(90deg, rgba(0,0,0,0.8) 0%, rgba(10, 15, 30, 0.65) 100%); }
        .security-status { display: flex; align-items: center; gap: 15px; }
        .status-indicator { width: 16px; height: 16px; border-radius: 50%; background: <?php echo $systemStatusColor; ?>; box-shadow: 0 0 15px <?php echo $systemStatusColor; ?>; animation: pulseStatus 2s infinite; }
        @keyframes pulseStatus { 0% { opacity: 0.6; box-shadow: 0 0 10px <?php echo $systemStatusColor; ?>; } 50% { opacity: 1; box-shadow: 0 0 25px <?php echo $systemStatusColor; ?>; } 100% { opacity: 0.6; box-shadow: 0 0 10px <?php echo $systemStatusColor; ?>; } }
        .security-info h4 { margin: 0; font-size: 13px; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; }
        .security-info h2 { margin: 5px 0 0; font-size: 24px; color: <?php echo $systemStatusColor; ?>; font-weight: 800; letter-spacing: 1px; }
        .security-metrics { display: flex; gap: 40px; }
        .metric-box text { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .metric-box val { display: block; font-size: 20px; color: #fff; font-weight: 700; }
        .section-header { font-size: 16px; text-transform: uppercase; letter-spacing: 3px; margin: 45px 0 25px 0; display: flex; align-items: center; gap: 15px; text-shadow: 0 2px 5px rgba(0,0,0,0.8); }
        .section-header::after { content: ''; height: 2px; background: linear-gradient(to right, #00e5ff, transparent); flex: 1; opacity: 0.5; }
        .section-header i { -webkit-text-fill-color: #00e5ff; font-size: 22px; filter: drop-shadow(0 0 8px #00e5ff); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 30px; margin-bottom: 30px; }
        .modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
        .stat-card { padding: 25px; display: flex; align-items: center; gap: 20px; }
        .stat-icon { width: 65px; height: 65px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 30px; box-shadow: inset 0 2px 5px rgba(255,255,255,0.4), 0 8px 20px rgba(0,0,0,0.6); border-top: 1px solid rgba(255,255,255,0.6); }
        .stat-info h3 { margin: 0; font-size: 36px; }
        .stat-info p { margin: 0; font-size: 12px; color: #e2e8f0; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700; text-shadow: 0 1px 3px #000; }
        .module-card { padding: 25px; display: flex; flex-direction: column; justify-content: space-between; min-height: 200px; }
        .module-header h4 { margin: 0 0 12px; font-size: 19px; color: #f8fafc; font-weight: 800; letter-spacing: 0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.8); }
        .module-desc { font-size: 13px; color: #cbd5e1; line-height: 1.6; margin-bottom: 25px; font-weight: 500; text-shadow: 0 1px 2px #000; }
        .module-btn { text-decoration: none; padding: 14px; border-radius: 12px; text-align: center; font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 2px; transition: all 0.3s ease; display: block; border-top: 1px solid rgba(255,255,255,0.7); border-bottom: 2px solid rgba(0,0,0,0.6); box-shadow: 0 8px 20px rgba(0,0,0,0.5), inset 0 2px 5px rgba(255,255,255,0.4); color: #ffffff !important; -webkit-text-fill-color: #ffffff; position: relative; overflow: hidden; }
        .module-btn::after { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(to bottom right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%); transform: rotate(45deg) translateY(-100%); transition: transform 0.6s ease; }
        .module-btn:hover::after { transform: rotate(45deg) translateY(100%); }
        .module-btn:hover { filter: brightness(130%); transform: translateY(-3px); box-shadow: 0 12px 25px rgba(0,0,0,0.6), inset 0 2px 8px rgba(255,255,255,0.7); }
        .bg-blue, .btn-blue { background: linear-gradient(135deg, #0284c7, #0ea5e9); }
        .bg-cyan, .btn-cyan { background: linear-gradient(135deg, #0891b2, #06b6d4); }
        .bg-purple, .btn-purple { background: linear-gradient(135deg, #7c3aed, #8b5cf6); }
        .bg-pink, .btn-pink { background: linear-gradient(135deg, #db2777, #ec4899); }
        .bg-orange, .btn-orange { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .stat-icon i { color: #fff; -webkit-text-fill-color: #fff; text-shadow: 0 3px 6px rgba(0,0,0,0.5); }
        .card-accent-blue { border-left: 4px solid #38bdf8; } .card-accent-cyan { border-left: 4px solid #22d3ee; } .card-accent-purple { border-left: 4px solid #a78bfa; } .card-accent-pink { border-left: 4px solid #f472b6; } .card-accent-orange { border-left: 4px solid #fbbf24; }
        .ai-card { background: linear-gradient(135deg, rgba(0, 229, 255, 0.15), rgba(0,0,0,0.8)); border: 1px solid #00e5ff; box-shadow: 0 0 30px rgba(0, 229, 255, 0.15), inset 0 0 20px rgba(0, 229, 255, 0.1); }
        .ai-card:hover { box-shadow: 0 0 50px rgba(0, 229, 255, 0.4), inset 0 0 30px rgba(0, 229, 255, 0.2); border: 1px solid #ffffff; }
        .ai-pulse { width: 12px; height: 12px; background: #00e5ff; border-radius: 50%; display: inline-block; margin-right: 10px; box-shadow: 0 0 20px #00e5ff, 0 0 40px #ffffff; animation: pulseAI 1.2s ease-in-out infinite; }
        @keyframes pulseAI { 0% { opacity: 0.5; transform: scale(0.9); } 50% { opacity: 1; transform: scale(1.2); } 100% { opacity: 0.5; transform: scale(0.9); } }
        
        /* AJAX Toast */
        #ajaxToast { position: fixed; top: 90px; right: -400px; background: rgba(15, 23, 42, 0.95); border: 1px solid var(--tech-emerald); border-left: 4px solid var(--tech-emerald); padding: 18px 25px; border-radius: 12px; display: flex; align-items: center; gap: 15px; z-index: 10000; transition: right 0.5s; }
        #ajaxToast.show { right: 30px; }
        #ajaxToast.error-toast { border-color: var(--tech-red); border-left-color: var(--tech-red); }
        #ajaxToast.error-toast i { color: var(--tech-red) !important; }
    </style>

    <div class="ai-cyber-bg">
        <div class="mini-robot bot-1"><i class="fas fa-robot"></i><div class="thruster"></div></div>
        <div class="mini-robot bot-2"><i class="fas fa-robot"></i><div class="thruster"></div></div>
        <div class="mini-robot bot-3"><i class="fas fa-robot"></i><div class="thruster"></div></div>
    </div>

    <!-- AJAX TOAST ELEMENT -->
    <div id="ajaxToast">
        <i class="fas fa-info-circle" style="font-size:24px; color: var(--tech-emerald);"></i>
        <div>
            <h4 id="toastTitle" style="margin:0 0 4px; font-size:14px; font-weight:800; color:#fff; text-transform:uppercase;">Notification</h4>
            <p id="toastMessage" style="margin:0; font-size:12px; color:#a1a1aa;">Message goes here</p>
        </div>
    </div>

    <div class="welcome-banner">
        <div class="welcome-text">
            <h1>VIDYAVERSE CENTRAL UNIT, <?php echo htmlentities($adminName); ?>!</h1>
            <p>AI-Enabled Educational Ecosystem Master Console</p>
        </div>
        
        <!-- MAINTENANCE TOGGLE -->
        <div class="maint-controls">
            <div class="maintenance-toggle-box" id="maintBox">
                <span class="m-label"><i class="fas fa-tools"></i> Maintenance Mode</span>
                <label class="switch">
                    <input type="checkbox" id="maintToggle" <?php echo ($is_maintenance == '1') ? 'checked' : ''; ?> onchange="toggleMaintenance(this.checked)">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="date-badge"><i class="ti-calendar"></i> <?php echo date("l, d M Y"); ?></div>
        </div>
    </div>

    <div class="security-matrix">
        <div class="security-status">
            <div class="status-indicator"></div>
            <div class="security-info">
                <h4>System Threat Intelligence</h4>
                <h2><?php echo $threatLevel; ?></h2>
            </div>
        </div>
        <div class="security-metrics">
            <div class="metric-box"><text>Active Admin IP</text><val><?php echo htmlentities($current_ip); ?></val></div>
            <div class="metric-box"><text>Anomalies (24h)</text><val><?php echo htmlentities($failedLogins); ?></val></div>
            <div class="metric-box"><text>Session Lock</text><val style="color: var(--tech-emerald);"><i class="fas fa-lock"></i> BOUND</val></div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-orange"><i class="ti-pencil-alt"></i></div>
            <div class="stat-info">
                <h3><?php echo htmlentities($cnt_pending);?></h3>
                <p>Pending Reviews</p>
                <?php if($cnt_pending > 0): ?>
                    <a href="grading-queue.php" style="font-size:12px; color:#fbbf24; font-weight:800; display:block; margin-top:8px; text-decoration:none; letter-spacing:1px;">CHECK QUEUE &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card"><div class="stat-icon bg-cyan"><i class="ti-user"></i></div><div class="stat-info"><h3><?php echo htmlentities($count_students);?></h3><p>Students</p></div></div>
        <div class="stat-card"><div class="stat-icon bg-purple"><i class="ti-id-badge"></i></div><div class="stat-info"><h3><?php echo htmlentities($count_teachers);?></h3><p>Teachers</p></div></div>
        <div class="stat-card"><div class="stat-icon bg-pink"><i class="ti-clipboard"></i></div><div class="stat-info"><h3><?php echo htmlentities($count_exams);?></h3><p>Active Exams</p></div></div>
        <div class="stat-card"><div class="stat-icon bg-blue"><i class="ti-blackboard"></i></div><div class="stat-info"><h3><?php echo htmlentities($count_classes);?></h3><p>Classes</p></div></div>
    </div>

    <div class="section-header"><i class="ti-check-box"></i> Attendance & Access Control</div>
    <div class="modules-grid">
        <div class="module-card card-accent-cyan">
            <div class="module-header">
                <h4>Student Scanner Node</h4>
                <div class="module-desc">Launch the student auto-scanner. Integrates with the security matrix and timetable.</div>
            </div>
            <a href="scanner.php" class="module-btn btn-cyan"><i class="fas fa-qrcode"></i> Launch Scanner</a>
        </div>
        <div class="module-card card-accent-cyan">
            <div class="module-header">
                <h4>Faculty Scanner Node</h4>
                <div class="module-desc">Launch the teacher check-in/check-out scanner portal.</div>
            </div>
            <a href="teacher_scanner.php" class="module-btn btn-cyan"><i class="fas fa-qrcode"></i> Launch Scanner</a>
        </div>
        <div class="module-card card-accent-orange">
            <div class="module-header">
                <h4>Student Matrix & Logs</h4>
                <div class="module-desc">View student scan logs, enforce time rules, and manually override attendance.</div>
            </div>
            <a href="manage-student-attendance.php" class="module-btn btn-orange"><i class="fas fa-user-graduate"></i> Manage Students</a>
        </div>
        <div class="module-card card-accent-orange">
            <div class="module-header">
                <h4>Faculty Matrix & Logs</h4>
                <div class="module-desc">View faculty logs, adjust time constraints, and resolve missed scans.</div>
            </div>
            <a href="manage-faculty-attendance.php" class="module-btn btn-orange"><i class="fas fa-user-tie"></i> Manage Faculty</a>
        </div>
    </div>

    <div class="section-header"><i class="ti-eye"></i> AI & Biometrics Control</div>
    <div class="modules-grid">
        <div class="module-card ai-card"><div class="module-header"><h4><span class="ai-pulse"></span> VidyaVerse Neural Core</h4><div class="module-desc">Activate AI Engine, Voice Intent Logic, and Kiosk Nodes.</div></div><a href="activate_system.php" class="module-btn btn-cyan"><i class="fas fa-power-off"></i> LAUNCH CONSOLE</a></div>
        <div class="module-card card-accent-pink"><div class="module-header"><h4>Live Monitor</h4><div class="module-desc">View real-time attendance tracking grid.</div></div><a href="dept_attendance_report.php" class="module-btn btn-pink">Open Monitor</a></div>
        <div class="module-card card-accent-cyan"><div class="module-header"><h4>Biometric Enroll</h4><div class="module-desc">Scan and register new student faces into the system.</div></div><a href="enroll_face.php" class="module-btn btn-cyan">Enroll User</a></div>
    </div>

    <div class="section-header"><i class="ti-layers"></i> Academics & Courses</div>
    <div class="modules-grid">
        <div class="module-card card-accent-blue"><div class="module-header"><h4>Manage Courses</h4><div class="module-desc">Add, edit, and control all course curriculums.</div></div><a href="course.php" class="module-btn btn-blue">Go to Courses</a></div>
        <div class="module-card card-accent-blue"><div class="module-header"><h4>Notice Board</h4><div class="module-desc">Post announcements for students.</div></div><a href="newsorannouncement.php" class="module-btn btn-blue">Update Notices</a></div>
    </div>

    <div class="section-header"><i class="ti-target"></i> Examination Control</div>
    <div class="modules-grid">
        <div class="module-card card-accent-orange"><div class="module-header"><h4>Question Bank</h4><div class="module-desc">Add questions, manage types, and filter by teacher.</div></div><a href="manage-questions.php" class="module-btn btn-orange">Manage Portal</a></div>
        <div class="module-card card-accent-orange"><div class="module-header"><h4>Schedule Exam</h4><div class="module-desc">Create and schedule new exam papers.</div></div><a href="create-exam.php" class="module-btn btn-orange">New Exam</a></div>
        <div class="module-card card-accent-pink"><div class="module-header"><h4>Live Monitor</h4><div class="module-desc">Proctoring system for active exams.</div></div><a href="exam-monitor.php" class="module-btn btn-pink">Open Monitor</a></div>
        <div class="module-card card-accent-orange"><div class="module-header"><h4>Manage Exam</h4><div class="module-desc">View active list, start or end exams.</div></div><a href="manage-exams.php" class="module-btn btn-orange">Manage Exams</a></div>
        <div class="module-card card-accent-orange"><div class="module-header"><h4>View Results</h4><div class="module-desc">Check student performance and grades.</div></div><a href="exam-results.php" class="module-btn btn-orange">Check Results</a></div>
    </div>

    <div class="section-header"><i class="ti-id-badge"></i> Faculty Management</div>
    <div class="modules-grid">
        <div class="module-card card-accent-cyan"><div class="module-header"><h4>Add Teacher</h4><div class="module-desc">Register new faculty members.</div></div><a href="add-teacher.php" class="module-btn btn-cyan">Add Teacher</a></div>
        <div class="module-card card-accent-cyan"><div class="module-header"><h4>Manage Faculty</h4><div class="module-desc">Update profiles and manage payroll.</div></div><a href="manage-teacher.php" class="module-btn btn-cyan">View Faculty</a></div>
    </div>

    <div class="section-header"><i class="ti-bar-chart"></i> Quality & Feedback</div>
    <div class="modules-grid">
        <div class="module-card card-accent-pink"><div class="module-header"><h4>Stealth Quality Check</h4><div class="module-desc">Launch live anonymous surveys to monitor teaching quality in real-time.</div></div><a href="quality-assurance.php" class="module-btn btn-pink"><i class="ti-signal"></i> Launch Console</a></div>
        <div class="module-card card-accent-blue"><div class="module-header"><h4>Performance Report</h4><div class="module-desc">View aggregated scores and determine teacher salary/bonus.</div></div><a href="teacher-performance.php" class="module-btn btn-blue"><i class="ti-stats-up"></i> View Report</a></div>
    </div>

    <div class="section-header"><i class="ti-calendar"></i> Scheduling</div>
    <div class="modules-grid">
        <div class="module-card card-accent-purple"><div class="module-header"><h4>Manage Batches</h4><div class="module-desc">Organize student groups.</div></div><a href="manage-batches.php" class="module-btn btn-purple">Batches</a></div>
        <div class="module-card card-accent-purple"><div class="module-header"><h4>Manage Subjects</h4><div class="module-desc">Assign subjects to courses.</div></div><a href="subject.php" class="module-btn btn-purple">Subjects</a></div>
        <div class="module-card card-accent-purple"><div class="module-header"><h4>Classrooms</h4><div class="module-desc">Manage room allocation.</div></div><a href="manage-classrooms.php" class="module-btn btn-purple">Rooms</a></div>
        <div class="module-card card-accent-purple"><div class="module-header"><h4>Availability</h4><div class="module-desc">Check free slots for teachers.</div></div><a href="teacher-availability.php" class="module-btn btn-purple">Check Slots</a></div>
        <div class="module-card card-accent-purple"><div class="module-header"><h4>Generate Timetable</h4><div class="module-desc">Auto-generate class routines.</div></div><a href="generate-timetable.php" class="module-btn btn-purple">Generate</a></div>
        <div class="module-card card-accent-purple"><div class="module-header"><h4>Visual Scheduler</h4><div class="module-desc">Drag and drop scheduler view.</div></div><a href="visual-scheduler.php" class="module-btn btn-purple">Visual View</a></div>
    </div>

    <div class="section-header"><i class="ti-files"></i> Reports & Requests</div>
    <div class="modules-grid">
        <div class="module-card card-accent-blue"><div class="module-header"><h4>Assignment Report</h4><div class="module-desc">View student submission stats.</div></div><a href="bwdates-assign-report.php" class="module-btn btn-blue">Manage Report</a></div>
        <div class="module-card card-accent-blue"><div class="module-header"><h4>Student Requests</h4><div class="module-desc">Approve password resets.</div></div><a href="manage-req-student.php" class="module-btn btn-blue">Manage Student PWD</a></div>
        <div class="module-card card-accent-blue"><div class="module-header"><h4>Teacher Requests</h4><div class="module-desc">Approve password resets.</div></div><a href="manage-req-teacher.php" class="module-btn btn-blue">Manage Teacher PWD</a></div>
        <div class="module-card card-accent-blue"><div class="module-header"><h4>Manage Students</h4><div class="module-desc">Manage student records and batches.</div></div><a href="manage-students.php" class="module-btn btn-blue">Manage Student Batches</a></div>
    </div>

    <div class="section-header"><i class="ti-write"></i> Paper Checking</div>
    <div class="modules-grid">
        <div class="module-card card-accent-blue"><div class="module-header"><h4>Pending Papers</h4><div class="module-desc">Grade student submissions and queue results.</div></div><a href="grading-queue.php" class="module-btn btn-blue">Manage Papers</a></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function updateMaintUI(isOn) {
            const box = document.getElementById('maintBox');
            if(isOn) {
                box.style.background = 'rgba(239, 68, 68, 0.15)';
                box.style.boxShadow = '0 0 25px rgba(239, 68, 68, 0.5)';
            } else {
                box.style.background = 'rgba(2, 6, 23, 0.8)';
                box.style.boxShadow = '0 0 15px rgba(239, 68, 68, 0.2)';
            }
        }
        
        updateMaintUI(document.getElementById('maintToggle').checked);

        function toggleMaintenance(status) {
            $.ajax({
                url: 'dashboard.php',
                type: 'POST',
                data: { action: 'toggle_maintenance', status: status },
                dataType: 'json',
                success: function(res) {
                    const toast = document.getElementById('ajaxToast');
                    const title = document.getElementById('toastTitle');
                    const msg = document.getElementById('toastMessage');
                    
                    if(res.success) {
                        updateMaintUI(status);
                        title.innerText = status ? 'System Offline' : 'System Online';
                        msg.innerText = status ? 'Maintenance Mode is active. Users are blocked.' : 'Main site is live.';
                        
                        if(status) {
                            toast.classList.add('error-toast');
                            toast.querySelector('i').className = 'fas fa-tools';
                        } else {
                            toast.classList.remove('error-toast');
                            toast.querySelector('i').className = 'fas fa-check-circle';
                        }
                    } else {
                        title.innerText = 'Error';
                        msg.innerText = 'Failed to update database.';
                        toast.classList.add('error-toast');
                    }
                    
                    toast.classList.add('show');
                    setTimeout(() => { toast.classList.remove('show'); }, 3500);
                },
                error: function() {
                    alert("Network error updating maintenance mode.");
                }
            });
        }
    </script>
</div>

<?php include('includes/footer.php');?>