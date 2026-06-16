<?php
session_start();
include('includes/dbconnection.php');

// Security: Ensure User is Logged In
if (empty($_SESSION['ocasuid'])) { header('location:logout.php'); exit; }

$uid = $_SESSION['ocasuid'];
$studentName = "Student";

// Fetch Student Name for personalization
$q = $dbh->prepare("SELECT FullName FROM tbluser WHERE ID=:uid");
$q->execute([':uid' => $uid]);
$res = $q->fetch(PDO::FETCH_OBJ);
if($res) $studentName = $res->FullName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Live Class Cockpit | VidyaVerse</title>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.7.0/vanilla-tilt.min.js"></script>

    <style>
        :root {
            --bg-deep: #050505;
            --neon-blue: #3b82f6;
            --neon-red: #ef4444;
            --neon-green: #10b981;
            --glass: rgba(255, 255, 255, 0.05);
            --border: 1px solid rgba(255, 255, 255, 0.1);
        }

        * { box-sizing: border-box; }

        body {
            background-color: var(--bg-deep);
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(59, 130, 246, 0.2), transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(239, 68, 68, 0.1), transparent 30%);
            color: #fff;
            font-family: 'Outfit', sans-serif;
            margin: 0; padding: 0;
            height: 100vh;
            overflow: hidden;
            display: flex; flex-direction: column;
        }

        /* --- LOADING SCREEN --- */
        #loadingScreen {
            position: fixed; inset: 0; background: #000; z-index: 9999;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .loader-ring {
            width: 80px; height: 80px; border: 4px solid transparent;
            border-top: 4px solid var(--neon-blue); border-right: 4px solid var(--neon-blue);
            border-radius: 50%; animation: spin 1s linear infinite;
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.5);
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* --- TOP HEADER --- */
        .cockpit-header {
            padding: 20px;
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(0,0,0,0.4); border-bottom: var(--border);
            backdrop-filter: blur(10px);
        }
        .live-badge {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 20px;
            background: rgba(16, 185, 129, 0.1); border: 1px solid var(--neon-green);
            color: var(--neon-green); font-weight: 700; font-size: 12px; letter-spacing: 1px;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
        }
        .pulse-dot { width: 8px; height: 8px; background: var(--neon-green); border-radius: 50%; animation: pulse-green 1.5s infinite; }
        @keyframes pulse-green { 0% { opacity: 1; transform: scale(1); } 100% { opacity: 0; transform: scale(2); } }

        /* --- MAIN DASHBOARD --- */
        .control-panel {
            flex: 1; padding: 30px;
            display: flex; flex-direction: column; gap: 25px;
            max-width: 600px; margin: 0 auto; width: 100%;
            justify-content: center;
        }

        /* --- ACTION CARDS --- */
        .action-card {
            background: var(--glass); border: var(--border);
            padding: 30px; border-radius: 24px;
            text-align: left; position: relative; overflow: hidden;
            cursor: pointer; transition: 0.3s;
            display: flex; align-items: center; gap: 20px;
        }
        .action-card:active { transform: scale(0.98); }
        .card-glow {
            position: absolute; width: 150px; height: 150px; background: radial-gradient(circle, rgba(255,255,255,0.1), transparent);
            border-radius: 50%; pointer-events: none; transform: translate(-50%, -50%); opacity: 0; transition: opacity 0.2s;
        }
        
        /* Specific Styles */
        .card-doubt { border-left: 4px solid var(--neon-blue); }
        .card-doubt:hover { box-shadow: 0 0 30px rgba(59, 130, 246, 0.15); background: rgba(59, 130, 246, 0.05); }
        .card-doubt i { font-size: 40px; color: var(--neon-blue); }

        .card-break { border-left: 4px solid var(--neon-red); }
        .card-break:hover { box-shadow: 0 0 30px rgba(239, 68, 68, 0.15); background: rgba(239, 68, 68, 0.05); }
        .card-break i { font-size: 40px; color: var(--neon-red); }

        /* Active Break State */
        .break-active {
            background: rgba(239, 68, 68, 0.2) !important;
            border-color: var(--neon-red) !important;
            animation: urgent-pulse 2s infinite;
        }
        @keyframes urgent-pulse { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); } }

        .h-text { margin: 0; font-size: 22px; font-weight: 700; }
        .sub-text { margin: 5px 0 0; color: #94a3b8; font-size: 14px; }

        /* --- MODAL --- */
        .glass-modal {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);
            z-index: 1000; align-items: center; justify-content: center;
        }
        .modal-box {
            background: #0f172a; border: 1px solid #334155;
            padding: 30px; width: 90%; max-width: 450px; border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        textarea {
            width: 100%; background: #1e293b; border: 1px solid #334155;
            color: #fff; padding: 15px; border-radius: 12px; font-family: inherit; font-size: 16px;
            resize: none; outline: none; transition: 0.3s;
        }
        textarea:focus { border-color: var(--neon-blue); box-shadow: 0 0 15px rgba(59, 130, 246, 0.2); }

        .btn-send {
            width: 100%; padding: 15px; margin-top: 15px; border-radius: 12px; border: none;
            background: linear-gradient(135deg, var(--neon-blue), #2563eb);
            color: white; font-weight: 700; font-size: 16px; cursor: pointer;
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4);
        }

        /* --- TOAST NOTIFICATION --- */
        #toast {
            visibility: hidden; position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
            background: #10b981; color: #fff; padding: 12px 24px; border-radius: 50px;
            font-weight: 600; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3); z-index: 2000;
            display: flex; align-items: center; gap: 10px; opacity: 0; transition: 0.3s;
        }
        #toast.show { visibility: visible; bottom: 50px; opacity: 1; }

    </style>
</head>
<body>

    <div id="loadingScreen">
        <div class="loader-ring"></div>
        <h2 style="margin-top: 20px; font-weight: 300;">Scanning Frequencies...</h2>
        <p style="color: #64748b;">Connecting to Batch Session</p>
        <a href="dashboard.php" style="margin-top: 30px; color: #ef4444; text-decoration: none; border-bottom: 1px dotted #ef4444;">Abort Connection</a>
    </div>

    <div id="mainUI" style="display:none; height: 100%;">
        
        <header class="cockpit-header">
            <a href="dashboard.php" style="color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <i class='bx bx-chevron-left' style="font-size: 24px;"></i> EXIT
            </a>
            <div class="live-badge">
                <div class="pulse-dot"></div> LIVE SESSION
            </div>
        </header>

        <div class="control-panel">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="font-size: 28px; margin: 0; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    Class Interaction
                </h1>
                <p style="color: #64748b; margin-top: 5px;">Session ID: <span id="sessIDDisplay" style="color: var(--neon-blue); font-family: monospace;">---</span></p>
            </div>

            <div class="action-card card-doubt" onclick="openDoubtModal()" data-tilt>
                <i class='bx bx-question-mark'></i>
                <div>
                    <h3 class="h-text">Silent Doubt</h3>
                    <p class="sub-text">Ask anonymously. Only the teacher sees this.</p>
                </div>
                <div class="card-glow"></div>
            </div>

            <div id="breakBtn" class="action-card card-break" onclick="toggleBreak()" data-tilt>
                <i id="breakIcon" class='bx bx-pause-circle'></i>
                <div>
                    <h3 id="breakTitle" class="h-text">Request Break</h3>
                    <p id="breakSub" class="sub-text">Signal fatigue or request a bio-break.</p>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <p style="font-size: 12px; color: #475569;">Logged in as: <strong><?php echo htmlentities($studentName); ?></strong></p>
            </div>
        </div>
    </div>

    <div id="doubtModal" class="glass-modal">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Submit Query</h3>
                <i class='bx bx-x' onclick="$('#doubtModal').fadeOut()" style="font-size: 24px; cursor: pointer; color: #64748b;"></i>
            </div>
            <textarea id="doubtMsg" rows="5" placeholder="Type your doubt here... (Your identity is hidden)"></textarea>
            <button class="btn-send" onclick="sendDoubt()">TRANSMIT <i class='bx bx-send'></i></button>
        </div>
    </div>

    <div id="toast"><i class='bx bx-check-circle'></i> <span>Action Successful</span></div>

    <script>
        // --- 3D TILT EFFECT ---
        VanillaTilt.init(document.querySelectorAll(".action-card"), {
            max: 10, speed: 400, glare: true, "max-glare": 0.2
        });

        // --- GLOBAL VARIABLES ---
        let sessionID = null;
        let myDoubtID = null;
        let doubtPoller = null;

        // --- 1. INITIAL CONNECT ---
        $(document).ready(function() {
            // Check for active session for student's batch
            $.post('../includes/live-updates.php', { action: 'check_student_session' }, function(resp) {
                if(resp.status === 'found') {
                    sessionID = resp.session_id;
                    $('#sessIDDisplay').text('#' + sessionID);
                    setTimeout(() => {
                        $('#loadingScreen').fadeOut(500);
                        $('#mainUI').fadeIn(500).css('display', 'flex');
                    }, 1000); // Artificial delay for "Scanning" effect
                } else {
                    $('#loadingScreen h2').text("No Signal Found");
                    $('#loadingScreen p').text("There is no active class session for your batch right now.");
                    $('.loader-ring').css('border-color', '#334155'); // Grey out loader
                }
            }, 'json').fail(function() {
                alert("Server Connection Failed. Please check internet.");
            });
        });

        // --- 2. DOUBT SYSTEM ---
        function openDoubtModal() {
            $('#doubtMsg').val('');
            $('#doubtModal').css('display', 'flex').hide().fadeIn(200);
            $('#doubtMsg').focus();
        }

        function sendDoubt() {
            let msg = $('#doubtMsg').val();
            if(!msg.trim()) { showToast("Please type something!", "error"); return; }
            
            // UI Feedback
            $('.btn-send').html('<i class="bx bx-loader-alt bx-spin"></i> SENDING...');

            $.post('../includes/live-updates.php', { 
                action: 'post_doubt', 
                session_id: sessionID, 
                msg: msg 
            }, function(resp) {
                $('.btn-send').html('TRANSMIT <i class="bx bx-send"></i>');
                if(resp.status === 'success') {
                    myDoubtID = resp.doubt_id;
                    $('#doubtModal').fadeOut();
                    showToast("Doubt Sent Anonymously!");
                    // Start polling to see if teacher marks it solved
                    if(doubtPoller) clearInterval(doubtPoller);
                    doubtPoller = setInterval(checkDoubtStatus, 3000);
                } else {
                    alert("Error sending doubt.");
                }
            }, 'json');
        }

        function checkDoubtStatus() {
            if(!myDoubtID) return;
            $.post('../includes/live-updates.php', { action: 'check_doubt_status', doubt_id: myDoubtID }, function(resp) {
                if(resp.status === 'solved') {
                    clearInterval(doubtPoller);
                    myDoubtID = null;
                    showToast("Teacher has addressed your doubt!");
                    // Optional: Play a subtle sound
                }
            }, 'json');
        }

        // --- 3. BREAK SYSTEM ---
        function toggleBreak() {
            let btn = $('#breakBtn');
            let isRequesting = !btn.hasClass('break-active'); // Toggle state logic

            if(isRequesting) {
                // Activate
                btn.addClass('break-active');
                $('#breakTitle').text("Requesting...");
                $('#breakSub').text("Waiting for approval...");
                $('#breakIcon').removeClass('bx-pause-circle').addClass('bx-loader-alt bx-spin');
                showToast("Break Request Sent");
            } else {
                // Deactivate
                btn.removeClass('break-active');
                $('#breakTitle').text("Request Break");
                $('#breakSub').text("Signal fatigue or request a bio-break.");
                $('#breakIcon').removeClass('bx-loader-alt bx-spin').addClass('bx-pause-circle');
            }

            // Send to server
            $.post('../includes/live-updates.php', { action: 'toggle_break', session_id: sessionID });
        }

        // --- UTILS ---
        function showToast(msg, type="success") {
            let t = $('#toast');
            let icon = type === "error" ? "<i class='bx bx-error'></i>" : "<i class='bx bx-check-circle'></i>";
            let color = type === "error" ? "#ef4444" : "#10b981";
            
            t.css('background', color).html(icon + " " + msg).addClass('show');
            setTimeout(() => { t.removeClass('show'); }, 3000);
        }

        // Mouse glow effect for cards
        document.querySelectorAll('.action-card').forEach(card => {
            card.onmousemove = e => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty('--x', x + 'px');
                card.style.setProperty('--y', y + 'px');
                
                const glow = card.querySelector('.card-glow');
                if(glow) {
                    glow.style.left = x + 'px';
                    glow.style.top = y + 'px';
                    glow.style.opacity = 1;
                }
            };
            card.onmouseleave = () => {
                const glow = card.querySelector('.card-glow');
                if(glow) glow.style.opacity = 0;
            };
        });
    </script>
</body>
</html>