<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocastid'] ?? '') == 0) {
    header('location:logout.php');
    exit();
} 

$tid = $_SESSION['ocastid'];

// --- 1. UPDATE PROFILE LOGIC (Address Only) ---
if(isset($_POST['submit'])) {
    $address = $_POST['address'];

    // Update ONLY Address in DB
    $sql = "UPDATE tblteacher SET Address=:address WHERE ID=:tid";
    $query = $dbh->prepare($sql);
    $query->execute([
        ':address' => $address, 
        ':tid' => $tid
    ]);

    echo '<script>alert("Address updated successfully."); window.location.href="profile.php";</script>';
}

// --- 2. FETCH TEACHER DATA ---
$sql_qr = "SELECT * FROM tblteacher WHERE ID=:tid";
$query_qr = $dbh->prepare($sql_qr);
$query_qr->bindParam(':tid', $tid, PDO::PARAM_STR);
$query_qr->execute();
$teacher_data = $query_qr->fetch(PDO::FETCH_OBJ);

// --- 3. QR CODE GENERATION (IF MISSING) ---
if (empty($teacher_data->qr_code_identifier)) {
    $new_qr_id = 'TCHR_' . bin2hex(random_bytes(10));
    $update_sql = "UPDATE tblteacher SET qr_code_identifier = ? WHERE ID = ?";
    $dbh->prepare($update_sql)->execute([$new_qr_id, $tid]);
    $teacher_data->qr_code_identifier = $new_qr_id;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc;
        }

        /* --- LAYOUT GRID --- */
        .profile-grid {
            display: grid; grid-template-columns: 2fr 1fr; gap: 30px;
            max-width: 1400px; margin: 40px auto; padding: 0 20px;
        }
        @media(max-width: 992px) { .profile-grid { grid-template-columns: 1fr; } }

        /* GLASS CARD */
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px; padding: 35px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            height: 100%;
        }

        .section-header {
            border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 25px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .header-title { font-size: 18px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }

        /* FORM STYLES */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; color: #94a3b8; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; }

        .modern-input {
            width: 100%; background: rgba(15, 23, 42, 0.8);
            border: 1px solid #334155; color: #fff;
            padding: 12px; border-radius: 12px; font-size: 14px; transition: 0.3s;
        }
        .modern-input:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        
        /* Read-Only Style */
        .readonly-field { 
            cursor: not-allowed; 
            opacity: 0.7; 
            border: 1px dashed #475569; 
            background: rgba(15, 23, 42, 0.4);
            color: #94a3b8;
        }

        /* Editable Address Field Highlight */
        .editable-field {
            border: 1px solid #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        /* QR CODE STYLES */
        .qr-wrapper {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; height: 100%;
        }
        .qr-box {
            background: #fff; padding: 20px; border-radius: 20px;
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.3); margin: 30px 0;
        }
        .teacher-badge {
            background: rgba(16, 185, 129, 0.15); color: #34d399;
            padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold;
            border: 1px solid rgba(16, 185, 129, 0.3); margin-top: 10px; display: inline-block;
        }
        .id-text { font-family: monospace; color: #64748b; font-size: 14px; margin-top: 5px; }

        .btn-update {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white; border: none; width: 100%; padding: 14px;
            border-radius: 12px; font-size: 16px; font-weight: 700;
            letter-spacing: 1px; cursor: pointer; margin-top: 20px;
            transition: 0.3s; text-transform: uppercase;
        }
        .btn-update:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(139, 92, 246, 0.6); }

    </style>
</head>
<body>

    <?php include_once('includes/header.php');?>

    <div class="profile-grid">
        
        <div class="glass-card">
            <div class="section-header">
                <div class="header-title">Profile Details</div>
                <span style="font-size:11px; color:#94a3b8;">(Only Address is Editable)</span>
            </div>

            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" class="modern-input readonly-field" value="<?php echo htmlentities($teacher_data->FirstName);?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" class="modern-input readonly-field" value="<?php echo htmlentities($teacher_data->LastName);?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="modern-input readonly-field" value="<?php echo htmlentities($teacher_data->Email);?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Mobile Number</label>
                        <input type="text" class="modern-input readonly-field" value="<?php echo htmlentities($teacher_data->MobileNumber);?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Gender</label>
                        <input type="text" class="modern-input readonly-field" value="<?php echo htmlentities($teacher_data->Gender);?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="text" class="modern-input readonly-field" value="<?php echo htmlentities($teacher_data->Dob);?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Religion</label>
                        <input type="text" class="modern-input readonly-field" value="<?php echo htmlentities($teacher_data->Religion);?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Joining Date</label>
                        <input type="text" value="<?php echo htmlentities($teacher_data->RegDate);?>" class="modern-input readonly-field" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label style="color: #3b82f6;">Residential Address (Editable)</label>
                    <input type="text" name="address" class="modern-input editable-field" value="<?php echo htmlentities($teacher_data->Address);?>" required>
                </div>

                <button type="submit" name="submit" class="btn-update">Update Address</button>
            </form>
        </div>

        <div class="glass-card" style="display:flex; align-items:center; justify-content:center;">
            <div class="qr-wrapper">
                <div style="font-size:16px; font-weight:700; color:#fff; letter-spacing:1px;">ATTENDANCE ID</div>
                <div style="font-size:12px; color:#94a3b8; margin-top:5px;">Scan to check-in/out</div>

                <div class="qr-box">
                    <div id="qrcode"></div>
                </div>

                <div style="font-size:18px; font-weight:700; color:#fff;">
                    <?php echo htmlentities($teacher_data->FirstName . " " . $teacher_data->LastName); ?>
                </div>
                <div class="teacher-badge">FACULTY MEMBER</div>
                <div class="id-text">ID: <?php echo htmlentities($teacher_data->EmpID); ?></div>
            </div>
        </div>

    </div>

    <?php include('includes/footer.php');?>

    <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $teacher_data->qr_code_identifier; ?>",
            width: 180,
            height: 180,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    </script>

</body>
</html>