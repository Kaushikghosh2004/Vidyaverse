<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include('includes/dbconnection.php');

$msg = "";
$msgType = "";

if(isset($_POST['submit'])) {
    $rollnum = $_POST['rollnum'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];

    // 1. Verify User Exists
    $sql = "SELECT ID FROM tbluser WHERE RollNumber=:rollnum AND Email=:email AND MobileNumber=:mobile";
    $query = $dbh->prepare($sql);
    $query->bindParam(':rollnum', $rollnum, PDO::PARAM_STR);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':mobile', $mobile, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);
    
    if($query->rowCount() > 0) {
        $uid = $result->ID;

        // 2. Check if a pending request already exists
        $chk = $dbh->prepare("SELECT ID FROM tblreset_requests WHERE UserID=:uid AND Status='Pending'");
        $chk->execute(['uid' => $uid]);

        if($chk->rowCount() == 0) {
            // 3. Insert Request
            $req = "INSERT INTO tblreset_requests(UserID) VALUES(:uid)";
            $q_req = $dbh->prepare($req);
            $q_req->bindParam(':uid', $uid, PDO::PARAM_INT);
            $q_req->execute();
            
            $msg = "Request Sent! Please contact the Admin to approve your reset.";
            $msgType = "success";
        } else {
            $msg = "You already have a pending request. Please wait for Admin approval.";
            $msgType = "warning";
        }
    } else {
        $msg = "Invalid Details. Please check your Roll Number, Email, and Mobile.";
        $msgType = "danger"; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Request Password Reset | VIDYAVERSE</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    
    <link href="../assets/css/lib/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/lib/themify-icons.css" rel="stylesheet">

    <style>
        :root { --bg-dark: #0f172a; --card-dark: #1e293b; --accent-red: #ef4444; --text-main: #f8fafc; --text-muted: #94a3b8; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background-color: var(--bg-dark); color: var(--text-main); height: 100vh; display: flex; align-items: center; justify-content: center; }
        
        .auth-container { display: flex; width: 90%; max-width: 900px; height: 70vh; background: var(--card-dark); border-radius: 20px; border: 1px solid #334155; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        
        .auth-left { flex: 1; background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); padding: 40px; color: white; display: flex; flex-direction: column; justify-content: center; position: relative; }
        .auth-left h1 { font-size: 2.5rem; margin: 0; font-weight: 800; }
        .auth-left p { opacity: 0.9; margin-top: 15px; line-height: 1.5; }
        
        .auth-right { flex: 1.3; padding: 40px; display: flex; flex-direction: column; justify-content: center; }
        
        .form-header h2 { margin: 0 0 10px 0; color: white; }
        .form-header p { color: var(--text-muted); margin: 0 0 25px 0; font-size: 14px; }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { width: 100%; padding: 12px 15px; background: var(--bg-dark); border: 1px solid #334155; border-radius: 8px; color: white; transition: 0.2s; }
        .form-control:focus { outline: none; border-color: var(--accent-red); }
        
        .btn-request { width: 100%; padding: 12px; background: var(--accent-red); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-request:hover { background: #dc2626; }
        
        .alert { padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
        .alert-danger { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #6ee7b7; }
        .alert-warning { background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #fcd34d; }
        
        .back-link { text-align: center; margin-top: 20px; font-size: 14px; }
        .back-link a { color: var(--text-muted); text-decoration: none; }
        .back-link a:hover { color: white; }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-left">
            <i class="ti-shield" style="font-size: 40px; margin-bottom: 20px;"></i>
            <h1>Secure<br>Reset</h1>
            <p>To protect your academic data, password resets must be approved by an administrator.</p>
        </div>

        <div class="auth-right">
            <div class="form-header">
                <h2>Request Reset</h2>
                <p>Verify your identity to send a request.</p>
            </div>

            <?php if($msg != ""): ?>
                <div class="alert alert-<?php echo $msgType; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label class="form-label">Roll Number (ID)</label>
                    <input type="text" class="form-control" name="rollnum" placeholder="Enter Roll No" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" name="email" placeholder="Enter Registered Email" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" class="form-control" name="mobile" placeholder="Enter Mobile No" maxlength="10" required>
                </div>

                <button type="submit" name="submit" class="btn-request">Send Request</button>

                <div class="back-link">
                    <a href="login.php">&larr; Back to Login</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>