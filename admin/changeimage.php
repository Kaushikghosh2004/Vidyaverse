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

// 1. CRITICAL FIX: Check if 'editid' exists in URL. If not, redirect back.
if (empty($_GET['editid'])) {
    header('location:manage-teacher.php');
    exit;
}

$eid = intval($_GET['editid']); // Securely store the ID

if(isset($_POST['submit'])) {
    $propic = $_FILES["propic"]["name"];
    
    // Validate File Extension
    $extension = strtolower(pathinfo($propic, PATHINFO_EXTENSION));
    $allowed_extensions = array("jpg", "jpeg", "png", "gif");
    
    if(!in_array($extension, $allowed_extensions)) {
        echo "<script>alert('Invalid format. Only jpg / jpeg/ png /gif format allowed');</script>";
    } else {
        // Rename & Upload
        $newfilename = md5($propic) . time() . "." . $extension;
        move_uploaded_file($_FILES["propic"]["tmp_name"], "images/" . $newfilename);

        // Update Database
        $sql = "UPDATE tblteacher SET ProfilePic=:propic WHERE ID=:eid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':propic', $newfilename, PDO::PARAM_STR);
        $query->bindParam(':eid', $eid, PDO::PARAM_INT);
        $query->execute();

        echo '<script>alert("Profile picture has been updated successfully."); window.location.href="manage-teacher.php";</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Update Profile Picture | VIDYAVERSE</title>
    
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
            max-width: 800px;
            margin-left: auto; margin-right: auto;
            min-height: calc(100vh - 80px);
            display: flex; flex-direction: column; justify-content: center;
        }

        /* CARD */
        .upload-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            text-align: center;
        }

        .card-title { font-size: 24px; font-weight: 700; color: #fff; margin-bottom: 10px; }
        .card-desc { color: #94a3b8; font-size: 14px; margin-bottom: 40px; }

        /* IMAGE PREVIEW */
        .img-preview {
            width: 150px; height: 150px;
            border-radius: 50%; object-fit: cover;
            border: 4px solid #3b82f6;
            margin-bottom: 30px;
            background: #0f172a;
        }

        /* UPLOAD ZONE */
        .file-upload-wrapper {
            position: relative;
            width: 100%;
            height: 150px;
            border: 2px dashed #334155;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #94a3b8;
            transition: 0.2s;
            cursor: pointer;
            background: rgba(15, 23, 42, 0.5);
            margin-bottom: 30px;
        }
        .file-upload-wrapper:hover { border-color: #3b82f6; color: #3b82f6; background: rgba(59, 130, 246, 0.05); }
        
        .file-upload-input {
            position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer;
        }

        /* BUTTONS */
        .btn-update {
            width: 100%;
            background: #10b981; color: white; border: none; padding: 15px;
            border-radius: 8px; font-weight: 700; font-size: 16px;
            cursor: pointer; transition: 0.2s;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-update:hover { background: #059669; }

    </style>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <i class="ti-camera"></i> UPDATE PHOTO
        </div>
        <a href="edit-teacher.php?editid=<?php echo $eid; ?>" class="btn-back">
            <i class="ti-arrow-left"></i> Cancel
        </a>
    </div>

    <div class="main-content">
        
        <div class="upload-card">
            <h2 class="card-title">Profile Picture</h2>
            <p class="card-desc">Update the display photo for this faculty member.</p>

            <?php
            // FIX: Use $eid variable set at top of file
            $sql = "SELECT ProfilePic FROM tblteacher WHERE ID=:eid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':eid', $eid, PDO::PARAM_INT);
            $query->execute();
            $row = $query->fetch(PDO::FETCH_OBJ);
            
            if($row) {
            ?>
            <form method="post" enctype="multipart/form-data">
                
                <img src="images/<?php echo htmlentities($row->ProfilePic); ?>" class="img-preview" alt="Current Photo">

                <div class="file-upload-wrapper">
                    <i class="ti-cloud-up" style="font-size:30px; margin-bottom:10px;"></i>
                    <span>Drag & Drop or Click to Upload New Image</span>
                    <input type="file" name="propic" class="file-upload-input" accept="image/*" required>
                </div>

                <button type="submit" name="submit" class="btn-update">Save Changes</button>
            </form>
            <?php } else { echo "<p class='text-danger'>Teacher not found.</p>"; } ?>
        </div>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>

</body>
</html>