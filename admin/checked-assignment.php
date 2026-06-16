<?php
session_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checked Assignments Report | VIDYAVERSE</title>
    
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
        .report-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .card-title { font-size: 24px; font-weight: 700; color: #fff; margin-bottom: 10px; text-align: center; }
        .card-desc { color: #94a3b8; text-align: center; margin-bottom: 40px; font-size: 14px; }

        /* FORM */
        .form-group label { display: block; color: #cbd5e1; margin-bottom: 8px; font-size: 13px; font-weight: 600; text-transform: uppercase; }
        
        /* --- DROPDOWN FIX --- */
        .form-control {
            width: 100%;
            height: 50px; /* Explicit Height added */
            background-color: #0f172a !important; /* Force Dark BG */
            border: 1px solid #334155;
            color: #ffffff !important; /* Force White Text */
            padding: 0 15px; /* Adjusted padding */
            border-radius: 8px;
            font-size: 15px;
            line-height: 50px; /* Vertical align text */
            transition: 0.2s;
            appearance: none; 
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
            cursor: pointer;
        }
        
        /* Force options to be dark in all browsers */
        .form-control option {
            background-color: #0f172a;
            color: #ffffff;
            padding: 10px;
        }

        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }

        /* BUTTON */
        .btn-generate {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white; border: none; padding: 15px;
            border-radius: 8px; font-weight: 700; font-size: 16px;
            cursor: pointer; transition: 0.2s;
            text-transform: uppercase; letter-spacing: 1px;
            margin-top: 25px;
        }
        .btn-generate:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3); }

    </style>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <i class="ti-check-box"></i> CHECKED ASSIGNMENTS
        </div>
        <a href="dashboard.php" class="btn-back">
            <i class="ti-arrow-left"></i> Dashboard
        </a>
    </div>

    <div class="main-content">
        
        <div class="report-card">
            <h2 class="card-title">Filter by Subject</h2>
            <p class="card-desc">Select a subject to view all graded assignment submissions.</p>

            <form method="post" action="checked-student-assin.php">
                
                <div class="form-group">
                    <label>Select Subject</label>
                    <select class="form-control" name="sid" required>
                        <option value="">Choose Subject...</option>
                        <?php
                        // Check if connection works
                        if(!isset($dbh)) {
                            echo '<option value="">Database Connection Error!</option>';
                        } else {
                            try {
                                $sql = "SELECT SubjectFullname, ID as subid, SubjectCode FROM tblsubject";
                                $query = $dbh->prepare($sql);
                                $query->execute();
                                $results = $query->fetchAll(PDO::FETCH_OBJ);

                                if($query->rowCount() > 0) {
                                    foreach($results as $row) { 
                                    ?>
                                    <option value="<?php echo htmlentities($row->subid);?>">
                                        <?php echo htmlentities($row->SubjectFullname);?> (<?php echo htmlentities($row->SubjectCode);?>)
                                    </option>
                                    <?php 
                                    }
                                } else {
                                    // IF NO SUBJECTS FOUND
                                    echo '<option value="">No Subjects Found in Database</option>';
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error Loading Subjects</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" name="submit" class="btn-generate">View Report <i class="ti-arrow-right"></i></button>

            </form>
        </div>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>

</body>
</html>