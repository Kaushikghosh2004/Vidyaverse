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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Subject Wise Report | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc;
            /* Header height handled by global CSS */
        }

        /* --- LAYOUT --- */
        .container { 
            display: flex; justify-content: center; 
            padding: 40px 20px;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px; padding: 40px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            width: 100%; max-width: 900px;
        }

        .section-label {
            font-size: 18px; font-weight: 700; color: #fff; 
            text-align: center; margin-bottom: 30px; letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .section-sub {
            text-align: center; font-size: 13px; color: #94a3b8; margin-bottom: 30px;
        }

        /* --- FORM ELEMENTS --- */
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }

        .form-group { margin-bottom: 15px; }
        .form-group label { 
            display: block; font-size: 12px; color: #94a3b8; 
            margin-bottom: 8px; font-weight: 600; text-transform: uppercase;
        }

        .modern-input {
            width: 100%; background: rgba(15, 23, 42, 0.8);
            border: 1px solid #334155; color: #fff;
            padding: 12px; border-radius: 12px; font-size: 14px; transition: 0.3s;
        }
        .modern-input:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        
        /* Dropdown Fix */
        select.modern-input {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat; background-position: right 15px center; background-size: 16px;
            appearance: none; color: #fff !important;
        }
        select.modern-input option { background-color: #1e293b; color: #fff; padding: 10px; }

        .btn-glow {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white; border: none; width: 100%; padding: 14px;
            border-radius: 12px; font-size: 16px; font-weight: 700;
            letter-spacing: 1px; cursor: pointer; text-transform: uppercase;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4); margin-top: 20px;
            transition: 0.3s;
        }
        .btn-glow:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(139, 92, 246, 0.6); }

    </style>
</head>
<body>

    <?php include_once('includes/header.php');?>

    <div class="container">
        <div class="glass-card">
            
            <div class="section-label">Generate Subject-Wise Report</div>
            <div class="section-sub">Select date range and subject to view assignment submission analytics.</div>

            <form method="post" action="subwise-report-assin.php">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="fromdate" class="modern-input" required>
                    </div>

                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="todate" class="modern-input" required>
                    </div>

                    <div class="form-group">
                        <label>Select Subject</label>
                        <select name="sid" class="modern-input" required>
                            <option value="">Choose Subject...</option>
                            <?php
                            $tid = $_SESSION['ocastid'];
                            
                            // Correct Query: Join tblteacher_subjects -> tblsubject
                            // This ensures only subjects assigned to THIS teacher are shown.
                            $sql = "SELECT s.ID, s.SubjectFullname, s.SubjectCode 
                                    FROM tblteacher_subjects ts
                                    JOIN tblsubject s ON ts.SubjectID = s.ID
                                    WHERE ts.TeacherID = :tid";
                            
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':tid', $tid);
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                            if ($query->rowCount() > 0) {
                                foreach ($results as $row) {
                                    echo "<option value='".htmlentities($row->ID)."'>".htmlentities($row->SubjectFullname)." (".htmlentities($row->SubjectCode).")</option>";
                                }
                            } else {
                                echo "<option value='' disabled>No subjects assigned.</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-glow">Generate Report</button>

            </form>
        </div>
    </div>

    <?php include('includes/footer.php');?>

</body>
</html>