<?php
session_start();
include('dbconnection.php');

$action = $_POST['action'];

// --- 1. ADMIN: BROADCAST NEW SURVEY ---
if ($action == 'broadcast_survey') {
    $tid = $_POST['teacher_id'];
    $cid = $_POST['course_id']; 

    // Close previous for this batch
    $upd = $dbh->prepare("UPDATE tblsurveys SET IsActive=0 WHERE CourseID=:cid");
    $upd->execute([':cid' => $cid]);

    $sql = "INSERT INTO tblsurveys (TeacherID, CourseID, IsActive) VALUES (:tid, :cid, 1)";
    $q = $dbh->prepare($sql);
    $q->execute([':tid' => $tid, ':cid' => $cid]);
    echo $dbh->lastInsertId();
    exit;
}

// --- 2. ADMIN: GET LIVE GRAPH DATA & REVIEWS ---
if ($action == 'get_live_stats') {
    $sid = $_POST['survey_id'];
    
    // A. General Stats
    $q1 = $dbh->prepare("SELECT AVG(Rating) as avg_rating, COUNT(*) as total_votes FROM tblsurvey_responses WHERE SurveyID=:sid");
    $q1->execute([':sid' => $sid]);
    $stats = $q1->fetch(PDO::FETCH_ASSOC);

    // B. Graph Data (Distribution of 1-5 Stars)
    $q2 = $dbh->prepare("SELECT Rating, COUNT(*) as count FROM tblsurvey_responses WHERE SurveyID=:sid GROUP BY Rating");
    $q2->execute([':sid' => $sid]);
    $distRows = $q2->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for Chart.js [1-star count, 2-star count, ..., 5-star count]
    $distribution = [0, 0, 0, 0, 0]; 
    foreach($distRows as $row) {
        $r = intval($row['Rating']);
        if($r >= 1 && $r <= 5) {
            $distribution[$r - 1] = intval($row['count']);
        }
    }

    // C. Written Reviews (Latest 10)
    $q3 = $dbh->prepare("SELECT Feedback, Rating, Timestamp FROM tblsurvey_responses WHERE SurveyID=:sid AND Feedback != '' ORDER BY ID DESC LIMIT 10");
    $q3->execute([':sid' => $sid]);
    $comments = $q3->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'stats' => $stats, 
        'graph_data' => $distribution, 
        'comments' => $comments
    ]);
    exit;
}

// --- 3. STUDENT: CHECK SURVEY ---
if ($action == 'check_survey') {
    $uid = $_SESSION['ocasuid'];
    $u_q = $dbh->prepare("SELECT Cid FROM tbluser WHERE ID=:uid");
    $u_q->execute([':uid' => $uid]);
    $user = $u_q->fetch(PDO::FETCH_OBJ);
    
    if($user) {
        $sql = "SELECT s.ID, t.FirstName, t.LastName FROM tblsurveys s JOIN tblteacher t ON s.TeacherID = t.ID WHERE s.CourseID=:cid AND s.IsActive=1 ORDER BY s.ID DESC LIMIT 1";
        $q = $dbh->prepare($sql);
        $q->execute([':cid' => $user->Cid]);
        $survey = $q->fetch(PDO::FETCH_ASSOC);

        if ($survey) {
            $chk = $dbh->prepare("SELECT ID FROM tblsurvey_responses WHERE SurveyID=:sid AND StudentID=:uid");
            $chk->execute([':sid' => $survey['ID'], ':uid' => $uid]);
            if ($chk->rowCount() == 0) {
                echo json_encode(['status' => 'found', 'data' => $survey]);
                exit;
            }
        }
    }
    echo json_encode(['status' => 'none']);
    exit;
}

// --- 4. STUDENT: SUBMIT (Rating + Review) ---
if ($action == 'submit_feedback') {
    $sid = $_POST['survey_id'];
    $uid = $_SESSION['ocasuid'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment']; // <--- Written review stored here

    $sql = "INSERT INTO tblsurvey_responses (SurveyID, StudentID, Rating, Feedback) VALUES (:sid, :uid, :rat, :msg)";
    $q = $dbh->prepare($sql);
    $q->execute([':sid' => $sid, ':uid' => $uid, ':rat' => $rating, ':msg' => $comment]);
    exit;
}

// --- 5. ADMIN: END SURVEY ---
if ($action == 'end_survey') {
    $sid = $_POST['survey_id'];
    $q = $dbh->prepare("UPDATE tblsurveys SET IsActive=0 WHERE ID=:sid");
    $q->execute([':sid' => $sid]);
    exit;
}
?>