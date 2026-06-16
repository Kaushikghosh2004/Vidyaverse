<?php 
session_start();
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('includes/dbconnection.php');

$msg = "";
$msgType = "";

if(isset($_POST['submit'])) {
    $fname = $_POST['fname'];
    $mobno = $_POST['mobno'];
    $email = $_POST['email'];
    $cid = $_POST['cid'];
    $rollnum = $_POST['rollnum'];
    $password = md5($_POST['password']); // Note: Consider switching to password_hash() in the future
    
    // 1. Check Duplicates
    $ret = "SELECT Email, MobileNumber, RollNumber FROM tbluser WHERE Email=:email || MobileNumber=:mobno || RollNumber=:rollnum";
    $query = $dbh->prepare($ret);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':mobno', $mobno, PDO::PARAM_STR);
    $query->bindParam(':rollnum', $rollnum, PDO::PARAM_STR);
    $query->execute();
    
    if($query->rowCount() == 0) {
        // 2. Generate Unique Data
        $qr_id = bin2hex(random_bytes(16)); 
        $batch_id = NULL; 

        // 3. Insert User
        $sql = "INSERT INTO tbluser(FullName, MobileNumber, Email, Cid, RollNumber, Password, qr_code_identifier, batch_id) 
                VALUES(:fname, :mobno, :email, :cid, :rollnum, :password, :qr_id, :batch_id)";
        
        $query = $dbh->prepare($sql);
        $query->bindParam(':fname', $fname, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':mobno', $mobno, PDO::PARAM_STR);
        $query->bindParam(':cid', $cid, PDO::PARAM_INT);
        $query->bindParam(':rollnum', $rollnum, PDO::PARAM_STR);
        $query->bindParam(':password', $password, PDO::PARAM_STR);
        $query->bindParam(':qr_id', $qr_id, PDO::PARAM_STR);
        $query->bindParam(':batch_id', $batch_id, PDO::PARAM_NULL);
        
        $query->execute();
        $lastInsertId = $dbh->lastInsertId();
        
        if($lastInsertId) {
            $msg = "Registration Successful! Redirecting...";
            $msgType = "success";
            echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
        } else {
            $msg = "Something went wrong. Please try again.";
            $msgType = "danger";
        }
    } else {
        $msg = "Email, Roll Number, or Mobile already exists.";
        $msgType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sign Up | VIDYAVERSE</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>

    <link href="../assets/css/lib/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/lib/themify-icons.css" rel="stylesheet">
    
    <style>
        /* --- CORE VARIABLES --- */
        :root {
            --bg-dark: #0f172a;
            --card-dark: #1e293b;
            --border-color: #334155;
            --accent: #3b82f6;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', 'Roboto', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* --- SPLIT LAYOUT --- */
        .auth-container {
            display: flex;
            width: 90%;
            max-width: 1100px;
            height: 85vh;
            background: var(--card-dark);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        /* LEFT SIDE (Branding) */
        .auth-left {
            flex: 1;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .auth-left h1 { font-size: 3rem; margin: 0; font-weight: 800; line-height: 1.1; }
        .auth-left p { font-size: 1.1rem; opacity: 0.8; margin-top: 15px; max-width: 80%; }
        
        /* Decorative Circles */
        .circle { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.1); }
        .c1 { width: 300px; height: 300px; top: -50px; left: -50px; }
        .c2 { width: 150px; height: 150px; bottom: 50px; right: 50px; }

        /* RIGHT SIDE (Form) */
        .auth-right {
            flex: 1.2;
            padding: 40px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto; /* Scrollable if form is long */
        }

        .form-header h2 { font-size: 24px; font-weight: 700; margin: 0 0 10px 0; color: white; }
        .form-header p { color: var(--text-muted); margin: 0 0 30px 0; font-size: 14px; }

        /* FORM ELEMENTS */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 8px; font-weight: 600; }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: var(--bg-dark);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: white;
            font-size: 14px;
            transition: 0.2s;
        }
        .form-control:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }

        /* DROPDOWN STYLING */
        select.form-control { appearance: none; background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E"); background-repeat: no-repeat; background-position: right 15px top 50%; background-size: 10px auto; }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 10px;
        }
        .btn-register:hover { background: #2563eb; }

        .login-link { text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-muted); }
        .login-link a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }

        /* ALERT BOXES */
        .alert { padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
        .alert-danger { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #6ee7b7; }

        /* MOBILE RESPONSIVE */
        @media (max-width: 768px) {
            .auth-container { flex-direction: column; height: 100vh; width: 100%; border-radius: 0; }
            .auth-left { display: none; }
            .auth-right { padding: 30px; }
        }
    </style>
</head>

<body>

    <div class="auth-container">
        
        <div class="auth-left">
            <div class="circle c1"></div>
            <div class="circle c2"></div>
            <h1>VIDYA<br>VERSE</h1>
            <p>Join the next generation of digital learning and academic management.</p>
        </div>

        <div class="auth-right">
            <div class="form-header">
                <h2>Create Account</h2>
                <p>Enter your details to register as a student.</p>
            </div>

            <?php if($msg != ""): ?>
                <div class="alert alert-<?php echo $msgType; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fname" class="form-control" placeholder="e.g. Kaushik Ghosh" required>
                </div>

                <div class="form-group" style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobno" class="form-control" maxlength="10" pattern="[0-9]+" placeholder="10 Digit Mobile" required>
                    </div>
                    <div style="flex:1;">
                        <label class="form-label">Roll Number</label>
                        <input type="text" name="rollnum" class="form-control" placeholder="ID No." required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="student@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Select Course</label>
                    <select name="cid" class="form-control" required>
                        <option value="">Choose Course...</option>
                        <?php
                        $sql="SELECT * from tblcourse";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results=$query->fetchAll(PDO::FETCH_OBJ);
                        if($query->rowCount() > 0) {
                            foreach($results as $row) { 
                        ?>
                            <option value="<?php echo $row->ID;?>">
                                <?php echo htmlentities($row->CourseName);?> (<?php echo htmlentities($row->BranchName);?>)
                            </option>
                        <?php 
                            }
                        } 
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" name="submit" class="btn-register">Register Now</button>

                <div class="login-link">
                    Already have an account? <a href="login.php">Log In</a>
                </div>
            </form>
        </div>

    </div>

</body>
</html>