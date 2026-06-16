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

$tid = $_SESSION['ocastid'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Exam Results | VidyaVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">

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
            padding: 40px 20px;
            max-width: 1400px; margin: 0 auto;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px; padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        .section-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 20px;
        }
        .header-title { font-size: 20px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }

        /* --- DATATABLES CUSTOM STYLING (FIX FOR PAGINATION) --- */
        
        /* Table Structure */
        table.dataTable { 
            width: 100% !important; border-collapse: separate; border-spacing: 0 8px; color: #cbd5e1; 
            background: transparent !important;
        }
        table.dataTable thead th { 
            border-bottom: 1px solid rgba(255,255,255,0.1) !important; color: #94a3b8; 
            font-weight: 600; text-transform: uppercase; font-size: 12px; padding: 15px;
        }
        table.dataTable tbody td { 
            background: rgba(30, 41, 59, 0.6); padding: 15px; border: none; font-size: 14px;
            border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }
        
        /* Rounded Corners for Rows */
        table.dataTable tbody tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        table.dataTable tbody tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
        table.dataTable tbody tr:hover td { background: rgba(59, 130, 246, 0.15) !important; color: #fff; }

        /* Search & Length Inputs */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_processing, 
        .dataTables_wrapper .dataTables_paginate {
            color: #94a3b8 !important; margin-bottom: 20px;
        }
        .dataTables_wrapper .dataTables_filter input {
            background: rgba(15, 23, 42, 0.8); border: 1px solid #334155; color: #fff;
            border-radius: 8px; padding: 6px 12px; outline: none; margin-left: 10px;
        }
        .dataTables_wrapper .dataTables_length select {
            background: rgba(15, 23, 42, 0.8); border: 1px solid #334155; color: #fff;
            border-radius: 6px; padding: 4px; outline: none;
        }

        /* PAGINATION BUTTONS (CUSTOM FIX) */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background: rgba(255, 255, 255, 0.05) !important;
            color: #94a3b8 !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 6px !important;
            margin: 0 3px !important;
            padding: 5px 12px !important;
            font-size: 13px !important;
            cursor: pointer !important;
        }
        
        /* Current Page */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #3b82f6 !important;
            color: #fff !important;
            border: 1px solid #3b82f6 !important;
            font-weight: bold;
        }

        /* Hover State */
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: rgba(59, 130, 246, 0.2) !important;
            color: #fff !important;
            border: 1px solid #3b82f6 !important;
        }

        /* Disabled Previous/Next */
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:active {
            opacity: 0.5;
            cursor: default !important;
            background: transparent !important;
            border-color: transparent !important;
            box-shadow: none;
        }

        /* BADGES */
        .badge-score { font-size: 14px; font-weight: 700; color: #34d399; }
        .badge-status-pass { background: rgba(16, 185, 129, 0.15); color: #34d399; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .badge-status-fail { background: rgba(239, 68, 68, 0.15); color: #f87171; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }

    </style>
</head>
<body>

    <?php include_once('includes/header.php');?>

    <div class="container">
        <div class="glass-card">
            
            <div class="section-header">
                <div class="header-title">Student Exam Results</div>
                <div style="font-size:12px; color:#94a3b8;"><i class="fas fa-chart-bar"></i> Performance Overview</div>
            </div>

            <div class="table-responsive">
                <table id="teacherResultsTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course Info</th>
                            <th>Exam Title</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch results
                        $sql = "SELECT s.Score, s.EndTime, s.Status, 
                                       u.FullName, u.RollNumber, 
                                       e.ExamTitle, e.TotalQuestions, 
                                       c.CourseName, sub.SubjectFullname
                                FROM tblexam_sessions s
                                JOIN tbluser u ON s.StudentID = u.ID
                                JOIN tblexams e ON s.ExamID = e.ID
                                JOIN tblsubject sub ON e.SubjectID = sub.ID
                                JOIN tblcourse c ON e.CourseID = c.ID
                                JOIN tblteacher_subjects ts ON ts.SubjectID = sub.ID
                                WHERE ts.TeacherID = :tid 
                                AND s.Status != 'Ongoing'
                                ORDER BY s.EndTime DESC";
                        
                        $query = $dbh->prepare($sql);
                        $query->execute(['tid' => $tid]);
                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                        if($query->rowCount() > 0) {
                            foreach($results as $row) {
                                $percentage = ($row->TotalQuestions > 0) ? ($row->Score / $row->TotalQuestions) * 100 : 0;
                                $statusBadge = ($row->Status == 'Terminated') 
                                    ? '<span class="badge-status-fail">Disqualified</span>' 
                                    : (($percentage >= 40) ? '<span class="badge-status-pass">Passed</span>' : '<span class="badge-status-fail">Failed</span>');
                        ?>
                        <tr>
                            <td>
                                <span style="font-weight:600; color:#fff;"><?php echo htmlentities($row->FullName); ?></span>
                                <br><span style="font-size:11px; color:#64748b;">Roll: <?php echo htmlentities($row->RollNumber); ?></span>
                            </td>
                            <td>
                                <?php echo htmlentities($row->SubjectFullname); ?>
                                <br><span style="font-size:11px; color:#64748b;"><?php echo htmlentities($row->CourseName); ?></span>
                            </td>
                            <td style="color:#e2e8f0;"><?php echo htmlentities($row->ExamTitle); ?></td>
                            <td>
                                <span class="badge-score"><?php echo htmlentities($row->Score); ?></span> 
                                <span style="color:#64748b; font-size:11px;">/ <?php echo htmlentities($row->TotalQuestions); ?></span>
                            </td>
                            <td><?php echo $statusBadge; ?></td>
                            <td style="color:#f59e0b; font-size:13px;"><?php echo date("d M Y", strtotime($row->EndTime)); ?></td>
                        </tr>
                        <?php 
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <?php include('includes/footer.php');?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#teacherResultsTable').DataTable({
                "order": [[ 5, "desc" ]], // Sort by Date
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search records..."
                },
                "dom": '<"top"f>rt<"bottom"lp><"clear">' // Custom layout
            });
        });
    </script>

</body>
</html>