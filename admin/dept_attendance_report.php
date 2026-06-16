<?php
// 1. DATABASE CONNECTION
$con = mysqli_connect("localhost", "root", "", "lexclassroom");
if (mysqli_connect_errno()) { echo "Failed to connect to MySQL: " . mysqli_connect_error(); exit(); }

$today = date("Y-m-d");

// 2. GET COURSES
$course_list_q = mysqli_query($con, "SELECT ID, CourseName, BranchName FROM tblcourse ORDER BY CourseName ASC, BranchName ASC");

// 3. FILTER LOGIC
$selected_cid = isset($_GET['cid']) ? $_GET['cid'] : 'ALL';

// 4. MAIN QUERY
$query = "SELECT u.FullName, u.RollNumber, c.CourseName, c.BranchName, a.FirstSeen, a.Status 
          FROM tbl_live_attendance a 
          JOIN tbluser u ON a.StudentID = u.ID 
          JOIN tblcourse c ON u.Cid = c.ID
          WHERE a.Date = '$today'";

if ($selected_cid != 'ALL') {
    $query .= " AND u.Cid = '$selected_cid'";
}

$query .= " ORDER BY c.CourseName ASC, u.RollNumber ASC";
$result = mysqli_query($con, $query);

// 5. EXPORT EXCEL
if (isset($_GET['export'])) {
    $filename = 'Attendance_Report_'.$today.'.xls';
    header('Content-Type: application/xls');
    header('Content-Disposition: attachment; filename='.$filename);
    echo "Name\tRoll No\tStream\tTime In\tStatus\n";
    while ($row = mysqli_fetch_assoc($result)) {
        $stream = $row['CourseName'] . " - " . $row['BranchName'];
        echo "{$row['FullName']}\t{$row['RollNumber']}\t{$stream}\t{$row['FirstSeen']}\tPresent\n";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Intelligence Report | VidyaVerse</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- SCREEN MODE (Dark Sci-Fi) --- */
        :root {
            --bg-dark: #050505;
            --neon-blue: #00f3ff;
            --neon-green: #0aff0a;
            --glass-bg: rgba(20, 24, 40, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
        }

        body { 
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(0, 243, 255, 0.05) 0%, transparent 40%),
                linear-gradient(rgba(0,0,0,0.9), rgba(0,0,0,0.9)),
                url('https://www.transparenttextures.com/patterns/cubes.png');
            font-family: 'Rajdhani', sans-serif;
            color: #e2e8f0;
            padding-bottom: 50px;
        }

        /* Header */
        .report-header {
            background: rgba(0, 20, 40, 0.8);
            border-bottom: 1px solid var(--neon-blue);
            padding: 20px 40px;
            margin-bottom: 40px;
            display: flex; justify-content: space-between; align-items: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.5);
        }
        .report-header h3 { font-family: 'Orbitron', sans-serif; color: var(--neon-blue); letter-spacing: 2px; margin: 0; }

        /* Filter Card */
        .glass-panel {
            background: var(--glass-bg);
            border: var(--glass-border);
            border-radius: 12px;
            padding: 25px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
            margin-bottom: 30px;
        }

        .form-select {
            background: rgba(0,0,0,0.5); border: 1px solid #444; color: #fff;
        }
        .form-select:focus {
            background: #000; color: #fff; border-color: var(--neon-blue); box-shadow: 0 0 10px rgba(0, 243, 255, 0.2);
        }

        /* Buttons */
        .btn-neon {
            border: 1px solid var(--neon-blue); color: var(--neon-blue); background: rgba(0, 243, 255, 0.1);
            font-family: 'Orbitron', sans-serif; font-size: 12px; padding: 10px 20px; transition: 0.3s;
        }
        .btn-neon:hover { background: var(--neon-blue); color: #000; box-shadow: 0 0 20px var(--neon-blue); }
        
        .btn-neon-green {
            border: 1px solid var(--neon-green); color: var(--neon-green); background: rgba(10, 255, 10, 0.1);
            font-family: 'Orbitron', sans-serif; font-size: 12px; padding: 10px 20px; transition: 0.3s;
        }
        .btn-neon-green:hover { background: var(--neon-green); color: #000; box-shadow: 0 0 20px var(--neon-green); }

        /* The Report Sheet (Screen View) */
        .paper-sheet {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 40px;
            max-width: 1100px; margin: 0 auto;
            border-radius: 4px;
            box-shadow: 0 0 50px rgba(0,0,0,0.5);
        }

        /* Table Styling */
        .table { color: #ccc; border-color: #333; }
        .table thead { background: rgba(0, 243, 255, 0.1); color: var(--neon-blue); font-family: 'Orbitron'; font-size: 14px; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(255,255,255,0.02); }
        .table-hover tbody tr:hover { background-color: rgba(0, 243, 255, 0.05); }
        .badge-stream { background: rgba(255, 255, 255, 0.1); border: 1px solid #555; color: #aaa; }

        /* --- PRINT MODE (Clean White Paper) --- */
        @media print {
            body { background: #fff !important; color: #000 !important; font-family: 'Segoe UI', sans-serif !important; padding: 0; }
            .no-print { display: none !important; }
            .paper-sheet {
                background: #fff !important; color: #000 !important;
                box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important;
            }
            .table { color: #000 !important; border-color: #000 !important; }
            .table thead { background: #eee !important; color: #000 !important; }
            .badge-stream { border: 1px solid #000 !important; color: #000 !important; font-weight: bold; }
            h2, h5 { color: #000 !important; }
            hr { border-color: #000 !important; opacity: 1 !important; }
        }
    </style>
</head>
<body>

    <div class="report-header no-print">
        <div>
            <h3><i class="fas fa-file-contract"></i> DATA INTELLIGENCE</h3>
            <small style="color: #888;">Daily Attendance Analytics</small>
        </div>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm" style="border-radius: 20px;">
            <i class="fas fa-arrow-left"></i> RETURN TO CORE
        </a>
    </div>

    <div class="container">
        
        <div class="glass-panel no-print">
            <form method="get" class="row align-items-end">
                <div class="col-md-6">
                    <label class="mb-2" style="color:var(--neon-blue); font-weight:bold;">FILTER DATA STREAM (COURSE):</label>
                    <select name="cid" class="form-select" onchange="this.form.submit()">
                        <option value="ALL" <?php if($selected_cid=='ALL') echo 'selected'; ?>>-- SHOW ALL STREAMS --</option>
                        <?php 
                        mysqli_data_seek($course_list_q, 0); // Reset pointer
                        while($course = mysqli_fetch_assoc($course_list_q)) {
                            $display_name = $course['CourseName'] . " - " . $course['BranchName'];
                            $sel = ($selected_cid == $course['ID']) ? 'selected' : '';
                            echo "<option value='{$course['ID']}' $sel>$display_name</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" onclick="window.print()" class="btn btn-neon me-2">
                        <i class="fas fa-print"></i> PRINT REPORT
                    </button>
                    <a href="?cid=<?php echo $selected_cid; ?>&export=true" class="btn btn-neon-green">
                        <i class="fas fa-file-csv"></i> EXPORT EXCEL
                    </a>
                </div>
            </form>
        </div>

        <div class="paper-sheet">
            <div class="text-center mb-4">
                <h2 style="font-family: 'Orbitron'; font-weight: 700; letter-spacing: 2px;">VIDYAVERSE INSTITUTE</h2>
                <h5 style="color: #888; text-transform: uppercase;">Daily Attendance Log</h5>
                <hr style="border-color: #555;">
                
                <div class="d-flex justify-content-between" style="font-size: 14px; font-weight: bold;">
                    <span>
                        <?php 
                            if($selected_cid == 'ALL') {
                                echo "STREAM: ALL DEPARTMENTS";
                            } else {
                                $c_q = mysqli_query($con, "SELECT CourseName, BranchName FROM tblcourse WHERE ID='$selected_cid'");
                                $c_row = mysqli_fetch_assoc($c_q);
                                echo "STREAM: " . strtoupper($c_row['CourseName'] . " - " . $c_row['BranchName']);
                            }
                        ?>
                    </span>
                    <span>DATE: <?php echo date("d F Y"); ?></span>
                </div>
            </div>

            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>STUDENT IDENTITY</th>
                        <th>ROLL NO</th>
                        <th>STREAM / BRANCH</th>
                        <th>TIME STAMP</th>
                        <th style="width: 150px;">SIGNATURE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    if(mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_assoc($result)) {
                            $stream_display = $row['CourseName'] . " - " . $row['BranchName'];
                            echo "<tr>";
                            echo "<td class='text-center'>{$i}</td>";
                            echo "<td style='font-weight:600;'>{$row['FullName']}</td>";
                            echo "<td style='font-family: monospace;'>{$row['RollNumber']}</td>";
                            echo "<td><span class='badge badge-stream'>{$stream_display}</span></td>";
                            echo "<td style='color: var(--neon-blue);'>{$row['FirstSeen']}</td>";
                            echo "<td></td>"; 
                            echo "</tr>";
                            $i++;
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center py-4' style='color: #ff4444;'>
                                <i class='fas fa-exclamation-circle'></i> NO ATTENDANCE DATA FOUND FOR THIS DATE/STREAM
                              </td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            
            <div class="mt-5 pt-5 row no-print-break">
                <div class="col-6 text-center">
                    <p>_______________________</p>
                    <p style="font-size:12px; text-transform:uppercase; letter-spacing:1px;">Faculty Signature</p>
                </div>
                <div class="col-6 text-center">
                    <p>_______________________</p>
                    <p style="font-size:12px; text-transform:uppercase; letter-spacing:1px;">HOD Verification</p>
                </div>
            </div>

        </div>
    </div>

</body>
</html>