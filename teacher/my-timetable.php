<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocastid'] ?? '') == 0) {
    header('location:logout.php');
    exit();
} 

$teacher_id = $_SESSION['ocastid'];

// Fetch Timetable Data
// Note: Joined with necessary tables to get names instead of IDs
$sql = "SELECT tt.*, s.SubjectFullname, b.batch_name, c.room_name_or_number 
        FROM timetable_schedule tt
        JOIN tblsubject s ON tt.subject_id = s.ID
        JOIN batches b ON tt.batch_id = b.id
        JOIN classrooms c ON tt.classroom_id = c.id
        WHERE tt.teacher_id = :teacher_id 
        ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time";

$query = $dbh->prepare($sql);
$query->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);

// Group results by Day for better display
$schedule_by_day = [];
foreach ($results as $row) {
    $schedule_by_day[$row->day_of_week][] = $row;
}
$days_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Timetable | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc;
            /* Header height handled by global CSS */
        }

        /* --- LAYOUT --- */
        .container { 
            padding: 40px 20px;
            max-width: 1400px; margin: 0 auto;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px; padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 20px;
        }
        .header-title { font-size: 20px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }

        /* TIMETABLE GRID */
        .day-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        /* DAY CARD */
        .day-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px; overflow: hidden;
            transition: 0.3s;
        }
        .day-card:hover { transform: translateY(-5px); border-color: rgba(59, 130, 246, 0.4); }

        .day-header {
            padding: 15px 20px;
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 1px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .day-header i { color: #3b82f6; }

        /* CLASS ITEMS */
        .class-list { padding: 0; margin: 0; list-style: none; }
        .class-item {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex; gap: 15px;
        }
        .class-item:last-child { border-bottom: none; }

        .time-box {
            min-width: 80px; text-align: center;
            display: flex; flex-direction: column; justify-content: center;
            background: rgba(255, 255, 255, 0.03); border-radius: 8px; padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .time-start { font-weight: 700; color: #fff; font-size: 14px; }
        .time-end { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        .class-info { flex: 1; }
        .subject-name { font-weight: 600; color: #e2e8f0; font-size: 15px; margin-bottom: 5px; display: block; }
        .batch-name { 
            font-size: 11px; background: rgba(16, 185, 129, 0.15); color: #34d399; 
            padding: 3px 8px; border-radius: 4px; font-weight: 600;
        }
        .room-info { font-size: 12px; color: #94a3b8; margin-top: 5px; display: flex; align-items: center; gap: 5px; }
        .room-info i { color: #f59e0b; }

        .no-class { padding: 30px; text-align: center; color: #64748b; font-size: 13px; font-style: italic; }

    </style>
</head>
<body>

    <?php include_once('includes/header.php');?>

    <div class="container">
        
        <div class="section-header">
            <div class="header-title">My Weekly Schedule</div>
            <div style="font-size:12px; color:#94a3b8;">
                <i class="ti-calendar"></i> Current Session
            </div>
        </div>

        <div class="day-grid">
            <?php 
            foreach ($days_order as $day) {
                // Skip Sunday or empty days if preferred, but usually good to show all weekdays
                if ($day == 'Sunday') continue;
            ?>
            <div class="day-card">
                <div class="day-header">
                    <span><?php echo $day; ?></span>
                    <?php if (isset($schedule_by_day[$day])) { ?>
                        <span style="font-size:11px; background:rgba(59,130,246,0.2); padding:2px 8px; border-radius:10px;">
                            <?php echo count($schedule_by_day[$day]); ?> Classes
                        </span>
                    <?php } ?>
                </div>
                
                <ul class="class-list">
                    <?php 
                    if (isset($schedule_by_day[$day])) {
                        foreach ($schedule_by_day[$day] as $class) {
                    ?>
                    <li class="class-item">
                        <div class="time-box">
                            <span class="time-start"><?php echo date('h:i A', strtotime($class->start_time)); ?></span>
                            <span class="time-end"><?php echo date('h:i A', strtotime($class->end_time)); ?></span>
                        </div>
                        <div class="class-info">
                            <span class="subject-name"><?php echo htmlentities($class->SubjectFullname); ?></span>
                            <span class="batch-name"><?php echo htmlentities($class->batch_name); ?></span>
                            <div class="room-info">
                                <i class="ti-location-pin"></i> 
                                Room: <?php echo htmlentities($class->room_name_or_number); ?>
                            </div>
                        </div>
                    </li>
                    <?php 
                        }
                    } else { 
                    ?>
                    <li class="no-class">No classes scheduled for this day.</li>
                    <?php } ?>
                </ul>
            </div>
            <?php } ?>
        </div>

    </div>

    <?php include('includes/footer.php');?>

</body>
</html>