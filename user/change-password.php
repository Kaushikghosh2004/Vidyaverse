<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocasuid'] ?? '') == 0) {
    header('location:logout.php');
    exit;
} else {
    if(isset($_POST['submit'])) {
        $uid = $_SESSION['ocasuid'];
        $cpassword = md5($_POST['currentpassword']);
        $newpassword = md5($_POST['newpassword']);
        
        // Check Current Password
        $sql = "SELECT ID FROM tbluser WHERE ID=:uid and Password=:cpassword";
        $query = $dbh->prepare($sql);
        $query->bindParam(':uid', $uid, PDO::PARAM_STR);
        $query->bindParam(':cpassword', $cpassword, PDO::PARAM_STR);
        $query->execute();
        
        if($query->rowCount() > 0) {
            // Update Password
            $con = "UPDATE tbluser SET Password=:newpassword WHERE ID=:uid";
            $chngpwd1 = $dbh->prepare($con);
            $chngpwd1->bindParam(':uid', $uid, PDO::PARAM_STR);
            $chngpwd1->bindParam(':newpassword', $newpassword, PDO::PARAM_STR);
            $chngpwd1->execute();

            echo '<script>alert("Your password has been successfully changed."); window.location.href ="change-password.php";</script>';
        } else {
            echo '<script>alert("Your current password is incorrect.");</script>';
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Change Password | VidyaVerse</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 50% 0%, #1e293b 0%, #0f172a 100%); 
            font-family: 'Outfit', sans-serif; color: #f8fafc;
            min-height: 100vh;
        }

        /* --- LAYOUT --- */
        .container { 
            padding: 40px 20px;
            max-width: 600px; margin: 0 auto; /* Centered narrow container */
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px; padding: 40px;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.5);
            margin-bottom: 30px;
            position: relative; overflow: hidden;
        }

        /* Glowing Border Animation */
        .glass-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, #3b82f6, transparent);
            opacity: 0.5;
        }

        .section-header {
            text-align: center; margin-bottom: 30px;
        }
        .header-title { font-size: 24px; font-weight: 700; color: #fff; margin-bottom: 5px; }
        .header-sub { font-size: 14px; color: #94a3b8; }

        /* --- FORM ELEMENTS --- */
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label {
            display: block; font-size: 12px; color: #cbd5e1; 
            margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;
        }

        .input-wrapper { position: relative; }
        .input-wrapper i {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: #64748b; font-size: 16px; transition: 0.3s;
        }

        .modern-input {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid #334155;
            padding: 14px 14px 14px 45px; /* Left padding for icon */
            border-radius: 12px;
            color: #fff; font-size: 15px; outline: none;
            transition: 0.3s;
        }
        .modern-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }
        .modern-input:focus + i { color: #3b82f6; }

        .btn-update {
            width: 100%; padding: 14px; margin-top: 10px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white; border: none; border-radius: 12px;
            font-size: 16px; font-weight: 600; cursor: pointer;
            transition: 0.3s; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
        }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(37, 99, 235, 0.6); }

        .back-btn {
            display: block; text-align: center; margin-bottom: 20px;
            color: #94a3b8; text-decoration: none; font-size: 14px; transition: 0.2s;
        }
        .back-btn:hover { color: #fff; transform: translateX(-3px); display: inline-block; }

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
        
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="glass-card">
            
            <div class="section-header">
                <div class="header-title">Update Security</div>
                <div class="header-sub">Ensure your account stays protected.</div>
            </div>

            <form method="post" name="changepassword" onsubmit="return checkpass();">
                
                <div class="form-group">
                    <label>Current Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="currentpassword" class="modern-input" placeholder="Enter current password" required>
                        <i class="fas fa-lock"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="newpassword" class="modern-input" placeholder="Enter new password" required>
                        <i class="fas fa-key"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirmpassword" class="modern-input" placeholder="Repeat new password" required>
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-update">
                    Change Password <i class="fas fa-arrow-right" style="margin-left:5px;"></i>
                </button>

            </form>
        </div>
    </div>

    <?php include('includes/footer.php');?>

</body>
</html>
<?php } ?>