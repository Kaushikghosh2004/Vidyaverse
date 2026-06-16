<?php
session_start();
require_once 'db_config.php'; // Your existing database config

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: labs_login_integrated.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$education_level = $_SESSION['education_level'] ?? 'school';

// Fetch user progress
$progress_sql = "
    SELECT 
        subject,
        COUNT(DISTINCT experiment_id) as total_experiments,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_experiments,
        AVG(quiz_score) as average_score
    FROM vlabs_activities 
    WHERE user_id = ?
    GROUP BY subject
";
$stmt = $conn->prepare($progress_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$progress_result = $stmt->get_result();
$progress_data = [];
while ($row = $progress_result->fetch_assoc()) {
    $progress_data[$row['subject']] = $row;
}

// Fetch available experiments based on education level
$exp_sql = "
    SELECT subject, COUNT(*) as count 
    FROM vlabs_experiments 
    WHERE education_level = ?
    GROUP BY subject
    ORDER BY subject
";
$stmt2 = $conn->prepare($exp_sql);
$stmt2->bind_param("s", $education_level);
$stmt2->execute();
$subjects_result = $stmt2->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>LEXCLASSROOM | Virtual Labs Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --success: #4cc9f0;
            --dark: #1a1a2e;
            --light: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }
        
        /* Header */
        .dashboard-header {
            background: white;
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            background: #f8f9fa;
            border-radius: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }
        
        /* Welcome Section */
        .welcome-section {
            padding: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }
        
        /* Subject Grid */
        .subject-grid {
            padding: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .subject-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .subject-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border-color: var(--primary);
        }
        
        .subject-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin-bottom: 25px;
        }
        
        .subject-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .subject-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        
        .progress-bar {
            flex: 1;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        /* Quick Stats */
        .quick-stats {
            padding: 0 40px 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stat-content h3 {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .stat-content p {
            color: #666;
            font-size: 14px;
        }
        
        /* Subject Colors */
        .physics { background: linear-gradient(135deg, #4361ee, #3a0ca3); }
        .chemistry { background: linear-gradient(135deg, #f72585, #7209b7); }
        .biology { background: linear-gradient(135deg, #4cc9f0, #4895ef); }
        .mathematics { background: linear-gradient(135deg, #f8961e, #f9c74f); }
        .cs { background: linear-gradient(135deg, #2a9d8f, #e9c46a); }
        .engineering { background: linear-gradient(135deg, #e63946, #a8dadc); }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <div class="logo">
                    <i class="fas fa-flask"></i>
                    LEXCLASSROOM VIRTUAL LABS
                </div>
                <div style="font-size: 14px; color: #666; background: #f0f7ff; padding: 8px 15px; border-radius: 10px;">
                    <i class="fas fa-graduation-cap"></i> 
                    <?php echo ucfirst($education_level); ?> Level
                </div>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    $initials = '';
                    if (isset($_SESSION['full_name'])) {
                        $names = explode(' ', $_SESSION['full_name']);
                        foreach ($names as $name) {
                            $initials .= strtoupper(substr($name, 0, 1));
                        }
                    } else {
                        $initials = 'US';
                    }
                    echo substr($initials, 0, 2);
                    ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo $_SESSION['full_name'] ?? 'User'; ?></div>
                    <div style="font-size: 12px; color: #666;"><?php echo ucfirst($_SESSION['user_type'] ?? 'student'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 style="font-size: 36px; margin-bottom: 15px;">Welcome to Virtual Labs!</h1>
            <p style="font-size: 18px; opacity: 0.9;">Explore interactive experiments and enhance your learning experience</p>
        </div>
        
        <!-- Quick Stats -->
        <div class="quick-stats">
            <?php
            // Calculate overall statistics
            $total_exps = 0;
            $completed_exps = 0;
            $total_score = 0;
            $count_score = 0;
            
            foreach ($progress_data as $subject => $data) {
                $total_exps += $data['total_experiments'];
                $completed_exps += $data['completed_experiments'];
                if ($data['average_score']) {
                    $total_score += $data['average_score'];
                    $count_score++;
                }
            }
            
            $avg_score = $count_score > 0 ? $total_score / $count_score : 0;
            ?>
            
            <div class="stat-card">
                <div class="stat-icon physics">
                    <i class="fas fa-vial"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $completed_exps; ?></h3>
                    <p>Experiments Completed</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon chemistry">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($avg_score, 1); ?>%</h3>
                    <p>Average Score</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon biology">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_exps; ?></h3>
                    <p>Total Experiments</p>
                </div>
            </div>
        </div>
        
        <!-- Subject Grid -->
        <div class="subject-grid">
            <?php
            // Define subjects with icons
            $subjects = [
                'Physics' => ['icon' => 'fa-atom', 'color' => 'physics'],
                'Chemistry' => ['icon' => 'fa-flask', 'color' => 'chemistry'],
                'Biology' => ['icon' => 'fa-dna', 'color' => 'biology'],
                'Mathematics' => ['icon' => 'fa-calculator', 'color' => 'mathematics'],
                'Computer Science' => ['icon' => 'fa-code', 'color' => 'cs'],
                'Engineering' => ['icon' => 'fa-cogs', 'color' => 'engineering']
            ];
            
            // Get actual counts from database
            $subject_counts = [];
            if ($subjects_result->num_rows > 0) {
                while ($row = $subjects_result->fetch_assoc()) {
                    $subject_counts[$row['subject']] = $row['count'];
                }
            }
            
            foreach ($subjects as $subject => $info):
                $count = $subject_counts[$subject] ?? 0;
                $progress = isset($progress_data[$subject]) ? $progress_data[$subject] : null;
                $completed = $progress['completed_experiments'] ?? 0;
                $progress_percent = $count > 0 ? ($completed / $count) * 100 : 0;
            ?>
            <div class="subject-card" onclick="openSubject('<?php echo strtolower($subject); ?>')">
                <div class="subject-icon <?php echo $info['color']; ?>">
                    <i class="fas <?php echo $info['icon']; ?>"></i>
                </div>
                <h3 class="subject-title"><?php echo $subject; ?> Lab</h3>
                <p style="color: #666; line-height: 1.6;">
                    <?php if($education_level == 'school'): ?>
                        Interactive simulations and experiments for <?php echo $subject; ?> education.
                    <?php else: ?>
                        Advanced <?php echo $subject; ?> experiments and simulations for college students.
                    <?php endif; ?>
                </p>
                
                <div class="subject-stats">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                    </div>
                    <div style="font-weight: 600; color: var(--primary);">
                        <?php echo $completed; ?>/<?php echo $count; ?>
                    </div>
                </div>
                
                <div style="margin-top: 15px; font-size: 12px; color: #888;">
                    <i class="fas fa-flask"></i> <?php echo $count; ?> experiments available
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function openSubject(subject) {
            window.location.href = 'subject_lab.php?subject=' + subject + '&level=<?php echo $education_level; ?>';
        }
    </script>
</body>
</html>