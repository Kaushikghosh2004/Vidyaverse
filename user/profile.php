<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocasuid'] ?? '') == 0) {
    header('location:logout.php');
    exit();
}

$uid = $_SESSION['ocasuid'];

// --- 1. HANDLE PROFILE UPDATE ---
if(isset($_POST['submit'])) {
    // Note: Name is NOT updated here as requested (it's read-only now)
    // Only Email can be updated by the user in this logic. 
    // (Mobile/Roll/RegDate are typically read-only too for students)
    
    $email = $_POST['email'];
    
    // Optional: Add phone update if you want to allow it, remove comment below
    // $mobno = $_POST['mobilenumber']; 
    
    $sql = "UPDATE tbluser SET Email=:email WHERE ID=:uid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':uid', $uid, PDO::PARAM_STR);
    $query->execute();

    echo '<script>alert("Profile updated successfully.");</script>';
}

// --- 2. FETCH USER DATA & QR LOGIC ---
$sql_qr = "SELECT * FROM tbluser WHERE ID=:uid";
$query_qr = $dbh->prepare($sql_qr);
$query_qr->bindParam(':uid', $uid, PDO::PARAM_STR);
$query_qr->execute();
$user_data = $query_qr->fetch(PDO::FETCH_OBJ);

// Auto-generate QR Identifier if missing
if (empty($user_data->qr_code_identifier)) {
    $new_qr_id = bin2hex(random_bytes(16));
    $update_sql = "UPDATE tbluser SET qr_code_identifier = ? WHERE ID = ?";
    $dbh->prepare($update_sql)->execute([$new_qr_id, $uid]);
    $user_data->qr_code_identifier = $new_qr_id;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Profile | VIDYAVERSE</title>
    
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

        /* MAIN LAYOUT */
        .main-content {
            margin-top: 80px;
            padding: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr; /* Two columns: Form | QR Card */
            gap: 40px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        @media (max-width: 992px) { .main-content { grid-template-columns: 1fr; } }

        /* CARDS */
        .profile-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card-header-custom {
            margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #334155;
        }
        .card-title { font-size: 22px; font-weight: 700; color: #fff; margin: 0; }
        .card-subtitle { color: #94a3b8; font-size: 14px; margin-top: 5px; }

        /* FORM STYLES */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; color: #94a3b8; font-size: 13px; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; }
        .form-control-custom {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            color: #fff;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 15px;
            transition: 0.2s;
        }
        .form-control-custom:focus { outline: none; border-color: #3b82f6; }
        .form-control-custom:read-only { background: #1e293b; color: #64748b; cursor: not-allowed; border-color: #334155; }

        .btn-save {
            background: #3b82f6; color: white; border: none; padding: 12px 30px;
            border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%;
            transition: 0.2s; margin-top: 10px; font-size: 16px;
        }
        .btn-save:hover { background: #2563eb; }

        /* QR BADGE STYLES */
        .qr-badge-container {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: linear-gradient(145deg, #1e293b, #0f172a);
            border: 2px solid #3b82f6; /* Blue Border */
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        /* "ID Card" Effect */
        .badge-header { 
            background: #3b82f6; color: white; padding: 8px 20px; border-radius: 20px; 
            font-size: 12px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
            margin-bottom: 30px;
        }
        .qr-box {
            background: white; padding: 15px; border-radius: 10px;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
        .student-name-display { font-size: 24px; font-weight: 700; color: #fff; margin-top: 25px; }
        .student-id-display { color: #94a3b8; font-family: monospace; font-size: 16px; margin-top: 5px; }
        
        .pulse-ring {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 300px; height: 300px; border-radius: 50%;
            border: 1px solid rgba(59, 130, 246, 0.1);
            animation: pulse 3s infinite; pointer-events: none;
        }
        @keyframes pulse { 0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0; } 50% { opacity: 1; } 100% { transform: translate(-50%, -50%) scale(1.5); opacity: 0; } }

    </style>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <i class="ti-user"></i> MY PROFILE
        </div>
        <a href="dashboard.php" class="btn-back">
            <i class="ti-arrow-left"></i> Dashboard
        </a>
    </div>

    <div class="main-content">

        <div class="profile-card">
            <div class="card-header-custom">
                <h2 class="card-title">Personal Information</h2>
                <p class="card-subtitle">Manage your account details</p>
            </div>

            <form method="post">
                <div class="form-group">
                    <label class="form-label">Full Name (Locked)</label>
                    <input type="text" class="form-control-custom" name="name" value="<?php echo htmlentities($user_data->FullName);?>" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">Roll Number (ID)</label>
                    <input type="text" class="form-control-custom" value="<?php echo htmlentities($user_data->RollNumber);?>" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control-custom" name="email" value="<?php echo htmlentities($user_data->Email);?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mobile Number (Locked)</label>
                    <input type="text" class="form-control-custom" name="mobilenumber" value="<?php echo htmlentities($user_data->MobileNumber);?>" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">Registration Date</label>
                    <input type="text" class="form-control-custom" value="<?php echo htmlentities($user_data->RegDate);?>" readonly>
                </div>

                <button type="submit" name="submit" class="btn-save">Update Profile</button>
            </form>
        </div>

        <div class="profile-card qr-badge-container">
            <div class="pulse-ring"></div>
            
            <div class="badge-header">STUDENT DIGITAL ID</div>
            
            <div class="qr-box">
                <div id="qrcode"></div>
            </div>

            <h3 class="student-name-display"><?php echo htmlentities($user_data->FullName); ?></h3>
            <p class="student-id-display">ID: <?php echo htmlentities($user_data->RollNumber); ?></p>
            
            <p style="color:#64748b; font-size:12px; margin-top:20px; max-width:250px;">
                Scan this code at the classroom entrance to mark your daily attendance automatically.
            </p>
        </div>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>

    <script>
        // Generate QR Code
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $user_data->qr_code_identifier; ?>",
            width: 180,
            height: 180,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    </script>

</body>
</html>