<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocasuid'] ?? '') == 0) {
    header('location:logout.php');
    exit();
} 

$student_id = $_SESSION['ocasuid'];
$schedule_data = []; // Array to hold grouped data
$batch_id = null; // Initialize variable

// Get Student's Batch ID
try {
    $stmt = $dbh->prepare("SELECT batch_id FROM tbluser WHERE ID = :student_id");
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($result && isset($result->batch_id)) {
        $batch_id = $result->batch_id;
    }
} catch (Exception $e) {
    // Batch fetch failed
}

if ($batch_id) {
    // Fetch Timetable
    $sql = "SELECT 
                tt.day_of_week, 
                tt.start_time, 
                tt.end_time, 
                s.SubjectFullname, 
                s.SubjectShortname,
                t.FirstName, 
                t.LastName, 
                c.room_name_or_number 
            FROM timetable_schedule tt
            JOIN tblsubject s ON tt.subject_id = s.ID
            JOIN tblteacher t ON tt.teacher_id = t.ID
            JOIN classrooms c ON tt.classroom_id = c.id
            WHERE tt.batch_id = :batch_id 
            ORDER BY FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), tt.start_time";
    
    $query = $dbh->prepare($sql);
    $query->bindParam(':batch_id', $batch_id, PDO::PARAM_INT);
    $query->execute();
    $raw_results = $query->fetchAll(PDO::FETCH_OBJ);

    // Group Data by Day for the new Layout
    foreach($raw_results as $row) {
        $schedule_data[$row->day_of_week][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Timetable | VIDYAVERSE</title>
    
    <link href="../assets/css/lib/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/lib/themify-icons.css" rel="stylesheet">
    <link href="../assets/css/lib/bootstrap.min.css" rel="stylesheet">

    <style>
        /* --- GLOBAL DARK THEME --- */
        * { box-sizing: border-box; }
        body { 
            background-color: #0f172a; 
            font-family: 'Segoe UI', 'Roboto', sans-serif; 
            color: #f8fafc; 
            margin: 0; padding: 0; 
            overflow-x: hidden;
        }

        /* HEADER */
        .simple-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 80px;
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px);
            z-index: 1000; display: flex; align-items: center; justify-content: space-between;
            padding: 0 40px; border-bottom: 1px solid #334155;
        }
        .header-title { font-size: 20px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px; }
        .btn-back {
            background: #334155; color: #fff; padding: 8px 20px; border-radius: 6px;
            text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 8px;
        }
        .btn-back:hover { background: #475569; color: #fff; }

        /* CONTENT LAYOUT */
        .main-content {
            margin-top: 80px;
            padding: 40px;
            max-width: 1200px;
            margin-left: auto; margin-right: auto;
        }

        /* DAY SECTION */
        .day-section {
            margin-bottom: 40px;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .day-header {
            font-size: 24px; font-weight: 800; color: #fff;
            margin-bottom: 20px; border-left: 5px solid #3b82f6;
            padding-left: 15px; display: flex; align-items: center;
        }
        
        /* CLASS CARD GRID */
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        /* CLASS CARD STYLES */
        .class-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            border-color: #3b82f6;
        }

        /* TIME STRIP */
        .time-strip {
            background: #0f172a;
            color: #3b82f6;
            padding: 10px 20px;
            font-weight: 700; font-size: 14px;
            border-bottom: 1px solid #334155;
            display: flex; align-items: center; gap: 8px;
        }

        .card-body { padding: 20px; flex-grow: 1; }
        
        .subject-title { font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 5px; }
        .subject-code { font-size: 13px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px; }

        .details-row {
            margin-top: 15px;
            display: flex; flex-direction: column; gap: 8px;
        }
        .detail-item { font-size: 14px; color: #cbd5e1; display: flex; align-items: center; gap: 10px; }
        .detail-item i { color: #64748b; width: 20px; text-align: center; }

        /* EMPTY STATE */
        .no-schedule {
            text-align: center; padding: 60px;
            background: #1e293b; border-radius: 16px; border: 2px dashed #334155;
            color: #94a3b8;
        }
        .no-schedule i { font-size: 40px; margin-bottom: 20px; display: block; color: #475569; }

    </style>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <i class="ti-calendar"></i> WEEKLY SCHEDULE
        </div>
        <a href="dashboard.php" class="btn-back">
            <i class="ti-arrow-left"></i> Dashboard
        </a>
    </div>

    <div class="main-content">
        
        <?php if (!empty($schedule_data)): ?>
            
            <?php foreach ($schedule_data as $day => $classes): ?>
                
                <div class="day-section">
                    <div class="day-header">
                        <?php echo htmlentities($day); ?>
                        <span style="font-size:14px; font-weight:400; color:#64748b; margin-left:15px;">
                            (<?php echo count($classes); ?> Classes)
                        </span>
                    </div>

                    <div class="classes-grid">
                        <?php foreach ($classes as $class): ?>
                            <?php 
                                $start = date('h:i A', strtotime($class->start_time));
                                $end = date('h:i A', strtotime($class->end_time));
                                $teacher = $class->FirstName . ' ' . $class->LastName;
                            ?>
                            
                            <div class="class-card">
                                <div class="time-strip">
                                    <i class="ti-time"></i> <?php echo $start; ?> - <?php echo $end; ?>
                                </div>
                                <div class="card-body">
                                    <div class="subject-title">
                                        <?php echo htmlentities($class->SubjectFullname); ?>
                                    </div>
                                    <span class="subject-code">
                                        <?php echo htmlentities($class->SubjectShortname ?? 'SUB'); ?>
                                    </span>

                                    <div class="details-row">
                                        <div class="detail-item">
                                            <i class="ti-user"></i> 
                                            <span><?php echo htmlentities($teacher); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="ti-location-pin"></i> 
                                            <span style="color:#10b981; font-weight:600;">
                                                Room: <?php echo htmlentities($class->room_name_or_number); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php endforeach; ?>

        <?php else: ?>
            
            <div class="no-schedule">
                <i class="ti-calendar"></i>
                <h2>No Timetable Found</h2>
                <p>
                    Your batch (ID: <?php echo htmlentities($batch_id ?? 'Not Assigned'); ?>) 
                    does not have a schedule generated yet.
                </p>
                <button onclick="window.history.back()" class="btn-back" style="display:inline-block; margin-top:20px;">Go Back</button>
            </div>

        <?php endif; ?>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>

</body>
</html>