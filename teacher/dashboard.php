<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['ocastid'])) {
    header('location:logout.php');
    exit;
}

// --- FETCH TEACHER NAME, QR ID & STATS ---
$teacherName = "Faculty";
$qrCodeID = "";
$count_assignments = 0;
$count_subjects = 0;

try {
    $tid = $_SESSION['ocastid'];
    
    // Get Name & QR Identifier
    $sql = "SELECT FirstName, LastName, qr_code_identifier FROM tblteacher WHERE ID=:tid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':tid', $tid);
    $query->execute();
    $res = $query->fetch(PDO::FETCH_OBJ);
    if($res) {
        $teacherName = $res->FirstName . " " . $res->LastName;
        $qrCodeID = $res->qr_code_identifier;
    }

    // Get Assignment Count
    $sqlAss = "SELECT count(*) FROM tblassigment WHERE Tid=:tid";
    $qAss = $dbh->prepare($sqlAss);
    $qAss->bindParam(':tid', $tid);
    $qAss->execute();
    $count_assignments = $qAss->fetchColumn();

    // Get Assigned Subjects Count
    $sqlSub = "SELECT count(*) FROM tblteacher_subjects WHERE TeacherID=:tid";
    $qSub = $dbh->prepare($sqlSub);
    $qSub->bindParam(':tid', $tid);
    $qSub->execute();
    $count_subjects = $qSub->fetchColumn();

} catch (Exception $e) {}

// Dynamic Greeting based on time
date_default_timezone_set('Asia/Kolkata');
$hour = date('H');
if ($hour < 12) { $greeting = "Good Morning"; } 
elseif ($hour < 17) { $greeting = "Good Afternoon"; } 
else { $greeting = "Good Evening"; }

$pageTitle = "Faculty Workspace";
$pageSubTitle = "Academic Control Center";
include('includes/header.php');
?>

<div class="container-fluid content-wrapper">
    
    <!-- FIX: Added FontAwesome directly to ensure icons render -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Load QR Code Generator Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- Academic Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">

    <style>
        /* --- DARK MODE ACADEMIC THEME VARIABLES --- */
        :root {
            --bg-dark: #020617; /* Deep Slate/Black */
            --text-main: #f8fafc;
            --text-muted: #cbd5e1;
            --faculty-gold: #fbbf24;
            --border-dark: #1e293b;
        }

        body { 
            background-color: var(--bg-dark);
            background-image: radial-gradient(circle at 50% 0%, #0f172a 0%, #020617 100%);
            background-attachment: fixed;
            font-family: 'Inter', sans-serif; 
            color: var(--text-main);
        }

        /* --- PRESTIGE BANNER --- */
        .faculty-banner {
            background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
            border: 1px solid var(--border-dark);
            border-left: 4px solid var(--faculty-gold);
            border-radius: 16px;
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 20px 0 40px 0;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        .faculty-banner::after {
            content: ''; position: absolute; right: -50px; top: -50px; height: 250px; width: 250px;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.05) 0%, transparent 70%);
            border-radius: 50%; pointer-events: none;
        }

        .banner-text { position: relative; z-index: 2; }
        .banner-text h1 { 
            margin: 0; font-size: 32px; color: #ffffff; 
            font-family: 'Playfair Display', serif; 
            font-weight: 700; letter-spacing: 0.5px; 
        }
        .banner-text h1 span { color: var(--faculty-gold); font-style: italic; }
        .banner-text p { 
            margin: 10px 0 0; color: #94a3b8; font-size: 14px; 
            text-transform: uppercase; letter-spacing: 1.5px; font-weight: 500; 
        }
        
        .banner-stats { display:flex; gap: 25px; align-items: center; position: relative; z-index: 2; }
        
        .stat-box { 
            text-align: center; background: rgba(0, 0, 0, 0.4); 
            padding: 15px 25px; border-radius: 12px; 
            border: 1px solid rgba(255, 255, 255, 0.05); 
        }
        .stat-box h2 { margin: 0; font-size: 26px; font-weight: 700; color: var(--text-main); }
        .stat-box span { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }

        /* QR Identity Button */
        .btn-identity {
            background: transparent;
            color: var(--faculty-gold); 
            border: 1px solid var(--faculty-gold); 
            padding: 15px 24px; border-radius: 12px; 
            font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; 
            text-transform: uppercase; letter-spacing: 1px;
            display: flex; align-items: center; gap: 10px;
        }
        .btn-identity:hover { 
            background: var(--faculty-gold); color: #000; 
            transform: translateY(-2px); box-shadow: 0 8px 20px rgba(251, 191, 36, 0.2); 
        }

        /* --- SECTION HEADERS --- */
        .academic-section-title {
            font-family: 'Playfair Display', serif;
            font-size: 24px; font-weight: 700; color: var(--text-main);
            margin: 50px 0 25px 0; display: flex; align-items: center; gap: 15px;
            padding-bottom: 15px; border-bottom: 1px solid var(--border-dark);
        }
        .academic-section-title i { color: var(--faculty-gold); font-size: 22px; }

        /* --- COLORED ACADEMIC CARDS --- */
        .card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        
        .academic-card {
            border-radius: 16px; padding: 30px;
            display: flex; flex-direction: column; justify-content: space-between; min-height: 220px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative; overflow: hidden; color: #fff;
        }
        .academic-card:hover { transform: translateY(-8px); }

        /* SPECIFIC CARD COLORS */
        .box-green {
            background: linear-gradient(135deg, rgba(6, 78, 59, 0.85), rgba(2, 44, 34, 0.95));
            border: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .box-green:hover { border-color: #10b981; box-shadow: 0 15px 35px rgba(6, 78, 59, 0.4); }

        .box-blue {
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.85), rgba(23, 37, 84, 0.95));
            border: 1px solid rgba(59, 130, 246, 0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .box-blue:hover { border-color: #3b82f6; box-shadow: 0 15px 35px rgba(30, 58, 138, 0.4); }

        .box-red {
            background: linear-gradient(135deg, rgba(127, 29, 29, 0.85), rgba(69, 10, 10, 0.95));
            border: 1px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .box-red:hover { border-color: #ef4444; box-shadow: 0 15px 35px rgba(127, 29, 29, 0.4); }

        .box-purple {
            background: linear-gradient(135deg, rgba(88, 28, 135, 0.85), rgba(46, 16, 101, 0.95));
            border: 1px solid rgba(139, 92, 246, 0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .box-purple:hover { border-color: #8b5cf6; box-shadow: 0 15px 35px rgba(88, 28, 135, 0.4); }

        /* Card Contents */
        .card-header-top { display: flex; align-items: flex-start; gap: 15px; margin-bottom: 15px; }
        
        .icon-wrapper {
            width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; 
            font-size: 22px; flex-shrink: 0; background: rgba(0, 0, 0, 0.4); color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-title-group h4 { margin: 0 0 8px 0; font-size: 18px; color: #fff; font-weight: 700; letter-spacing: 0.5px; }
        .card-desc { font-size: 13px; color: rgba(255,255,255,0.7); line-height: 1.6; margin: 0; font-weight: 300; }
        
        /* Buttons Inside Cards */
        .card-btn {
            text-decoration: none; padding: 14px; border-radius: 10px; text-align: center;
            font-weight: 600; font-size: 13px; transition: 0.3s; display: block; 
            background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.15);
            text-transform: uppercase; letter-spacing: 1px;
        }
        .card-btn:hover { background: rgba(255, 255, 255, 0.25); border-color: #fff; color: #fff; }

        /* Primary Action Override */
        .btn-primary-action { background: #fff; color: #000; font-weight: 800; border: none; }
        .btn-primary-action:hover { background: #f8fafc; transform: scale(1.02); }

        /* --- QR IDENTITY MODAL --- */
        .qr-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(2, 6, 23, 0.85); backdrop-filter: blur(10px);
            z-index: 9999; display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden; transition: 0.3s;
        }
        .qr-modal-overlay.active { opacity: 1; visibility: visible; }
        .qr-card {
            background: #0f172a; border-radius: 20px; padding: 40px; text-align: center; width: 380px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5); transform: scale(0.95); transition: 0.3s;
            position: relative; border: 1px solid var(--faculty-gold);
        }
        .qr-modal-overlay.active .qr-card { transform: scale(1); }
        .close-qr { position: absolute; top: 20px; right: 20px; font-size: 24px; color: #64748b; cursor: pointer; transition: 0.2s; }
        .close-qr:hover { color: #ef4444; }
        
        .qr-code-box {
            background: #fff; padding: 20px; border-radius: 16px; display: inline-block; margin: 25px 0;
            border: 4px solid var(--border-dark);
        }
    </style>

    <!-- PRESTIGE WELCOME BANNER -->
    <div class="faculty-banner">
        <div class="banner-text">
            <h1><?php echo $greeting; ?>, <span>Prof. <?php echo htmlentities($teacherName); ?></span></h1>
            <p>Faculty Operations & Academic Control Center</p>
        </div>
        <div class="banner-stats">
            <button class="btn-identity" onclick="openQRModal()"><i class="fas fa-qrcode"></i> Faculty ID Node</button>
            <div class="stat-box">
                <h2><?php echo $count_subjects; ?></h2>
                <span>Modules</span>
            </div>
            <div class="stat-box">
                <h2><?php echo $count_assignments; ?></h2>
                <span>Tasks</span>
            </div>
        </div>
    </div>

    <!-- CLASSROOM & ATTENDANCE -->
    <h3 class="academic-section-title"><i class="fas fa-chalkboard-teacher"></i> Classroom Operations</h3>
    <div class="card-grid">
        
        <div class="academic-card box-green" style="border: 1px solid #10b981;">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-expand"></i></div>
                <div class="card-title-group">
                    <h4>Scan Attendance</h4>
                    <p class="card-desc">Launch the terminal to scan student ID QR codes for daily log marking.</p>
                </div>
            </div>
            <a href="scan-student-attendance.php" class="card-btn btn-primary-action">Initialize Scanner</a>
        </div>

        <div class="academic-card box-green">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-satellite-dish"></i></div>
                <div class="card-title-group">
                    <h4>Live Class Console</h4>
                    <p class="card-desc">Monitor anonymous doubts and manage break requests in real-time during lectures.</p>
                </div>
            </div>
            <a href="live-monitor.php" class="card-btn">Launch Console</a>
        </div>

        <div class="academic-card box-green">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="far fa-calendar-alt"></i></div>
                <div class="card-title-group">
                    <h4>My Timetable</h4>
                    <p class="card-desc">View your synchronized weekly academic class matrix and venue assignments.</p>
                </div>
            </div>
            <a href="my-timetable.php" class="card-btn">Check Schedule</a>
        </div>
        
        <div class="academic-card box-green">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-users"></i></div>
                <div class="card-title-group">
                    <h4>Registered Students</h4>
                    <p class="card-desc">Access the database of students officially enrolled in your assigned courses.</p>
                </div>
            </div>
            <a href="reg-coursewiseusers.php" class="card-btn">View Registry</a>
        </div>

        <div class="academic-card box-green">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-bullhorn"></i></div>
                <div class="card-title-group">
                    <h4>Announcements</h4>
                    <p class="card-desc">Broadcast news, syllabus updates, or urgent notices across the student network.</p>
                </div>
            </div>
            <a href="newsorannouncement.php" class="card-btn">Post Broadcast</a>
        </div>
    </div>

    <!-- ASSIGNMENT CONTROL -->
    <h3 class="academic-section-title"><i class="fas fa-tasks"></i> Task & Assignment Matrix</h3>
    <div class="card-grid">
        <div class="academic-card box-blue">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-cloud-upload-alt"></i></div>
                <div class="card-title-group">
                    <h4>Deploy Task</h4>
                    <p class="card-desc">Draft and deploy new homework, projects, or lab reports for students.</p>
                </div>
            </div>
            <a href="add-assignment.php" class="card-btn">Create New</a>
        </div>
        
        <div class="academic-card box-blue">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-clipboard-list"></i></div>
                <div class="card-title-group">
                    <h4>Manage Tasks</h4>
                    <p class="card-desc">Edit, delete, or update parameters for currently active assignments.</p>
                </div>
            </div>
            <a href="manage-assignment.php" class="card-btn">View Database</a>
        </div>
        
        <div class="academic-card box-blue">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-check-double"></i></div>
                <div class="card-title-group">
                    <h4>Check Submissions</h4>
                    <p class="card-desc">Review and grade digital student submissions (Pending & Checked status).</p>
                </div>
            </div>
            <a href="student-uploaded-ass.php" class="card-btn">Open Grading Portal</a>
        </div>
    </div>

    <!-- EXAMINATIONS -->
    <h3 class="academic-section-title"><i class="fas fa-graduation-cap"></i> Examination Core</h3>
    <div class="card-grid">
        <div class="academic-card box-red">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-layer-group"></i></div>
                <div class="card-title-group">
                    <h4>Question Bank</h4>
                    <p class="card-desc">Compile, review, and finalize exam questions (MCQ/Theory) for your modules.</p>
                </div>
            </div>
            <a href="question-maker.php" class="card-btn">Access Bank</a>
        </div>
        
        <div class="academic-card box-red">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-chart-pie"></i></div>
                <div class="card-title-group">
                    <h4>Exam Analytics</h4>
                    <p class="card-desc">View scorecards, pass rates, and detailed student performance metrics.</p>
                </div>
            </div>
            <a href="exam-results.php" class="card-btn">View Analytics</a>
        </div>

        <div class="academic-card box-red" style="border: 1px solid #ef4444;">
            <div class="card-header-top">
                <!-- Red glowing eye/camera for proctoring -->
                <div class="icon-wrapper" style="background:rgba(239, 68, 68, 0.4); color: #fff; box-shadow: 0 0 15px rgba(239, 68, 68, 0.6);"><i class="fas fa-eye"></i></div>
                <div class="card-title-group">
                    <h4>Live Proctor</h4>
                    <p class="card-desc">Monitor ongoing exams, issue formal warnings, or revoke candidate access.</p>
                </div>
            </div>
            <a href="exam-monitor.php" class="card-btn btn-primary-action">Launch Proctor</a>
        </div>

        <div class="academic-card box-red">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-file-signature"></i></div>
                <div class="card-title-group">
                    <h4>Pending Papers</h4>
                    <p class="card-desc">Evaluate and score subjective theory papers submitted by examination candidates.</p>
                </div>
            </div>
            <a href="grading-queue.php" class="card-btn">Grade Papers</a>
        </div>
    </div>

    <!-- PERSONAL -->
    <h3 class="academic-section-title"><i class="fas fa-id-badge"></i> Identity Management</h3>
    <div class="card-grid" style="padding-bottom: 50px;">
        <div class="academic-card box-purple">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-user-tie"></i></div>
                <div class="card-title-group">
                    <h4>My Profile</h4>
                    <p class="card-desc">Update your official contact details, academic credentials, and visual avatar.</p>
                </div>
            </div>
            <a href="profile.php" class="card-btn">Edit Profile</a>
        </div>
        
        <div class="academic-card box-purple">
            <div class="card-header-top">
                <div class="icon-wrapper"><i class="fas fa-shield-alt"></i></div>
                <div class="card-title-group">
                    <h4>Security Settings</h4>
                    <p class="card-desc">Update your cryptographic login credentials and authentication passwords.</p>
                </div>
            </div>
            <a href="change-password.php" class="card-btn">Update Security</a>
        </div>
    </div>

    <!-- MY IDENTITY QR MODAL -->
    <div class="qr-modal-overlay" id="qrModal">
        <div class="qr-card">
            <i class="fas fa-times close-qr" onclick="closeQRModal()"></i>
            <h3 style="font-family:'Playfair Display', serif; margin-bottom: 5px; color:#fff; font-size:22px; font-weight:700;">FACULTY IDENTITY NODE</h3>
            <p style="font-size:12px; color:#94a3b8; margin-bottom: 0; text-transform:uppercase; letter-spacing:1px;">Scan at Central Kiosk Terminal</p>
            
            <div class="qr-code-box" id="qrcode"></div>
            
            <div style="font-family: monospace; font-size:13px; color:var(--faculty-gold); letter-spacing:1px; word-break:break-all; background: rgba(0,0,0,0.5); padding: 10px; border-radius: 8px;">
                <?php echo htmlspecialchars($qrCodeID); ?>
            </div>
        </div>
    </div>

    <script>
        // Modal & QR Logic
        let qrRendered = false;
        const qrString = "<?php echo addslashes($qrCodeID); ?>";

        function openQRModal() {
            document.getElementById('qrModal').classList.add('active');
            
            if(!qrRendered && qrString !== "") {
                new QRCode(document.getElementById("qrcode"), {
                    text: qrString,
                    width: 200,
                    height: 200,
                    colorDark : "#0f172a",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
                qrRendered = true;
            } else if (qrString === "") {
                document.getElementById("qrcode").innerHTML = "<p style='color:#ef4444; font-weight:600; font-size:13px; padding: 20px;'>QR Identifier Missing in Database</p>";
            }
        }

        function closeQRModal() {
            document.getElementById('qrModal').classList.remove('active');
        }

        // Close modal when clicking outside the card
        window.addEventListener('click', function(e) {
            if (e.target == document.getElementById('qrModal')) {
                closeQRModal();
            }
        });
    </script>

</div>

<?php include('includes/footer.php');?>