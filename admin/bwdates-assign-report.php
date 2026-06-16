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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Report: Assignment Uploads | VIDYAVERSE</title>
    
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
            max-width: 800px; /* Centered narrow layout */
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
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        @media(max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }

        .form-group label { display: block; color: #cbd5e1; margin-bottom: 8px; font-size: 13px; font-weight: 600; text-transform: uppercase; }
        
        .form-control {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            font-size: 15px;
            transition: 0.2s;
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
        }
        .btn-generate:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3); }

    </style>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <i class="ti-bar-chart"></i> REPORT GENERATOR
        </div>
        <a href="dashboard.php" class="btn-back">
            <i class="ti-arrow-left"></i> Dashboard
        </a>
    </div>

    <div class="main-content">
        
        <div class="report-card">
            <h2 class="card-title">Assignment Submission Report</h2>
            <p class="card-desc">Select a date range to view all student assignment uploads within that period.</p>

            <form method="post" action="bwdates-report-assindetails.php">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" class="form-control" name="fromdate" required>
                    </div>
                    
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" class="form-control" name="todate" required>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-generate">Generate Report <i class="ti-arrow-right"></i></button>

            </form>
        </div>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>

</body>
</html>