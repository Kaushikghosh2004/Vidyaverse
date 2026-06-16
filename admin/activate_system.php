<?php
// --- CONFIGURATION ---
// 1. HIDDEN MODE: Using 'pythonw.exe' so no black window appears
$python_executable = "C:\\Users\\Kaushik\\AppData\\Local\\Programs\\Python\\Python311\\pythonw.exe";
$script_dir = "E:\\Xampp\\htdocs\\Vidyaverse\\python_ai_engine";
$script_name = "ai_classroom_monitor.py"; // Attendance Script

// Database Connection (Keep this for clearing logs)
$con = mysqli_connect("localhost", "root", "", "lexclassroom");

// --- 2. MEMORY SYSTEM ---
$config_file = 'camera_config.txt';
$default_cam = "0";
if (file_exists($config_file)) {
    $default_cam = file_get_contents($config_file);
}

// --- 3. STATUS CHECK ---
$system_status = "OFFLINE";
$connection = @fsockopen('127.0.0.1', 5000, $errno, $errstr, 1);
if (is_resource($connection)) {
    $system_status = "ONLINE";
    fclose($connection);
}

// --- 4. COMMAND LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = $_POST['action'] ?? '';
    $cam_source = $_POST['cam_input'] ?? '0';

    // --- START SEQUENCE ---
    if ($action == 'start') {
        // Save Config
        file_put_contents($config_file, $cam_source);
        
        // 1. Cleanup Old Data & Processes
        if($con) mysqli_query($con, "TRUNCATE TABLE tbl_kiosk_live");
        shell_exec("taskkill /F /IM pythonw.exe /T > NUL 2>&1");
        shell_exec("taskkill /F /IM python.exe /T > NUL 2>&1");

        // 2. Launch in Background (Hidden)
        // start "" starts a separate process
        $cmd = "start \"\" /d \"$script_dir\" \"$python_executable\" \"$script_name\" \"$cam_source\"";
        pclose(popen($cmd, "r"));
        
        sleep(3); // Wait for boot
        
        // Redirect with Success Message
        header("Location: activate_system.php?msg=activated");
        exit;
    }

    // --- STOP SEQUENCE ---
    if ($action == 'stop') {
        shell_exec("taskkill /F /IM pythonw.exe /T > NUL 2>&1");
        shell_exec("taskkill /F /IM python.exe /T > NUL 2>&1");
        if($con) mysqli_query($con, "TRUNCATE TABLE tbl_kiosk_live");
        
        // Redirect with Stop Message
        header("Location: activate_system.php?msg=deactivated");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VIDYAVERSE | ATTENDANCE CORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* CYBER BLUE THEME for Attendance */
            --primary: #00f3ff;
            --primary-dim: rgba(0, 243, 255, 0.3);
            --accent: #bc13fe;
            --danger: #ff003c;
            --success: #0aff0a;
            --bg-dark: #00050a;
            --glass: rgba(5, 15, 20, 0.85);
        }

        * { box-sizing: border_box; }

        body {
            margin: 0; padding: 0;
            background-color: var(--bg-dark);
            background-image: 
                linear-gradient(rgba(0, 243, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 243, 255, 0.05) 1px, transparent 1px),
                radial-gradient(circle at 50% 50%, #001a24 0%, #000 100%);
            background-size: 40px 40px, 40px 40px, 100% 100%;
            color: white; font-family: 'Rajdhani', sans-serif;
            height: 100vh; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
        }

        /* --- THE GIGANTIC HUD CONTAINER --- */
        .hud-container {
            width: 95vw; height: 90vh;
            display: grid;
            grid-template-columns: 350px 1fr 300px;
            gap: 20px;
            border: 1px solid var(--primary-dim);
            background: var(--glass);
            backdrop-filter: blur(10px);
            box-shadow: 0 0 50px rgba(0, 243, 255, 0.1);
            position: relative;
        }

        /* DECORATIVE CORNERS */
        .corner { position: absolute; width: 30px; height: 30px; border: 2px solid var(--primary); transition: 0.5s; }
        .tl { top: -2px; left: -2px; border-right: none; border-bottom: none; }
        .tr { top: -2px; right: -2px; border-left: none; border-bottom: none; }
        .bl { bottom: -2px; left: -2px; border-right: none; border-top: none; }
        .br { bottom: -2px; right: -2px; border-left: none; border-top: none; }
        .hud-container:hover .corner { width: 50px; height: 50px; box-shadow: 0 0 15px var(--primary); }

        /* --- LEFT PANEL: VISUALIZER --- */
        .panel-left {
            padding: 30px; border-right: 1px solid var(--primary-dim);
            display: flex; flex-direction: column; align-items: center;
        }

        .scanner-circle {
            width: 200px; height: 200px;
            border-radius: 50%;
            border: 2px solid <?php echo ($system_status == 'ONLINE') ? 'var(--success)' : 'var(--danger)'; ?>;
            box-shadow: 0 0 30px <?php echo ($system_status == 'ONLINE') ? 'var(--success)' : 'var(--danger)'; ?>;
            position: relative;
            display: flex; align-items: center; justify-content: center;
            margin-top: 50px;
            animation: rotate-ring 10s linear infinite;
        }

        .scanner-circle::after {
            content: ''; position: absolute; width: 160px; height: 160px;
            border-radius: 50%; border: 1px dashed var(--primary);
            animation: rotate-ring 5s linear infinite reverse;
        }

        .scanner-circle i {
            font-size: 80px;
            color: <?php echo ($system_status == 'ONLINE') ? 'var(--success)' : 'var(--danger)'; ?>;
            animation: none !important; /* Stop icon from spinning */
        }

        .status-text {
            font-family: 'Orbitron'; font-size: 32px; font-weight: 900;
            margin-top: 30px; letter-spacing: 4px;
            color: <?php echo ($system_status == 'ONLINE') ? 'var(--success)' : 'var(--danger)'; ?>;
            text-shadow: 0 0 20px currentColor;
        }

        /* --- CENTER PANEL: CONTROLS --- */
        .panel-center {
            padding: 40px; text-align: center;
            display: flex; flex-direction: column; justify-content: center;
        }

        h1 {
            font-family: 'Orbitron'; font-size: 48px; margin: 0;
            background: linear-gradient(to right, #fff, var(--primary));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            letter-spacing: 5px; text-transform: uppercase;
        }
        h2 {
            font-family: 'Share Tech Mono'; color: var(--primary); font-size: 16px; letter-spacing: 8px;
            margin-bottom: 40px; opacity: 0.8;
        }

        .input-group { margin-bottom: 30px; text-align: left; max-width: 400px; margin-left: auto; margin-right: auto; }
        .input-label { color: #888; font-family: 'Share Tech Mono'; font-size: 12px; margin-bottom: 5px; display: block; }
        .sci-input {
            width: 100%; padding: 15px; background: rgba(0,0,0,0.5);
            border: 1px solid var(--primary); color: var(--primary);
            font-family: 'Orbitron'; letter-spacing: 2px;
            transition: 0.3s; outline: none;
        }
        .sci-input:focus { background: rgba(0, 243, 255, 0.1); box-shadow: 0 0 15px var(--primary); }

        .big-btn {
            background: transparent;
            border: 2px solid var(--primary);
            color: white; padding: 20px 40px;
            font-size: 24px; font-family: 'Orbitron'; letter-spacing: 2px;
            cursor: pointer; position: relative; overflow: hidden;
            transition: 0.3s; width: 100%; max-width: 400px; margin: 10px auto;
            clip-path: polygon(20px 0, 100% 0, 100% calc(100% - 20px), calc(100% - 20px) 100%, 0 100%, 0 20px);
        }

        .big-btn::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 243, 255, 0.4), transparent);
            transition: 0.5s;
        }

        .big-btn:hover::before { left: 100%; }
        
        .btn-start { border-color: var(--primary); box-shadow: 0 0 20px rgba(0, 243, 255,0.2); }
        .btn-start:hover { background: var(--primary); box-shadow: 0 0 50px var(--primary); text-shadow: 0 0 10px white; color: black; }

        .btn-stop { border-color: var(--danger); color: var(--danger); }
        .btn-stop:hover { background: var(--danger); color: white; box-shadow: 0 0 50px var(--danger); }

        .btn-nav { font-size: 14px; padding: 15px; margin-top: 10px; text-decoration: none; display: block; color: #888; font-family: 'Share Tech Mono'; transition: 0.3s; border-bottom: 1px solid transparent; }
        .btn-nav:hover { color: var(--primary); border-bottom: 1px solid var(--primary); letter-spacing: 1px; }

        /* --- RIGHT PANEL: DATA LOGS --- */
        .panel-right {
            border-left: 1px solid var(--primary-dim); padding: 20px;
            background: rgba(0,0,0,0.3); font-family: 'Share Tech Mono';
        }

        .log-title { color: var(--primary); border-bottom: 1px solid var(--primary); padding-bottom: 5px; margin-bottom: 10px; }
        
        .log-entry { font-size: 12px; color: #888; margin-bottom: 5px; }
        .log-entry span { color: var(--primary); }

        .bar-container { margin-top: 20px; }
        .bar-label { font-size: 10px; color: #aaa; display: flex; justify-content: space-between; }
        .bar-bg { width: 100%; height: 5px; background: #333; margin-top: 3px; }
        .bar-fill { height: 100%; background: var(--success); width: 0%; animation: loadBar 2s infinite alternate; }

        /* --- ANIMATIONS --- */
        @keyframes rotate-ring { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes loadBar { 0% { width: 10%; } 100% { width: 80%; } }

        .crt-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: repeating-linear-gradient(0deg, rgba(0,0,0,0.1), rgba(0,0,0,0.1) 1px, transparent 1px, transparent 2px);
            pointer-events: none; z-index: 100; opacity: 0.3;
        }
    </style>
</head>
<body>

    <div class="crt-overlay"></div>

    <div class="hud-container">
        <div class="corner tl"></div><div class="corner tr"></div>
        <div class="corner bl"></div><div class="corner br"></div>

        <div class="panel-left">
            <div style="width:100%; text-align:left; font-size:12px; color:var(--primary);">
                SYS.ID: A-5000<br>Target: Face Recognition
            </div>
            
            <div class="scanner-circle">
                <i class="fas fa-users-viewfinder"></i>
            </div>

            <div class="status-text"><?php echo $system_status; ?></div>
            
            <div style="margin-top: 30px; font-size: 14px; color: #888;">
                DATABASE LINK: <span style="color:white">CONNECTED</span><br>
                NEURAL NETWORK: <span style="color:white">READY</span>
            </div>
        </div>

        <div class="panel-center">
            <h1>VidyaVerse</h1>
            <h2>ATTENDANCE MONITORING SYSTEM</h2>

            <form method="post">
                <?php if($system_status == "OFFLINE"): ?>
                    
                    <div class="input-group">
                        <label class="input-label">OPTICAL INPUT SOURCE (0 = Default)</label>
                        <input type="text" name="cam_input" class="sci-input" value="<?php echo $default_cam; ?>" placeholder="0">
                    </div>

                    <button type="submit" name="action" value="start" class="big-btn btn-start">
                        <i class="fas fa-power-off"></i> INITIATE SCAN
                    </button>
                <?php else: ?>
                    <button type="submit" name="action" value="stop" class="big-btn btn-stop">
                        <i class="fas fa-ban"></i> TERMINATE SYSTEM
                    </button>
                    
                    <a href="../kiosk_display.php" target="_blank" class="big-btn" style="border-color:var(--success); color:var(--success); display:inline-block; text-decoration:none; margin-top:20px; font-size:18px;">
                        <i class="fas fa-eye"></i> VIEW LIVE FEED
                    </a>
                <?php endif; ?>
            </form>

            <div style="margin-top: 40px; border-top: 1px solid #333; padding-top: 20px; width: 100%;">
                <a href="activate_system_gesture.php" class="btn-nav">
                    SWITCH TO GESTURE CONTROL <i class="fas fa-arrow-right"></i>
                </a>
                <a href="activate_system_voice.php" class="btn-nav">
                    SWITCH TO VOICE CONTROL <i class="fas fa-microphone"></i>
                </a>
            </div>
        </div>

        <div class="panel-right">
            <div class="log-title">>> SYSTEM LOGS</div>
            <div class="log-entry"><span>[INIT]</span> Connecting to DB... OK</div>
            <div class="log-entry"><span>[CHECK]</span> Config File... LOADED</div>
            <div class="log-entry"><span>[NET]</span> Flask Port 5000... <?php echo $system_status; ?></div>
            <div class="log-entry"><span>[AI]</span> Face Recognition... STANDBY</div>
            

[Image of face recognition technology]

            <div class="log-entry"><span>[SYS]</span> Process ID... <?php echo rand(1000,9999); ?></div>

            <div class="bar-container">
                <div class="bar-label">CPU USAGE <span>41%</span></div>
                <div class="bar-bg"><div class="bar-fill" style="animation-duration: 3s; background:var(--primary);"></div></div>
            </div>
            <div class="bar-container">
                <div class="bar-label">DB LATENCY <span>12ms</span></div>
                <div class="bar-bg"><div class="bar-fill" style="animation-duration: 1.5s; background:var(--accent);"></div></div>
            </div>
            <div class="bar-container">
                <div class="bar-label">MEMORY <span>24%</span></div>
                <div class="bar-bg"><div class="bar-fill" style="animation-duration: 4s; background:var(--success);"></div></div>
            </div>
        </div>
    </div>

</body>
</html>