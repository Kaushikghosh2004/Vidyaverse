<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocasuid'] ?? '') == 0) {
    header('location:logout.php');
    exit();
}
$uid = $_SESSION['ocasuid'];

// --- 1. HANDLE MANDATORY PHOTO UPLOAD ---
$upload_error = "";
if(isset($_POST['upload_photo'])) {
    if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $img = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        
        if(in_array($ext, $allowed)) {
            $new_name = md5($uid . time()) . '.' . $ext;
            $dir = 'Uploads/students/';
            
            // Create directory if it doesn't exist
            if(!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            
            if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dir . $new_name)) {
                $dbh->prepare("UPDATE tbluser SET UserImage=? WHERE ID=?")->execute([$new_name, $uid]);
                header("Location: dashboard.php"); // Refresh to dismiss modal
                exit;
            } else {
                $upload_error = "Server Error: Could not save the file.";
            }
        } else {
            $upload_error = "Invalid Format: Only JPG and PNG allowed.";
        }
    } else {
        $upload_error = "No file selected or upload corrupted.";
    }
}

// --- 2. HANDLE QR REGENERATION ---
if(isset($_POST['regen_qr'])) {
    // Generate a fresh secure token
    $new_qr = 'STU_' . bin2hex(random_bytes(8));
    $dbh->prepare("UPDATE tbluser SET qr_code_identifier=? WHERE ID=?")->execute([$new_qr, $uid]);
    $_SESSION['toast_msg'] = "Identity Token Cycled Successfully.";
    $_SESSION['toast_type'] = "success";
    header("Location: dashboard.php");
    exit;
}

// --- 3. FETCH STUDENT DATA ---
$student_name = "Student";
$batch_id = 0; 
$user_image = "";
$qr_code_id = "";

try {
    $sql = "SELECT FullName, Cid, UserImage, qr_code_identifier FROM tbluser WHERE ID = :uid";
    $query = $dbh->prepare($sql);
    $query->execute(['uid' => $uid]);
    $res = $query->fetch(PDO::FETCH_OBJ);
    if($res) {
        $student_name = $res->FullName;
        $batch_id = $res->Cid; 
        $user_image = $res->UserImage;
        $qr_code_id = $res->qr_code_identifier;
        
        // Auto-generate QR if entirely missing
        if(empty($qr_code_id)) {
            $qr_code_id = 'STU_' . bin2hex(random_bytes(8));
            $dbh->prepare("UPDATE tbluser SET qr_code_identifier=? WHERE ID=?")->execute([$qr_code_id, $uid]);
        }
    }
} catch (Exception $e) {}

// Check if photo is missing to trigger lock modal
$needs_photo = empty($user_image) || $user_image == 'default.png' || $user_image == 'default.jpg';

// --- 4. CALCULATE STATS ---
$pending_tasks = 0;
$upcoming_exams = 0;

if($batch_id > 0) {
    try {
        $sql_pend = "SELECT count(*) FROM tblassigment WHERE Cid = :bid AND ID NOT IN (SELECT AssId FROM tbluploadass WHERE UserID = :uid)";
        $q_pend = $dbh->prepare($sql_pend);
        $q_pend->execute(['bid' => $batch_id, 'uid' => $uid]);
        $pending_tasks = $q_pend->fetchColumn();

        $sql_ex = "SELECT count(*) FROM tblexams WHERE (BatchID = :bid OR BatchID = 0) AND ExamDate >= CURDATE()";
        $q_ex = $dbh->prepare($sql_ex);
        $q_ex->execute(['bid' => $batch_id]);
        $upcoming_exams = $q_ex->fetchColumn();
    } catch (Exception $e) {}
}

// Fetch Toast
$toastMsg = $_SESSION['toast_msg'] ?? '';
$toastType = $_SESSION['toast_type'] ?? '';
unset($_SESSION['toast_msg'], $_SESSION['toast_type']);

// Time Greeting
date_default_timezone_set('Asia/Kolkata');
$hour = date('H');
if ($hour < 12) { $greeting = "Good Morning"; } 
elseif ($hour < 17) { $greeting = "Good Afternoon"; } 
else { $greeting = "Good Evening"; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Terminal | VidyaVerse</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <style>
        /* --- CORE THEME --- */
        :root {
            --bg-deep: #020617;
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-border: 1px solid rgba(255, 255, 255, 0.08);
            --neon-blue: #3b82f6;
            --neon-purple: #8b5cf6;
            --neon-cyan: #06b6d4;
            --neon-red: #ef4444;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            background-color: var(--bg-deep);
            background-image: radial-gradient(circle at 50% 0%, #0f172a 0%, #020617 100%);
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            margin: 0; padding: 0; overflow-x: hidden;
        }

        /* Wrap below fixed header */
        .main-wrapper { padding: 100px 30px 40px 30px; max-width: 1400px; margin: 0 auto; animation: fadeIn 0.8s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* --- LOCK SCREEN MODAL (MANDATORY PHOTO) --- */
        .lock-screen {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(2, 6, 23, 0.95); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            z-index: 99999; display: flex; align-items: center; justify-content: center;
        }
        .lock-card {
            background: #0f172a; border: 1px solid var(--neon-cyan); border-radius: 20px;
            padding: 40px; width: 450px; text-align: center; box-shadow: 0 0 50px rgba(6, 182, 212, 0.2);
        }
        .lock-card h2 { font-family: 'Orbitron', sans-serif; margin: 0 0 10px; color: #fff; }
        .lock-card p { color: var(--text-muted); font-size: 13px; margin-bottom: 25px; line-height: 1.5; }
        
        .upload-zone {
            border: 2px dashed #334155; border-radius: 16px; padding: 30px;
            background: rgba(0,0,0,0.3); transition: 0.3s; cursor: pointer; position: relative;
        }
        .upload-zone:hover { border-color: var(--neon-cyan); background: rgba(6, 182, 212, 0.05); }
        .upload-zone i { font-size: 40px; color: var(--neon-cyan); margin-bottom: 15px; }
        .upload-input { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        
        .btn-upload {
            background: var(--neon-cyan); color: #000; font-family: 'Orbitron', sans-serif; font-weight: 800;
            border: none; padding: 15px; width: 100%; border-radius: 12px; margin-top: 20px;
            cursor: pointer; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s;
        }
        .btn-upload:hover { box-shadow: 0 0 20px rgba(6, 182, 212, 0.5); transform: translateY(-2px); }

        /* --- PRESTIGE BANNER --- */
        .student-banner {
            background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
            border: 1px solid #1e293b; border-left: 4px solid var(--neon-cyan);
            border-radius: 16px; padding: 40px; display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 40px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5); position: relative; overflow: hidden;
        }
        .banner-text h1 { margin: 0; font-size: 32px; color: #ffffff; font-family: 'Orbitron', sans-serif; font-weight: 700; letter-spacing: 1px; }
        .banner-text h1 span { color: var(--neon-cyan); }
        .banner-text p { margin: 10px 0 0; color: #94a3b8; font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 500; }
        
        .banner-stats { display:flex; gap: 20px; align-items: center; z-index: 2; }
        .stat-box { text-align: center; background: rgba(0, 0, 0, 0.4); padding: 15px 25px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); }
        .stat-box h2 { margin: 0; font-size: 26px; font-weight: 700; color: #fff; }
        .stat-box span { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }

        .btn-identity {
            background: transparent; color: var(--neon-cyan); border: 1px solid var(--neon-cyan); 
            padding: 15px 24px; border-radius: 12px; font-size: 13px; font-weight: 700; cursor: pointer; 
            transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 10px;
        }
        .btn-identity:hover { background: var(--neon-cyan); color: #000; box-shadow: 0 8px 20px rgba(6, 182, 212, 0.3); transform: translateY(-2px); }

        /* Alert Box */
        .alert-box { background: rgba(239, 68, 68, 0.15); border: 1px solid var(--neon-red); color: #fca5a5; padding: 15px; border-radius: 12px; margin-top: 20px; display: inline-flex; align-items: center; gap: 15px; font-size: 14px; }

        /* --- BENTO GRID --- */
        .section-heading { font-family: 'Orbitron', sans-serif; font-size: 20px; font-weight: 700; margin: 40px 0 25px 0; display: flex; align-items: center; gap: 15px; color: #fff; letter-spacing: 1px; }
        .line-dec { height: 1px; flex: 1; background: linear-gradient(90deg, #334155, transparent); }

        .bento-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }

        .action-card {
            background: rgba(15, 23, 42, 0.6); border: 1px solid var(--glass-border); border-radius: 20px; padding: 30px;
            min-height: 200px; display: flex; flex-direction: column; justify-content: space-between;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); text-decoration: none; color: #fff;
        }
        .action-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.5); }

        .card-icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 20px; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.05); transition: 0.3s; }
        
        .ac-title { font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 8px; letter-spacing: 0.5px; }
        .ac-desc { font-size: 13px; color: var(--text-muted); line-height: 1.5; }
        .ac-arrow { margin-top: 20px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 1px; }

        /* Card Themes */
        .theme-blue:hover { border-color: var(--neon-blue); } .theme-blue .card-icon-box { color: var(--neon-blue); } .theme-blue .ac-arrow { color: var(--neon-blue); }
        .theme-cyan:hover { border-color: var(--neon-cyan); } .theme-cyan .card-icon-box { color: var(--neon-cyan); } .theme-cyan .ac-arrow { color: var(--neon-cyan); }
        .theme-purple:hover { border-color: var(--neon-purple); } .theme-purple .card-icon-box { color: var(--neon-purple); } .theme-purple .ac-arrow { color: var(--neon-purple); }
        .theme-red:hover { border-color: var(--neon-red); } .theme-red .card-icon-box { color: var(--neon-red); } .theme-red .ac-arrow { color: var(--neon-red); }

        /* --- QR IDENTITY MODAL --- */
        .qr-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(2, 6, 23, 0.85); backdrop-filter: blur(10px);
            z-index: 9999; display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden; transition: 0.3s;
        }
        .qr-modal-overlay.active { opacity: 1; visibility: visible; }
        .qr-card {
            background: #0f172a; border-radius: 20px; padding: 40px; text-align: center; width: 380px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5); transform: scale(0.95); transition: 0.3s;
            position: relative; border: 1px solid var(--neon-cyan);
        }
        .qr-modal-overlay.active .qr-card { transform: scale(1); }
        .close-qr { position: absolute; top: 20px; right: 20px; font-size: 24px; color: #64748b; cursor: pointer; transition: 0.2s; }
        .close-qr:hover { color: #ef4444; }
        
        .qr-code-box {
            background: #fff; padding: 20px; border-radius: 16px; display: inline-block; margin: 25px 0;
            border: 4px solid #1e293b;
        }

        .btn-cycle {
            background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 12px; width: 100%; border-radius: 10px; font-weight: 700; font-family: 'Orbitron', sans-serif;
            cursor: pointer; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; transition: 0.3s;
        }
        .btn-cycle:hover { background: #ef4444; color: #fff; box-shadow: 0 0 20px rgba(239, 68, 68, 0.4); }

        /* Toast */
        .glass-toast { position: fixed; top: 90px; right: -400px; background: rgba(15, 23, 42, 0.95); border: 1px solid #1e293b; border-left: 4px solid #10b981; padding: 18px 25px; border-radius: 12px; display: flex; align-items: center; gap: 15px; z-index: 10000; transition: right 0.5s; }
        .glass-toast.show { right: 30px; }

        @media (max-width: 992px) { .student-banner { flex-direction: column; text-align: center; gap: 20px; } .banner-stats { flex-wrap: wrap; justify-content: center; } }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<!-- 1. MANDATORY PHOTO UPLOAD LOCK SCREEN -->
<?php if($needs_photo): ?>
    <div class="lock-screen">
        <div class="lock-card">
            <h2><i class="fas fa-camera-retro" style="color:var(--neon-cyan);"></i> Security Protocol</h2>
            <p>System detects a missing visual identifier. Upload your official portrait to unlock the command center.</p>
            
            <?php if($upload_error): ?>
                <div style="color:#ef4444; font-size:12px; margin-bottom:10px; font-weight:bold;"><?php echo $upload_error; ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="upload-zone" id="drop-zone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <div id="file-name" style="font-size:13px; color:#cbd5e1; font-weight:600;">Tap to select JPG/PNG</div>
                    <input type="file" name="profile_pic" class="upload-input" accept=".jpg,.jpeg,.png" required onchange="updateFileName(this)">
                </div>
                <button type="submit" name="upload_photo" class="btn-upload">Upload & Unlock</button>
            </form>
        </div>
    </div>
    <script>
        function updateFileName(input) {
            if(input.files.length > 0) {
                document.getElementById('file-name').innerText = input.files[0].name;
                document.getElementById('drop-zone').style.borderColor = "var(--neon-cyan)";
            }
        }
    </script>
<?php endif; ?>

<div class="main-wrapper">

    <!-- Toast Notification -->
    <div id="syncToast" class="glass-toast <?php echo ($toastType == 'success') ? 'toast-success' : 'toast-error'; ?>">
        <i class="fas fa-check-circle" style="font-size:24px; color:#10b981;"></i>
        <div>
            <h4 style="margin:0 0 4px; font-size:14px; font-weight:800; color:#fff; text-transform:uppercase;">System Update</h4>
            <p style="margin:0; font-size:12px; color:#a1a1aa;"><?php echo $toastMsg; ?></p>
        </div>
    </div>

    <!-- BANNER -->
    <div class="student-banner">
        <div class="banner-text">
            <h1><?php echo $greeting; ?>,<br><span><?php echo htmlentities($student_name); ?></span></h1>
            <p>Academic Terminal & Evaluation Center</p>
            
            <?php if($batch_id == 0): ?>
                <div class="alert-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Batch Not Allocated!</strong><br>
                        You have not been assigned to a class. Contact administration to unlock exams.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="banner-stats">
            <button class="btn-identity" onclick="openQRModal()"><i class="fas fa-qrcode"></i> My Identity Node</button>
            <div class="stat-box">
                <h2 style="color:var(--neon-cyan);"><?php echo $pending_tasks; ?></h2>
                <span>Pending Tasks</span>
            </div>
            <div class="stat-box">
                <h2 style="color:var(--neon-red);"><?php echo $upcoming_exams; ?></h2>
                <span>Active Exams</span>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="section-heading"><i class="fas fa-bolt" style="color:var(--neon-cyan);"></i> Quick Actions <div class="line-dec"></div></div>
    <div class="bento-grid">
        <a href="live-feedback.php" class="action-card theme-red" style="border: 1px solid rgba(239, 68, 68, 0.4);">
            <div>
                <div class="card-icon-box" style="color:var(--neon-red);"><i class="fas fa-broadcast-tower"></i></div>
                <div class="ac-title">Live Class Feedback</div>
                <div class="ac-desc" style="color:#fca5a5;">Ask anonymous doubts or request breaks during live physical lectures.</div>
            </div>
            <div class="ac-arrow">Join Session <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="new-assignment.php" class="action-card theme-blue">
            <div>
                <div class="card-icon-box"><i class="fas fa-book"></i></div>
                <div class="ac-title">My Assignments</div>
                <div class="ac-desc">View uploaded homework and submit your document payload.</div>
            </div>
            <div class="ac-arrow">View Tasks <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="my-exams.php" class="action-card theme-cyan">
            <div>
                <div class="card-icon-box"><i class="fas fa-laptop-code"></i></div>
                <div class="ac-title">Online Exams</div>
                <div class="ac-desc">Enter the secure exam hall environment and check scorecards.</div>
            </div>
            <div class="ac-arrow">Enter Hall <i class="fas fa-arrow-right"></i></div>
        </a>
    </div>

    <!-- ACADEMIC TOOLS -->
    <div class="section-heading"><i class="fas fa-layer-group" style="color:var(--neon-purple);"></i> Academic Tools <div class="line-dec"></div></div>
    <div class="bento-grid">
        <a href="my-timetable.php" class="action-card theme-blue">
            <div>
                <div class="card-icon-box"><i class="fas fa-calendar-alt"></i></div>
                <div class="ac-title">Timetable Matrix</div>
                <div class="ac-desc">Check your daily class routine and faculty assignments.</div>
            </div>
            <div class="ac-arrow">View Schedule <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="uploaded-assignment.php" class="action-card theme-purple">
            <div>
                <div class="card-icon-box"><i class="fas fa-cloud-upload-alt"></i></div>
                <div class="ac-title">Submission History</div>
                <div class="ac-desc">Track previously submitted assignments and instructor grading.</div>
            </div>
            <div class="ac-arrow">Check Status <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="search.php" class="action-card theme-cyan">
            <div>
                <div class="card-icon-box"><i class="fas fa-search"></i></div>
                <div class="ac-title">Announcements</div>
                <div class="ac-desc">Query specific updates broadcasted by the administration.</div>
            </div>
            <div class="ac-arrow">Search Now <i class="fas fa-arrow-right"></i></div>
        </a>
    </div>

    <!-- MY IDENTITY QR MODAL -->
    <div class="qr-modal-overlay" id="qrModal">
        <div class="qr-card">
            <i class="fas fa-times close-qr" onclick="closeQRModal()"></i>
            <h3 style="font-family:'Orbitron', sans-serif; margin-bottom: 5px; color:#fff; font-size:22px; font-weight:800;">STUDENT IDENTITY</h3>
            <p style="font-size:12px; color:#94a3b8; margin-bottom: 0; text-transform:uppercase; letter-spacing:1px;">Display to Kiosk Scanner</p>
            
            <div class="qr-code-box" id="qrcode"></div>
            
            <div style="font-family: monospace; font-size:13px; color:var(--neon-cyan); letter-spacing:1px; word-break:break-all; background: rgba(0,0,0,0.5); padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($qr_code_id); ?>
            </div>

            <!-- SECURITY: Cycle Token Button -->
            <form method="post">
                <button type="submit" name="regen_qr" class="btn-cycle" onclick="return confirm('Cycling your token will invalidate your previous QR code. Proceed?');">
                    <i class="fas fa-sync-alt"></i> Cycle Identity Token
                </button>
            </form>
        </div>
    </div>

</div>

<script>
    // Toast Notification
    document.addEventListener("DOMContentLoaded", function() {
        const toastMsg = "<?php echo addslashes($toastMsg); ?>";
        if (toastMsg.trim() !== "") {
            const toast = document.getElementById('syncToast');
            setTimeout(() => { toast.classList.add('show'); }, 100);
            setTimeout(() => { toast.classList.remove('show'); }, 3500);
        }
    });

    // QR Logic
    let qrRendered = false;
    const qrString = "<?php echo addslashes($qr_code_id); ?>";

    function openQRModal() {
        document.getElementById('qrModal').classList.add('active');
        
        if(!qrRendered && qrString !== "") {
            new QRCode(document.getElementById("qrcode"), {
                text: qrString,
                width: 200,
                height: 200,
                colorDark : "#0f172a",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
            qrRendered = true;
        }
    }

    function closeQRModal() {
        document.getElementById('qrModal').classList.remove('active');
    }

    window.addEventListener('click', function(e) {
        if (e.target == document.getElementById('qrModal')) closeQRModal();
    });
</script>

<?php include('includes/footer.php');?>
</body>
</html>