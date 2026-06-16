<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocasuid'] ?? '') == 0) {
    header('location:logout.php');
    exit();
}

$uid = $_SESSION['ocasuid'];
// Fetch basic user info for display if needed
$student_name = "Student";
try {
    $stmt = $dbh->prepare("SELECT FullName FROM tbluser WHERE ID = :uid");
    $stmt->execute(['uid' => $uid]);
    $res = $stmt->fetch(PDO::FETCH_OBJ);
    if($res) $student_name = $res->FullName;
} catch(Exception $e) {}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Scan Attendance | VIDYAVERSE</title>
    
    <link href="../assets/css/lib/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/lib/themify-icons.css" rel="stylesheet">
    <link href="../assets/css/lib/bootstrap.min.css" rel="stylesheet">

    <style>
        /* --- GLOBAL DARK THEME --- */
        * { box-sizing: border-box; }
        body { 
            background-color: #0f172a; 
            font-family: 'Segoe UI', 'Roboto', sans-serif; 
            color: #f8fafc; 
            margin: 0; padding: 0; 
            overflow-x: hidden;
        }

        /* HEADER */
        .simple-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 80px;
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px);
            z-index: 1000; display: flex; align-items: center; justify-content: space-between;
            padding: 0 40px; border-bottom: 1px solid #334155;
        }
        .header-title { font-size: 20px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px; }
        .live-dot { width: 10px; height: 10px; background: #ef4444; border-radius: 50%; box-shadow: 0 0 10px #ef4444; animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0.5; } }

        .btn-back {
            background: #334155; color: #fff; padding: 8px 20px; border-radius: 6px;
            text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 8px;
        }
        .btn-back:hover { background: #475569; color: #fff; }

        /* CONTENT LAYOUT */
        .main-content {
            margin-top: 80px;
            min-height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* SCANNER BOX */
        .scanner-wrapper {
            position: relative;
            width: 100%;
            max-width: 600px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 0 50px rgba(59, 130, 246, 0.2);
            border: 2px solid #334155;
            background: #000;
        }
        
        #qr-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* SCANNING ANIMATION LINE */
        .scan-line {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 4px;
            background: #10b981;
            box-shadow: 0 0 15px #10b981;
            animation: scan 2s infinite linear;
            z-index: 10;
        }
        @keyframes scan {
            0% { top: 0%; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }

        .scanner-overlay {
            position: absolute; bottom: 0; left: 0; width: 100%;
            padding: 15px;
            background: rgba(0,0,0,0.7);
            text-align: center;
            color: #10b981; font-weight: 600; letter-spacing: 1px;
            text-transform: uppercase; font-size: 14px;
        }

        /* RESULT CARD (Hidden by default) */
        #result-card {
            display: none; /* Hidden initially */
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 30px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .success-icon {
            width: 80px; height: 80px; background: rgba(16, 185, 129, 0.2);
            color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 40px; margin: 0 auto 20px auto; border: 2px solid #10b981;
        }
        .error-icon {
            width: 80px; height: 80px; background: rgba(239, 68, 68, 0.2);
            color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 40px; margin: 0 auto 20px auto; border: 2px solid #ef4444;
        }

        .result-title { font-size: 24px; font-weight: 700; color: #fff; margin-bottom: 10px; }
        .result-sub { color: #94a3b8; margin-bottom: 25px; }

        .detail-row {
            display: flex; justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #334155;
            color: #cbd5e1;
            font-size: 15px;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: 600; color: #64748b; text-transform: uppercase; font-size: 12px; }
        .detail-val { font-weight: 700; }

        .btn-retry {
            margin-top: 25px;
            background: #3b82f6; color: white; border: none; padding: 12px 30px;
            border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%;
            transition: 0.2s;
        }
        .btn-retry:hover { background: #2563eb; }

        /* Loading Spinner */
        .spinner { border: 4px solid rgba(255,255,255,0.1); border-top: 4px solid #3b82f6; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <span class="live-dot"></span> SCAN ATTENDANCE
        </div>
        <a href="dashboard.php" class="btn-back">
            <i class="ti-arrow-left"></i> Dashboard
        </a>
    </div>

    <div class="main-content">

        <div class="scanner-wrapper" id="scanner-view">
            <div class="scan-line"></div>
            <video id="qr-video"></video>
            <div class="scanner-overlay">
                <i class="ti-target"></i> Align QR Code within frame
            </div>
        </div>
        
        <div id="loading-view" style="display:none; text-align:center;">
            <div class="spinner"></div>
            <p style="margin-top:15px; color:#94a3b8;">Verifying Attendance...</p>
        </div>

        <div id="result-card">
            </div>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qr-scanner@1.4.2/qr-scanner.umd.min.js"></script>

    <script type="module">
        const videoElem = document.getElementById('qr-video');
        const scannerView = document.getElementById('scanner-view');
        const loadingView = document.getElementById('loading-view');
        const resultCard = document.getElementById('result-card');
        
        let scanner;

        // --- 1. HANDLE SCAN SUCCESS ---
        function onScanSuccess(result) {
            console.log("Scanned Data:", result.data);
            
            // Stop Scanner
            scanner.stop();
            scannerView.style.display = 'none';
            loadingView.style.display = 'block';

            // Send to Backend
            $.ajax({
                url: 'api_mark_attendance.php',
                type: 'POST',
                data: { qr_data: result.data },
                dataType: 'json',
                success: function(response) {
                    loadingView.style.display = 'none';
                    showResult(response);
                },
                error: function() {
                    loadingView.style.display = 'none';
                    showError("Connection Error", "Could not reach the server.");
                }
            });
        }

        // --- 2. SHOW RESULT CARD ---
        function showResult(data) {
            resultCard.style.display = 'block';
            
            if(data.success) {
                // Determine current time strings
                const now = new Date();
                const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const dateStr = now.toLocaleDateString();

                resultCard.innerHTML = `
                    <div class="success-icon"><i class="ti-check"></i></div>
                    <h2 class="result-title">Attendance Marked!</h2>
                    <p class="result-sub">Your presence has been recorded successfully.</p>
                    
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-val" style="color:#10b981">PRESENT</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date</span>
                        <span class="detail-val">${dateStr}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Time</span>
                        <span class="detail-val">${timeStr}</span>
                    </div>
                     <div class="detail-row">
                        <span class="detail-label">Message</span>
                        <span class="detail-val">${data.message}</span>
                    </div>
                    
                    <button onclick="window.location.href='dashboard.php'" class="btn-retry">Back to Dashboard</button>
                `;
            } else {
                showError("Scan Failed", data.message);
            }
        }

        function showError(title, msg) {
            resultCard.style.display = 'block';
            resultCard.innerHTML = `
                <div class="error-icon"><i class="ti-close"></i></div>
                <h2 class="result-title">${title}</h2>
                <p class="result-sub" style="color:#ef4444">${msg}</p>
                <button onclick="location.reload()" class="btn-retry">Try Again</button>
            `;
        }

        // --- 3. AUTO-START SCANNER ---
        $(document).ready(function() {
            QrScanner.WORKER_PATH = 'https://cdn.jsdelivr.net/npm/qr-scanner@1.4.2/qr-scanner-worker.min.js';
            
            scanner = new QrScanner(videoElem, result => onScanSuccess(result), {
                highlightScanRegion: true,
                highlightCodeOutline: true,
            });

            scanner.start().catch(err => {
                console.error(err);
                scannerView.style.display = 'none';
                showError("Camera Error", "Permission denied or camera not found.");
            });
        });
    </script>
</body>
</html>