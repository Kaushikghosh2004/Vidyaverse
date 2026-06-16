<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if(isset($_POST['login'])) {
    $empid = $_POST['empid'];
    $password = md5($_POST['password']);
    
    $sql = "SELECT ID, EmpID, CourseID, FirstName, LastName FROM tblteacher WHERE EmpID=:empid and Password=:password";
    $query = $dbh->prepare($sql);
    $query->bindParam(':empid', $empid, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);
    
    if($query->rowCount() > 0) {
        foreach ($results as $result) {
            $_SESSION['ocastid'] = $result->ID;
            $_SESSION['ocasteaid'] = $result->EmpID;
            $_SESSION['ocastcid'] = $result->CourseID;
            $_SESSION['teacher_name'] = $result->FirstName . " " . $result->LastName;
        }
        $_SESSION['login'] = $_POST['empid'];
        echo "<script type='text/javascript'> document.location ='dashboard.php'; </script>";
    } else {
        echo "<script>alert('Invalid Details. Please check your Employee ID or Password.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    <title>Teacher Login | VidyaVerse</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- ANIMATED BACKGROUND & GLOBAL --- */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(-45deg, #0f172a, #1e293b, #3b82f6, #0f172a);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            overflow: hidden;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* --- GLASS CARD CONTAINER --- */
        .login-wrapper {
            position: relative;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- HEADER SECTION --- */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .brand-icon {
            font-size: 48px;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            display: inline-block;
            filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.5));
        }

        .login-header h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .login-header p {
            color: #94a3b8;
            font-size: 14px;
        }

        /* --- FORM ELEMENTS --- */
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 18px;
            transition: 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid #334155;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-control::placeholder { color: #64748b; }

        .form-control:focus {
            border-color: #3b82f6;
            background: rgba(15, 23, 42, 0.9);
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.3);
        }

        .form-control:focus + i { color: #3b82f6; }

        /* --- BUTTONS --- */
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.6);
        }

        .btn-login:active { transform: translateY(0); }

        /* --- FOOTER LINKS --- */
        .form-footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .form-footer a {
            color: #94a3b8;
            text-decoration: none;
            transition: 0.2s;
        }

        .form-footer a:hover { color: #fff; text-decoration: underline; }

        .back-home {
            display: block;
            text-align: center;
            margin-top: 30px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
        }
        
        .back-home:hover { color: #3b82f6; }

        /* --- DECORATIVE ELEMENTS --- */
        .circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            filter: blur(60px);
            z-index: -1;
            opacity: 0.6;
            animation: float 6s ease-in-out infinite;
        }
        
        .c1 { width: 300px; height: 300px; top: -100px; left: -100px; }
        .c2 { width: 250px; height: 250px; bottom: -50px; right: -50px; animation-delay: -3s; background: linear-gradient(135deg, #06b6d4, #3b82f6); }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(20px); }
        }

    </style>
</head>
<body>

    <div class="circle c1"></div>
    <div class="circle c2"></div>

    <div class="login-wrapper">
        <div class="login-header">
            <i class="fas fa-chalkboard-teacher brand-icon"></i>
            <h2>Teacher Portal</h2>
            <p>Enter your Employee ID to access the dashboard</p>
        </div>

        <form method="post">
            <div class="input-group">
                <input type="text" name="empid" class="form-control" placeholder="Employee ID" required>
                <i class="fas fa-id-badge"></i>
            </div>

            <div class="input-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <i class="fas fa-lock"></i>
            </div>

            <button type="submit" name="login" class="btn-login">
                Secure Login <i class="fas fa-arrow-right" style="margin-left:8px;"></i>
            </button>

            <div class="form-footer">
                <label style="cursor:pointer; color:#94a3b8;">
                    <input type="checkbox"> Remember me
                </label>
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
        </form>

        <a href="../index.php" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>

</body>
</html>