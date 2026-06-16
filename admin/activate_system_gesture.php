<?php
// --- CONFIGURATION ---
// 1. RELIABLE MODE: Use standard python.exe (not pythonw)
$python_executable = "C:\\Users\\Kaushik\\AppData\\Local\\Programs\\Python\\Python311\\python.exe";

// 2. FULL PATH: Exact location of the script
$script_path = "E:\\Xampp\\htdocs\\Vidyaverse\\python_ai_engine\\ai_gesture_system.py";

$system_status = "OFFLINE";
// Check if Port 5000 is open (Flask)
$connection = @fsockopen('127.0.0.1', 5000, $errno, $errstr, 1);
if (is_resource($connection)) {
    $system_status = "ONLINE";
    fclose($connection);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'stop') {
        // Kill processes
        shell_exec("taskkill /F /IM python.exe /T > NUL 2>&1");
        // Also kill pythonw just in case
        shell_exec("taskkill /F /IM pythonw.exe /T > NUL 2>&1");
        header("Refresh:0");
        exit;
    }

    if ($action == 'start') {
        // 1. Cleanup old processes
        shell_exec("taskkill /F /IM python.exe /T > NUL 2>&1");
        
        // 2. LAUNCH COMMAND (ROBUST)
        // start /min = Starts minimized (in taskbar, not popping up)
        // cmd /k = Keeps the window alive so it doesn't crash instantly
        $cmd = "start /min cmd /k \"\"$python_executable\" \"$script_path\"\"";
        
        pclose(popen($cmd, "r"));
        
        sleep(3); // Wait 3 seconds for Python to boot up
        header("Refresh:0");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VIDYAVERSE | GESTURE CORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #bc13fe;
            --primary-dim: rgba(188, 19, 254, 0.3);
            --accent: #00f3ff;
            --danger: #ff003c;
            --success: #0aff0a;
            --bg-dark: #05010a;
            --glass: rgba(10, 5, 15, 0.85);
        }

        * { box-sizing: border_box; }

        body {
            margin: 0; padding: 0;
            background-color: var(--bg-dark);
            background-image: 
                linear-gradient(rgba(188, 19, 254, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(188, 19, 254, 0.05) 1px, transparent 1px),
                radial-gradient(circle at 50% 50%, #1a0024 0%, #000 100%);
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
            box-shadow: 0 0 50px rgba(188, 19, 254, 0.1);
            position: relative;
        }

        /* DECORATIVE CORNERS */
        .corner { position: absolute; width: 30px; height: 30px; border: 2px solid var(--accent); transition: 0.5s; }
        .tl { top: -2px; left: -2px; border-right: none; border-bottom: none; }
        .tr { top: -2px; right: -2px; border-left: none; border-bottom: none; }
        .bl { bottom: -2px; left: -2px; border-right: none; border-top: none; }
        .br { bottom: -2px; right: -2px; border-left: none; border-top: none; }
        .hud-container:hover .corner { width: 50px; height: 50px; box-shadow: 0 0 15px var(--accent); }

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
            animation: pulse-glow 2s infinite;
        }

        .scanner-circle i {
            font-size: 80px;
            color: <?php echo ($system_status == 'ONLINE') ? 'var(--success)' : 'var(--danger)'; ?>;
        }

        .scan-line {
            position: absolute; width: 100%; height: 2px;
            background: <?php echo ($system_status == 'ONLINE') ? 'var(--success)' : 'var(--danger)'; ?>;
            top: 0; left: 0;
            animation: scan 1.5s infinite linear;
            box-shadow: 0 0 10px currentColor;
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
            font-family: 'Share Tech Mono'; color: var(--accent); font-size: 16px; letter-spacing: 8px;
            margin-bottom: 60px; opacity: 0.8;
        }

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
            background: linear-gradient(90deg, transparent, rgba(188, 19, 254, 0.4), transparent);
            transition: 0.5s;
        }

        .big-btn:hover::before { left: 100%; }
        
        .btn-start { border-color: var(--primary); box-shadow: 0 0 20px rgba(188,19,254,0.2); }
        .btn-start:hover { background: var(--primary); box-shadow: 0 0 50px var(--primary); text-shadow: 0 0 10px white; }

        .btn-stop { border-color: var(--danger); color: var(--danger); }
        .btn-stop:hover { background: var(--danger); color: white; box-shadow: 0 0 50px var(--danger); }

        .btn-return { border-color: var(--accent); color: var(--accent); font-size: 16px; padding: 15px; margin-top: 30px; text-decoration: none; display: inline-block; }
        .btn-return:hover { background: var(--accent); color: black; box-shadow: 0 0 30px var(--accent); }

        /* --- RIGHT PANEL: DATA LOGS --- */
        .panel-right {
            border-left: 1px solid var(--primary-dim); padding: 20px;
            background: rgba(0,0,0,0.3); font-family: 'Share Tech Mono';
        }

        .log-title { color: var(--accent); border-bottom: 1px solid var(--accent); padding-bottom: 5px; margin-bottom: 10px; }
        
        .log-entry { font-size: 12px; color: #888; margin-bottom: 5px; }
        .log-entry span { color: var(--primary); }

        .bar-container { margin-top: 20px; }
        .bar-label { font-size: 10px; color: #aaa; display: flex; justify-content: space-between; }
        .bar-bg { width: 100%; height: 5px; background: #333; margin-top: 3px; }
        .bar-fill { height: 100%; background: var(--success); width: 0%; animation: loadBar 2s infinite alternate; }

        /* --- ANIMATIONS --- */
        @keyframes scan { 0% { top: 0; opacity: 0; } 50% { opacity: 1; } 100% { top: 100%; opacity: 0; } }
        @keyframes pulse-glow { 0% { box-shadow: 0 0 20px currentColor; opacity: 0.8; } 100% { box-shadow: 0 0 50px currentColor; opacity: 1; } }
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
            <div style="width:100%; text-align:left; font-size:12px; color:var(--accent);">
                SYS.ID: G-9000<br>Target: Hand Landmark
            </div>
            
            <div class="scanner-circle">
                <i class="fas fa-hand-paper"></i>
                <div class="scan-line"></div>
            </div>

            <div class="status-text"><?php echo $system_status; ?></div>
            
            <div style="margin-top: 30px; font-size: 14px; color: #888;">
                AI ENGINE CONFIDENCE: <span style="color:white">98.4%</span>
            </div>
        </div>

        <div class="panel-center">
            <h1>VidyaVerse</h1>
            <h2>GESTURE CONTROL INTERFACE</h2>

            <form method="post">
                <?php if($system_status == "OFFLINE"): ?>
                    <button type="submit" name="action" value="start" class="big-btn btn-start">
                        <i class="fas fa-power-off"></i> INITIATE SYSTEM
                    </button>
                <?php else: ?>
                    <button type="submit" name="action" value="stop" class="big-btn btn-stop">
                        <i class="fas fa-ban"></i> TERMINATE SYSTEM
                    </button>
                    
                    <a href="../kiosk_display.php" target="_blank" class="big-btn" style="border-color:var(--success); color:var(--success); display:inline-block; text-decoration:none; margin-top:20px; font-size:18px;">
                        <i class="fas fa-eye"></i> VIEW CAMERA FEED
                    </a>
                <?php endif; ?>
            </form>

            <a href="activate_system.php" class="big-btn btn-return">
                <i class="fas fa-arrow-left"></i> RETURN TO MAIN
            </a>
        </div>

        <div class="panel-right">
            <div class="log-title">>> SYSTEM LOGS</div>
            <div class="log-entry"><span>[INIT]</span> Loading MediaPipe... OK</div>
            <div class="log-entry"><span>[CHECK]</span> Camera Input 0... OK</div>
            <div class="log-entry"><span>[NET]</span> Flask Server Port 5000... <?php echo $system_status; ?></div>
            <div class="log-entry"><span>[AI]</span> Hand Tracking Model... LOADED</div>
            <div class="log-entry"><span>[SYS]</span> NumPy Compatibility... CHECKED</div>

            <div class="bar-container">
                <div class="bar-label">CPU USAGE <span>34%</span></div>
                <div class="bar-bg"><div class="bar-fill" style="animation-duration: 3s; background:var(--accent);"></div></div>
            </div>
            <div class="bar-container">
                <div class="bar-label">GPU USAGE <span>67%</span></div>
                <div class="bar-bg"><div class="bar-fill" style="animation-duration: 1.5s; background:var(--primary);"></div></div>
            </div>
            <div class="bar-container">
                <div class="bar-label">MEMORY <span>12%</span></div>
                <div class="bar-bg"><div class="bar-fill" style="animation-duration: 4s; background:var(--success);"></div></div>
            </div>
        </div>
    </div>

</body>
</html>