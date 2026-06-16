<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
} else {
    
    // --- UPDATE LOGIC ---
    if(isset($_POST['submit'])) {
        $admin_id = $_SESSION['admin_id'];
        $bname = $_POST['branchname'];
        $cname = $_POST['coursename'];
        $eid = $_GET['editid'];

        $sql = "UPDATE tblcourse SET BranchName=:branchname, CourseName=:coursename WHERE ID=:eid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':branchname', $bname, PDO::PARAM_STR);
        $query->bindParam(':coursename', $cname, PDO::PARAM_STR);
        $query->bindParam(':eid', $eid, PDO::PARAM_STR);

        $query->execute();
        echo '<script>alert("Course has been updated successfully")</script>';
        echo "<script>window.location.href ='course.php'</script>";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Update Course | VIDYAVERSE</title>
    
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

        /* CONTENT LAYOUT */
        .main-content {
            margin-top: 80px;
            padding: 40px;
            min-height: calc(100vh - 80px);
            display: flex;
            justify-content: center;
        }

        /* CARD */
        .custom-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px; /* Centered card width */
        }
        .card-header-title {
            font-size: 18px; font-weight: 700; color: #fff;
            margin-bottom: 25px; padding-bottom: 15px;
            border-bottom: 1px solid #334155;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        /* FORMS */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #94a3b8; margin-bottom: 8px; font-size: 13px; font-weight: 600; }
        .form-control {
            background-color: #0f172a; border: 1px solid #334155; color: #fff;
            padding: 12px; border-radius: 8px; height: auto; width: 100%;
        }
        .form-control:focus { background-color: #0f172a; border-color: #3b82f6; box-shadow: none; color: #fff;}
        
        .btn-submit {
            background: #f59e0b; color: #0f172a; border: none; padding: 12px 20px;
            border-radius: 6px; font-weight: 700; cursor: pointer; transition: 0.2s;
            width: 100%; margin-top: 10px;
        }
        .btn-submit:hover { background: #d97706; }

    </style>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <i class="ti-pencil-alt"></i> UPDATE COURSE
        </div>
        <a href="course.php" class="btn-back">
            <i class="ti-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="main-content">
        
        <div class="custom-card">
            <div class="card-header-title">Edit Course Details</div>
            
            <form method="post">
                <?php
                $eid = $_GET['editid'];
                // Use Prepared Statement for Fetching Data (More Secure)
                $sql = "SELECT * from tblcourse where ID=:eid";
                $query = $dbh->prepare($sql);
                $query->bindParam(':eid', $eid, PDO::PARAM_STR);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);

                if($query->rowCount() > 0) {
                    foreach($results as $row) { ?>
                        
                        <div class="form-group">
                            <label>Course Name</label>
                            <input type="text" class="form-control" name="coursename" value="<?php echo htmlentities($row->CourseName);?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Branch Name</label>
                            <input type="text" class="form-control" name="branchname" value="<?php echo htmlentities($row->BranchName);?>" required>
                        </div>

                    <?php } 
                } else {
                    echo "<p style='color: #f87171; text-align: center;'>Invalid Course ID</p>";
                } ?>
                
                <button type="submit" name="submit" class="btn-submit">
                    <i class="ti-check"></i> Update Course
                </button>
            </form>
        </div>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>

</body>
</html>
<?php 
}
include('includes/footer.php'); 
?>