<?php
session_start();
include('includes/dbconnection.php');
if (strlen($_SESSION['admin_id'] ?? '') == 0) {
    header('location:logout.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>TIB IDEA CAFE | Co-Ordinator Access Node</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            /* TIB Idea Cafe Core Theme */
            --tib-cyan: #00e5ff;
            --tib-blue: #0a4275;
            --tib-dark: #020617;
            --glass-bg: rgba(10, 25, 47, 0.45);
            --metallic-highlight: linear-gradient(135deg, #fff 0%, #a1a1aa 100%);
            
            /* Dynamic State Variables (Changes on Toggle) */
            --active-color: #00e5ff; 
            --active-bg: rgba(0, 229, 255, 0.1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--tib-dark);
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(0, 229, 255, 0.08), transparent 30%),
                radial-gradient(circle at 85% 30%, rgba(10, 66, 117, 0.3), transparent 40%),
                linear-gradient(to bottom, #020617, #0f172a);
            background-attachment: fixed;
            font-family: 'Outfit', sans-serif;
            color: #fff;
            display: flex; flex-direction: column; align-items: center; min-height: 100vh;
        }

        /* --- TIB HEADER --- */
        .tib-header {
            width: 100%; height: 80px; 
            background: rgba(2, 6, 23, 0.75); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.05); 
            display: flex; align-items: center; justify-content: space-between; 
            padding: 0 40px; position: fixed; top: 0; z-index: 1000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .header-brand { 
            font-family: 'Orbitron', sans-serif; font-weight: 900; font-size: 20px; 
            letter-spacing: 2px; display: flex; align-items: center; gap: 12px;
            background: var(--metallic-highlight); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .header-brand i { -webkit-text-fill-color: var(--active-color); transition: 0.3s; }
        
        .btn-exit { 
            color: var(--active-color); text-decoration: none; font-size: 12px; font-weight: 800; 
            text-transform: uppercase; letter-spacing: 2px; font-family: 'Orbitron', sans-serif;
            border: 1px solid var(--active-color); padding: 8px 20px; border-radius: 30px; 
            transition: 0.3s; background: var(--active-bg); box-shadow: inset 0 0 10px var(--active-bg);
        }
        .btn-exit:hover { background: var(--active-color); color: #000; box-shadow: 0 0 20px var(--active-color); }

        /* --- AERO-GLASS SCANNER CONTAINER --- */
        .scanner-container {
            margin-top: 110px; width: 100%; max-width: 550px; padding: 35px;
            background: var(--glass-bg); 
            backdrop-filter: blur(30px) saturate(150%); -webkit-backdrop-filter: blur(30px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.1); border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 30px; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(255, 255, 255, 0.02);
            text-align: center; position: relative; z-index: 10; transition: 0.3s;
        }

        /* --- MODE TOGGLE SWITCH --- */
        .mode-toggle {
            display: flex; background: rgba(0,0,0,0.6); border-radius: 16px;
            padding: 6px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.05);
        }
        .mode-btn {
            flex: 1; padding: 14px; border: none; background: transparent; color: #71717a;
            font-family: 'Orbitron', sans-serif; font-size: 12px; font-weight: 800; 
            text-transform: uppercase; letter-spacing: 2px;
            border-radius: 12px; cursor: pointer; transition: 0.3s;
        }
        .mode-btn.active.in { background: var(--tib-cyan); color: #000; box-shadow: 0 0 20px rgba(0, 229, 255, 0.4); }
        .mode-btn.active.out { background: #ff0055; color: #fff; box-shadow: 0 0 20px rgba(255, 0, 85, 0.4); }

        /* --- HTML5-QRCODE TARGET BOX --- */
        #reader {
            width: 100%; border-radius: 20px; overflow: hidden;
            border: 2px solid var(--active-color); box-shadow: 0 0 40px var(--active-bg);
            background: #000; position: relative; transition: 0.3s;
        }
        
        #reader__dashboard_section_csr span, #reader__dashboard_section_swaplink { color: #fff !important; font-family: 'Outfit', sans-serif; }
        #reader__dashboard_section_csr button { background: var(--active-color); color: #000; border: none; padding: 8px 15px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; }
        #reader__scan_region { position: relative; }

        /* --- 3D METALLIC RESULT CARD --- */
        #scan-result { margin-top: 30px; min-height: 120px; }
        
        .digital-id-card {
            background: linear-gradient(145deg, var(--active-bg), rgba(0, 0, 0, 0.6));
            border: 1px solid var(--active-color); border-top: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px; padding: 25px; display: flex; align-items: center; gap: 20px; text-align: left;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5), inset 0 0 20px var(--active-bg);
            animation: cardPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        
        @keyframes cardPop { 0% { transform: scale(0.9) translateY(20px); opacity: 0; } 100% { transform: scale(1) translateY(0); opacity: 1; } }

        .avatar-box {
            width: 65px; height: 65px; background: rgba(0,0,0,0.4);
            border: 2px solid var(--active-color); border-radius: 16px;
            display: flex; align-items: center; justify-content: center; font-size: 28px; color: var(--active-color);
            box-shadow: 0 0 20px var(--active-bg); transform: rotate(-5deg);
        }

        .user-details h3 { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 4px; font-family: 'Outfit', sans-serif; }
        .user-details p { font-size: 11px; color: #a1a1aa; font-family: 'Orbitron', sans-serif; letter-spacing: 2px; margin-bottom: 12px; }
        
        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--active-color); color: #000;
            padding: 6px 15px; border-radius: 8px; font-family: 'Orbitron', sans-serif;
            font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;
            box-shadow: 0 0 15px var(--active-bg);
        }
        .status-badge.out-mode { color: #fff; }

        /* Alerts */
        .glass-alert { padding: 18px; border-radius: 16px; font-size: 12px; font-weight: 700; font-family: 'Orbitron', sans-serif; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 12px; text-transform: uppercase; }
        .alert-processing { background: var(--active-bg); color: var(--active-color); border: 1px solid var(--active-color); box-shadow: 0 0 20px var(--active-bg); }
        .alert-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid #ef4444; box-shadow: 0 0 20px rgba(239,68,68,0.2); animation: shake 0.4s; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

        @media (max-width: 600px) { .scanner-container { border-radius: 0; margin-top: 80px; padding: 20px; border-left: none; border-right: none; } .tib-header { padding: 0 20px; } }
    </style>
</head>
<body>

    <div class="tib-header">
        <div class="header-brand"><i class="fas fa-satellite-dish"></i> SYNC NODE</div>
        <a href="dashboard.php" class="btn-exit">Console</a>
    </div>

    <div class="scanner-container">
        
        <div class="mode-toggle">
            <button class="mode-btn active in" id="btn-in" onclick="setMode('in')"><i class="fas fa-sign-in-alt"></i> Log In</button>
            <button class="mode-btn" id="btn-out" onclick="setMode('out')"><i class="fas fa-sign-out-alt"></i> Log Out</button>
        </div>
        
        <div id="reader"></div>
        
        <div id="scan-result">
            <p id="idle-text" style="color: #52525b; font-size: 12px; text-transform: uppercase; letter-spacing: 3px; margin-top: 40px; font-family: 'Orbitron', sans-serif;">Awaiting Neural Signal...</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    
    <script>
        const scanResultElem = document.getElementById('scan-result');
        const root = document.documentElement;
        
        let currentMode = 'in'; // Default
        let lastScanTime = 0;

        const successSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        const errorSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2955/2955-preview.mp3');

        // Dynamic Theme Switcher
        window.setMode = function(mode) {
            currentMode = mode;
            scanResultElem.innerHTML = `<p style="color: #52525b; font-size: 12px; text-transform: uppercase; letter-spacing: 3px; margin-top: 40px; font-family: 'Orbitron', sans-serif;">Awaiting ${mode === 'in' ? 'Check-In' : 'Check-Out'} Signal...</p>`;
            
            if (mode === 'in') {
                document.getElementById('btn-in').classList.add('active', 'in');
                document.getElementById('btn-out').classList.remove('active', 'out');
                root.style.setProperty('--active-color', '#00e5ff');
                root.style.setProperty('--active-bg', 'rgba(0, 229, 255, 0.1)');
            } else {
                document.getElementById('btn-out').classList.add('active', 'out');
                document.getElementById('btn-in').classList.remove('active', 'in');
                root.style.setProperty('--active-color', '#ff0055');
                root.style.setProperty('--active-bg', 'rgba(255, 0, 85, 0.1)');
            }
        };

        // --- NEW: Text-to-Speech Announcer ---
        function announceName(name, mode) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel(); // Stop any ongoing speech
                const actionWord = mode === 'in' ? 'Check in' : 'Check out';
                const utterance = new SpeechSynthesisUtterance(`${name}, ${actionWord} logged.`);
                utterance.rate = 1.0; 
                utterance.pitch = 1.0;
                window.speechSynthesis.speak(utterance);
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            const now = Date.now();
            if (now - lastScanTime < 4000) return; // Anti-spam throttle
            lastScanTime = now;
            
            scanResultElem.innerHTML = `<div class="glass-alert alert-processing"><i class="fas fa-atom fa-spin"></i> Authenticating Signal...</div>`;
            
            $.ajax({
                url: 'api_faculty_attendance.php', // Target your backend processor
                type: 'POST',
                data: { 
                    qr_id: decodedText,
                    action: currentMode 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        successSound.play().catch(e => {});
                        
                        const personName = response.teacher_name || response.student_name || 'Verified User';
                        
                        // Trigger the Voice Announcement
                        announceName(personName, currentMode);
                        
                        const statusIcon = currentMode === 'in' ? 'fa-sign-in-alt' : 'fa-sign-out-alt';
                        const textClass = currentMode === 'in' ? '' : 'out-mode';

                        scanResultElem.innerHTML = `
                            <div class="digital-id-card">
                                <div class="avatar-box"><i class="fas fa-fingerprint"></i></div>
                                <div class="user-details">
                                    <h3>${personName}</h3>
                                    <p>${response.employee_id || response.roll_number || 'ID VERIFIED'}</p>
                                    <div class="status-badge ${textClass}">
                                        <i class="fas ${statusIcon}"></i> ${response.message}
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        errorSound.play().catch(e => {});
                        scanResultElem.innerHTML = `<div class="glass-alert alert-danger"><i class="fas fa-radiation"></i> ${response.message}</div>`;
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                    scanResultElem.innerHTML = `<div class="glass-alert alert-danger"><i class="fas fa-satellite-dish"></i> Neural Link Severed (API Error)</div>`;
                }
            });
        }

        function onScanFailure(error) {
            // Suppress continuous failure logs from the scanner
        }

        const html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", 
            { fps: 10, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 }, 
            false
        );
        
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    </script>
</body>
</html>