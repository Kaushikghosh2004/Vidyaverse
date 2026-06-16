<?php
session_start();
include('includes/dbconnection.php');

// Un-comment this and fix the variable name when you are ready to enforce teacher login
/* if (empty($_SESSION['teacher_id'])) {
    header('location:login.php');
    exit();
} */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>VidyaVerse | Classroom Scanner Node</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --node-color: #3b82f6; /* Teacher Blue */
            --node-bg: rgba(59, 130, 246, 0.1);
            --bg-dark: #020617;
            --glass-bg: rgba(10, 25, 47, 0.6);
            --metallic-highlight: linear-gradient(135deg, #fff 0%, #a1a1aa 100%);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(59, 130, 246, 0.15), transparent 50%),
                linear-gradient(to bottom, #020617, #0f172a);
            background-attachment: fixed;
            font-family: 'Outfit', sans-serif;
            color: #fff; display: flex; flex-direction: column; align-items: center; min-height: 100vh;
        }

        /* --- HEADER --- */
        .node-header {
            width: 100%; height: 80px; 
            background: rgba(2, 6, 23, 0.75); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.05); 
            display: flex; align-items: center; justify-content: space-between; 
            padding: 0 40px; position: fixed; top: 0; z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .header-brand { 
            font-family: 'Orbitron', sans-serif; font-weight: 900; font-size: 20px; 
            letter-spacing: 2px; display: flex; align-items: center; gap: 12px;
            background: var(--metallic-highlight); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .header-brand i { -webkit-text-fill-color: var(--node-color); }
        
        .btn-exit { 
            color: var(--node-color); text-decoration: none; font-size: 12px; font-weight: 800; 
            text-transform: uppercase; letter-spacing: 2px; font-family: 'Orbitron', sans-serif;
            border: 1px solid var(--node-color); padding: 8px 20px; border-radius: 30px; 
            transition: 0.3s; background: var(--node-bg); box-shadow: inset 0 0 10px var(--node-bg);
        }
        .btn-exit:hover { background: var(--node-color); color: #fff; box-shadow: 0 0 20px rgba(59, 130, 246, 0.6); }

        /* --- SCANNER CONTAINER --- */
        .scanner-container {
            margin-top: 120px; width: 100%; max-width: 550px; padding: 35px;
            background: var(--glass-bg); backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1); border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 30px; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(255, 255, 255, 0.02);
            text-align: center;
        }

        .scanner-title {
            font-family: 'Orbitron', sans-serif; font-size: 14px; color: var(--node-color);
            letter-spacing: 3px; text-transform: uppercase; margin-bottom: 25px; font-weight: 700;
        }

        #reader {
            width: 100%; border-radius: 20px; overflow: hidden;
            border: 2px solid var(--node-color); box-shadow: 0 0 40px var(--node-bg); background: #000;
        }
        #reader__dashboard_section_csr span, #reader__dashboard_section_swaplink { color: #fff !important; font-family: 'Outfit', sans-serif; }
        #reader__dashboard_section_csr button { background: var(--node-color); color: #fff; border: none; padding: 8px 15px; border-radius: 8px; font-weight: 800; cursor: pointer; }

        /* --- RESULT CARD --- */
        #scan-result { margin-top: 30px; min-height: 120px; }
        
        .digital-id-card {
            background: linear-gradient(145deg, var(--node-bg), rgba(0, 0, 0, 0.6));
            border: 1px solid var(--node-color); border-top: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px; padding: 25px; display: flex; align-items: center; gap: 20px; text-align: left;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5), inset 0 0 20px var(--node-bg);
            animation: cardPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes cardPop { 0% { transform: scale(0.9) translateY(20px); opacity: 0; } 100% { transform: scale(1) translateY(0); opacity: 1; } }

        .avatar-box {
            width: 65px; height: 65px; background: rgba(0,0,0,0.4);
            border: 2px solid var(--node-color); border-radius: 16px;
            display: flex; align-items: center; justify-content: center; font-size: 28px; color: var(--node-color);
            transform: rotate(-5deg);
        }

        .user-details h3 { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .user-details p { font-size: 11px; color: #a1a1aa; font-family: 'Orbitron', sans-serif; letter-spacing: 2px; margin-bottom: 12px; }
        
        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--node-color); color: #fff; padding: 6px 15px; border-radius: 8px; font-family: 'Orbitron', sans-serif;
            font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;
        }

        /* --- ALERTS --- */
        .glass-alert { padding: 18px; border-radius: 16px; font-size: 12px; font-weight: 700; font-family: 'Orbitron', sans-serif; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 12px; text-transform: uppercase; }
        .alert-processing { background: var(--node-bg); color: var(--node-color); border: 1px solid var(--node-color); }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid #ef4444; box-shadow: 0 0 20px rgba(239,68,68,0.2); animation: shake 0.4s; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

        @media (max-width: 600px) { .scanner-container { border-radius: 0; margin-top: 80px; padding: 20px; border-left: none; border-right: none; } .node-header { padding: 0 20px; } }
    </style>
</head>
<body>

    <div class="node-header">
        <div class="header-brand"><i class="fas fa-chalkboard-teacher"></i> CLASSROOM NODE</div>
        <a href="dashboard.php" class="btn-exit">Close</a>
    </div>

    <div class="scanner-container">
        
        <div class="scanner-title"><i class="fas fa-video"></i> Optical Scanner Active</div>
        
        <div id="reader"></div>
        
        <div id="scan-result">
            <p style="color: #52525b; font-size: 12px; text-transform: uppercase; letter-spacing: 3px; margin-top: 40px; font-family: 'Orbitron', sans-serif;">Awaiting Student ID...</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    
    <script>
        const scanResultElem = document.getElementById('scan-result');
        let lastScanTime = 0;

        // Sound Effects
        const successSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        const errorSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2955/2955-preview.mp3');

        // Text to Speech
        function announceName(name, message) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                let textToSpeak = `${name}, ${message}`;
                
                // Special overrides for errors
                if (message.includes("PROXY")) textToSpeak = `Proxy Alert. ${name} already scanned.`;
                if (message.includes("DENIED")) textToSpeak = `Access Denied. Main gate scan required.`;
                
                const utterance = new SpeechSynthesisUtterance(textToSpeak);
                window.speechSynthesis.speak(utterance);
            }
        }

        // When QR is found
        function onScanSuccess(decodedText) {
            const now = Date.now();
            if (now - lastScanTime < 4000) return; // Anti-spam throttle
            lastScanTime = now;
            
            scanResultElem.innerHTML = `<div class="glass-alert alert-processing"><i class="fas fa-circle-notch fa-spin"></i> Verifying Class Schedule...</div>`;
            
            $.ajax({
                url: 'api_scan_student.php', // Calls the Class/Proxy API
                type: 'POST',
                data: { qr_id: decodedText },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        successSound.play().catch(e => {});
                        announceName(response.student_name, 'Present for class.');

                        scanResultElem.innerHTML = `
                            <div class="digital-id-card">
                                <div class="avatar-box"><i class="fas fa-user-graduate"></i></div>
                                <div class="user-details">
                                    <h3>${response.student_name}</h3>
                                    <p>${response.roll_number}</p>
                                    <div class="status-badge">
                                        <i class="fas fa-check-circle"></i> ${response.message}
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        errorSound.play().catch(e => {});
                        
                        // Speak the error name if available
                        let stuName = 'Student';
                        if(response.message.includes('PROXY ALERT:')) {
                            const proxyNameMatch = response.message.match(/PROXY ALERT: (.*?) ALREADY SCANNED/);
                            if(proxyNameMatch && proxyNameMatch[1]) stuName = proxyNameMatch[1];
                        }
                        announceName(stuName, response.message);

                        scanResultElem.innerHTML = `<div class="glass-alert alert-danger"><i class="fas fa-shield-alt"></i> ${response.message}</div>`;
                    }
                },
                error: function(xhr, status, error) { 
                    console.error("AJAX Error:", xhr.responseText);
                    scanResultElem.innerHTML = `<div class="glass-alert alert-danger"><i class="fas fa-satellite-dish"></i> API Connection Error</div>`; 
                }
            });
        }

        // Initialize HTML5 QR Code Scanner
        const html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", 
            { fps: 10, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 }, 
            false
        );
        
        html5QrcodeScanner.render(onScanSuccess, function(){});
    </script>
</body>
</html>