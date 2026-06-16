<?php
session_start();
error_reporting(0);
date_default_timezone_set('Asia/Kolkata');

$con = mysqli_connect("localhost", "root", "", "lexclassroom");
if (mysqli_connect_errno()) { echo "System Failure: Database Disconnected."; exit(); }

$current_time = date("H:i:s");
$current_date = date("Y-m-d");

// Check Active Slot
$slot_query = mysqli_query($con, "SELECT * FROM tbl_timetable_slots WHERE '$current_time' BETWEEN StartTime AND EndTime LIMIT 1");
$active_slot = mysqli_fetch_array($slot_query);
$slot_id = $active_slot['ID'] ?? 0;
$subject_name = $active_slot['SubjectName'] ?? "NO ACTIVE SESSION";
$is_class_active = ($active_slot && $active_slot['SlotType'] == 'Class');

// Calculate Stats
$stats_query = mysqli_query($con, "
    SELECT 
        COUNT(u.ID) as Total,
        SUM(CASE WHEN a.Status = 'Present' THEN 1 ELSE 0 END) as Present
    FROM tbluser u
    LEFT JOIN tbl_live_attendance a 
    ON u.ID = a.StudentID AND a.Date = '$current_date' AND a.SlotID = '$slot_id'
");
$stats = mysqli_fetch_array($stats_query);
$total_students = $stats['Total'];
$present_count = $stats['Present'];

// --- NEW CALCULATION FOR ABSENT COUNT ---
$absent_count = $total_students - $present_count;

$attendance_percent = ($total_students > 0) ? round(($present_count / $total_students) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VidyaVerse | Overwatch Monitor</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-dark: #0f172a;
            --panel-bg: rgba(30, 41, 59, 0.6);
            --neon-cyan: #06b6d4;
            --neon-green: #10b981;
            --neon-red: #ef4444;
            --neon-orange: #f59e0b;
            --text-main: #f1f5f9;
        }

        body {
            background-color: var(--bg-dark);
            background-image: 
                linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)),
                url('https://www.transparenttextures.com/patterns/cubes.png');
            font-family: 'Roboto', sans-serif;
            color: var(--text-main);
            margin: 0; padding: 0;
            overflow-x: hidden;
        }

        /* HEADER */
        .command-header {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid rgba(6, 182, 212, 0.3);
            padding: 15px 30px;
            display: flex; justify-content: space-between; align-items: center;
            backdrop-filter: blur(10px);
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        .brand { font-family: 'Orbitron', sans-serif; font-size: 24px; color: var(--neon-cyan); letter-spacing: 2px; }
        .system-time { font-family: 'Courier New', monospace; font-weight: bold; color: var(--neon-orange); }

        /* LAYOUT */
        .dashboard-grid {
            display: grid; grid-template-columns: 300px 1fr; gap: 20px; padding: 20px;
            max-width: 1600px; margin: 0 auto;
        }

        /* SIDEBAR */
        .hud-panel {
            background: var(--panel-bg);
            border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 20px;
            height: fit-content;
        }
        .hud-metric { margin-bottom: 25px; }
        .hud-label { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .hud-value { font-size: 24px; font-weight: 700; color: #fff; }
        .hud-value.highlight { color: var(--neon-cyan); text-shadow: 0 0 10px rgba(6, 182, 212, 0.5); }

        .status-badge {
            display: inline-block; padding: 8px 15px; border-radius: 4px;
            font-family: 'Orbitron', sans-serif; font-size: 12px; letter-spacing: 1px;
            width: 100%; text-align: center; box-sizing: border-box;
        }
        .status-active { background: rgba(16, 185, 129, 0.2); border: 1px solid var(--neon-green); color: var(--neon-green); box-shadow: 0 0 15px rgba(16, 185, 129, 0.2); }
        .status-offline { background: rgba(239, 68, 68, 0.2); border: 1px solid var(--neon-red); color: var(--neon-red); }

        /* STUDENT GRID */
        .student-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;
        }
        .student-card {
            background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px; padding: 20px;
            display: flex; flex-direction: column; align-items: center;
            transition: all 0.3s ease;
        }
        
        .card-present { border: 1px solid var(--neon-green); box-shadow: inset 0 0 20px rgba(16, 185, 129, 0.1); }
        .card-absent { border: 1px solid rgba(239, 68, 68, 0.3); opacity: 0.7; }
        
        .student-img {
            width: 80px; height: 80px; border-radius: 50%; object-fit: cover;
            border: 2px solid rgba(255,255,255,0.1); margin-bottom: 15px;
        }
        .card-present .student-img { border-color: var(--neon-green); }
        .card-absent .student-img { filter: grayscale(100%); }

        .student-name { font-weight: 500; color: #fff; margin-bottom: 5px; text-align: center; }
        .student-id { font-size: 12px; color: #94a3b8; margin-bottom: 10px; }

        .live-tag {
            font-size: 10px; font-weight: bold; text-transform: uppercase;
            padding: 4px 10px; border-radius: 20px;
        }
        .tag-present { background: var(--neon-green); color: #000; animation: pulse 2s infinite; }
        .tag-absent { background: rgba(255,255,255,0.1); color: #94a3b8; }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* FOOTER LOG */
        .system-footer {
            margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1);
            padding: 20px; font-family: 'Courier New', monospace;
            color: #64748b; font-size: 12px; text-align: center;
        }
        #countdown { color: var(--neon-cyan); font-weight: bold; }
    </style>
</head>
<body>

    <div class="command-header">
        <div class="brand"><i class="fas fa-eye"></i> VIDYAVERSE <span style="font-weight:300; font-size:18px;">OVERWATCH</span></div>
        <div class="system-time"><i class="far fa-clock"></i> <?php echo date("H:i:s T"); ?></div>
    </div>

    <div class="dashboard-grid">
        <aside class="hud-panel">
            <div class="hud-metric">
                <div class="hud-label">System Status</div>
                <?php if($is_class_active): ?>
                    <div class="status-badge status-active"><i class="fas fa-satellite-dish"></i> MONITORING ACTIVE</div>
                <?php else: ?>
                    <div class="status-badge status-offline"><i class="fas fa-ban"></i> SYSTEM OFFLINE</div>
                <?php endif; ?>
            </div>

            <hr style="border-color: rgba(255,255,255,0.1);">

            <div class="hud-metric">
                <div class="hud-label">Current Subject</div>
                <div class="hud-value highlight"><?php echo htmlentities($subject_name); ?></div>
            </div>

            <div class="hud-metric">
                <div class="hud-label">Attendance Rate</div>
                <div class="hud-value" style="font-size: 36px; color: <?php echo ($attendance_percent > 75 ? 'var(--neon-green)' : 'var(--neon-red)'); ?>;">
                    <?php echo $attendance_percent; ?>%
                </div>
            </div>

            <div class="hud-metric">
                <div class="hud-label">Attendance Matrix</div>
                
                <div style="font-size: 13px; color: #94a3b8; margin-bottom: 10px;">
                    Total Students: <b style="color:white;"><?php echo $total_students; ?></b>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; gap: 10px;">
                    
                    <div style="background: rgba(16, 185, 129, 0.1); border:1px solid var(--neon-green); border-radius:8px; padding:10px; flex:1; text-align:center;">
                        <div style="font-size: 11px; color:var(--neon-green);">PRESENT</div>
                        <div style="font-size: 20px; font-weight:bold; color:white;"><?php echo $present_count; ?></div>
                    </div>

                    <div style="background: rgba(239, 68, 68, 0.1); border:1px solid var(--neon-red); border-radius:8px; padding:10px; flex:1; text-align:center;">
                        <div style="font-size: 11px; color:var(--neon-red);">ABSENT</div>
                        <div style="font-size: 20px; font-weight:bold; color:white;"><?php echo $absent_count; ?></div>
                    </div>

                </div>
            </div>

        </aside>

        <main class="student-grid">
            <?php
            $query = "SELECT u.ID, u.FullName, u.UserImage, a.Status, a.LastSeen 
                      FROM tbluser u 
                      LEFT JOIN tbl_live_attendance a 
                      ON u.ID = a.StudentID AND a.Date = '$current_date' AND a.SlotID = '$slot_id'
                      ORDER BY a.Status DESC, u.FullName ASC"; 
            
            $result = mysqli_query($con, $query);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    $is_present = ($row['Status'] == 'Present');
                    $card_class = $is_present ? 'card-present' : 'card-absent';
                    $img_path = !empty($row['UserImage']) ? "../uploads/" . $row['UserImage'] : "assets/default_avatar.png";
                    $last_seen_display = $is_present ? date("h:i:s A", strtotime($row['LastSeen'])) : "--:--:--";
            ?>
                <div class="student-card <?php echo $card_class; ?>">
                    <img src="<?php echo $img_path; ?>" class="student-img">
                    <div class="student-name"><?php echo $row['FullName']; ?></div>
                    <div class="student-id">ID: <?php echo $row['ID']; ?></div>
                    
                    <?php if($is_present): ?>
                        <div class="live-tag tag-present"><i class="fas fa-wifi"></i> LIVE</div>
                        <div style="font-size:11px; color:var(--neon-green); margin-top:8px; letter-spacing:1px;">
                            <i class="fas fa-history"></i> LAST SCAN: <?php echo $last_seen_display; ?>
                        </div>
                    <?php else: ?>
                        <div class="live-tag tag-absent">MISSING</div>
                        <div style="font-size:11px; color:#64748b; margin-top:8px; letter-spacing:1px;">NO SIGNAL</div>
                    <?php endif; ?>
                </div>
            <?php 
                } 
            } else {
                echo '<div style="grid-column: 1/-1; text-align:center; padding:50px; color:#64748b;"><i class="fas fa-database fa-3x"></i><br><br>No Student Data Found</div>';
            }
            ?>
        </main>
    </div>

    <div class="system-footer">
        [SYSTEM LOG] >> Scanning Matrix... <?php echo $total_students; ?> entities indexed. 
        >> Next Refresh in <span id="countdown">5</span>s... 
        >> Connected to Node: Localhost
    </div>

    <script>
        let timeLeft = 5;
        const countdownElement = document.getElementById('countdown');
        const timerId = setInterval(function() {
            timeLeft--;
            countdownElement.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timerId);
                window.location.reload(true); 
            }
        }, 1000);
    </script>

</body>
</html>