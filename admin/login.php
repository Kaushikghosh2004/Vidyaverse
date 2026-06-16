<?php
// 1. SECURE SESSION START
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}
error_reporting(0);
include('includes/dbconnection.php');

// If already logged in, push them to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('location:dashboard.php');
    exit;
}

if(isset($_POST['login'])) {
    $username = trim($_POST['username']);
    // Note: MD5 is used here to maintain compatibility with your current database. 
    // For future projects, consider migrating to password_hash() and password_verify().
    $password = md5($_POST['password']); 
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $sql = "SELECT ID FROM tbladmin WHERE UserName=:username and Password=:password";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);
    
    if($query->rowCount() > 0) {
        // --- LOGIN SUCCESS ---
        
        // 1. Prevent Session Fixation Attacks by regenerating ID on login
        session_regenerate_id(true); 
        
        foreach ($results as $result) {
            $_SESSION['admin_id'] = $result->ID;
        }
        
        // 2. Log successful login to the Audit Trail
        try {
            $audit_sql = "INSERT INTO tblaudit_logs (user_id, action, ip_address) VALUES (:uid, 'login_success', :ip)";
            $audit_query = $dbh->prepare($audit_sql);
            $audit_query->execute([':uid' => $username, ':ip' => $ip_address]);
        } catch (Exception $e) {}

        // 3. Handle Cookies Securely
        if(!empty($_POST["remember"])) {
            setcookie("user_login", $_POST["username"], time() + (10 * 365 * 24 * 60 * 60), "/", "", false, true); // Added HttpOnly flag
            setcookie("userpassword", $_POST["password"], time() + (10 * 365 * 24 * 60 * 60), "/", "", false, true);
        } else {
            if(isset($_COOKIE["user_login"])) { setcookie("user_login", "", time() - 3600, "/"); }
            if(isset($_COOKIE["userpassword"])) { setcookie("userpassword", "", time() - 3600, "/"); }
        }
        
        $_SESSION['login'] = $_POST['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        // --- LOGIN FAILED (THREAT DETECTION) ---
        
        // Log the failed attempt to trigger the Dashboard Security Matrix
        try {
            $audit_sql = "INSERT INTO tblaudit_logs (user_id, action, ip_address) VALUES (:uid, 'failed_login', :ip)";
            $audit_query = $dbh->prepare($audit_sql);
            $audit_query->execute([':uid' => $username, ':ip' => $ip_address]);
        } catch (Exception $e) {}

        $errorMessage = "Authentication Failed. Activity Logged.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>VidyaVerse | Secure Admin Terminal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* --- RESET & CORE --- */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            background-color: #030712;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            height: 100vh; display: flex; align-items: center; justify-content: center;
            overflow: hidden; perspective: 1000px;
            
            /* Match the dashboard background */
            background: 
                linear-gradient(to bottom, rgba(3, 7, 18, 0.85), rgba(8, 145, 178, 0.4)),
                url('https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed;
            background-size: cover;
        }

        /* 3D Moving Floor Grid */
        body::after {
            content: ""; position: fixed; bottom: -50%; left: -50%; width: 200%; height: 150%;
            background-image: linear-gradient(rgba(0, 229, 255, 0.15) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 229, 255, 0.15) 1px, transparent 1px);
            background-size: 50px 50px; transform: rotateX(75deg) translateY(0); transform-origin: top center;
            animation: gridMove 15s linear infinite; pointer-events: none; z-index: -1;
            -webkit-mask-image: linear-gradient(to top, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 60%);
        }
        @keyframes gridMove { 0% { transform: rotateX(75deg) translateY(0); } 100% { transform: rotateX(75deg) translateY(50px); } }

        /* --- 3D LOGIN CONTAINER --- */
        .login-card {
            width: 100%; max-width: 450px; padding: 50px 40px;
            background: rgba(10, 15, 30, 0.65);
            border: 1px solid rgba(0, 229, 255, 0.3);
            border-radius: 20px;
            backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.7), inset 0 1px 2px rgba(255, 255, 255, 0.4);
            transform-style: preserve-3d; transform: rotateX(0deg) rotateY(0deg);
            transition: transform 0.1s ease-out; position: relative;
        }

        /* Hover Glow Effect */
        .login-card::after {
            content: ''; position: absolute; inset: 0; border-radius: 20px;
            box-shadow: 0 0 40px rgba(0, 229, 255, 0.15);
            opacity: 0; transition: 0.3s; pointer-events: none;
        }
        .login-card:hover::after { opacity: 1; }

        /* --- CONTENT STYLING --- */
        .header { text-align: center; margin-bottom: 35px; transform: translateZ(30px); }
        
        .icon-box {
            width: 80px; height: 80px; margin: 0 auto 20px;
            background: linear-gradient(135deg, rgba(0, 229, 255, 0.2), rgba(0,0,0,0.7));
            border: 1px solid #00e5ff; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; color: #00e5ff; box-shadow: 0 0 25px rgba(0, 229, 255, 0.2);
            animation: pulseIcon 2s infinite;
        }
        @keyframes pulseIcon { 0% { box-shadow: 0 0 15px rgba(0,229,255,0.2); } 50% { box-shadow: 0 0 35px rgba(0,229,255,0.5); } 100% { box-shadow: 0 0 15px rgba(0,229,255,0.2); } }

        .title { 
            font-family: 'Orbitron', sans-serif; font-size: 26px; font-weight: 800; 
            letter-spacing: 2px; color: #fff; margin-bottom: 5px; text-transform: uppercase;
            text-shadow: 0 0 10px rgba(0, 229, 255, 0.5);
        }
        .subtitle { font-size: 13px; color: #a5f3fc; letter-spacing: 2px; text-transform: uppercase; }

        /* Error Message */
        .error-message {
            background: rgba(239, 68, 68, 0.2); border-left: 4px solid #ef4444;
            color: #fca5a5; padding: 12px; font-size: 13px; font-weight: 600;
            margin-bottom: 20px; border-radius: 4px; text-align: center;
            transform: translateZ(25px); letter-spacing: 1px; text-transform: uppercase;
        }

        /* --- FORM ELEMENTS --- */
        .form-group { margin-bottom: 25px; position: relative; transform: translateZ(20px); }
        .form-label { display: block; font-size: 12px; color: #cbd5e1; margin-bottom: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 1.5px; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: #00e5ff; opacity: 0.6; transition: 0.3s; }
        .modern-input {
            width: 100%; padding: 16px 20px 16px 50px;
            background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(0, 229, 255, 0.3);
            border-radius: 12px; color: #fff; font-size: 15px; outline: none; font-family: 'Outfit', sans-serif; transition: 0.3s;
        }
        .modern-input:focus { border-color: #00e5ff; background: rgba(0, 0, 0, 0.7); box-shadow: 0 0 20px rgba(0, 229, 255, 0.2); }
        .modern-input:focus + i { opacity: 1; text-shadow: 0 0 10px #00e5ff; }

        /* --- ACTION BUTTONS --- */
        .actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; font-size: 13px; transform: translateZ(20px); }
        .actions label { cursor: pointer; color: #94a3b8; display: flex; align-items: center; gap: 8px; }
        .actions a { color: #00e5ff; text-decoration: none; transition: 0.2s; font-weight: 600; }
        .actions a:hover { color: #fff; text-shadow: 0 0 10px #00e5ff; }

        .btn-login {
            width: 100%; padding: 18px; background: linear-gradient(135deg, #0891b2, #06b6d4);
            border: 1px solid #00e5ff; border-radius: 12px; color: white; font-family: 'Orbitron', sans-serif;
            font-size: 15px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; cursor: pointer;
            transition: 0.3s; transform: translateZ(30px); box-shadow: 0 10px 25px rgba(0, 229, 255, 0.3);
        }
        .btn-login:hover { transform: translateZ(35px) translateY(-2px); box-shadow: 0 15px 35px rgba(0, 229, 255, 0.5); filter: brightness(120%); }

        .back-home { display: block; text-align: center; margin-top: 30px; color: #64748b; text-decoration: none; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; transform: translateZ(10px); transition: 0.3s; }
        .back-home:hover { color: #00e5ff; }
    </style>
</head>
<body>

    <div class="login-card" id="tiltCard">
        
        <div class="header">
            <div class="icon-box"><i class="fas fa-fingerprint"></i></div>
            <div class="title">Admin Terminal</div>
            <div class="subtitle">VidyaVerse Neural Core</div>
        </div>

        <?php if(isset($errorMessage)) { echo '<div class="error-message"><i class="fas fa-exclamation-triangle"></i> ' . $errorMessage . '</div>'; } ?>

        <form method="post">
            <div class="form-group">
                <label class="form-label">System ID</label>
                <div class="input-wrapper">
                    <input type="text" name="username" class="modern-input" placeholder="Enter Admin ID" required value="<?php if(isset($_COOKIE["user_login"])) { echo $_COOKIE["user_login"]; } ?>">
                    <i class="fas fa-user-astronaut"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Security Key</label>
                <div class="input-wrapper">
                    <input type="password" name="password" class="modern-input" placeholder="Enter Password" required value="<?php if(isset($_COOKIE["userpassword"])) { echo $_COOKIE["userpassword"]; } ?>">
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <div class="actions">
                <label><input type="checkbox" name="remember" <?php if(isset($_COOKIE["user_login"])) { ?> checked <?php } ?> > Remember Node</label>
                <a href="forgot-password.php">Lost Access?</a>
            </div>

            <button type="submit" name="login" class="btn-login">
                Authenticate <i class="fas fa-arrow-right" style="margin-left:8px;"></i>
            </button>
        </form>

        <a href="../index.php" class="back-home"><i class="fas fa-power-off"></i> Terminate Connection</a>

    </div>

    <script>
        // 3D Tilt Effect
        const card = document.getElementById('tiltCard');
        document.addEventListener('mousemove', (e) => {
            const xAxis = (window.innerWidth / 2 - e.pageX) / 35;
            const yAxis = (window.innerHeight / 2 - e.pageY) / 35;
            card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });
        document.addEventListener('mouseleave', () => {
            card.style.transform = `rotateY(0deg) rotateX(0deg)`;
        });
    </script>

</body>
</html>