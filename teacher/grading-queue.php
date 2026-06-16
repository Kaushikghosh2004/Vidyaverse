<?php
session_start();
include('includes/dbconnection.php');

// Security: Ensure it's a Teacher logged in
if (empty($_SESSION['ocastid'])) { header('location:logout.php'); exit; }
$tid = $_SESSION['ocastid'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grading Queue | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    
    <style>
        /* --- GIGANTIC DARK THEME --- */
        :root {
            --bg-dark: #0b1120;       /* Deep Black/Blue */
            --card-bg: #1e293b;       /* Slate Card */
            --text-main: #f8fafc;     /* White Text */
            --text-muted: #94a3b8;    /* Gray Text */
            --accent: #3b82f6;        /* Blue Highlight */
            --purple: #8b5cf6;        /* Purple Highlight */
            --border: #334155;        /* Subtle Borders */
            --hover: #2d3b55;         /* Hover State */
        }

        * { box-sizing: border-box; }
        
        body { 
            background-color: var(--bg-dark); 
            color: var(--text-main); 
            font-family: 'Segoe UI', 'Roboto', sans-serif; 
            margin: 0; padding: 0; 
            min-height: 100vh;
        }

        /* --- HEADER SECTION --- */
        .top-header {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid var(--border);
            padding: 20px 40px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100;
            backdrop-filter: blur(10px);
        }

        .page-title { font-size: 24px; font-weight: 700; letter-spacing: 0.5px; display: flex; align-items: center; gap: 15px; }
        .page-title i { color: var(--accent); font-size: 28px; }

        .btn-back {
            color: var(--text-muted); text-decoration: none; font-size: 14px; 
            border: 1px solid var(--border); padding: 8px 20px; border-radius: 30px;
            transition: 0.3s;
        }
        .btn-back:hover { background: var(--border); color: #fff; }

        /* --- MAIN CONTAINER --- */
        .container {
            max-width: 1600px;
            margin: 40px auto;
            padding: 0 40px;
        }

        .section-label {
            font-size: 18px; color: var(--text-muted); font-weight: 600;
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
            text-transform: uppercase; letter-spacing: 1px;
        }

        /* --- GIGANTIC CARD --- */
        .big-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            overflow: hidden;
            margin-bottom: 60px; /* Space between sections */
        }

        /* --- TABLE STYLING --- */
        table { width: 100%; border-collapse: collapse; }
        thead { background: rgba(0,0,0,0.2); }
        
        th { 
            text-align: left; padding: 25px 30px; 
            color: var(--text-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;
        }

        td { 
            padding: 25px 30px; 
            border-bottom: 1px solid var(--border); 
            color: var(--text-main); font-size: 16px; 
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: var(--hover); transition: 0.2s; }

        /* --- ELEMENTS --- */
        .student-name { font-size: 18px; font-weight: 700; color: #fff; display: block; margin-bottom: 4px; }
        .exam-meta { font-size: 14px; color: var(--text-muted); }

        .badge-pulse {
            background: rgba(245, 158, 11, 0.15); color: #fbbf24;
            padding: 8px 16px; border-radius: 30px; font-size: 12px; font-weight: 700; border: 1px solid rgba(245, 158, 11, 0.3);
            display: inline-flex; align-items: center; gap: 8px;
        }
        .dot { width: 8px; height: 8px; background: #fbbf24; border-radius: 50%; animation: pulse 1.5s infinite; }
        
        /* Different color for Assignment Badge */
        .badge-assign {
            background: rgba(139, 92, 246, 0.15); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.3);
        }
        .dot-assign { background: #c4b5fd; }

        .btn-action {
            background: var(--accent); color: white; text-decoration: none;
            padding: 12px 25px; border-radius: 10px; font-weight: 600; font-size: 14px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); transition: transform 0.2s;
            display: inline-block;
        }
        .btn-action.assign { background: var(--purple); box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3); }
        
        .btn-action:hover { transform: translateY(-2px); opacity: 0.9; }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

        /* --- EMPTY STATE --- */
        .empty-state { padding: 60px; text-align: center; color: var(--text-muted); }
        .empty-icon { font-size: 40px; color: var(--border); margin-bottom: 15px; display: block; }

    </style>
</head>
<body>

    <div class="top-header">
        <div class="page-title">
            <i class="ti-layers-alt"></i> Grading Hub
        </div>
        <a href="dashboard.php" class="btn-back"><i class="ti-arrow-left"></i> Return to Dashboard</a>
    </div>

    <div class="container">
        
        <div class="section-label"><i class="ti-pencil-alt"></i> Pending Exam Papers</div>
        <div class="big-card">
            <table>
                <thead>
                    <tr>
                        <th width="30%">Student Details</th>
                        <th width="25%">Exam Module</th>
                        <th width="20%">Submission Time</th>
                        <th width="15%">Status</th>
                        <th width="10%" style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch EXAM sessions marked as 'Pending Review'
                    $sql = "SELECT s.ID as SessID, s.EndTime, u.FullName, u.RollNumber, e.ExamTitle 
                            FROM tblexam_sessions s
                            JOIN tbluser u ON s.StudentID = u.ID
                            JOIN tblexams e ON s.ExamID = e.ID
                            WHERE s.Status = 'Pending Review'
                            ORDER BY s.EndTime ASC";
                    $query = $dbh->prepare($sql);
                    $query->execute();
                    $results = $query->fetchAll(PDO::FETCH_OBJ);

                    if($query->rowCount() > 0) {
                        foreach($results as $row) {
                            $dateStr = date("M d, Y", strtotime($row->EndTime));
                            $timeStr = date("h:i A", strtotime($row->EndTime));
                            echo "<tr>
                                    <td>
                                        <span class='student-name'>{$row->FullName}</span>
                                        <span class='exam-meta'>ID: {$row->RollNumber}</span>
                                    </td>
                                    <td><span style='color:#e2e8f0; font-weight:500;'>{$row->ExamTitle}</span></td>
                                    <td>
                                        <div style='color:#fff; font-weight:600;'>{$dateStr}</div>
                                        <div class='exam-meta'>{$timeStr}</div>
                                    </td>
                                    <td><span class='badge-pulse'><span class='dot'></span> Needs Review</span></td>
                                    <td style='text-align:right;'>
                                        <a href='grade-paper.php?sid={$row->SessID}' class='btn-action'>
                                            Grade Exam <i class='ti-arrow-right'></i>
                                        </a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'><div class='empty-state'><i class='ti-check-box empty-icon'></i>No pending exams.</div></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="section-label"><i class="ti-clip"></i> Pending Assignments</div>
        <div class="big-card">
            <table>
                <thead>
                    <tr>
                        <th width="30%">Student Details</th>
                        <th width="25%">Assignment Title</th>
                        <th width="20%">Submitted On</th>
                        <th width="15%">Status</th>
                        <th width="10%" style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch ASSIGNMENTS where Marks are NULL or Empty
                    $sql2 = "SELECT a.ID as SubID, a.SubmitDate, u.FullName, u.RollNumber, asm.AssignmenttTitle
                             FROM tbluploadass a
                             JOIN tbluser u ON a.UserID = u.ID
                             JOIN tblassigment asm ON a.AssId = asm.ID
                             WHERE a.Marks IS NULL OR a.Marks = ''
                             ORDER BY a.SubmitDate ASC";
                    $query2 = $dbh->prepare($sql2);
                    $query2->execute();
                    $results2 = $query2->fetchAll(PDO::FETCH_OBJ);

                    if($query2->rowCount() > 0) {
                        foreach($results2 as $row) {
                            $dateStr = date("M d, Y", strtotime($row->SubmitDate));
                            $timeStr = date("h:i A", strtotime($row->SubmitDate));
                            echo "<tr>
                                    <td>
                                        <span class='student-name'>{$row->FullName}</span>
                                        <span class='exam-meta'>ID: {$row->RollNumber}</span>
                                    </td>
                                    <td><span style='color:#c4b5fd; font-weight:500;'>{$row->AssignmenttTitle}</span></td>
                                    <td>
                                        <div style='color:#fff; font-weight:600;'>{$dateStr}</div>
                                        <div class='exam-meta'>{$timeStr}</div>
                                    </td>
                                    <td><span class='badge-pulse badge-assign'><span class='dot dot-assign'></span> New Upload</span></td>
                                    <td style='text-align:right;'>
                                        <a href='grade-assignment.php?id={$row->SubID}' class='btn-action assign'>
                                            Check File <i class='ti-arrow-right'></i>
                                        </a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'><div class='empty-state'><i class='ti-folder empty-icon'></i>No pending assignments.</div></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>