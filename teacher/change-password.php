<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['ocastid'])) {
    header('location:logout.php');
    exit;
}

// --- CHANGE PASSWORD LOGIC ---
if(isset($_POST['submit'])) {
    $tid = $_SESSION['ocastid'];
    $cpassword = md5($_POST['currentpassword']);
    $newpassword = md5($_POST['newpassword']);
    
    // Verify Current Password
    $sql = "SELECT ID FROM tblteacher WHERE ID=:tid AND Password=:cpassword";
    $query = $dbh->prepare($sql);
    $query->bindParam(':tid', $tid, PDO::PARAM_STR);
    $query->bindParam(':cpassword', $cpassword, PDO::PARAM_STR);
    $query->execute();
    
    if($query->rowCount() > 0) {
        // Update to New Password
        $con = "UPDATE tblteacher SET Password=:newpassword WHERE ID=:tid";
        $chngpwd1 = $dbh->prepare($con);
        $chngpwd1->bindParam(':tid', $tid, PDO::PARAM_STR);
        $chngpwd1->bindParam(':newpassword', $newpassword, PDO::PARAM_STR);
        $chngpwd1->execute();
        
        echo '<script>alert("Your password has been successfully changed."); window.location.href ="change-password.php";</script>';
    } else {
        echo '<script>alert("Error: Your current password is incorrect.");</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    <title>Change Password | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc;
            /* Header height handled by global CSS */
        }

        /* --- LAYOUT --- */
        .container { 
            display: flex; justify-content: center; align-items: center;
            min-height: calc(100vh - 140px); /* Adjust for header/footer */
            padding: 20px;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px; padding: 40px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            width: 100%; max-width: 500px;
        }

        .section-label {
            font-size: 18px; font-weight: 700; color: #fff; 
            text-align: center; margin-bottom: 30px; letter-spacing: 0.5px;
        }
        
        /* --- FORM ELEMENTS --- */
        .form-group { margin-bottom: 25px; position: relative; }
        .form-group label { 
            display: block; font-size: 13px; color: #94a3b8; 
            margin-bottom: 8px; font-weight: 500; 
        }

        .modern-input {
            width: 100%; background: rgba(15, 23, 42, 0.8);
            border: 1px solid #334155; color: #fff;
            padding: 14px 14px 14px 45px; /* Left padding for icon */
            border-radius: 12px; font-size: 14px; transition: 0.3s;
        }
        .modern-input:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        
        .input-icon {
            position: absolute; left: 15px; top: 42px; /* Adjusted for label height */
            color: #64748b; font-size: 16px; transition: 0.3s;
        }
        .modern-input:focus + .input-icon { color: #3b82f6; }

        .btn-glow {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white; border: none; width: 100%; padding: 14px;
            border-radius: 12px; font-size: 16px; font-weight: 700;
            letter-spacing: 1px; cursor: pointer; text-transform: uppercase;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4); margin-top: 10px;
            transition: 0.3s;
        }
        .btn-glow:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(139, 92, 246, 0.6); }

    </style>

    <script type="text/javascript">
        function checkpass() {
            if(document.changepassword.newpassword.value != document.changepassword.confirmpassword.value) {
                alert('New Password and Confirm Password field does not match');
                document.changepassword.confirmpassword.focus();
                return false;
            }
            return true;
        }
    </script>
</head>
<body>

    <?php include_once('includes/header.php');?>

    <div class="container">
        <div class="glass-card">
            
            <div class="section-label">Change Password</div>

            <form method="post" name="changepassword" onsubmit="return checkpass();">
                
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="currentpassword" class="modern-input" required>
                    <i class="ti-lock input-icon"></i>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="newpassword" class="modern-input" required>
                    <i class="ti-key input-icon"></i>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirmpassword" class="modern-input" required>
                    <i class="ti-check-box input-icon"></i>
                </div>

                <button type="submit" name="submit" class="btn-glow">Update Password</button>

            </form>
        </div>
    </div>

    <?php include('includes/footer.php');?>

</body>
</html>