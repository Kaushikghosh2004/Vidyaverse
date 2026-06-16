<?php
session_start();
include('includes/dbconnection.php');
if (empty($_SESSION['admin_id'])) { header('location:logout.php'); exit; }

// --- LOGIC: FETCH AGGREGATED RATINGS ---
$sql = "SELECT 
            t.ID, t.FirstName, t.LastName, t.MobileNumber,
            COUNT(r.ID) as TotalReviews,
            COALESCE(AVG(r.Rating), 0) as AvgScore
        FROM tblteacher t
        LEFT JOIN tblsurveys s ON t.ID = s.TeacherID
        LEFT JOIN tblsurvey_responses r ON s.ID = r.SurveyID
        GROUP BY t.ID
        ORDER BY AvgScore DESC";

$query = $dbh->prepare($sql);
$query->execute();
$teachers = $query->fetchAll(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Analytics | VidyaVerse</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        /* --- SCREEN MODE (Dark Sci-Fi Glass) --- */
        :root {
            --bg-dark: #050505;
            --neon-blue: #00f3ff;
            --neon-green: #0aff0a;
            --neon-orange: #f59e0b;
            --neon-red: #ff003c;
            --glass-bg: rgba(20, 24, 40, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
        }

        body { 
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 80% 20%, rgba(0, 243, 255, 0.05) 0%, transparent 40%),
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

        /* Glass Panel */
        .glass-panel {
            background: var(--glass-bg);
            border: var(--glass-border);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
            overflow: hidden;
        }

        .panel-header {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex; justify-content: space-between; align-items: center;
        }
        .panel-header h4 { margin: 0; font-family: 'Orbitron'; color: #fff; font-size: 18px; }

        /* Table Styling */
        .table { color: #ccc; border-color: #333; margin-bottom: 0; }
        .table thead th { 
            background: rgba(0, 243, 255, 0.1); color: var(--neon-blue); 
            font-family: 'Orbitron'; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 1px solid var(--neon-blue); padding: 15px;
        }
        .table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(255,255,255,0.02); }
        .table-hover tbody tr:hover { background-color: rgba(0, 243, 255, 0.05); }

        /* Custom Elements */
        .avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: var(--neon-blue); margin-right: 15px;
        }

        .rating-track { width: 120px; height: 6px; background: #333; border-radius: 3px; display: inline-block; vertical-align: middle; margin-right: 10px; overflow: hidden; }
        .rating-fill { height: 100%; border-radius: 3px; box-shadow: 0 0 10px currentColor; }

        .score-badge {
            padding: 5px 12px; border-radius: 4px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
            border: 1px solid transparent;
        }
        .score-high { background: rgba(16, 185, 129, 0.1); color: var(--neon-green); border-color: var(--neon-green); }
        .score-mid { background: rgba(245, 158, 11, 0.1); color: var(--neon-orange); border-color: var(--neon-orange); }
        .score-low { background: rgba(239, 68, 68, 0.1); color: var(--neon-red); border-color: var(--neon-red); }
        .score-none { background: rgba(255,255,255,0.05); color: #888; border-color: #444; }

        .btn-view {
            text-decoration: none; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;
            color: var(--neon-blue); border: 1px solid var(--neon-blue); padding: 6px 12px; border-radius: 4px;
            transition: 0.3s;
        }
        .btn-view:hover { background: var(--neon-blue); color: #000; box-shadow: 0 0 15px var(--neon-blue); }

        .btn-print {
            background: rgba(0, 243, 255, 0.1); color: var(--neon-blue); border: 1px solid var(--neon-blue);
            font-family: 'Orbitron'; font-size: 12px; padding: 8px 16px; transition: 0.3s;
        }
        .btn-print:hover { background: var(--neon-blue); color: #000; }

        /* --- PRINT MODE (Clean White Paper) --- */
        @media print {
            body { background: #fff !important; color: #000 !important; font-family: 'Segoe UI', sans-serif !important; padding: 0; }
            .no-print { display: none !important; }
            .glass-panel { box-shadow: none; border: 1px solid #ccc; background: #fff; }
            .panel-header { background: #eee; border-bottom: 1px solid #000; color: #000; }
            .panel-header h4 { color: #000; }
            .table { color: #000 !important; border-color: #000 !important; }
            .table thead th { background: #ddd !important; color: #000 !important; border-bottom: 2px solid #000; }
            .table td { border-bottom: 1px solid #ccc; }
            .score-badge { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; font-weight: bold; }
            .rating-track { background: #ddd !important; }
            .rating-fill { box-shadow: none !important; }
        }
    </style>
</head>
<body>

    <div class="report-header no-print">
        <div>
            <h3><i class='bx bx-analyse'></i> FACULTY ANALYTICS</h3>
            <small style="color: #888;">Performance Metrics & Quality Assurance</small>
        </div>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm" style="border-radius: 20px;">
            <i class='bx bx-arrow-back'></i> RETURN TO CORE
        </a>
    </div>

    <div class="container">
        
        <div class="glass-panel">
            <div class="panel-header">
                <h4>PERFORMANCE MATRIX</h4>
                <button class="btn btn-print no-print" onclick="window.print()">
                    <i class='bx bx-printer'></i> PRINT REPORT
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Faculty Identity</th>
                            <th>Contact Info</th>
                            <th class="text-center">Review Count</th>
                            <th>Avg. Rating Score</th>
                            <th>Performance Status</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($teachers as $t) { 
                            $avg = number_format($t->AvgScore, 1);
                            
                            // Logic for Status based on Score
                            $statusClass = 'score-mid';
                            $statusText = 'MONITOR';
                            $barColor = 'var(--neon-orange)';

                            if($avg >= 4.0) {
                                $statusClass = 'score-high';
                                $statusText = 'EXCELLENT';
                                $barColor = 'var(--neon-green)';
                            } elseif($avg < 2.5 && $t->TotalReviews > 0) {
                                $statusClass = 'score-low';
                                $statusText = 'CRITICAL';
                                $barColor = 'var(--neon-red)';
                            } elseif($t->TotalReviews == 0) {
                                $statusText = 'NO DATA';
                                $barColor = '#555';
                                $statusClass = 'score-none';
                            }

                            $width = ($avg / 5) * 100; 
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar no-print">
                                        <?php echo substr($t->FirstName, 0, 1); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700; font-size:15px;"><?php echo htmlentities($t->FirstName . " " . $t->LastName); ?></div>
                                        <div style="font-size:11px; color:#888; font-family:'Share Tech Mono';">ID: <?php echo htmlentities($t->ID); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-family:'Share Tech Mono';"><?php echo htmlentities($t->MobileNumber); ?></td>
                            <td class="text-center">
                                <span class="badge bg-dark border border-secondary"><?php echo htmlentities($t->TotalReviews); ?></span>
                            </td>
                            <td>
                                <div class="rating-track">
                                    <div class="rating-fill" style="width:<?php echo $width; ?>%; background:<?php echo $barColor; ?>;"></div>
                                </div>
                                <span style="font-weight:bold; font-size:16px;"><?php echo $avg; ?></span> <small style="color:#666;">/ 5.0</small>
                            </td>
                            <td>
                                <span class="score-badge <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="no-print">
                                <a href="teacher-reviews-detail.php?tid=<?php echo $t->ID; ?>" class="btn-view">DETAILS</a>
                            </td>
                        </tr>
                        <?php } ?>

                        <?php if(count($teachers) == 0) { ?>
                            <tr><td colspan="6" class="text-center py-5" style="color: #666;">NO DATA AVAILABLE IN THE MATRIX.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>