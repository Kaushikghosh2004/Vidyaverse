<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

if(isset($_POST['submit'])) {
    $adminid = $_SESSION['admin_id'];
    $cpassword = md5($_POST['currentpassword']);
    $newpassword = md5($_POST['newpassword']);
    
    // Verify Old Password
    $sql = "SELECT ID FROM tbladmin WHERE ID=:adminid AND Password=:cpassword";
    $query = $dbh->prepare($sql);
    $query->bindParam(':adminid', $adminid, PDO::PARAM_STR);
    $query->bindParam(':cpassword', $cpassword, PDO::PARAM_STR);
    $query->execute();
    
    if($query->rowCount() > 0) {
        // Update Password
        $con = "UPDATE tbladmin SET Password=:newpassword WHERE ID=:adminid";
        $chngpwd1 = $dbh->prepare($con);
        $chngpwd1->bindParam(':adminid', $adminid, PDO::PARAM_STR);
        $chngpwd1->bindParam(':newpassword', $newpassword, PDO::PARAM_STR);
        $chngpwd1->execute();
        
        echo '<script>alert("Password successfully changed."); window.location.href="change-password.php";</script>';
    } else {
        echo '<script>alert("Current password is incorrect.");</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Change Password | Admin</title>
    
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
        
        .btn-back {
            background: #334155; color: #fff; padding: 8px 20px; border-radius: 6px;
            text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 8px;
        }
        .btn-back:hover { background: #475569; color: #fff; }

        /* CONTENT */
        .main-content {
            margin-top: 80px;
            padding: 40px;
            max-width: 600px; /* Centered narrow card */
            margin-left: auto; margin-right: auto;
            min-height: calc(100vh - 80px);
            display: flex; flex-direction: column; justify-content: center;
        }

        /* CARD */
        .password-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .card-title { font-size: 24px; font-weight: 700; color: #fff; margin-bottom: 5px; text-align: center; }
        .card-desc { color: #94a3b8; text-align: center; margin-bottom: 30px; font-size: 14px; }

        /* FORM */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; color: #cbd5e1; margin-bottom: 8px; font-size: 13px; font-weight: 600; text-transform: uppercase; }
        
        .form-control {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            font-size: 15px;
            transition: 0.2s;
        }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }

        /* BUTTON */
        .btn-save {
            width: 100%;
            background: #3b82f6;
            color: white; border: none; padding: 15px;
            border-radius: 8px; font-weight: 700; font-size: 16px;
            cursor: pointer; transition: 0.2s;
            text-transform: uppercase; letter-spacing: 1px;
            margin-top: 10px;
        }
        .btn-save:hover { background: #2563eb; }

    </style>

    <script type="text/javascript">
        function checkpass() {
            if(document.changepassword.newpassword.value != document.changepassword.confirmpassword.value) {
                alert('New Password and Confirm Password fields do not match');
                document.changepassword.confirmpassword.focus();
                return false;
            }
            return true;
        }
    </script>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <i class="ti-lock"></i> SECURITY SETTINGS
        </div>
        <a href="dashboard.php" class="btn-back">
            <i class="ti-arrow-left"></i> Dashboard
        </a>
    </div>

    <div class="main-content">
        
        <div class="password-card">
            <h2 class="card-title">Update Password</h2>
            <p class="card-desc">Secure your admin account with a new password.</p>

            <form method="post" name="changepassword" onsubmit="return checkpass();">
                
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" class="form-control" name="currentpassword" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="newpassword" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" name="confirmpassword" required>
                </div>

                <button type="submit" name="submit" class="btn-save">Update Credentials</button>

            </form>
        </div>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>

</body>
</html>