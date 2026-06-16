<?php
// --- VIDYAVERSE CONFIG ---
$python_executable = "C:\\Users\\Kaushik\\AppData\\Local\\Programs\\Python\\Python311\\python.exe";
$script_path = "E:\\Xampp\\htdocs\\Vidyaverse\\python_ai_engine\\ai_voice_system.py";
$log_file = "E:\\Xampp\\htdocs\\Vidyaverse\\python_ai_engine\\voice_log.txt";

// --- AJAX LOG STREAM ---
if (isset($_GET['fetch_logs'])) {
    if (file_exists($log_file)) {
        $lines = file($log_file);
        $last_lines = array_slice($lines, -50); 
        echo implode("", $last_lines);
    } else { echo ">> [ERR] LINK SEVERED"; }
    exit;
}

// --- SYSTEM CHECK ---
$status = "OFFLINE";
if (strpos(shell_exec("tasklist /FI \"IMAGENAME eq python.exe\" 2>&1"), 'python.exe') !== false) $status = "ONLINE";

// --- CONTROLLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // --- MAIN SYSTEM CONTROL ---
    if ($action == 'stop') {
        shell_exec("taskkill /F /IM python.exe /T > NUL 2>&1");
        header("Location: activate_system_voice.php?shutdown=true"); exit;
    }
    if ($action == 'start') {
        shell_exec("taskkill /F /IM python.exe /T > NUL 2>&1");
        file_put_contents($log_file, ">> INITIALIZING VIDYAVERSE PROTOCOLS...\n");
        pclose(popen("start /B cmd /c \"\"$python_executable\" \"$script_path\" > \"$log_file\" 2>&1\"", "r"));
        sleep(2); header("Location: activate_system_voice.php?boot=true"); exit;
    }

    // --- MODULE SHORTCUTS (Placeholder Logic) ---
    if ($action == 'gesture') {
        // Redirect to Gesture Page or Launch Script
        header("Location: activate_system_gesture.php"); exit; 
    }
    if ($action == 'face') {
        // Redirect to Face Recog Page or Launch Script
        header("Location: activate_system.php"); exit; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VIDYAVERSE | HYPER-SCALE AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --core: #00f3ff;          /* Cyan Core */
            --plasma: #bc13fe;        /* Purple Plasma */
            --alert: #ff2a2a;         /* Red Alert */
            --bg-dark: #020205;
            --glass-heavy: rgba(10, 15, 25, 0.95);
            --panel-border: 2px solid rgba(0, 243, 255, 0.3);
        }

        body {
            margin: 0; background: var(--bg-dark); color: white; font-family: 'Rajdhani', sans-serif;
            height: 100vh; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            perspective: 1000px;
        }

        /* --- BACKGROUND --- */
        body::before {
            content: ''; position: absolute; top: 50%; left: -50%; width: 200%; height: 100%;
            background: 
                linear-gradient(transparent 20%, var(--bg-dark) 90%),
                linear-gradient(90deg, rgba(0, 243, 255, 0.05) 1px, transparent 1px),
                linear-gradient(rgba(0, 243, 255, 0.05) 1px, transparent 1px);
            background-size: 100% 100%, 80px 80px, 80px 80px;
            transform: rotateX(75deg); z-index: -1;
        }

        .vignette {
            position: absolute; top:0; left:0; width:100%; height:100%;
            background: radial-gradient(circle at center, transparent 40%, black 100%);
            z-index: 1; pointer-events: none;
        }

        /* --- HUD LAYOUT --- */
        .hud-container {
            width: 96vw; height: 92vh; z-index: 5;
            display: grid;
            grid-template-columns: 400px 1fr 400px;
            grid-template-rows: 80px 1fr;
            gap: 25px;
            overflow: hidden;
        }

        /* --- PANELS --- */
        .panel { 
            background: var(--glass-heavy); 
            border: var(--panel-border);
            box-shadow: 0 0 30px rgba(0, 243, 255, 0.1), inset 0 0 20px rgba(0,0,0,0.5);
            padding: 30px; 
            display: flex; flex-direction: column;
            position: relative;
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 30px), calc(100% - 30px) 100%, 0 100%);
            overflow: hidden;
        }

        .panel::before, .panel::after { content: ''; position: absolute; width: 10px; height: 10px; border: 2px solid var(--core); }
        .panel::before { top: -2px; left: -2px; border-right: none; border-bottom: none; }
        .panel::after { bottom: -2px; right: -2px; border-left: none; border-top: none; }

        /* --- TOP BAR --- */
        .top-bar { grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: center; background: var(--glass-heavy); border-bottom: 3px solid var(--core); padding: 0 40px; }
        .logo { font-family: 'Orbitron'; font-size: 36px; font-weight: 900; letter-spacing: 8px; color: var(--core); text-shadow: 0 0 30px var(--core); }
        .sys-time { font-family: 'Share Tech Mono'; color: var(--core); font-size: 18px; }

        /* --- DIAGNOSTICS & BUTTONS (Left) --- */
        .stat-label { font-size: 14px; color: #888; letter-spacing: 3px; font-family: 'Orbitron'; margin-bottom: 5px;}
        .stat-bar-container { height: 12px; background: #050505; border: 1px solid #333; padding: 2px; margin-bottom: 25px; }
        .stat-fill { height: 100%; background: var(--core); width: 50%; box-shadow: 0 0 15px var(--core); position: relative; overflow: hidden; }
        .stat-fill::after { content:''; position: absolute; top:0; left:0; width:100%; height:100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent); animation: scanbar 2s infinite linear;}

        /* MODULE BUTTONS */
        .mod-btn {
            width: 100%; padding: 15px; margin-bottom: 15px;
            background: rgba(0, 243, 255, 0.05); border: 1px solid var(--core);
            color: var(--core); font-family: 'Orbitron'; letter-spacing: 2px;
            cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .mod-btn:hover { background: var(--core); color: #000; box-shadow: 0 0 20px var(--core); }
        .mod-btn i { font-size: 18px; }

        /* --- CENTER: REACTOR --- */
        .reactor-zone { 
            grid-column: 2; grid-row: 2; display: flex; flex-direction: column; 
            align-items: center; justify-content: center; position: relative; 
        }

        .reactor { width: 500px; height: 500px; position: relative; display: flex; align-items: center; justify-content: center; }
        .containment-field { position: absolute; width: 100%; height: 100%; border-radius: 50%; border: 1px solid var(--core); box-shadow: 0 0 50px rgba(0, 243, 255, 0.2), inset 0 0 50px rgba(0, 243, 255, 0.2); opacity: 0.7; }
        .circle { position: absolute; border-radius: 50%; border: 1px solid transparent; transition: 0.5s; }
        .c1 { width: 90%; height: 90%; border-top: 8px solid var(--core); border-bottom: 8px solid var(--core); animation: spin 12s linear infinite; }
        .c2 { width: 70%; height: 70%; border-left: 8px solid var(--core); border-right: 8px solid var(--core); animation: spin 7s linear infinite reverse; opacity: 0.8; }
        .c3 { width: 50%; height: 50%; border: 4px dotted white; animation: spin 25s linear infinite; opacity: 0.4; }
        .core-icon-box { position: absolute; font-size: 100px; color: white; text-shadow: 0 0 40px var(--core); z-index: 10; transition: 0.3s; }
        
        .active .c1, .active .c2 { border-color: var(--plasma); box-shadow: 0 0 80px var(--plasma); }
        .active .containment-field { border-color: var(--plasma); box-shadow: 0 0 100px var(--plasma), inset 0 0 60px var(--plasma); }
        .active .core-icon-box { color: var(--plasma); text-shadow: 0 0 50px var(--plasma); transform: scale(1.2); }

        .btn-main {
            margin-top: 60px; padding: 20px 80px; font-family: 'Orbitron'; font-size: 24px; letter-spacing: 8px; font-weight: 900;
            background: rgba(0, 243, 255, 0.1); color: var(--core); border: 3px solid var(--core);
            cursor: pointer; transition: 0.3s; text-transform: uppercase; clip-path: polygon(20px 0, 100% 0, 100% calc(100% - 20px), calc(100% - 20px) 100%, 0 100%, 0 20px);
        }
        .btn-main:hover { background: var(--core); color: black; box-shadow: 0 0 80px var(--core); }
        .btn-stop { border-color: var(--alert); color: var(--alert); background: rgba(255, 42, 42, 0.1); }
        .btn-stop:hover { background: var(--alert); color: white; box-shadow: 0 0 80px var(--alert); }

        /* --- RIGHT: LOGS (FIXED SCROLL & HEIGHT) --- */
        .log-feed {
            font-family: 'Share Tech Mono'; font-size: 12px; color: #aaa; line-height: 1.6;
            flex-grow: 1; min-height: 0; overflow-y: auto;
            border-left: 2px solid #333; padding-left: 15px; margin-bottom: 20px;
            scroll-behavior: smooth;
        }
        
        /* Custom Scrollbar */
        .log-feed::-webkit-scrollbar { width: 5px; }
        .log-feed::-webkit-scrollbar-thumb { background: var(--core); }
        .log-feed::-webkit-scrollbar-track { background: #111; }

        .l-ok { color: var(--core); font-weight: bold;}
        .l-err { color: var(--alert); font-weight: bold;}

        /* AUDIO VISUALIZER BOX */
        .viz-box {
            width: 100%; height: 100px;
            background: rgba(0,0,0,0.5); border: 1px solid var(--core);
            margin-top: auto; position: relative; flex-shrink: 0;
        }
        canvas { display: block; width: 100%; height: 100%; }

        /* --- OVERLAY SCREENS --- */
        .overlay-screen {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.95); z-index: 9999;
            display: none; align-items: center; justify-content: center;
            flex-direction: column; transition: opacity 0.5s ease-out;
        }
        
        .boot-text { font-family: 'Orbitron'; font-size: 100px; font-weight: 900; letter-spacing: 20px; animation: impact 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        .txt-on { color: var(--core); text-shadow: 0 0 80px var(--core); }
        .txt-off { color: var(--alert); text-shadow: 0 0 80px var(--alert); }
        .boot-sub { font-family: 'Share Tech Mono'; color: #fff; font-size: 24px; margin-top: 20px; letter-spacing: 10px; opacity: 0.7; border-top: 1px solid #666; padding-top: 20px; }

        @keyframes spin { 100% { transform: rotate(360deg); } }
        @keyframes scanbar { 0% { left: -100%; } 100% { left: 100%; } }
        @keyframes impact { 0% { transform: scale(5); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }

    </style>
</head>
<body>

<div class="vignette"></div>

<div class="overlay-screen" id="bootScreen">
    <div class="boot-text txt-on">SYSTEM ONLINE</div>
    <div class="boot-sub">VIDYAVERSE NEURAL LINK ESTABLISHED</div>
</div>

<div class="overlay-screen" id="shutdownScreen">
    <div class="boot-text txt-off">SYSTEM OFFLINE</div>
    <div class="boot-sub" style="border-color:var(--alert)">VIDYAVERSE CORE TERMINATED</div>
</div>

<div class="hud-container">

    <div class="top-bar">
        <div class="logo">VIDYAVERSE <span style="font-size:14px; color:#888; letter-spacing: 2px;">// HYPER-SCALE CORE //</span></div>
        <div class="sys-time"><i class="fas fa-clock"></i> <span id="clock">00:00:00</span></div>
    </div>

    <div class="panel">
        <div class="h-title" style="font-family:'Orbitron'; color:var(--core); margin-bottom:30px; border-bottom:2px solid var(--core);">/// SYSTEM VITALITY</div>
        <div class="stat-label">NEURAL LOAD CAPACITY</div>
        <div class="stat-bar-container"><div class="stat-fill" style="width:45%"></div></div>
        <div class="stat-label">QUANTUM MEMORY INTEGRITY</div>
        <div class="stat-bar-container"><div class="stat-fill" style="width:99%; background:#0f0;"></div></div>
        
        <div style="margin-top:20px;">
            <div class="stat-label">MODULE ACCESS</div>
            <form method="post">
                <button type="submit" name="action" value="gesture" class="mod-btn">
                    <i class="fas fa-hand-sparkles"></i> GESTURE MODE
                </button>
                <button type="submit" name="action" value="face" class="mod-btn">
                    <i class="fas fa-user-shield"></i> FACE RECOGNITION
                </button>
            </form>
        </div>

        <div style="margin-top:auto; padding:30px; text-align:center; border: 3px solid #333; background: rgba(0,0,0,0.5);">
            <h1 style="margin:0; font-family:'Orbitron'; font-size:36px; letter-spacing:5px; color:<?php echo ($status=='ONLINE')?'var(--core)':'#555'; ?>">
                <?php echo $status; ?>
            </h1>
            <small style="color:#888; font-family:'Share Tech Mono'; letter-spacing:3px;">OPERATIONAL STATE</small>
        </div>
    </div>

    <div class="reactor-zone">
        <div class="reactor" id="reactor">
            <div class="containment-field"></div>
            <div class="circle c1"></div>
            <div class="circle c2"></div>
            <div class="circle c3"></div>
            <div class="core-icon-box" id="coreIcon"><i class="fas fa-power-off"></i></div>
        </div>
        <form method="post">
            <?php if($status == "OFFLINE"): ?>
                <button type="submit" name="action" value="start" class="btn-main">ENGAGE SYSTEM</button>
            <?php else: ?>
                <button type="submit" name="action" value="stop" class="btn-main btn-stop">EMERGENCY SCRAM</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="panel">
        <div class="h-title" style="font-family:'Orbitron'; color:var(--core); margin-bottom:20px; border-bottom:2px solid var(--core);">/// LIVE DATA FEED</div>
        
        <div class="log-feed" id="liveLogs">>> WAITING FOR INPUT...</div>
        
        <div class="stat-label" style="margin-top:10px;">AUDIO SENSOR INPUT</div>
        <div class="viz-box">
            <canvas id="audioViz"></canvas>
        </div>
    </div>

</div>

<script>
    // --- 1. BOOT/SHUTDOWN UI LOGIC ---
    const urlParams = new URLSearchParams(window.location.search);
    const bootScreen = document.getElementById('bootScreen');
    const shutScreen = document.getElementById('shutdownScreen');

    if(urlParams.has('boot')) {
        bootScreen.style.display = "flex";
        setTimeout(() => {
            bootScreen.style.opacity = "0";
            setTimeout(() => {
                bootScreen.style.display = "none";
                window.history.replaceState({}, document.title, window.location.pathname);
                initAudio(); // START MIC
            }, 500);
        }, 3000);
    }

    if(urlParams.has('shutdown')) {
        shutScreen.style.display = "flex";
        if('speechSynthesis' in window) {
            const msg = new SpeechSynthesisUtterance("System Offline. Goodbye.");
            msg.rate = 0.9; 
            window.speechSynthesis.speak(msg);
        }
        setTimeout(() => {
            shutScreen.style.opacity = "0";
            setTimeout(() => {
                shutScreen.style.display = "none";
                window.location.href = "activate_system_voice.php";
            }, 500);
        }, 3000);
    }

    // --- 2. AUDIO VISUALIZER ---
    function initAudio() {
        if (!navigator.mediaDevices) return;
        navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const source = audioCtx.createMediaStreamSource(stream);
            const analyzer = audioCtx.createAnalyser();
            analyzer.fftSize = 256;
            source.connect(analyzer);
            const bufferLength = analyzer.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);
            const canvas = document.getElementById('audioViz');
            const canvasCtx = canvas.getContext('2d');

            function draw() {
                canvas.width = canvas.offsetWidth;
                canvas.height = canvas.offsetHeight;
                requestAnimationFrame(draw);
                analyzer.getByteFrequencyData(dataArray);
                canvasCtx.fillStyle = 'rgba(0, 0, 0, 0.2)';
                canvasCtx.fillRect(0, 0, canvas.width, canvas.height);
                const barWidth = (canvas.width / bufferLength) * 2.5;
                let barHeight;
                let x = 0;
                for(let i = 0; i < bufferLength; i++) {
                    barHeight = dataArray[i] / 2;
                    if(barHeight > 60) canvasCtx.fillStyle = '#bc13fe'; 
                    else canvasCtx.fillStyle = '#00f3ff';
                    canvasCtx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
                    x += barWidth + 1;
                }
            }
            draw();
        }).catch(err => console.log('Mic Error:', err));
    }
    if("<?php echo $status; ?>" === "ONLINE") setTimeout(initAudio, 1000);

    // --- 3. CLOCK & LOGS ---
    setInterval(() => { document.getElementById('clock').innerText = new Date().toLocaleTimeString(); }, 1000);
    const logs = document.getElementById('liveLogs');
    const reactor = document.getElementById('reactor');
    const icon = document.getElementById('coreIcon');
    
    function sync() {
        fetch('activate_system_voice.php?fetch_logs=true')
            .then(r => r.text())
            .then(data => {
                if(data.trim()) {
                    let formatted = data.replace(/\n/g, "<br>").replace(/\[CRITICAL FAILURE\]/g, "<span class='l-err'>[CRITICAL]</span>").replace(/[WARN]/g, "<span class='l-err'>[WARN]</span>").replace(/\[SUCCESS\]/g, "<span class='l-ok'>[OK]</span>");
                    if(logs.innerHTML !== formatted) { logs.innerHTML = formatted; logs.scrollTop = logs.scrollHeight; }
                    if(data.includes("Listening")) { reactor.classList.add('active'); icon.innerHTML = "<i class='fas fa-microphone-lines'></i>"; } 
                    else if(data.includes("Broadcasting")) { reactor.classList.add('active'); icon.innerHTML = "<i class='fas fa-brain'></i>"; } 
                    else { reactor.classList.remove('active'); icon.innerHTML = "<i class='fas fa-fingerprint'></i>"; }
                }
            });
    }
    setInterval(sync, 1000);

</script>
</body>
</html>