<?php
session_start();
error_reporting(0); // Hide system warnings
include('admin/includes/dbconnection.php');

// --- MAINTENANCE MODE CHECK ---
$is_maintenance = false;
try {
    // Automatically create the setting if it doesn't exist
    $dbh->query("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('maintenance_mode', '0')");
    
    $stmt = $dbh->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $is_maintenance = ($row['setting_value'] == '1');
    }
} catch(Exception $e) {}

// --- FEEDBACK FORM HANDLER ---
if(isset($_POST['submit_feedback'])) {
    $name = trim($_POST['fb_name']);
    $email = trim($_POST['fb_email']);
    $role = trim($_POST['fb_role']);
    $message = trim($_POST['fb_message']);

    try {
        $checkTable = $dbh->query("SHOW TABLES LIKE 'tblfeedback'");
        if($checkTable->rowCount() == 0) {
            $dbh->exec("CREATE TABLE tblfeedback (ID INT AUTO_INCREMENT PRIMARY KEY, FullName VARCHAR(100), Email VARCHAR(100), Role VARCHAR(50), Message TEXT, CreationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        }

        $sql = "INSERT INTO tblfeedback (FullName, Email, Role, Message) VALUES (:name, :email, :role, :msg)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':name', $name);
        $query->bindParam(':email', $email);
        $query->bindParam(':role', $role);
        $query->bindParam(':msg', $message);
        $query->execute();

        $_SESSION['toast_msg'] = "Transmission Logged: Thank you for your feedback!";
        $_SESSION['toast_type'] = "success";
    } catch(Exception $e) {
        $_SESSION['toast_msg'] = "Transmission Failed. Network Error.";
        $_SESSION['toast_type'] = "error";
    }
    header("Location: index.php#feedback");
    exit;
}

$toastMsg = $_SESSION['toast_msg'] ?? '';
$toastType = $_SESSION['toast_type'] ?? '';
unset($_SESSION['toast_msg'], $_SESSION['toast_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>VIDYAVERSE | The Future of Learning</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        /* --- RESET & BASE --- */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #0f172a; color: #fff; font-family: 'Outfit', sans-serif; overflow-x: hidden; scroll-behavior: smooth; }

        /* --- PRELOADER (IDEA CAFE STYLE) --- */
        #preloader {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-color: #000; z-index: 999999;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            transition: opacity 0.8s ease, visibility 0.8s ease;
        }
        .preloader-logo {
            font-family: 'Orbitron', sans-serif; font-size: 70px; font-weight: 900;
            color: #000; -webkit-text-stroke: 2px #00e5ff;
            text-shadow: 0 0 20px rgba(0, 229, 255, 0.8), 0 0 40px rgba(0, 229, 255, 0.4);
            letter-spacing: 10px; margin-bottom: 40px; text-transform: uppercase;
        }
        .preloader-text {
            color: #00e5ff; font-family: 'Orbitron', sans-serif; font-weight: 700;
            letter-spacing: 20px; font-size: 14px; animation: pulseLoad 1.5s infinite alternate;
        }
        @keyframes pulseLoad { 0% { opacity: 0.3; } 100% { opacity: 1; text-shadow: 0 0 15px #00e5ff; } }
        
        /* Preloader hide class */
        .preloader-hidden { opacity: 0; visibility: hidden; }

        /* --- MAINTENANCE UI --- */
        .maintenance-screen {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: radial-gradient(circle at center, #0f172a 0%, #000 100%); z-index: 888888;
            display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;
        }
        .maint-icon { font-size: 80px; color: #f59e0b; margin-bottom: 20px; filter: drop-shadow(0 0 20px rgba(245, 158, 11, 0.5)); animation: floatHolo 3s infinite ease-in-out; }
        .maint-title { font-family: 'Orbitron', sans-serif; font-size: 40px; font-weight: 900; color: #fff; margin-bottom: 15px; letter-spacing: 3px; }
        .maint-desc { font-size: 16px; color: #94a3b8; max-width: 500px; line-height: 1.6; }

        /* --- STUDENT-FRIENDLY AURORA BACKGROUND --- */
        .bg-universe {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -2;
            background: linear-gradient(-45deg, #0f172a, #312e81, #1e1b4b, #083344);
            background-size: 400% 400%; animation: gradientBG 15s ease infinite; overflow: hidden;
        }
        @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

        /* --- FLOATING EDUCATIONAL HOLOGRAMS --- */
        .edu-float { position: absolute; z-index: -1; transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); pointer-events: none; }
        .float-1 { font-size: 200px; top: 15%; left: 8%; animation: floatItem 12s ease-in-out infinite alternate; color: rgba(59, 130, 246, 0.15); filter: drop-shadow(0 0 20px rgba(59,130,246,0.3)); }
        .float-2 { font-size: 250px; bottom: 5%; right: 5%; animation: floatItem 18s ease-in-out infinite alternate-reverse; color: rgba(139, 92, 246, 0.15); filter: drop-shadow(0 0 20px rgba(139,92,246,0.3)); }
        .float-3 { font-size: 150px; top: 35%; right: 20%; animation: floatItem 15s ease-in-out infinite alternate; color: rgba(245, 158, 11, 0.15); filter: drop-shadow(0 0 20px rgba(245,158,11,0.3)); }
        .float-4 { font-size: 180px; bottom: 20%; left: 15%; animation: floatItem 20s ease-in-out infinite alternate-reverse; color: rgba(16, 185, 129, 0.15); filter: drop-shadow(0 0 20px rgba(16,185,129,0.3)); }
        .float-5 { font-size: 120px; top: -5%; right: 40%; animation: floatItem 14s ease-in-out infinite alternate; color: rgba(236, 72, 153, 0.15); filter: drop-shadow(0 0 20px rgba(236,72,153,0.3)); }
        @keyframes floatItem { 0% { transform: translateY(0px) rotate(0deg); } 50% { transform: translateY(-40px) rotate(15deg); } 100% { transform: translateY(20px) rotate(-10deg); } }
        .bg-universe::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at center, transparent 0%, rgba(0,0,0,0.6) 100%); z-index: -1; }

        /* --- NAVIGATION --- */
        .navbar { position: fixed; top: 0; width: 100%; height: 80px; z-index: 1000; display: flex; justify-content: space-between; align-items: center; padding: 0 50px; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.05); transition: 0.3s; }
        .logo { font-family: 'Orbitron', sans-serif; font-size: 24px; font-weight: 800; letter-spacing: 2px; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; text-shadow: 0 0 15px rgba(6, 182, 212, 0.5); }
        .logo i { color: #00e5ff; font-size: 28px; }
        .nav-links { display: flex; gap: 25px; }
        .nav-links a { text-decoration: none; color: #cbd5e1; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s; position: relative; cursor: pointer; }
        .nav-links a:hover, .nav-links a.active { color: #00e5ff; text-shadow: 0 0 10px rgba(0,229,255,0.5); }
        .btn-header { padding: 10px 24px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s; margin-left: 10px; border: 1px solid #00e5ff; color: #fff; background: rgba(0, 229, 255, 0.1); cursor: pointer; box-shadow: 0 0 15px rgba(0, 229, 255, 0.2); }
        .btn-header:hover { background: #00e5ff; color: #000; box-shadow: 0 0 25px rgba(0, 229, 255, 0.6); }

        /* --- HERO SECTION --- */
        .hero { height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 0 20px; position: relative; z-index: 1; }
        .hero-content { max-width: 1000px; position: relative; z-index: 10; }
        .hero h1 { font-family: 'Orbitron', sans-serif; font-size: 75px; line-height: 1.1; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; background: linear-gradient(to right, #fff, #00e5ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 0 20px rgba(0,229,255,0.3)); }
        .hero p { font-size: 20px; color: #e2e8f0; margin-bottom: 50px; font-weight: 400; letter-spacing: 1px; }
        .btn-giant { background: linear-gradient(135deg, #06b6d4, #3b82f6); color: #fff; padding: 20px 50px; font-size: 16px; font-weight: 800; border-radius: 50px; text-decoration: none; text-transform: uppercase; letter-spacing: 2px; box-shadow: 0 10px 30px rgba(6, 182, 212, 0.4); border: none; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 15px; }
        .btn-giant:hover { transform: translateY(-5px) scale(1.05); box-shadow: 0 15px 40px rgba(6, 182, 212, 0.6); }

        /* --- MODALS --- */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(15px); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: 0.4s; }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .close-modal { position: absolute; top: 30px; right: 40px; font-size: 40px; color: #fff; cursor: pointer; transition: 0.3s; z-index: 10000; }
        .close-modal:hover { color: #ef4444; transform: rotate(90deg); }
        .role-container { display: flex; gap: 30px; transform: scale(0.8) translateY(50px); transition: 0.4s; }
        .modal-overlay.active .role-container { transform: scale(1) translateY(0); }
        .role-card { background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; padding: 40px 30px; width: 280px; text-align: center; text-decoration: none; color: #fff; transition: 0.3s; box-shadow: inset 0 2px 10px rgba(255,255,255,0.05); }
        .role-card:hover { transform: translateY(-15px); background: rgba(0, 229, 255, 0.1); border-color: #00e5ff; box-shadow: 0 15px 35px rgba(0,229,255,0.2); }
        .role-icon { font-size: 60px; margin-bottom: 20px; color: #94a3b8; transition: 0.3s; }
        .role-card:hover .role-icon { color: #00e5ff; filter: drop-shadow(0 0 15px #00e5ff); }
        .role-title { font-size: 22px; font-weight: 800; font-family: 'Orbitron', sans-serif; display: block; margin-bottom: 10px; }
        .role-desc { font-size: 13px; color: #cbd5e1; line-height: 1.5; }

        /* --- COMMON SECTION STYLES --- */
        .section { padding: 120px 50px; position: relative; z-index: 10; }
        .section-title { text-align: center; font-size: 36px; font-weight: 800; margin-bottom: 60px; text-transform: uppercase; letter-spacing: 2px; font-family: 'Orbitron', sans-serif; }
        .section-title span { color: #00e5ff; text-shadow: 0 0 20px rgba(0,229,255,0.4); }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px; max-width: 1400px; margin: 0 auto; }
        .tilt-card { background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px; padding: 40px; backdrop-filter: blur(10px); transform-style: preserve-3d; transform: perspective(1000px); transition: border 0.3s, box-shadow 0.3s; cursor: pointer; text-decoration: none; display: block; color: inherit; }
        .tilt-card:hover { border-color: rgba(0, 229, 255, 0.4); box-shadow: 0 15px 40px rgba(0, 229, 255, 0.15); }
        .card-icon { font-size: 50px; color: #00e5ff; margin-bottom: 25px; transform: translateZ(40px); display: inline-block; filter: drop-shadow(0 0 15px rgba(0,229,255,0.4)); }
        .card-title { font-size: 22px; font-weight: 800; margin-bottom: 15px; transform: translateZ(30px); font-family: 'Orbitron', sans-serif; color: #fff; }
        .card-desc { color: #cbd5e1; line-height: 1.6; transform: translateZ(20px); font-size: 15px; }

        /* --- SPLIT SECTIONS --- */
        .split-section { background: linear-gradient(180deg, transparent, rgba(15,23,42,0.8), transparent); border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .split-content { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; max-width: 1400px; margin: 0 auto; }
        .split-text h2 { font-size: 45px; font-weight: 800; margin-bottom: 20px; font-family: 'Orbitron', sans-serif; background: linear-gradient(to right, #00e5ff, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .split-text p { font-size: 18px; color: #cbd5e1; margin-bottom: 30px; line-height: 1.6; }
        .hologram-stage { position: relative; height: 400px; display: flex; align-items: center; justify-content: center; }
        .hologram-base { position: absolute; bottom: 50px; width: 250px; height: 40px; background: radial-gradient(ellipse at center, rgba(0,229,255,0.4) 0%, transparent 70%); border-radius: 50%; }
        .stage-icon { font-size: 160px; color: #00e5ff; filter: drop-shadow(0 0 30px rgba(0,229,255,0.6)); animation: floatHolo 4s infinite ease-in-out; position: relative; z-index: 2; }
        @keyframes floatHolo { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-25px); } }

        /* --- ADMISSIONS BANNER --- */
        .admissions-banner { max-width: 1200px; margin: 0 auto; background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(59, 130, 246, 0.2)); border: 1px solid rgba(139, 92, 246, 0.4); border-radius: 30px; padding: 60px; text-align: center; box-shadow: 0 20px 50px rgba(139, 92, 246, 0.2); }
        .admissions-banner h2 { font-family: 'Orbitron', sans-serif; font-size: 40px; color: #fff; margin-bottom: 20px; }
        .admissions-banner p { font-size: 18px; color: #e2e8f0; margin-bottom: 40px; }

        /* --- FEEDBACK FORM --- */
        .feedback-container { max-width: 800px; margin: 0 auto; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 50px; box-shadow: 0 15px 50px rgba(0,0,0,0.5); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { position: relative; }
        .form-group label { display: block; font-size: 12px; color: #94a3b8; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 8px; }
        .modern-input { width: 100%; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 12px; color: #fff; font-family: 'Outfit', sans-serif; transition: 0.3s; outline: none; }
        .modern-input:focus { border-color: #00e5ff; box-shadow: 0 0 15px rgba(0,229,255,0.2); }
        select.modern-input { appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2300e5ff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 15px center; background-size: 16px; }
        select.modern-input option { background: #0f172a; color: #fff; }
        textarea.modern-input { resize: vertical; min-height: 120px; }
        .btn-submit { width: 100%; background: linear-gradient(135deg, #06b6d4, #3b82f6); color: #fff; border: none; padding: 18px; border-radius: 12px; font-size: 16px; font-weight: 800; font-family: 'Orbitron', sans-serif; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(6, 182, 212, 0.4); }

        /* --- TOAST NOTIFICATION --- */
        .glass-toast { position: fixed; top: 90px; right: -400px; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.1); border-left: 4px solid #06b6d4; padding: 18px 25px; border-radius: 12px; display: flex; align-items: center; gap: 15px; box-shadow: 0 15px 35px rgba(0,0,0,0.6); z-index: 9999; transition: right 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .glass-toast.show { right: 30px; }
        .toast-icon { font-size: 24px; }
        .toast-content h4 { margin: 0 0 4px; font-size: 14px; font-weight: 800; color: #fff; letter-spacing: 1px; text-transform: uppercase; }
        .toast-content p { margin: 0; font-size: 12px; color: #a1a1aa; }
        .toast-success { border-left-color: #10b981; } .toast-success .toast-icon { color: #10b981; text-shadow: 0 0 15px rgba(16, 185, 129, 0.5); }
        .toast-error { border-left-color: #ef4444; } .toast-error .toast-icon { color: #ef4444; text-shadow: 0 0 15px rgba(239, 68, 68, 0.5); }

        /* --- FOOTER --- */
        footer { text-align: center; padding: 40px; border-top: 1px solid rgba(255,255,255,0.05); color: #64748b; font-size: 14px; background: #000; }

        @media (max-width: 992px) {
            .preloader-logo { font-size: 40px; }
            .hero h1 { font-size: 40px; } .nav-links { display: none; }
            .split-content, .form-row { grid-template-columns: 1fr; text-align: center; }
            .role-container { flex-direction: column; gap: 20px; }
        }
    </style>
</head>
<body>

    <!-- 1. THE IDEA CAFE CYBER PRELOADER -->
    <div id="preloader">
        <div class="preloader-logo">VIDYAVERSE</div>
        <div class="preloader-text">L O A D I N G . . .</div>
    </div>

    <!-- 2. MAINTENANCE INTERCEPTOR -->
    <?php if ($is_maintenance): ?>
        <div class="maintenance-screen">
            <i class="fas fa-tools maint-icon"></i>
            <h1 class="maint-title">SYSTEM UPGRADE</h1>
            <p class="maint-desc">The VidyaVerse Master Console is currently offline for scheduled neural core upgrades and database synchronization. Please check back shortly.</p>
        </div>
        <!-- Script to hide preloader even during maintenance -->
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() { document.getElementById('preloader').classList.add('preloader-hidden'); }, 1500);
            });
        </script>
    </body>
    </html>
    <?php exit; endif; ?>
    <!-- ======================================= -->

    <!-- TOAST NOTIFICATION -->
    <div id="syncToast" class="glass-toast <?php echo ($toastType == 'success') ? 'toast-success' : 'toast-error'; ?>">
        <i class="fas <?php echo ($toastType == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> toast-icon"></i>
        <div class="toast-content">
            <h4><?php echo ($toastType == 'success') ? 'System Update' : 'System Alert'; ?></h4>
            <p><?php echo $toastMsg; ?></p>
        </div>
    </div>

    <!-- AURORA / ANTIGRAVITY BACKGROUND -->
    <div class="bg-universe">
        <i class="fas fa-atom edu-float float-1" data-speed="-1.5"></i>
        <i class="fas fa-book-open edu-float float-2" data-speed="1.2"></i>
        <i class="fas fa-lightbulb edu-float float-3" data-speed="-2"></i>
        <i class="fas fa-graduation-cap edu-float float-4" data-speed="1.5"></i>
        <i class="fas fa-rocket edu-float float-5" data-speed="-1.8"></i>
    </div>

    <nav class="navbar">
        <a href="index.php" class="logo"><i class='bx bx-cube-alt'></i> VIDYAVERSE</a>
        <div class="nav-links">
            <a href="index.php" class="active">Home</a>
            <a href="#about">About</a>
            <a href="#courses">Courses</a>
            <a href="#ailabs">AI Labs</a>
            <a href="http://ideacafe.42web.io" target="_blank">Events <i class="fas fa-external-link-alt" style="font-size:10px;"></i></a>
            <a href="#admissions">Admissions</a>
        </div>
        <button type="button" onclick="openLoginModal()" class="btn-header"><i class="fas fa-fingerprint"></i> Access Portal</button>
    </nav>

    <header class="hero">
        <div class="hero-content anti-grav-element" data-speed="1.5">
            <h1>THE METAVERSE<br> OF EDUCATION</h1>
            <p>Experience zero-risk virtual labs, AI-monitored secure examinations, and seamless campus event integrations.</p>
            <button type="button" onclick="openLoginModal()" class="btn-giant">
                Initiate Sequence <i class="fas fa-rocket"></i>
            </button>
        </div>
    </header>

    <!-- LOGIN MODAL -->
    <div class="modal-overlay" id="loginModal">
        <div class="close-modal" onclick="closeLoginModal()">&times;</div>
        <div class="role-container">
            <a href="user/login.php" class="role-card">
                <div class="role-icon"><i class="fas fa-user-astronaut"></i></div>
                <span class="role-title">Student Node</span>
                <span class="role-desc">Access VR labs, scheduled exams, and grade analytics.</span>
            </a>
            <a href="teacher/login.php" class="role-card">
                <div class="role-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <span class="role-title">Faculty Node</span>
                <span class="role-desc">Deploy matrix schedules and monitor live proctoring.</span>
            </a>
            <a href="admin/login.php" class="role-card" style="border-color: rgba(239, 68, 68, 0.3);">
                <div class="role-icon" style="color:#ef4444;"><i class="fas fa-shield-alt"></i></div>
                <span class="role-title" style="color:#ef4444;">Admin Core</span>
                <span class="role-desc">System overrides and database synchronization.</span>
            </a>
        </div>
    </div>

    <!-- FOUNDER'S MESSAGE -->
    <section class="section split-section" id="about">
        <div class="split-content">
            <div class="hologram-stage anti-grav-element" data-speed="-0.5">
                <i class="fas fa-user-tie stage-icon" style="color:#8b5cf6;"></i>
                <div class="hologram-base" style="background: radial-gradient(ellipse at center, rgba(139,92,246,0.4) 0%, transparent 70%);"></div>
            </div>
            <div class="split-text anti-grav-element" data-speed="1">
                <h2 style="font-size:35px;">The Architect's Vision</h2>
                <p>
                    "Education is no longer confined to the four walls of a classroom. At VidyaVerse, we are building a bridge between theoretical knowledge and practical execution through immersive digital environments. 
                    <br><br>
                    Our mission is to democratize high-end technical education, providing every student with their own AI-driven laboratory, transparent evaluation systems, and an open hub of infinite knowledge."
                </p>
                <div style="color:#8b5cf6; font-family:'Orbitron', sans-serif; font-weight:800; font-size:20px;">— Founder & System Architect</div>
            </div>
        </div>
    </section>

    <!-- COURSES (ACADEMIC NODES) -->
    <section class="section" id="courses">
        <h2 class="section-title">Academic <span>Nodes</span></h2>
        <div class="feature-grid">
            <div class="tilt-card">
                <i class="fas fa-laptop-code card-icon"></i>
                <div class="card-title">Computer Science</div>
                <div class="card-desc">Master algorithms, artificial intelligence, and full-stack software development in a fully digital environment.</div>
            </div>
            <div class="tilt-card">
                <i class="fas fa-network-wired card-icon" style="color:#8b5cf6;"></i>
                <div class="card-title" style="color:#fff;">Information Technology</div>
                <div class="card-desc">Specialize in cloud architecture, cybersecurity protocols, and enterprise-grade database management.</div>
            </div>
            <div class="tilt-card">
                <i class="fas fa-microchip card-icon" style="color:#f59e0b;"></i>
                <div class="card-title" style="color:#fff;">Electronics & Comm.</div>
                <div class="card-desc">Design VLSI circuits, simulate IoT microcontrollers, and study advanced signal transmission systems.</div>
            </div>
        </div>
    </section>

    <!-- CORE SYSTEMS -->
    <section class="section" id="features">
        <h2 class="section-title">Platform <span>Features</span></h2>
        <div class="feature-grid">
            <div class="tilt-card">
                <i class="fas fa-video card-icon"></i>
                <div class="card-title">Neural Proctoring</div>
                <div class="card-desc">Advanced optical surveillance ensures exam integrity by monitoring environmental anomalies.</div>
            </div>
            <div class="tilt-card">
                <i class="fas fa-brain card-icon"></i>
                <div class="card-title">Anti-Cheat Matrix</div>
                <div class="card-desc">Machine learning algorithms instantly flag tab-switching and unauthorized hardware.</div>
            </div>
            <a href="http://ideacafe.42web.io" target="_blank" class="tilt-card" id="card-event">
                <i class="fas fa-calendar-alt card-icon"></i>
                <div class="card-title">IdeaCafe Events <i class="fas fa-external-link-alt" style="font-size:12px;"></i></div>
                <div class="card-desc">Organize and participate in campus hackathons directly through our Event Hub.</div>
            </a>
        </div>
    </section>

    <!-- VIRTUAL AI LABS -->
    <section class="section" id="ailabs">
        <h2 class="section-title" style="color:#fff;">Virtual <span style="color:#8b5cf6;">AI Labs</span></h2>
        <div class="feature-grid">
            <div class="tilt-card">
                <i class="fas fa-atom card-icon"></i>
                <div class="card-title">Astro-Physics</div>
                <div class="card-desc">Explore the mechanics of the universe. Manipulate gravity and interact with the Solar System VR.</div>
            </div>
            <div class="tilt-card">
                <i class="fas fa-flask card-icon"></i>
                <div class="card-title">Chemical Synthesis</div>
                <div class="card-desc">Conduct highly reactive experiments with zero physical risk. Mix compounds and analyze atomic structures.</div>
            </div>
            <div class="tilt-card">
                <i class="fas fa-dna card-icon"></i>
                <div class="card-title">Molecular Biology</div>
                <div class="card-desc">Dive into the microscopic world. Edit DNA structures and observe genetic mutations.</div>
            </div>
        </div>
    </section>

    <!-- E-LIBRARY -->
    <section class="section split-section" id="library">
        <div class="split-content">
            <div class="split-text anti-grav-element" data-speed="1">
                <h2>The Infinite Codex</h2>
                <p>Tap into our decentralized E-Library. Access thousands of academic papers, digitized textbooks, and legacy records instantly. Knowledge should never be gated.</p>
                <a href="library.php" class="btn-giant" style="font-size:14px; padding:15px 35px;"><i class="fas fa-book-open"></i> Access Codex</a>
            </div>
            <div class="hologram-stage anti-grav-element" data-speed="-1">
                <i class="bx bx-book-reader stage-icon"></i>
                <div class="hologram-base"></div>
            </div>
        </div>
    </section>

    <!-- ADMISSIONS CTA -->
    <section class="section" id="admissions">
        <div class="admissions-banner anti-grav-element" data-speed="0.5">
            <h2>Session 2026 Intake is Live</h2>
            <p>Join the next generation of technologists, engineers, and digital architects. The future requires builders. Are you ready to initialize your journey?</p>
            <a href="#feedback" class="btn-giant" style="background: linear-gradient(135deg, #8b5cf6, #3b82f6); border:none;"><i class="fas fa-user-plus"></i> Initiate Enrollment</a>
        </div>
    </section>

    <!-- FEEDBACK / COMM-LINK -->
    <section class="section" id="feedback">
        <h2 class="section-title">Establish <span>Comm-Link</span></h2>
        
        <div class="feedback-container anti-grav-element" data-speed="-0.3">
            <form method="post" action="index.php">
                <div class="form-row">
                    <div class="form-group">
                        <label>Identity (Full Name)</label>
                        <input type="text" name="fb_name" class="modern-input" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="form-group">
                        <label>Return Address (Email)</label>
                        <input type="email" name="fb_email" class="modern-input" placeholder="e.g. john@vidyaverse.edu" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom:20px;">
                    <label>Designation / Role</label>
                    <select name="fb_role" class="modern-input" required>
                        <option value="">-- Select Designation --</option>
                        <option value="Prospective Student">Prospective Student (Admissions)</option>
                        <option value="Current Student">Current Student</option>
                        <option value="Faculty/Educator">Faculty / Educator</option>
                        <option value="General Inquiry">General Inquiry</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Transmission Payload (Message)</label>
                    <textarea name="fb_message" class="modern-input" placeholder="Enter your query or feedback here..." required></textarea>
                </div>

                <button type="submit" name="submit_feedback" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Transmit Data
                </button>
            </form>
        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> VIDYAVERSE 2.0 | NEURAL EDUCATIONAL NETWORK</p>
    </footer>

    <!-- INTERACTIVE SCRIPTS -->
    <script>
        // --- PRELOADER REMOVAL SCRIPT ---
        // Minimum delay of 1.5 seconds so users can appreciate the cyber animation
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('preloader').classList.add('preloader-hidden');
                // Optional: Remove it entirely from the DOM after fade out
                setTimeout(() => document.getElementById('preloader').style.display = 'none', 800);
            }, 1500); 
        });

        // Toast Animation
        document.addEventListener("DOMContentLoaded", function() {
            const toastMsg = "<?php echo addslashes($toastMsg); ?>";
            if (toastMsg.trim() !== "") {
                const toast = document.getElementById('syncToast');
                setTimeout(() => { toast.classList.add('show'); }, 100);
                setTimeout(() => { toast.classList.remove('show'); }, 3500);
            }
        });

        // Modal Logic
        function openLoginModal() { document.getElementById('loginModal').classList.add('active'); }
        function closeLoginModal() { document.getElementById('loginModal').classList.remove('active'); }
        window.addEventListener('click', function(e) { if (e.target == document.getElementById('loginModal')) closeLoginModal(); });

        // 3D Tilt Logic
        document.addEventListener('mousemove', (e) => {
            document.querySelectorAll('.tilt-card').forEach(card => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                if(x > -50 && x < rect.width + 50 && y > -50 && y < rect.height + 50) {
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = ((y - centerY) / 25) * -1;
                    const rotateY = (x - centerX) / 25;
                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
                } else {
                    card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1)`;
                }
            });
        });

        // Antigravity Engine
        document.addEventListener('mousemove', (e) => {
            const x = (window.innerWidth / 2 - e.pageX) / 50;
            const y = (window.innerHeight / 2 - e.pageY) / 50;
            document.querySelectorAll('.edu-float, .anti-grav-element').forEach(el => {
                const speed = el.getAttribute('data-speed') || 1;
                el.style.transform = `translate(${x * speed}px, ${y * speed}px)`;
            });
        });
        
        // Navbar Scroll
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                nav.style.background = 'rgba(15, 23, 42, 0.95)';
                nav.style.borderBottom = '1px solid rgba(0, 229, 255, 0.2)';
            } else {
                nav.style.background = 'rgba(15, 23, 42, 0.5)';
                nav.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
            }
        });
    </script>
</body>
</html>