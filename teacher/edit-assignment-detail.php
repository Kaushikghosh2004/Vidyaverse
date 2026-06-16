<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['ocastid'])) {
    header('location:logout.php');
    exit;
}

$eid = intval($_GET['editid']);

// --- UPDATE LOGIC ---
if(isset($_POST['submit'])) {
    $title = $_POST['asstitle'];
    $desc = $_POST['assdesc'];
    $marks = $_POST['assmarks'];
    $date = $_POST['lsdate'];

    try {
        $sql = "UPDATE tblassigment 
                SET AssignmenttTitle=:title, AssignmentDescription=:desc, AssigmentMarks=:marks, SubmissionDate=:date 
                WHERE ID=:eid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':title', $title);
        $query->bindParam(':desc', $desc);
        $query->bindParam(':marks', $marks);
        $query->bindParam(':date', $date);
        $query->bindParam(':eid', $eid);
        $query->execute();

        echo '<script>alert("Assignment details updated successfully."); window.location.href="manage-assignment.php";</script>';
    } catch (Exception $e) {
        echo '<script>alert("Error updating record.");</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Assignment Details | VidyaVerse</title>
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

        /* --- LAYOUT --- */
        .container { 
            display: flex; justify-content: center; align-items: center;
            min-height: calc(100vh - 140px); 
            padding: 40px 20px;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px; padding: 40px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            width: 100%; max-width: 800px;
        }

        .section-label {
            font-size: 18px; font-weight: 700; color: #fff; 
            text-align: center; margin-bottom: 30px; letter-spacing: 0.5px;
        }

        /* --- FORM ELEMENTS --- */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { 
            display: block; font-size: 13px; color: #94a3b8; 
            margin-bottom: 8px; font-weight: 500; 
        }

        .modern-input {
            width: 100%; background: rgba(15, 23, 42, 0.8);
            border: 1px solid #334155; color: #fff;
            padding: 12px; border-radius: 12px; font-size: 14px; transition: 0.3s;
        }
        .modern-input:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        textarea.modern-input { resize: vertical; min-height: 120px; }

        .btn-update {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white; border: none; width: 100%; padding: 14px;
            border-radius: 12px; font-size: 16px; font-weight: 700;
            letter-spacing: 1px; cursor: pointer; text-transform: uppercase;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4); margin-top: 20px;
            transition: 0.3s;
        }
        .btn-update:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(139, 92, 246, 0.6); }

        .btn-cancel {
            display: block; text-align: center; margin-top: 20px;
            color: #94a3b8; text-decoration: none; font-size: 14px;
        }
        .btn-cancel:hover { color: #fff; }

        /* Highlight for Date Field */
        .date-input { border-color: #f59e0b; background: rgba(245, 158, 11, 0.05); }
    </style>
</head>
<body>

    <?php include_once('includes/header.php');?>

    <div class="container">
        <div class="glass-card">
            
            <?php
            // Fetch Assignment Details
            $sql = "SELECT * FROM tblassigment WHERE ID = :eid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':eid', $eid);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_OBJ);

            if($query->rowCount() > 0) {
                foreach($results as $row) { 
            ?>

            <div class="section-label">Edit Assignment Details</div>

            <form method="post">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Assignment Title</label>
                        <input type="text" name="asstitle" class="modern-input" value="<?php echo htmlentities($row->AssignmenttTitle);?>" required>
                    </div>
                    <div class="form-group">
                        <label>Total Marks</label>
                        <input type="text" name="assmarks" class="modern-input" value="<?php echo htmlentities($row->AssigmentMarks);?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description / Instructions</label>
                    <textarea name="assdesc" class="modern-input" required><?php echo htmlentities($row->AssignmentDescription);?></textarea>
                </div>

                <div class="form-group">
                    <label style="color:#f59e0b;">Submission Deadline (Closing Date)</label>
                    <input type="date" name="lsdate" class="modern-input date-input" value="<?php echo htmlentities($row->SubmissionDate);?>" required>
                </div>

                <button type="submit" name="submit" class="btn-update">Save Changes</button>
                <a href="manage-assignment.php" class="btn-cancel">Cancel</a>

            </form>

            <?php 
                } 
            } else {
                echo '<div style="text-align:center; color:#ef4444;">Assignment not found.</div>';
            } 
            ?>
        </div>
    </div>

    <?php include('includes/footer.php');?>

</body>
</html>