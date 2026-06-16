<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. SYNC TIMEZONE
date_default_timezone_set('Asia/Kolkata'); 

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocasuid'] ?? '') == 0) {
    header('location:logout.php');
    exit();
}
$uid = $_SESSION['ocasuid'];

// Validate URL
if(!isset($_GET['exam_id'])){ 
    echo "<script>alert('Invalid Access'); window.location.href='dashboard.php';</script>"; 
    exit; 
}
$exam_id = intval($_GET['exam_id']);

// =================================================================
// 2. STRONG GATEKEEPER: PREVENT RE-ENTRY
// =================================================================
$gateStmt = $dbh->prepare("SELECT ID, Status FROM tblexam_sessions WHERE ExamID=:eid AND StudentID=:sid");
$gateStmt->execute(['eid'=>$exam_id, 'sid'=>$uid]);
$existingSession = $gateStmt->fetch(PDO::FETCH_OBJ);

if($existingSession) {
    // Clean the status string to remove invisible spaces/capitalization issues
    $cleanStatus = strtoupper(trim($existingSession->Status));

    // A. Check for Completion
    if($cleanStatus === 'COMPLETED' || $cleanStatus === 'PENDING REVIEW') {
        echo "<script>
            alert('You have already submitted this exam. Redirecting to results...'); 
            window.location.href='exam-complete.php?sid={$existingSession->ID}';
        </script>";
        exit(); // STOP SCRIPT IMMEDIATELY
    }

    // B. Check for Disqualification
    if($cleanStatus === 'TERMINATED') {
        die("<div style='background:#000; color:red; height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:sans-serif;'>
                <h1 style='font-size:3rem;'>🚫 DISQUALIFIED</h1>
                <h3>Your exam was terminated by the AI Proctor.</h3>
                <a href='logout.php' style='color:white;'>Logout</a>
             </div>");
    }
}
// =================================================================


// 3. GET EXAM INFO
$stmt = $dbh->prepare("SELECT * FROM tblexams WHERE ID=:eid");
$stmt->execute(['eid'=>$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_OBJ);

if(!$exam) { echo "Exam not found."; exit; }

// 4. SESSION HANDLING (Start or Resume)
if(!$existingSession) {
    // Start New Session
    $sqlInsert = "INSERT INTO tblexam_sessions(ExamID, StudentID, Status, StartTime) VALUES(:eid, :sid, 'Ongoing', NOW())";
    $dbh->prepare($sqlInsert)->execute(['eid'=>$exam_id, 'sid'=>$uid]);
    $session_id = $dbh->lastInsertId();
} else {
    // Resume
    $session_id = $existingSession->ID;
}

// 5. TIMER CALCULATION
$examStartTime = strtotime($exam->ExamDate);
$durationSec = $exam->Duration * 60;
$examEndTime = $examStartTime + $durationSec;
$currentTime = time();
$timeRemaining = $examEndTime - $currentTime;

if($timeRemaining <= 0) {
    echo "<script>alert('Exam time is over!'); window.location.href='dashboard.php';</script>";
    exit;
}

// 6. FETCH QUESTIONS
$sqlQ = "SELECT * FROM tblquestions WHERE SubjectID = :subid ORDER BY RAND() LIMIT :limit";
$qStmt = $dbh->prepare($sqlQ);
$qStmt->bindValue(':subid', $exam->SubjectID, PDO::PARAM_INT);
$qStmt->bindValue(':limit', (int)$exam->TotalQuestions, PDO::PARAM_INT);
$qStmt->execute();
$questions = $qStmt->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    <title>Secure Exam | <?php echo htmlentities($exam->ExamTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f8f9fa; user-select: none; padding-bottom: 100px; }
        
        /* Camera Window */
        #proctor-window {
            position: fixed; top: 20px; right: 20px; width: 220px; height: 165px;
            background: #000; border: 3px solid #dc3545; border-radius: 12px;
            z-index: 9999; box-shadow: 0 10px 30px rgba(0,0,0,0.3); overflow: hidden;
        }
        #user-camera { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        .rec-badge {
            position: absolute; top: 10px; left: 10px; background: red; color: white;
            padding: 2px 8px; font-size: 10px; border-radius: 4px; font-weight: bold;
            animation: blink 1s infinite; z-index: 10;
        }
        @keyframes blink { 50% { opacity: 0; } }

        #motion-bar {
            position: absolute; bottom: 0; left: 0; height: 6px; width: 0%; 
            background: #10b981; transition: width 0.1s linear, background 0.2s; z-index: 10;
        }

        #timer-bar {
            position: fixed; top: 0; left: 0; width: 100%; background: #212529; color: #fff;
            padding: 12px; text-align: center; font-size: 20px; font-weight: 700; z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        #warning-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(220, 53, 69, 0.95); z-index: 10000; color: white;
            align-items: center; justify-content: center; text-align: center; flex-direction: column;
        }

        .container { margin-top: 100px; max-width: 800px; }
        .q-card {
            background: #fff; padding: 30px; border-radius: 12px; margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid #0d6efd;
        }
        .form-check { margin-bottom: 10px; padding-left: 30px; }
        .form-check-input { cursor: pointer; transform: scale(1.2); margin-left: -25px; }
        .form-check-label { cursor: pointer; width: 100%; display: block; }
        .form-check:hover { background-color: #f1f3f5; border-radius: 5px; }
    </style>
</head>
<body oncontextmenu="return false;">

    <div id="warning-modal">
        <h1 style="font-size: 80px;">⚠️</h1>
        <h2>VIOLATION DETECTED!</h2>
        <p class="lead">You switched tabs or moved out of frame.<br>This incident has been recorded.</p>
        <button class="btn btn-light btn-lg mt-3" onclick="closeWarning()">Return to Exam</button>
    </div>

    <div id="timer-bar">
        Time Left: <span id="time-display" style="color:#0d6efd;">Loading...</span>
    </div>

    <div id="proctor-window">
        <div class="rec-badge">REC</div>
        <video id="user-camera" autoplay playsinline muted></video>
        <div id="motion-bar"></div> 
    </div>

    <div class="container">
        <h3 class="text-center mb-4 fw-bold"><?php echo htmlentities($exam->ExamTitle); ?></h3>
        
        <form id="examForm" action="submit-exam.php" method="POST">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">

            <?php 
            $i = 1;
            if($questions) {
                foreach($questions as $q) { ?>
                    <div class="q-card">
                        <h5 class="mb-3">Q<?php echo $i; ?>. <?php echo htmlentities($q->QuestionText); ?></h5>
                        <?php if($q->QuestionType == 'MCQ') { ?>
                            <?php foreach(['A','B','C','D'] as $opt) { 
                                $optText = $q->{'Option'.$opt}; ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                           name="ans[<?php echo $q->ID; ?>]" 
                                           value="<?php echo $opt; ?>" 
                                           id="q<?php echo $q->ID.'_'.$opt; ?>">
                                    <label class="form-check-label" for="q<?php echo $q->ID.'_'.$opt; ?>">
                                        <?php echo htmlentities($optText); ?>
                                    </label>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <textarea class="form-control" name="ans[<?php echo $q->ID; ?>]" rows="4"></textarea>
                        <?php } ?>
                    </div>
                <?php $i++; } 
            } else { echo "<div class='alert alert-warning text-center'>No questions found.</div>"; }
            ?>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-success btn-lg fw-bold" onclick="return confirm('Finish Exam?');">
                    Submit Exam
                </button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const SESSION_ID = <?php echo $session_id; ?>;
        let timeLeft = <?php echo $timeRemaining; ?>; 
        
        let tabSwitchCount = 0;
        let movementWarnings = 0;
        let isCameraReady = false;

        // Camera Logic
        const video = document.getElementById('user-camera');
        const motionBar = document.getElementById('motion-bar');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        let lastFrame = null;

        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 } });
                video.srcObject = stream;
                isCameraReady = true;
            } catch (err) { alert("Enable Camera Permissions!"); }
        }
        startCamera();

        // Motion Detection
        setInterval(() => {
            if (!isCameraReady || video.readyState !== 4) return;
            canvas.width = 100; canvas.height = 100;
            ctx.drawImage(video, 0, 0, 100, 100);
            const currentFrame = ctx.getImageData(0, 0, 100, 100);
            let movementScore = 0;
            if (lastFrame) {
                for (let i = 0; i < currentFrame.data.length; i += 16) { 
                    if (Math.abs(currentFrame.data[i] - lastFrame.data[i]) > 30) movementScore++; 
                }
            }
            lastFrame = currentFrame;
            let percentage = Math.min(100, (movementScore / 300) * 100);
            motionBar.style.width = percentage + "%";
            if (movementScore > 400) { 
                motionBar.style.background = "#ef4444"; 
                movementWarnings++; 
            } else { motionBar.style.background = "#10b981"; }
        }, 250);

        // Heartbeat
        function sendHeartbeat() {
            if (!isCameraReady) return;
            const snapCanvas = document.createElement('canvas');
            snapCanvas.width = 320; snapCanvas.height = 240;
            snapCanvas.getContext('2d').drawImage(video, 0, 0, 320, 240);
            
            $.ajax({
                url: "ajax-monitor-update.php",
                type: "POST",
                data: {
                    sid: SESSION_ID,
                    tab_switches: tabSwitchCount,
                    movement_alert: (movementWarnings > 0) ? 1 : 0,
                    image: snapCanvas.toDataURL('image/jpeg', 0.5)
                },
                dataType: "json",
                success: function(resp) {
                    movementWarnings = 0; 
                    if(resp.exam_status === 'Terminated') {
                        alert("EXAM TERMINATED."); window.location.href = "logout.php";
                    }
                }
            });
        }
        setInterval(sendHeartbeat, 5000);

        // Tab Switching
        document.addEventListener("visibilitychange", function() {
            if (document.hidden) { 
                tabSwitchCount++;
                document.getElementById('warning-modal').style.display = 'flex';
                sendHeartbeat();
            }
        });

        function closeWarning() {
            document.getElementById('warning-modal').style.display = 'none';
        }

        // Timer
        const timerDisplay = document.getElementById('time-display');
        const timerInterval = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                document.getElementById('examForm').submit();
            } else {
                let h = Math.floor(timeLeft / 3600);
                let m = Math.floor((timeLeft % 3600) / 60);
                let s = timeLeft % 60;
                timerDisplay.innerText = `${h>0?h+'h ':''}${m<10?'0'+m:m}m ${s<10?'0'+s:s}s`;
                if(timeLeft < 300) timerDisplay.style.color = "#dc3545";
                timeLeft--;
            }
        }, 1000);
    </script>
</body>
</html>