<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if(isset($_POST['submit'])) {
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $newpassword = md5($_POST['newpassword']);
    
    // Check if user exists
    $sql = "SELECT Email FROM tbladmin WHERE Email=:email AND MobileNumber=:mobile";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':mobile', $mobile, PDO::PARAM_STR);
    $query->execute();
    
    if($query->rowCount() > 0) {
        // Update Password
        $con = "UPDATE tbladmin SET Password=:newpassword WHERE Email=:email AND MobileNumber=:mobile";
        $chngpwd1 = $dbh->prepare($con);
        $chngpwd1->bindParam(':email', $email);
        $chngpwd1->bindParam(':mobile', $mobile);
        $chngpwd1->bindParam(':newpassword', $newpassword);
        $chngpwd1->execute();
        
        echo "<script>alert('Password reset successful. Please login.'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Invalid Email or Mobile Number.');</script>"; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    <title>Reset Password | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL RESET & THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%);
            height: 100vh;
            display: flex; align-items: center; justify-content: center;
            color: #f8fafc; overflow: hidden;
        }

        /* --- GLASS CARD --- */
        .login-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 40px;
            width: 100%; max-width: 420px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* --- HEADER --- */
        .brand-section { text-align: center; margin-bottom: 30px; }
        .brand-title { 
            font-size: 24px; font-weight: 700; color: #fff; letter-spacing: 1px; 
            margin-bottom: 5px; display: block;
        }
        .brand-subtitle { font-size: 13px; color: #94a3b8; }
        .brand-icon { font-size: 32px; color: #10b981; margin-bottom: 10px; }

        /* --- FORM ELEMENTS --- */
        .input-group { position: relative; margin-bottom: 20px; }
        
        .modern-input {
            width: 100%; background: rgba(15, 23, 42, 0.6);
            border: 1px solid #334155; color: #fff;
            padding: 14px 14px 14px 45px; /* Left padding for icon */
            border-radius: 12px; font-size: 14px; transition: 0.3s; outline: none;
        }
        .modern-input:focus {
            border-color: #10b981; box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
        }
        
        .input-icon {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: #64748b; font-size: 18px; transition: 0.3s;
        }
        .modern-input:focus + .input-icon { color: #10b981; }

        .btn-reset {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border: none; width: 100%; padding: 14px;
            border-radius: 12px; font-size: 15px; font-weight: 600;
            letter-spacing: 0.5px; cursor: pointer; margin-top: 10px;
            transition: 0.3s; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }
        .btn-reset:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6); }

        .back-link {
            display: block; text-align: center; margin-top: 25px;
            color: #94a3b8; text-decoration: none; font-size: 13px; transition: 0.2s;
        }
        .back-link:hover { color: #fff; }
    </style>

    <script type="text/javascript">
        function valid() {
            if(document.chngpwd.newpassword.value != document.chngpwd.confirmpassword.value) {
                alert("New Password and Confirm Password do not match!");
                document.chngpwd.confirmpassword.focus();
                return false;
            }
            return true;
        }
    </script>
</head>
<body>

    <div class="login-card">
        <div class="brand-section">
            <div class="brand-icon"><i class="ti-lock"></i></div>
            <span class="brand-title">Reset Password</span>
            <span class="brand-subtitle">Enter details to regain access</span>
        </div>

        <form method="post" name="chngpwd" onSubmit="return valid();">
            
            <div class="input-group">
                <input type="email" name="email" class="modern-input" placeholder="Registered Email" required>
                <i class="ti-email input-icon"></i>
            </div>

            <div class="input-group">
                <input type="text" name="mobile" class="modern-input" placeholder="Registered Mobile" maxlength="10" pattern="[0-9]+" required>
                <i class="ti-mobile input-icon"></i>
            </div>

            <div class="input-group">
                <input type="password" name="newpassword" class="modern-input" placeholder="New Password" required>
                <i class="ti-key input-icon"></i>
            </div>

            <div class="input-group">
                <input type="password" name="confirmpassword" class="modern-input" placeholder="Confirm New Password" required>
                <i class="ti-check-box input-icon"></i>
            </div>

            <button type="submit" name="submit" class="btn-reset">Reset Password</button>
            
            <a href="login.php" class="back-link">
                <i class="ti-arrow-left"></i> Back to Login
            </a>
        </form>
    </div>

</body>
</html>