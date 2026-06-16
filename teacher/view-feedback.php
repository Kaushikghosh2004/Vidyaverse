<?php
session_start();
include('includes/dbconnection.php');
if (empty($_SESSION['ocastid'])) { header('location:logout.php'); exit; }

$tid = $_SESSION['ocastid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Performance Feedback | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <style>
        /* --- GIGANTIC DARK THEME --- */
        body { background: #0b1120; color: #fff; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header-title { font-size: 24px; font-weight: bold; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        
        /* METRIC CARDS */
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: #1e293b; padding: 25px; border-radius: 15px; border: 1px solid #334155; display: flex; align-items: center; gap: 20px; }
        .stat-icon { font-size: 40px; color: #3b82f6; }
        .stat-val { font-size: 32px; font-weight: bold; color: #fff; }
        .stat-label { font-size: 14px; color: #94a3b8; text-transform: uppercase; }

        /* FEEDBACK FEED */
        .feedback-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .feedback-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 20px; transition: 0.2s; }
        .feedback-card:hover { transform: translateY(-3px); border-color: #3b82f6; }
        
        .stars { color: #f59e0b; letter-spacing: 2px; font-size: 18px; }
        .comment-text { color: #e2e8f0; font-size: 15px; margin: 15px 0; line-height: 1.5; font-style: italic; }
        .meta-info { font-size: 12px; color: #64748b; display: flex; justify-content: space-between; border-top: 1px solid #334155; padding-top: 10px; }
        
        .empty-state { grid-column: 1 / -1; text-align: center; padding: 50px; color: #64748b; background: #1e293b; border-radius: 15px; border: 1px dashed #334155; }
    </style>
</head>
<body>

    <?php include_once('includes/sidebar.php');?>
    <?php include_once('includes/header.php');?>

    <div class="content-wrap">
        <div class="main">
            <div class="container-fluid">
                
                <div class="header-title"><i class="ti-bar-chart"></i> Student Feedback Report</div>

                <?php
                // 1. CALCULATE AGGREGATE STATS
                $sqlStats = "SELECT 
                                COUNT(r.ID) as TotalReviews, 
                                AVG(r.Rating) as AvgRating 
                             FROM tblsurvey_responses r 
                             JOIN tblsurveys s ON r.SurveyID = s.ID 
                             WHERE s.TeacherID = :tid";
                $qStats = $dbh->prepare($sqlStats);
                $qStats->execute([':tid' => $tid]);
                $stats = $qStats->fetch(PDO::FETCH_ASSOC);
                
                $rating = number_format($stats['AvgRating'], 1);
                $count = $stats['TotalReviews'];
                ?>

                <div class="stats-row">
                    <div class="stat-card">
                        <i class="ti-star stat-icon" style="color:#f59e0b;"></i>
                        <div>
                            <div class="stat-val"><?php echo $rating; ?>/5.0</div>
                            <div class="stat-label">Average Rating</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="ti-comment-alt stat-icon"></i>
                        <div>
                            <div class="stat-val"><?php echo $count; ?></div>
                            <div class="stat-label">Total Responses</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="ti-check-box stat-icon" style="color:#10b981;"></i>
                        <div>
                            <div class="stat-val">Active</div>
                            <div class="stat-label">Current Status</div>
                        </div>
                    </div>
                </div>

                <h4 style="color:#cbd5e1; margin-bottom:20px;">Recent Student Comments</h4>
                <div class="feedback-grid">
                    <?php
                    // 2. FETCH DETAILED COMMENTS
                    $sqlFeed = "SELECT r.Rating, r.Feedback, r.Timestamp, c.CourseName, c.BranchName 
                                FROM tblsurvey_responses r 
                                JOIN tblsurveys s ON r.SurveyID = s.ID 
                                JOIN tblcourse c ON s.CourseID = c.ID
                                WHERE s.TeacherID = :tid 
                                ORDER BY r.ID DESC LIMIT 20";
                    $qFeed = $dbh->prepare($sqlFeed);
                    $qFeed->execute([':tid' => $tid]);
                    $feed = $qFeed->fetchAll(PDO::FETCH_ASSOC);

                    if(count($feed) > 0) {
                        foreach($feed as $row) {
                            $stars = str_repeat("★", $row['Rating']) . str_repeat("☆", 5 - $row['Rating']);
                            $comment = !empty($row['Feedback']) ? htmlentities($row['Feedback']) : "<i>No written feedback provided.</i>";
                    ?>
                        <div class="feedback-card">
                            <div class="stars"><?php echo $stars; ?></div>
                            <div class="comment-text">"<?php echo $comment; ?>"</div>
                            <div class="meta-info">
                                <span><i class="ti-book"></i> <?php echo htmlentities($row['CourseName']); ?></span>
                                <span><?php echo date("M d, Y", strtotime($row['Timestamp'])); ?></span>
                            </div>
                        </div>
                    <?php 
                        }
                    } else {
                        echo '<div class="empty-state"><i class="ti-comments" style="font-size:40px; margin-bottom:10px; display:block;"></i>No feedback received yet.</div>';
                    }
                    ?>
                </div>

            </div>
        </div>
    </div>

    <?php include_once('includes/footer.php');?>
</body>
</html>