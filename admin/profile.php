<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// --- UPDATE PROFILE LOGIC ---
if(isset($_POST['submit'])) {
    $adminid = $_SESSION['admin_id'];
    $AName = $_POST['adminname'];
    $mobno = $_POST['mobilenumber'];
    $email = $_POST['email'];

    try {
        $sql = "UPDATE tbladmin SET AdminName=:adminname, MobileNumber=:mobilenumber, Email=:email WHERE ID=:aid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':adminname', $AName, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':mobilenumber', $mobno, PDO::PARAM_STR);
        $query->bindParam(':aid', $adminid, PDO::PARAM_STR);
        $query->execute();

        echo '<script>alert("Profile has been updated successfully.")</script>';
        echo "<script>window.location.href ='profile.php'</script>";
    } catch (Exception $e) {
        echo '<script>alert("Error updating profile: ' . addslashes($e->getMessage()) . '")</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    <title>Admin Profile | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL RESET --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
            min-height: 100vh;
            padding-top: 80px; /* Space for fixed header */
        }

        /* --- GLASS HEADER --- */
        .glass-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 40px; z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        .brand { font-size: 18px; font-weight: 700; color: #fff; letter-spacing: 1px; display: flex; align-items: center; gap: 10px; }
        .brand i { color: #3b82f6; font-size: 20px; } 
        
        .nav-actions { display: flex; gap: 20px; }
        .nav-btn { 
            text-decoration: none; color: #94a3b8; font-size: 14px; font-weight: 600; 
            display: flex; align-items: center; gap: 8px; transition: 0.3s;
        }
        .nav-btn:hover { color: #fff; }
        .logout-btn { color: #ef4444; }
        .logout-btn:hover { color: #fca5a5; }

        /* --- CONTAINER & CARD --- */
        .container { max-width: 700px; margin: 0 auto; padding: 30px; }

        .glass-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px; padding: 40px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            position: relative;
            overflow: hidden;
        }

        /* --- PROFILE AVATAR SECTION --- */
        .profile-header {
            display: flex; flex-direction: column; align-items: center; margin-bottom: 40px;
        }
        .avatar-circle {
            width: 100px; height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: #fff; font-size: 36px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 25px rgba(59, 130, 246, 0.5);
            margin-bottom: 15px; border: 3px solid rgba(255,255,255,0.1);
        }
        .admin-title { font-size: 24px; font-weight: 700; color: #fff; margin: 0; }
        .admin-role { font-size: 12px; text-transform: uppercase; color: #94a3b8; letter-spacing: 2px; margin-top: 5px; }

        /* --- INPUTS --- */
        .input-group { position: relative; margin-bottom: 25px; }
        .input-group i {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: #64748b; font-size: 18px; transition: 0.3s;
        }
        
        .modern-input {
            width: 100%; background: rgba(15, 23, 42, 0.6);
            border: 1px solid #334155; color: #fff;
            padding: 14px 14px 14px 48px; 
            border-radius: 12px; font-size: 14px; transition: 0.3s;
        }
        .modern-input:focus {
            border-color: #3b82f6; box-shadow: 0 0 15px rgba(59, 130, 246, 0.2); outline: none;
        }
        .modern-input:focus + i { color: #3b82f6; }
        .modern-input[readonly] { cursor: not-allowed; opacity: 0.7; border-style: dashed; }

        .modern-label {
            position: absolute; top: -9px; left: 15px;
            background: #1e293b; padding: 0 6px;
            font-size: 11px; color: #94a3b8; border-radius: 4px;
        }

        .btn-update {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white; border: none; width: 100%; padding: 16px;
            border-radius: 12px; font-size: 16px; font-weight: 700;
            letter-spacing: 1px; cursor: pointer; text-transform: uppercase;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
            transition: 0.3s; margin-top: 10px;
        }
        .btn-update:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(59, 130, 246, 0.6); }

    </style>
</head>
<body>

    <header class="glass-header">
        <div class="brand">
            <i class="ti-settings"></i> Admin Settings
        </div>
        <div class="nav-actions">
            <a href="dashboard.php" class="nav-btn"><i class="ti-arrow-left"></i> Dashboard</a>
            <a href="logout.php" class="nav-btn logout-btn"><i class="ti-power-off"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="glass-card">
            
            <?php
            $aid = $_SESSION['admin_id'];
            $sql = "SELECT * from tbladmin where ID=:aid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':aid', $aid, PDO::PARAM_STR);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_OBJ);

            if($query->rowCount() > 0) {
                foreach($results as $row) { 
                    // Handle potential missing columns gracefully
                    $regDate = isset($row->AdminRegdate) ? $row->AdminRegdate : (isset($row->RegDate) ? $row->RegDate : 'N/A');
            ?>

            <div class="profile-header">
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($row->AdminName, 0, 1)); ?>
                </div>
                <h2 class="admin-title"><?php echo htmlentities($row->AdminName);?></h2>
                <span class="admin-role">System Administrator</span>
            </div>

            <form method="post">
                
                <div class="input-group">
                    <input type="text" name="adminname" class="modern-input" value="<?php echo htmlentities($row->AdminName);?>" required>
                    <i class="ti-user"></i>
                    <span class="modern-label">Full Name</span>
                </div>

                <div class="input-group">
                    <input type="text" value="<?php echo htmlentities($row->UserName);?>" class="modern-input" readonly>
                    <i class="ti-id-badge"></i>
                    <span class="modern-label">Username</span>
                </div>

                <div class="input-group">
                    <input type="email" name="email" class="modern-input" value="<?php echo htmlentities($row->Email);?>" required>
                    <i class="ti-email"></i>
                    <span class="modern-label">Email Address</span>
                </div>

                <div class="input-group">
                    <input type="text" name="mobilenumber" class="modern-input" value="<?php echo htmlentities($row->MobileNumber);?>" maxlength="10" required>
                    <i class="ti-mobile"></i>
                    <span class="modern-label">Contact Number</span>
                </div>

                <div class="input-group">
                    <input type="text" value="<?php echo htmlentities($regDate);?>" class="modern-input" readonly>
                    <i class="ti-calendar"></i>
                    <span class="modern-label">Registration Date</span>
                </div>

                <button type="submit" name="submit" class="btn-update">Update Profile</button>

            </form>

            <?php } } ?>
        </div>
    </div>

</body>
</html>