<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- 1. CONFIGURATION & DATABASE ---
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "lexclassroom";

$con = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (mysqli_connect_errno()) {
    die("Connection Failed: " . mysqli_connect_error());
}

// ==========================================
// [NEW FEATURE] REPAIR DATABASE LOGIC
// ==========================================
$repair_output = "";
if(isset($_POST['run_repair'])) {
    // 1. Your Python Path
    $python_exe = "C:\\Users\\Kaushik\\AppData\\Local\\Programs\\Python\\Python311\\python.exe";
    
    // 2. Path to the Repair Script
    $script_path = realpath(__DIR__ . '/../python_ai_engine/repair_encodings.py');
    $script_dir = dirname($script_path);

    // 3. Command: Go to folder -> Run Script -> Capture Output
    // "2>&1" ensures we capture errors too
    $cmd = "cd /d \"$script_dir\" && \"$python_exe\" \"$script_path\" 2>&1";
    
    // 4. Execute
    $repair_output = shell_exec($cmd);
}
// ==========================================

// --- 2. BACKEND LOGIC (Enrollment) ---
$msg = "";
$msgType = ""; // success or error

if (isset($_POST['submit'])) {
    $uid = mysqli_real_escape_string($con, $_POST['student_id']);
    $capture_mode = $_POST['capture_mode']; // 'camera' or 'upload'
    
    // Define Upload Directory
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true); // Auto-create folder if missing
    }

    $filename = "face_" . $uid . "_" . time() . ".jpg";
    $target_file = $target_dir . $filename;
    $uploadOk = 1;

    // A. HANDLE LIVE CAMERA CAPTURE (Base64)
    if ($capture_mode == 'camera' && !empty($_POST['image_base64'])) {
        $data = $_POST['image_base64'];
        
        // Remove the "data:image/png;base64," part
        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);
        
        if(file_put_contents($target_file, $data)) {
            $uploadOk = 1;
        } else {
            $uploadOk = 0;
            $msg = "Failed to write Base64 image to server.";
            $msgType = "error";
        }
    } 
    // B. HANDLE MANUAL FILE UPLOAD
    elseif ($capture_mode == 'upload' && !empty($_FILES["face_image"]["name"])) {
        if (move_uploaded_file($_FILES["face_image"]["tmp_name"], $target_file)) {
            $uploadOk = 1;
        } else {
            $uploadOk = 0;
            $msg = "Failed to move uploaded file.";
            $msgType = "error";
        }
    } else {
        $uploadOk = 0;
        $msg = "No image data received.";
        $msgType = "error";
    }

    // C. DATABASE UPDATE
    if ($uploadOk == 1) {
        // We set FaceEncoding to NULL to force the Python AI to re-scan this new image
        $sql = "UPDATE tbluser SET UserImage='$filename', FaceEncoding=NULL WHERE ID='$uid'";
        if (mysqli_query($con, $sql)) {
            $msg = "Biometric Enrollment Successful! Student ID: $uid";
            $msgType = "success";
        } else {
            $msg = "Database Error: " . mysqli_error($con);
            $msgType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    <title>VidyaVerse | Neural Enrollment Console</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-dark: #0f172a;
            --panel-bg: rgba(30, 41, 59, 0.7);
            --accent-cyan: #06b6d4;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --text-main: #f1f5f9;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(6, 182, 212, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 20%);
            color: var(--text-main);
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* --- HEADER --- */
        .top-bar {
            height: 60px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
        }
        .brand {
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            color: var(--accent-cyan);
            letter-spacing: 2px;
        }
        .status-badge {
            font-size: 12px;
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(16, 185, 129, 0.2);
            color: var(--accent-green);
            border: 1px solid var(--accent-green);
        }

        /* --- MAIN LAYOUT --- */
        .main-container {
            flex: 1;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            padding: 20px;
            height: calc(100vh - 60px);
        }

        /* --- LEFT PANEL (CAMERA) --- */
        .camera-panel {
            background: var(--panel-bg);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .video-container {
            flex: 1;
            background: #000;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1); /* Mirror effect */
        }

        /* Face scanning overlay animation */
        .scanner-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                linear-gradient(to bottom, var(--accent-cyan) 2px, transparent 2px) 0 0;
            background-size: 100% 3px;
            background-repeat: no-repeat;
            animation: scan 3s infinite linear;
            opacity: 0.5;
            pointer-events: none;
            display: none; /* Hidden until camera active */
        }
        @keyframes scan {
            0% { background-position: 0 0; }
            100% { background-position: 0 100%; }
        }

        /* --- RIGHT PANEL (CONTROLS) --- */
        .control-panel {
            background: var(--panel-bg);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            backdrop-filter: blur(10px);
            overflow-y: auto; /* Allow scrolling if logs get long */
        }

        h2 { margin: 0 0 10px 0; font-size: 18px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--accent-cyan);
            font-weight: 600;
        }

        select, input[type="file"] {
            width: 100%;
            padding: 12px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            outline: none;
            transition: 0.3s;
        }
        select:focus, input:focus {
            border-color: var(--accent-cyan);
        }

        .toggle-mode {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            background: rgba(0,0,0,0.3);
            padding: 5px;
            border-radius: 8px;
        }
        .mode-btn {
            flex: 1;
            background: transparent;
            border: none;
            color: #64748b;
            padding: 10px;
            cursor: pointer;
            border-radius: 6px;
            font-weight: 600;
            transition: 0.3s;
        }
        .mode-btn.active {
            background: var(--accent-cyan);
            color: #fff;
        }

        .action-btn {
            width: 100%;
            padding: 15px;
            background: var(--accent-green);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
            margin-top: auto;
        }
        .action-btn:hover {
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
            transform: translateY(-2px);
        }

        .snapshot-preview {
            width: 100%;
            height: 150px;
            background: #000;
            border-radius: 8px;
            border: 1px dashed rgba(255,255,255,0.2);
            margin-top: 20px;
            display: none; /* Hidden initially */
            background-size: cover;
            background-position: center;
        }

        /* Logs */
        .system-log {
            margin-top: 20px;
            height: 100px;
            background: rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 8px;
            padding: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #10b981;
            overflow-y: auto;
        }

        /* Success/Error Message */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid var(--accent-green); color: var(--accent-green); }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid var(--accent-red); color: var(--accent-red); }

    </style>
</head>
<body>

    <div class="top-bar">
        <div class="brand"><i class="fas fa-biohazard"></i> VIDYAVERSE BIOMETRICS</div>
        <div class="status-badge"><i class="fas fa-circle"></i> SYSTEM ONLINE</div>
    </div>

    <div class="main-container">
        
        <div class="camera-panel">
            <div class="video-container" id="camera-frame">
                <video id="video" autoplay playsinline></video>
                <div class="scanner-overlay" id="scan-line"></div>
                <div style="position:absolute; color: #64748b; font-size: 20px;" id="cam-placeholder">
                    <i class="fas fa-video-slash"></i> Camera Inactive
                </div>
            </div>
            <canvas id="canvas" style="display:none;"></canvas>
        </div>

        <div class="control-panel">
            
            <?php if($msg != ""): ?>
                <div class="alert alert-<?php echo $msgType; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <h2>Enrollment Protocol</h2>

            <form method="post" enctype="multipart/form-data" id="enrollForm">
                
                <div class="form-group">
                    <label>SELECT TARGET (STUDENT)</label>
                    <select name="student_id" required onchange="logEvent('Target Selected: ' + this.options[this.selectedIndex].text)">
                        <option value="">-- Initiate Selection --</option>
                        <?php
                        $ret = mysqli_query($con, "SELECT ID, FullName FROM tbluser");
                        while ($row = mysqli_fetch_array($ret)) {
                            echo '<option value="'.$row['ID'].'">'.$row['FullName'].' (ID: '.$row['ID'].')</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label>INPUT SOURCE</label>
                    <div class="toggle-mode">
                        <button type="button" class="mode-btn active" id="btn-cam" onclick="setMode('camera')">
                            <i class="fas fa-camera"></i> Live Cam
                        </button>
                        <button type="button" class="mode-btn" id="btn-up" onclick="setMode('upload')">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                </div>

                <input type="hidden" name="capture_mode" id="capture_mode" value="camera">
                
                <textarea name="image_base64" id="image_base64" style="display:none;"></textarea>

                <div id="camera-controls">
                    <button type="button" class="action-btn" style="background: var(--accent-cyan); margin-bottom: 10px;" onclick="takeSnapshot()">
                        <i class="fas fa-expand"></i> CAPTURE FRAME
                    </button>
                    <div class="snapshot-preview" id="snap-preview"></div>
                </div>

                <div id="upload-controls" style="display:none;">
                    <input type="file" name="face_image" accept="image/*" onchange="previewFile()">
                    <div class="snapshot-preview" id="file-preview" style="display:block; background-image:none;"></div>
                </div>

                <div class="system-log" id="sys-log">
                    > System Initialized...<br>
                    > Waiting for user input...
                </div>

                <button type="submit" name="submit" class="action-btn" style="margin-top: 20px;">
                    <i class="fas fa-fingerprint"></i> INITIATE ENROLLMENT
                </button>

            </form>

            <div class="mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 30px; padding-top: 20px;">
                <h6 class="text-muted text-uppercase mb-2" style="font-size: 0.8rem; letter-spacing: 1px; color: #ffc107;">
                    <i class="fas fa-tools"></i> Database Maintenance
                </h6>
                
                <form method="post">
                    <button type="submit" name="run_repair" class="action-btn" 
                            style="background: rgba(255, 193, 7, 0.1); border: 1px solid #ffc107; color: #ffc107; font-size: 14px;">
                        <i class="fas fa-sync-alt"></i> REPAIR ENCODINGS
                    </button>
                </form>

                <?php if(!empty($repair_output)): ?>
                    <div style="margin-top: 15px; padding: 10px; background: #000; border: 1px solid #ffc107; border-radius: 5px; font-family: monospace; font-size: 11px; color: #ccc; max-height: 150px; overflow-y: auto;">
                        <strong style="color: #ffc107;">>> SYSTEM LOG:</strong><br>
                        <pre style="white-space: pre-wrap; margin: 0;"><?php echo $repair_output; ?></pre>
                    </div>
                <?php endif; ?>
            </div>
            </div>
    </div>

    <script>
        // --- VARIABLES ---
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');
        const scanLine = document.getElementById('scan-line');
        const camPlaceholder = document.getElementById('cam-placeholder');
        const snapPreview = document.getElementById('snap-preview');
        const base64Input = document.getElementById('image_base64');
        const sysLog = document.getElementById('sys-log');
        let stream = null;

        // --- 1. INITIALIZE CAMERA ---
        async function startCamera() {
            try {
                logEvent("Attempting to access optical sensors...");
                stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                video.srcObject = stream;
                camPlaceholder.style.display = 'none';
                scanLine.style.display = 'block';
                logEvent("Camera Access GRANTED.");
            } catch (err) {
                logEvent("CRITICAL: Camera Access DENIED.");
                alert("Could not access camera. Please allow permissions.");
            }
        }

        // --- 2. STOP CAMERA ---
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                video.srcObject = null;
                scanLine.style.display = 'none';
                camPlaceholder.style.display = 'block';
                logEvent("Optical sensors disengaged.");
            }
        }

        // --- 3. MODE SWITCHING ---
        function setMode(mode) {
            document.getElementById('capture_mode').value = mode;
            
            // Toggle Buttons
            document.getElementById('btn-cam').className = (mode === 'camera') ? 'mode-btn active' : 'mode-btn';
            document.getElementById('btn-up').className = (mode === 'upload') ? 'mode-btn active' : 'mode-btn';

            // Toggle Areas
            if (mode === 'camera') {
                document.getElementById('camera-controls').style.display = 'block';
                document.getElementById('upload-controls').style.display = 'none';
                startCamera();
            } else {
                document.getElementById('camera-controls').style.display = 'none';
                document.getElementById('upload-controls').style.display = 'block';
                stopCamera();
            }
        }

        // --- 4. CAPTURE SNAPSHOT ---
        function takeSnapshot() {
            if (!video.srcObject) {
                alert("Camera not active!");
                return;
            }
            
            // Set canvas dimensions to match video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw video to canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert to Base64
            const dataURL = canvas.toDataURL('image/jpeg');
            base64Input.value = dataURL; // Store in hidden input
            
            // Show Preview
            snapPreview.style.display = 'block';
            snapPreview.style.backgroundImage = `url('${dataURL}')`;
            
            logEvent("Frame Captured. Size: " + Math.round(dataURL.length/1024) + "KB");
            
            // Blink effect
            document.querySelector('.video-container').style.borderColor = '#fff';
            setTimeout(() => {
                document.querySelector('.video-container').style.borderColor = 'rgba(255,255,255,0.05)';
            }, 200);
        }

        // --- 5. LOGGING UTILITY ---
        function logEvent(text) {
            const time = new Date().toLocaleTimeString();
            sysLog.innerHTML += `> [${time}] ${text}<br>`;
            sysLog.scrollTop = sysLog.scrollHeight;
        }

        // --- 6. FILE PREVIEW ---
        function previewFile() {
            const preview = document.getElementById('file-preview');
            const file = document.querySelector('input[type=file]').files[0];
            const reader = new FileReader();

            reader.onloadend = function () {
                preview.style.backgroundImage = `url('${reader.result}')`;
                preview.style.display = 'block';
                logEvent("File loaded locally.");
            }

            if (file) {
                reader.readAsDataURL(file);
            }
        }

        // --- INIT ---
        window.onload = function() {
            startCamera(); // Auto-start camera on load
        };

    </script>

</body>
</html>