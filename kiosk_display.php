<?php 
$con = mysqli_connect("localhost", "root", "", "lexclassroom");
// Reset Kiosk Status on Load
mysqli_query($con, "UPDATE tbl_kiosk_live SET StudentName='Waiting...', ScanTime=NULL WHERE id=1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VidyaVerse | Quantum Node</title>
    
    <?php include('includes/app_headers.php'); ?>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #00f3ff;
            --secondary: #bc13fe;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --depth: 1200px;
        }

        body {
            margin: 0;
            background: #000;
            background-image: 
                radial-gradient(circle at 50% 50%, #111 0%, #000 100%),
                linear-gradient(rgba(0, 243, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 243, 255, 0.03) 1px, transparent 1px);
            background-size: 100% 100%, 40px 40px, 40px 40px;
            font-family: 'Rajdhani', sans-serif;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            perspective: var(--depth);
        }

        .scene-wrapper {
            position: relative;
            transform-style: preserve-3d;
            animation: float 8s ease-in-out infinite;
        }

        .holo-terminal {
            width: 900px;
            height: 600px;
            position: relative;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            box-shadow: 
                0 0 60px rgba(0, 243, 255, 0.1),
                inset 0 0 30px rgba(255, 255, 255, 0.05);
            padding: 20px;
            transform: rotateX(5deg);
            transition: all 0.5s ease;
        }
        
        .holo-terminal:hover {
            transform: rotateX(0deg) scale(1.02);
            box-shadow: 0 0 80px rgba(188, 19, 254, 0.2);
            border-color: rgba(188, 19, 254, 0.3);
        }

        .video-viewport {
            width: 100%;
            height: 100%;
            background: #000;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 243, 255, 0.2);
        }

        /* --- FIXED: REMOVED SCALE(-1) --- */
        .live-feed {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.9;
            /* transform: scaleX(-1);  <-- REMOVED THIS LINE */
        }

        .hud-layer {
            position: absolute; top:0; left:0; width:100%; height:100%;
            pointer-events: none;
            z-index: 10;
        }

        .laser-scan {
            position: absolute;
            width: 100%;
            height: 4px;
            background: var(--primary);
            box-shadow: 0 0 20px var(--primary);
            top: 10%;
            animation: scan 3s linear infinite;
            opacity: 0.5;
        }
        @keyframes scan {
            0% { top: 5%; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 95%; opacity: 0; }
        }

        .focus-reticle {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 280px; height: 280px;
            pointer-events: none;
            animation: breathe-reticle 3s ease-in-out infinite;
            background: none; border: none;
        }

        .reticle-corners {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background:
                linear-gradient(to right, var(--primary) 2px, transparent 2px) 0 0,
                linear-gradient(to bottom, var(--primary) 2px, transparent 2px) 0 0,
                linear-gradient(to left, var(--primary) 2px, transparent 2px) 100% 0,
                linear-gradient(to bottom, var(--primary) 2px, transparent 2px) 100% 0,
                linear-gradient(to right, var(--primary) 2px, transparent 2px) 0 100%,
                linear-gradient(to top, var(--primary) 2px, transparent 2px) 0 100%,
                linear-gradient(to left, var(--primary) 2px, transparent 2px) 100% 100%,
                linear-gradient(to top, var(--primary) 2px, transparent 2px) 100% 100%;
            background-repeat: no-repeat;
            background-size: 30px 30px;
            border: none;
            filter: drop-shadow(0 0 5px var(--primary)); 
        }

        @keyframes breathe-reticle {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.8; }
            50% { transform: translate(-50%, -50%) scale(1.05); opacity: 1; }
        }

        .holo-card {
            position: absolute;
            bottom: 30px; 
            left: 50%;
            width: 90%;   
            max-width: 600px;
            background: rgba(10, 10, 20, 0.9);
            border: 1px solid var(--primary);
            border-bottom: 4px solid var(--primary); 
            border-radius: 12px;
            padding: 15px 30px;
            display: flex; 
            align-items: center;
            justify-content: space-between; 
            transform: translateX(-50%) translateZ(100px) translateY(100px); 
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 -10px 40px rgba(0, 243, 255, 0.2);
            z-index: 50;
        }

        .show-card {
            transform: translateX(-50%) translateZ(100px) translateY(0);
            opacity: 1;
        }

        .student-avatar {
            width: 70px; height: 70px; 
            border-radius: 50%;
            border: 2px solid var(--secondary);
            object-fit: cover;
            margin-right: 20px;
            box-shadow: 0 0 15px var(--secondary);
        }

        .card-content { text-align: left; flex: 1; }

        .student-name {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            color: #fff;
            margin: 0;
            text-transform: uppercase;
            line-height: 1;
        }

        .info-pill {
            display: inline-block;
            background: rgba(0, 243, 255, 0.2);
            border: 1px solid var(--primary);
            padding: 2px 10px;
            border-radius: 4px;
            color: var(--primary);
            font-weight: 600;
            font-size: 11px;
            margin-top: 5px;
            letter-spacing: 1px;
        }
        
        .entry-time {
            font-size: 18px;
            color: #fff;
            font-family: 'Share Tech Mono', monospace;
            border-left: 1px solid rgba(255,255,255,0.2);
            padding-left: 20px;
        }

        .offline-message {
            position: absolute; top:0; left:0; width:100%; height:100%;
            background: #000;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            color: var(--secondary);
            display: none;
        }

        #init-layer {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000; z-index: 2000;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
        }
        
        @keyframes float { 0% { transform: translateY(0px) rotateX(0deg); } 50% { transform: translateY(-15px) rotateX(2deg); } 100% { transform: translateY(0px) rotateX(0deg); } }

    </style>
</head>
<body>

    <div id="init-layer" onclick="bootSystem()">
        <div style="text-align: center;">
            <i class="fas fa-fingerprint" style="font-size: 80px; color: var(--primary); margin-bottom: 20px;"></i>
            <h1 style="font-family: 'Orbitron'; color: #fff;">TOUCH TO INITIALIZE</h1>
            <p style="color: #666;">VIDYAVERSE NEURAL LINK v4.0</p>
        </div>
    </div>

    <div class="scene-wrapper">
        <div class="holo-terminal">
            
            <div style="display:flex; justify-content:space-between; margin-bottom:15px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px;">
                <div style="color: var(--primary); font-weight: 700; letter-spacing: 2px;">
                    <i class="fas fa-satellite-dish"></i> LIVE FEED
                </div>
                <div id="sys-clock" style="color: #fff; font-family: 'Orbitron';">00:00:00</div>
            </div>

            <div class="video-viewport">
                <img src="http://<?php echo $_SERVER['SERVER_NAME']; ?>:5000/video_feed" class="live-feed" onerror="showOffline()">
                
                <div id="offline-msg" class="offline-message">
                    <i class="fas fa-video-slash" style="font-size: 50px; margin-bottom: 20px;"></i>
                    <h2>SIGNAL LOST</h2>
                    <p>RE-ESTABLISHING CONNECTION...</p>
                </div>

                <div class="hud-layer">
                    <div class="laser-scan"></div>
                    <div class="focus-reticle"><div class="reticle-corners"></div></div>
                    <div style="position: absolute; bottom: 20px; left: 20px; color: rgba(255,255,255,0.5); font-size: 12px;">
                        AI CONFIDENCE: 98.4%<br>LATENCY: 12ms
                    </div>
                </div>

                <div id="success-popup" class="holo-card">
                    <div style="display:flex; align-items:center;">
                        <img id="u-img" src="assets/images/user.png" class="student-avatar">
                        <div class="card-content">
                            <h2 class="student-name" id="u-name">...</h2>
                            <div class="info-pill">ACCESS GRANTED</div>
                        </div>
                    </div>
                    <div class="entry-time" id="u-time">...</div>
                </div>

            </div>

            <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 12px; color: #666;">SYSTEM STATUS: <span style="color: var(--primary);">ONLINE</span></div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="rushMode" style="background-color: #333; border-color: #555;">
                    <label class="form-check-label" style="color: var(--secondary); font-weight: bold; margin-left: 5px;">
                        <i class="fas fa-bolt"></i> RUSH MODE
                    </label>
                </div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function bootSystem() {
            document.getElementById('init-layer').style.display = 'none';
            window.speechSynthesis.getVoices();
        }

        function showOffline() {
            document.querySelector('.live-feed').style.display = 'none';
            document.getElementById('offline-msg').style.display = 'flex';
        }

        setInterval(() => {
            document.getElementById('sys-clock').innerText = new Date().toLocaleTimeString();
        }, 1000);

        setInterval(function() {
            $.ajax({
                url: 'check_kiosk_status.php', 
                cache: false,
                success: function(data) {
                    try {
                        let result = JSON.parse(data);
                        if (result.status === "found") {
                            activateHologram(result);
                        } else {
                            if($('#success-popup').hasClass('show-card')) {
                                $('#success-popup').removeClass('show-card');
                                window.speechSynthesis.cancel();
                            }
                        }
                    } catch(e) { console.log("Data Parse Error"); }
                }
            });
        }, 500);

        function activateHologram(data) {
            $('#u-name').text(data.name);
            $('#u-time').text(data.time);
            let imgPath = "https://cdn-icons-png.flaticon.com/512/149/149071.png"; 
            $('#u-img').attr('src', imgPath);

            if(!$('#success-popup').hasClass('show-card')) {
                $('#success-popup').addClass('show-card');
                playChime();
                if(!$('#rushMode').is(':checked')) {
                    window.speechSynthesis.cancel();
                    let greeting = new SpeechSynthesisUtterance("Welcome, " + data.name);
                    window.speechSynthesis.speak(greeting);
                }
            }
        }

        function playChime() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(1200, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(600, ctx.currentTime + 0.2);
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.2);
        }
    </script>
</body>
</html>