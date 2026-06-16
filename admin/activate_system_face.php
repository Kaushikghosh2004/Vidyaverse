<?php
// --- 1. CONFIGURATION ---
// Using 'pythonw.exe' so the black console window does NOT appear
$python_executable = "C:\\Users\\Kaushik\\AppData\\Local\\Programs\\Python\\Python311\\pythonw.exe";
$script_dir = "D:\\Xampp\\htdocs\\Vidyaverse\\python_ai_engine";
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
$ai_status = "OFFLINE";
$connection = @fsockopen('127.0.0.1', 5000, $errno, $errstr, 1);
if (is_resource($connection)) {
    $ai_status = "ONLINE";
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
    <title>Neural Core | VidyaVerse</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --neon-cyan: #00f3ff;
            --neon-green: #0aff0a;
            --neon-red: #ff003c;
            --glass-bg: rgba(20, 24, 40, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --depth: 1000px;
        }

        body {
            margin: 0; background-color: #050505;
            background-image: 
                radial-gradient(circle at 50% 50%, #1a1a2e 0%, #000 100%),
                linear-gradient(rgba(0, 243, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 243, 255, 0.03) 1px, transparent 1px);
            background-size: 100% 100%, 50px 50px, 50px 50px;
            height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Rajdhani', sans-serif; overflow: hidden; perspective: var(--depth);
        }

        .main-interface {
            width: 900px; height: 550px; background: var(--glass-bg);
            backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border: var(--glass-border); border-radius: 20px;
            box-shadow: 0 0 80px rgba(0,0,0,0.6), inset 0 0 30px rgba(255,255,255,0.05);
            display: grid; grid-template-columns: 1fr 1.5fr;
            padding: 40px; gap: 40px; transform: rotateX(5deg);
            transition: transform 0.3s ease; position: relative; z-index: 10;
        }
        .main-interface:hover { transform: rotateX(0deg) scale(1.01); }

        /* Left Column */
        .core-chamber {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            border-right: 1px solid rgba(255,255,255,0.1); padding-right: 40px; position: relative;
        }

        /* Cube Animation */
        .cube-wrapper { width: 140px; height: 140px; perspective: 600px; margin-bottom: 40px; }
        .cube { width: 100%; height: 100%; position: relative; transform-style: preserve-3d; animation: spin 10s infinite linear; }
        .face {
            position: absolute; width: 140px; height: 140px;
            border: 2px solid <?php echo ($ai_status == 'ONLINE') ? 'var(--neon-green)' : 'var(--neon-red)'; ?>;
            background: <?php echo ($ai_status == 'ONLINE') ? 'rgba(10, 255, 10, 0.1)' : 'rgba(255, 0, 60, 0.1)'; ?>;
            box-shadow: 0 0 30px <?php echo ($ai_status == 'ONLINE') ? 'var(--neon-green)' : 'var(--neon-red)'; ?>;
            display: flex; align-items: center; justify-content: center;
            font-size: 40px; color: #fff;
        }
        .front { transform: rotateY(0deg) translateZ(70px); }
        .back { transform: rotateY(180deg) translateZ(70px); }
        .right { transform: rotateY(90deg) translateZ(70px); }
        .left { transform: rotateY(-90deg) translateZ(70px); }
        .top { transform: rotateX(90deg) translateZ(70px); }
        .bottom { transform: rotateX(-90deg) translateZ(70px); }
        @keyframes spin { from { transform: rotateX(0) rotateY(0); } to { transform: rotateX(360deg) rotateY(360deg); } }
        
        .core-status {
            font-size: 36px; font-weight: 800;
            color: <?php echo ($ai_status == 'ONLINE') ? 'var(--neon-green)' : 'var(--neon-red)'; ?>;
            text-shadow: 0 0 15px currentColor; letter-spacing: 2px;
        }

        /* Right Column */
        .control-deck { display: flex; flex-direction: column; justify-content: center; }
        .header-title { font-family: 'Orbitron', sans-serif; font-size: 28px; color: #fff; margin-bottom: 5px; text-transform: uppercase; }
        .sub-header { font-family: 'Share Tech Mono', monospace; color: var(--neon-cyan); margin-bottom: 20px; font-size: 14px; opacity: 0.7; }

        .cam-input-group { margin-bottom: 20px; }
        .cam-label { display: block; color: #888; font-size: 10px; margin-bottom: 5px; letter-spacing: 1px; }
        .sci-input {
            width: 100%; padding: 12px; background: rgba(0,0,0,0.5); border: 1px solid var(--neon-cyan);
            color: var(--neon-cyan); font-family: 'Share Tech Mono', monospace;
            font-size: 14px; border-radius: 4px; transition: 0.3s;
        }
        .sci-input:focus { outline: none; box-shadow: 0 0 15px rgba(0, 243, 255, 0.3); background: rgba(0,0,0,0.8); }

        /* Buttons */
        .btn-3d {
            position: relative; display: block; width: 100%; padding: 20px;
            border: none; background: transparent; font-family: 'Rajdhani', sans-serif; 
            font-weight: 700; font-size: 18px; text-transform: uppercase; letter-spacing: 2px;
            cursor: pointer; margin-bottom: 20px; transition: 0.2s;
            clip-path: polygon(15px 0, 100% 0, 100% calc(100% - 15px), calc(100% - 15px) 100%, 0 100%, 0 15px);
        }
        .btn-start { border: 1px solid var(--neon-green); color: var(--neon-green); }
        .btn-start:hover { background: var(--neon-green); color: #000; box-shadow: 0 0 30px var(--neon-green); }

        .btn-stop { border: 1px solid var(--neon-red); color: var(--neon-red); }
        .btn-stop:hover { background: var(--neon-red); color: #fff; box-shadow: 0 0 30px var(--neon-red); }

        .btn-kiosk { border: 1px solid var(--neon-cyan); color: var(--neon-cyan); text-align: center; text-decoration: none; }
        .btn-kiosk:hover { background: rgba(0, 243, 255, 0.2); box-shadow: 0 0 20px var(--neon-cyan); }

        /* Terminal */
        .terminal {
            height: 100px; background: rgba(0,0,0,0.8);
            border: 1px solid rgba(255,255,255,0.1); border-left: 3px solid var(--neon-cyan);
            padding: 15px; font-family: 'Share Tech Mono', monospace;
            font-size: 12px; color: #aaa; overflow: hidden;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.5);
        }
        
        .nav-link {
            display: block; margin-top: 10px; color: #555; text-decoration: none;
            font-size: 12px; font-family: 'Share Tech Mono'; text-align: right; transition: 0.3s;
        }
        .nav-link:hover { color: var(--neon-cyan); }

    </style>
</head>
<body>

    <div class="main-interface">
        
        <div class="core-chamber">
            <div class="cube-wrapper">
                <div class="cube">
                    <div class="face front"><i class="fas fa-microchip"></i></div>
                    <div class="face back"><i class="fas fa-server"></i></div>
                    <div class="face right"><i class="fas fa-database"></i></div>
                    <div class="face left"><i class="fas fa-network-wired"></i></div>
                    <div class="face top"></div>
                    <div class="face bottom"></div>
                </div>
            </div>
            <div class="core-status"><?php echo $ai_status; ?></div>
            <div style="font-size:12px; color:#666; margin-top:5px; letter-spacing:1px;">NEURAL ENGINE STATUS</div>
            
            <div style="margin-top: 30px; width: 100%; padding: 0 20px;">
                <div style="display:flex; justify-content:space-between; font-size:10px; color:#888; margin-bottom:5px;">
                    <span>CPU LOAD</span><span>Memory</span>
                </div>
                <div style="width:100%; height:4px; background:#333; border-radius:2px; overflow:hidden;">
                    <div style="width: 45%; height:100%; background: var(--neon-cyan); animation: load 3s infinite;"></div>
                </div>
            </div>
        </div>

        <div class="control-deck">
            <div class="header-title"><i class="fas fa-dna"></i> VIDYAVERSE CORE</div>
            <div class="sub-header">
                // SYSTEM ID: V-9000-X <br>
                // SECURITY LEVEL: ADMIN ALPHA
            </div>

            <form method="post" id="aiForm">
                <?php if($ai_status == "OFFLINE"): ?>
                    
                    <div class="cam-input-group">
                        <label class="cam-label">OPTICAL INPUT SOURCE (0=Laptop, URL=Phone)</label>
                        <input type="text" name="cam_input" class="sci-input" value="<?php echo $default_cam; ?>" placeholder="e.g. 0">
                    </div>

                    <button type="submit" name="action" value="start" class="btn-3d btn-start" onclick="animStart(this)">
                        <i class="fas fa-power-off"></i> INITIALIZE SEQUENCE
                    </button>
                <?php else: ?>
                    <button type="submit" name="action" value="stop" class="btn-3d btn-stop" onclick="animStop(this)">
                        <i class="fas fa-radiation"></i> TERMINATE PROCESS
                    </button>
                <?php endif; ?>
            </form>

            <a href="../kiosk_display.php" target="_blank" class="btn-3d btn-kiosk">
                <i class="fas fa-tv"></i> LAUNCH HOLOGRAPHIC KIOSK
            </a>

            <div class="terminal">
                <div id="termLog">
                    >> SYSTEM READY.<br>>> WAITING FOR INPUT...
                </div>
            </div>

            <a href="activate_system_gesture.php" class="nav-link">
                SWITCH TO GESTURE INTERFACE <i class="fas fa-arrow-right"></i>
            </a>
            <a href="activate_system_voice.php" class="nav-link">
                SWITCH TO VOICE INTERFACE <i class="fas fa-microphone"></i>
            </a>
        </div>

    </div>

    <script>
        function animStart(btn) {
            btn.innerHTML = "<i class='fas fa-cog fa-spin'></i> ALLOCATING RESOURCES...";
            btn.style.opacity = "0.7";
            document.getElementById('termLog').innerHTML = ">> HANDSHAKE INITIATED...<br>>> STARTING HIDDEN PROCESS...<br>>> PLEASE WAIT...";
        }

        function animStop(btn) {
            btn.innerHTML = "<i class='fas fa-skull'></i> KILLING PROCESS...";
            document.getElementById('termLog').innerHTML = ">> SENDING KILL SIGNAL...<br>>> FLUSHING MEMORY...";
        }
        
        // STATUS MESSAGE LOGIC (From URL)
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');
        const termLog = document.getElementById('termLog');

        if (msg === 'activated') {
            termLog.innerHTML = "<span style='color:#0f0;'>>> SYSTEM SUCCESSFULLY ACTIVATED.</span><br>>> MONITORING RUNNING IN BACKGROUND.";
            window.history.replaceState(null, null, window.location.pathname);
        } else if (msg === 'deactivated') {
            termLog.innerHTML = "<span style='color:#f00;'>>> SYSTEM DEACTIVATED.</span><br>>> ALL PROCESSES TERMINATED.";
            window.history.replaceState(null, null, window.location.pathname);
        }

        document.styleSheets[0].insertRule("@keyframes load { 0% { width: 10%; } 50% { width: 70%; } 100% { width: 40%; } }", 0);
    </script>

</body>
</html>