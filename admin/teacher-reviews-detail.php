<?php
session_start();
include('includes/dbconnection.php');
if (empty($_SESSION['admin_id'])) { header('location:logout.php'); exit; }

// 1. SAFETY CHECK: Ensure ID exists
if (!isset($_GET['tid']) || empty($_GET['tid'])) {
    echo "<script>alert('Invalid Request'); window.location.href='teacher-performance.php';</script>";
    exit;
}

$tid = $_GET['tid'];

// 2. FETCH TEACHER INFO (With Error Handling)
$tq = $dbh->prepare("SELECT FirstName, LastName FROM tblteacher WHERE ID=:tid");
$tq->execute([':tid' => $tid]);
$teacher = $tq->fetch(PDO::FETCH_OBJ);

// If teacher ID is wrong/deleted
if (!$teacher) {
    echo "<script>alert('Teacher not found in database.'); window.location.href='teacher-performance.php';</script>";
    exit;
}

// 3. FETCH REVIEWS
$sql = "SELECT r.Rating, r.Feedback, r.Timestamp, c.CourseName 
        FROM tblsurvey_responses r
        JOIN tblsurveys s ON r.SurveyID = s.ID
        JOIN tblcourse c ON s.CourseID = c.ID
        WHERE s.TeacherID = :tid
        ORDER BY r.ID DESC";
$q = $dbh->prepare($sql);
$q->execute([':tid' => $tid]);
$reviews = $q->fetchAll(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Detailed Reviews | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Outfit', sans-serif; }
        .container { max-width: 800px; }
        .review-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; border-left: 5px solid #ddd; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .review-card.bad { border-left-color: #ef4444; }
        .review-card.good { border-left-color: #10b981; }
        .review-card.mid { border-left-color: #f59e0b; }
        .star { color: #f59e0b; font-size: 1.2rem; }
    </style>
</head>
<body>
<div class="container mt-5">
    <a href="teacher-performance.php" class="btn btn-light mb-3">&larr; Back to Report</a>
    
    <h3>Feedback for: <strong><?php echo htmlentities($teacher->FirstName . " " . $teacher->LastName); ?></strong></h3>
    <hr>

    <?php 
    if(count($reviews) > 0) {
        foreach($reviews as $r) {
            $class = 'mid';
            if($r->Rating >= 4) $class = 'good';
            if($r->Rating <= 2) $class = 'bad';
            
            // Safe Star Generation
            $rating = intval($r->Rating);
            $stars = str_repeat("★", $rating) . str_repeat("☆", 5 - $rating);
    ?>
        <div class="review-card <?php echo $class; ?>">
            <div class="d-flex justify-content-between">
                <span class="star"><?php echo $stars; ?></span>
                <small class="text-muted"><?php echo htmlentities($r->Timestamp); ?></small>
            </div>
            <div class="text-muted small mb-2 text-uppercase fw-bold"><?php echo htmlentities($r->CourseName); ?></div>
            <p class="mb-0 fs-6">
                <?php echo htmlentities(!empty($r->Feedback) ? $r->Feedback : "No written comment."); ?>
            </p>
        </div>
    <?php 
        } 
    } else {
        echo "<div class='alert alert-secondary text-center mt-5'>No reviews found for this teacher yet.</div>";
    }
    ?>
</div>
</body>
</html>