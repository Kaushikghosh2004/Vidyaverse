<?php
session_start();
// Enable error reporting for debugging
error_reporting(0); // Set to E_ALL temporarily if you need to see errors
include('includes/dbconnection.php');

if(isset($_POST['login'])) {
    $rollmobilenum = $_POST['rollmobilenum'];
    $password = md5($_POST['password']);
    
    // FIX: We must use unique placeholders (:roll and :mobile) even if the value is the same
    $sql ="SELECT ID, FullName FROM tbluser WHERE (RollNumber=:roll || MobileNumber=:mobile) AND Password=:password";
    $query = $dbh->prepare($sql);
    
    // Bind the same input variable to both placeholders
    $query->bindParam(':roll', $rollmobilenum, PDO::PARAM_STR);
    $query->bindParam(':mobile', $rollmobilenum, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);
    
    if($query->rowCount() > 0) {
        foreach ($results as $result) {
            $_SESSION['ocasuid'] = $result->ID;
            // $_SESSION['ocasucid'] = $result->Cid; // Uncomment if your table has a 'Cid' column
            $_SESSION['student_name'] = $result->FullName;
        }
        $_SESSION['login'] = $_POST['rollmobilenum'];
        echo "<script type='text/javascript'> document.location ='dashboard.php'; </script>";
    } else {
        echo "<script>alert('Invalid Details. Please check your credentials.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | VidyaVerse</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap" rel="stylesheet">

    <style>
        /* --- RESET & BASE --- */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a;
            color: #fff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            perspective: 1000px; /* 3D Perspective */
        }

        /* --- ANIMATED BACKGROUND --- */
        .bg-animation {
            position: absolute; width: 100%; height: 100%; z-index: -1;
            background: radial-gradient(circle at 50% 50%, #1e293b 0%, #0f172a 100%);
            overflow: hidden;
        }
        .orb {
            position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.6;
            animation: floatOrb 10s infinite ease-in-out alternate;
        }
        .orb-1 { width: 300px; height: 300px; background: #8b5cf6; top: -50px; left: -50px; }
        .orb-2 { width: 400px; height: 400px; background: #3b82f6; bottom: -100px; right: -100px; animation-delay: -5s; }
        
        @keyframes floatOrb {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30px, 50px); }
        }

        /* --- 3D LOGIN CARD --- */
        .login-card {
            width: 100%; max-width: 420px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transform-style: preserve-3d;
            transform: rotateX(0deg) rotateY(0deg);
            transition: transform 0.1s ease-out; /* Smooth tilt */
        }

        /* --- HEADER --- */
        .header { text-align: center; margin-bottom: 30px; transform: translateZ(20px); }
        .logo-icon {
            font-size: 40px; color: #3b82f6; margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }
        .header h1 { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .header p { color: #94a3b8; font-size: 14px; }

        /* --- FORM --- */
        .form-group { margin-bottom: 20px; position: relative; transform: translateZ(10px); }
        .form-label {
            display: block; margin-bottom: 8px; font-size: 12px; font-weight: 600;
            color: #cbd5e1; text-transform: uppercase; letter-spacing: 1px;
        }
        
        .input-box { position: relative; }
        .input-box input {
            width: 100%; padding: 14px 14px 14px 45px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid #334155; border-radius: 12px;
            color: #fff; font-size: 14px; outline: none; transition: 0.3s;
        }
        .input-box i {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: #64748b; font-size: 16px; transition: 0.3s;
        }

        /* Focus States */
        .input-box input:focus {
            border-color: #3b82f6; background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .input-box input:focus + i { color: #3b82f6; }

        /* --- BUTTON --- */
        .btn-login {
            width: 100%; padding: 14px; margin-top: 10px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none; border-radius: 12px;
            color: white; font-size: 16px; font-weight: 600; cursor: pointer;
            transition: 0.3s; transform: translateZ(20px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
        }
        .btn-login:hover { transform: translateZ(25px) scale(1.02); box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.5); }

        /* --- LINKS --- */
        .links {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 25px; font-size: 13px; color: #94a3b8; transform: translateZ(10px);
        }
        .links a { color: #60a5fa; text-decoration: none; transition: 0.2s; }
        .links a:hover { color: #93c5fd; text-decoration: underline; }

        .signup-text { text-align: center; margin-top: 20px; font-size: 13px; color: #94a3b8; }
        .back-home { 
            position: absolute; top: 20px; left: 20px; color: #64748b; 
            text-decoration: none; font-size: 14px; transition: 0.2s;
            z-index: 10;
        }
        .back-home:hover { color: #fff; }

    </style>
</head>
<body>

    <div class="bg-animation">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <a href="../index.php" class="back-home"><i class="fas fa-arrow-left"></i> Home</a>

    <div class="login-card" id="tiltCard">
        
        <div class="header">
            <i class="fas fa-user-graduate logo-icon"></i>
            <h1>Student Portal</h1>
            <p>Access your classes, exams, and results</p>
        </div>

        <form method="post">
            <div class="form-group">
                <label class="form-label">Identity</label>
                <div class="input-box">
                    <input type="text" name="rollmobilenum" placeholder="Roll No. or Mobile Number" required>
                    <i class="fas fa-id-card"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <div class="links">
                <label style="cursor:pointer;"><input type="checkbox"> Remember me</label>
                <a href="forgot-password.php">Forgot Password?</a>
            </div>

            <button type="submit" name="login" class="btn-login">
                Login <i class="fas fa-chevron-right" style="font-size:12px; margin-left:5px;"></i>
            </button>

            <div class="signup-text">
                New here? <a href="signup.php">Create an Account</a>
            </div>
        </form>

    </div>

    <script>
        // 3D Tilt Effect Logic
        const card = document.getElementById('tiltCard');
        
        document.addEventListener('mousemove', (e) => {
            const xAxis = (window.innerWidth / 2 - e.pageX) / 25;
            const yAxis = (window.innerHeight / 2 - e.pageY) / 25;
            card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });

        // Reset position on mouse leave
        document.addEventListener('mouseleave', () => {
            card.style.transform = `rotateY(0deg) rotateX(0deg)`;
        });
    </script>

</body>
</html>