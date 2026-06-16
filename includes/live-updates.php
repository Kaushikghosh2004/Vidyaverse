<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

include('dbconnection.php');

if (!isset($_POST['action'])) { echo json_encode(['status' => 'error', 'message' => 'No action']); exit; }

$action = $_POST['action'];

try {
    // --- 1. END SESSION (CRITICAL FIX) ---
    if ($action == 'end_session') {
        $tid = $_SESSION['ocastid'];
        // Force end ALL active sessions for this teacher
        $upd = $dbh->prepare("UPDATE tbllivesession SET Status='Ended' WHERE TeacherID=:tid");
        $upd->execute([':tid' => $tid]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // --- 2. START SESSION ---
    if ($action == 'start_session') {
        $tid = $_SESSION['ocastid'];
        $cid = $_POST['course_id']; 

        // Close old sessions first
        $upd = $dbh->prepare("UPDATE tbllivesession SET Status='Ended' WHERE TeacherID=:tid");
        $upd->execute([':tid' => $tid]);

        // Start new session
        $sql = "INSERT INTO tbllivesession (TeacherID, CourseID, Status) VALUES (:tid, :cid, 'Active')";
        $q = $dbh->prepare($sql);
        $q->execute([':tid' => $tid, ':cid' => $cid]);
        
        echo json_encode(['status' => 'success', 'session_id' => $dbh->lastInsertId()]);
        exit;
    }

    // --- 3. CHECK ACTIVE SESSION ---
    if ($action == 'check_active_session') {
        $tid = $_SESSION['ocastid'];
        $q = $dbh->prepare("SELECT ID, CourseID FROM tbllivesession WHERE TeacherID=:tid AND Status='Active' ORDER BY ID DESC LIMIT 1");
        $q->execute([':tid' => $tid]);
        $sess = $q->fetch(PDO::FETCH_ASSOC);
        
        if ($sess) {
            echo json_encode([
                'status' => 'active', 
                'session_id' => $sess['ID'],
                'course_id' => $sess['CourseID']
            ]);
        } else {
            echo json_encode(['status' => 'none']);
        }
        exit;
    }

    // --- 4. FETCH MONITOR DATA ---
    if ($action == 'fetch_teacher_data') {
        $sessID = $_POST['session_id'];
        
        $q1 = $dbh->prepare("SELECT COUNT(*) FROM tblliveinteraction WHERE SessionID=:sid AND Type='Break' AND IsActive=1");
        $q1->execute([':sid' => $sessID]);
        $breakCount = $q1->fetchColumn();

        $q2 = $dbh->prepare("SELECT ID, Message, Timestamp FROM tblliveinteraction WHERE SessionID=:sid AND Type='Doubt' AND IsActive=1 ORDER BY ID DESC");
        $q2->execute([':sid' => $sessID]);
        $doubts = $q2->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'breaks' => $breakCount, 'doubts' => $doubts]);
        exit;
    }

    // --- 5. STUDENT ACTIONS ---
    if ($action == 'check_student_session') {
        $uid = $_SESSION['ocasuid'];
        $uq = $dbh->prepare("SELECT Cid FROM tbluser WHERE ID=:uid");
        $uq->execute([':uid' => $uid]);
        $user = $uq->fetch(PDO::FETCH_ASSOC);
        
        if($user) {
            $cid = $user['Cid'];
            $q = $dbh->prepare("SELECT ID, StartTime FROM tbllivesession WHERE (CourseID=:cid OR CourseID=0) AND Status='Active' ORDER BY ID DESC LIMIT 1");
            $q->execute([':cid' => $cid]);
            $sess = $q->fetch(PDO::FETCH_ASSOC);

            if($sess) {
                echo json_encode(['status' => 'found', 'session_id' => $sess['ID']]);
            } else {
                echo json_encode(['status' => 'none']);
            }
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }

    if ($action == 'post_doubt') {
        $sid = $_POST['session_id'];
        $uid = $_SESSION['ocasuid'];
        $msg = $_POST['msg'];
        $q = $dbh->prepare("INSERT INTO tblliveinteraction (SessionID, StudentID, Type, Message, IsActive) VALUES (:sid, :uid, 'Doubt', :msg, 1)");
        $q->execute([':sid' => $sid, ':uid' => $uid, ':msg' => $msg]);
        echo json_encode(['status' => 'success', 'doubt_id' => $dbh->lastInsertId()]);
        exit;
    }

    if ($action == 'solve_doubt') {
        $did = $_POST['doubt_id'];
        $q = $dbh->prepare("UPDATE tblliveinteraction SET IsActive=0 WHERE ID=:did");
        $q->execute([':did' => $did]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action == 'check_doubt_status') {
        $did = $_POST['doubt_id'];
        $q = $dbh->prepare("SELECT IsActive FROM tblliveinteraction WHERE ID=:did");
        $q->execute([':did' => $did]);
        $st = $q->fetchColumn();
        echo json_encode(['status' => ($st == 0 ? 'solved' : 'pending')]);
        exit;
    }

    if ($action == 'toggle_break') {
        $sid = $_POST['session_id'];
        $uid = $_SESSION['ocasuid'];
        $chk = $dbh->prepare("SELECT ID, IsActive FROM tblliveinteraction WHERE SessionID=:sid AND StudentID=:uid AND Type='Break'");
        $chk->execute([':sid' => $sid, ':uid' => $uid]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $newSt = ($row['IsActive'] == 1) ? 0 : 1;
            $u = $dbh->prepare("UPDATE tblliveinteraction SET IsActive=:st WHERE ID=:id");
            $u->execute([':st' => $newSt, ':id' => $row['ID']]);
        } else {
            $i = $dbh->prepare("INSERT INTO tblliveinteraction (SessionID, StudentID, Type, IsActive) VALUES (:sid, :uid, 'Break', 1)");
            $i->execute([':sid' => $sid, ':uid' => $uid]);
        }
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action == 'clear_breaks') {
        $sid = $_POST['session_id'];
        $q = $dbh->prepare("UPDATE tblliveinteraction SET IsActive=0 WHERE SessionID=:sid AND Type='Break'");
        $q->execute([':sid' => $sid]);
        echo json_encode(['status' => 'success']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>