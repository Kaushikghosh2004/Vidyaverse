<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: labs_login_integrated.php");
    exit();
}

$subject = $_GET['subject'] ?? 'physics';
$education_level = $_GET['level'] ?? $_SESSION['education_level'] ?? 'school';
$user_id = $_SESSION['user_id'];

// Fetch experiments for this subject and education level
$exp_sql = "SELECT * FROM vlabs_experiments WHERE subject = ? AND education_level = ? ORDER BY experiment_name";
$stmt = $conn->prepare($exp_sql);
$stmt->bind_param("ss", $subject, $education_level);
$stmt->execute();
$experiments_result = $stmt->get_result();

// Fetch user activities for this subject
$activity_sql = "SELECT * FROM vlabs_activities WHERE user_id = ? AND experiment_id IN (SELECT id FROM vlabs_experiments WHERE subject = ?)";
$stmt2 = $conn->prepare($activity_sql);
$stmt2->bind_param("is", $user_id, $subject);
$stmt2->execute();
$activities_result = $stmt2->get_result();
$activities = [];
while ($row = $activities_result->fetch_assoc()) {
    $activities[$row['experiment_id']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo ucfirst($subject); ?> Lab | LEXCLASSROOM</title>
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
        }
        
        .lab-container {
            display: flex;
            height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 350px;
            background: white;
            border-right: 2px solid #f0f0f0;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 25px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .back-btn:hover {
            opacity: 1;
        }
        
        .experiment-list {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .exp-item {
            padding: 20px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            position: relative;
        }
        
        .exp-item:hover {
            background: #eef4ff;
            transform: translateX(5px);
        }
        
        .exp-item.completed {
            border-left-color: #4caf50;
        }
        
        .exp-item.in-progress {
            border-left-color: #ff9800;
        }
        
        .exp-status {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .status-completed {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-inprogress {
            background: #fff3e0;
            color: #ef6c00;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }
        
        .content-header {
            padding: 25px 40px;
            background: white;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .exp-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .exp-desc {
            color: #666;
            font-size: 16px;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            background: #f8f9fa;
            padding: 0 40px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .tab-btn {
            padding: 20px 30px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .tab-btn:hover {
            color: var(--primary);
        }
        
        .tab-btn.active {
            color: var(--primary);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
        }
        
        /* Tab Content */
        .tab-content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Theory Content */
        .formula-box {
            background: linear-gradient(135deg, #f0f7ff, #e6f0ff);
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            border-left: 5px solid var(--primary);
        }
        
        /* Simulation Container */
        .simulation-container {
            display: flex;
            gap: 30px;
            height: 600px;
        }
        
        .sim-canvas {
            flex: 1;
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        
        canvas {
            display: block;
            width: 100%;
            height: 100%;
        }
        
        .sim-controls {
            width: 350px;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
        }
        
        .control-group {
            margin-bottom: 25px;
        }
        
        .control-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--dark);
        }
        
        input[type="range"] {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            outline: none;
            -webkit-appearance: none;
        }
        
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 22px;
            height: 22px;
            background: var(--primary);
            border-radius: 50%;
            cursor: pointer;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        
        /* Quiz */
        .quiz-question {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }
        
        .quiz-options {
            display: grid;
            gap: 15px;
            margin: 20px 0;
        }
        
        .quiz-option {
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quiz-option:hover {
            border-color: var(--primary);
            background: #f0f7ff;
        }
        
        .quiz-option.selected {
            border-color: var(--primary);
            background: #eef4ff;
        }
    </style>
</head>
<body>
    <div class="lab-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <a href="labs_dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <h2><i class="fas <?php 
                    $icons = [
                        'physics' => 'fa-atom',
                        'chemistry' => 'fa-flask',
                        'biology' => 'fa-dna',
                        'mathematics' => 'fa-calculator',
                        'computer science' => 'fa-code',
                        'engineering' => 'fa-cogs'
                    ];
                    echo $icons[strtolower($subject)] ?? 'fa-flask';
                ?>"></i> <?php echo ucfirst($subject); ?> Lab</h2>
                <p style="opacity: 0.9; margin-top: 5px;"><?php echo ucfirst($education_level); ?> Level</p>
            </div>
            
            <div class="experiment-list">
                <h3 style="color: #666; margin-bottom: 20px; font-size: 14px;">EXPERIMENTS</h3>
                
                <?php while($exp = $experiments_result->fetch_assoc()): 
                    $activity = $activities[$exp['id']] ?? null;
                    $status = $activity['status'] ?? 'not_started';
                ?>
                <div class="exp-item <?php echo $status; ?>" onclick="loadExperiment(<?php echo $exp['id']; ?>)">
                    <h4><?php echo $exp['experiment_name']; ?></h4>
                    <p style="color: #666; font-size: 13px; margin-top: 5px;"><?php echo $exp['description']; ?></p>
                    
                    <?php if($status != 'not_started'): ?>
                    <div class="exp-status status-<?php echo $status; ?>">
                        <?php echo ucfirst($status); ?>
                        <?php if($activity['quiz_score']): ?>
                            | Score: <?php echo $activity['quiz_score']; ?>%
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1 class="exp-title" id="currentExpTitle">Select an Experiment</h1>
                <p class="exp-desc" id="currentExpDesc">Choose an experiment from the sidebar to begin</p>
            </div>
            
            <div class="tabs">
                <button class="tab-btn" onclick="switchTab('theory')">
                    <i class="fas fa-book"></i> Theory
                </button>
                <button class="tab-btn" onclick="switchTab('procedure')">
                    <i class="fas fa-list-ol"></i> Procedure
                </button>
                <button class="tab-btn" onclick="switchTab('simulation')">
                    <i class="fas fa-play-circle"></i> Simulation
                </button>
                <button class="tab-btn" onclick="switchTab('quiz')">
                    <i class="fas fa-question-circle"></i> Quiz
                </button>
                <button class="tab-btn" onclick="switchTab('analysis')">
                    <i class="fas fa-chart-bar"></i> Analysis
                </button>
            </div>
            
            <div class="tab-content">
                <!-- Theory Tab -->
                <div id="theory-tab" class="tab-pane">
                    <div id="theory-content">
                        <p>Select an experiment to view its theory.</p>
                    </div>
                </div>
                
                <!-- Procedure Tab -->
                <div id="procedure-tab" class="tab-pane">
                    <div id="procedure-content">
                        <p>Select an experiment to view its procedure.</p>
                    </div>
                </div>
                
                <!-- Simulation Tab -->
                <div id="simulation-tab" class="tab-pane">
                    <div class="simulation-container">
                        <div class="sim-canvas">
                            <canvas id="simulationCanvas"></canvas>
                        </div>
                        <div class="sim-controls">
                            <h3>Simulation Controls</h3>
                            <div id="sim-controls-content">
                                <p>Select an experiment to view controls.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quiz Tab -->
                <div id="quiz-tab" class="tab-pane">
                    <div id="quiz-content">
                        <p>Select an experiment to take its quiz.</p>
                    </div>
                </div>
                
                <!-- Analysis Tab -->
                <div id="analysis-tab" class="tab-pane">
                    <div id="analysis-content">
                        <p>Select an experiment to view analysis tools.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentExperimentId = null;
        let currentTab = 'theory';
        
        // Load experiment data
        function loadExperiment(expId) {
            currentExperimentId = expId;
            
            // Fetch experiment data via AJAX
            fetch(`get_experiment.php?id=${expId}`)
                .then(response => response.json())
                .then(data => {
                    // Update UI
                    document.getElementById('currentExpTitle').textContent = data.experiment_name;
                    document.getElementById('currentExpDesc').textContent = data.description;
                    
                    // Update tabs content
                    document.getElementById('theory-content').innerHTML = data.theory_content;
                    document.getElementById('procedure-content').innerHTML = `
                        <h3>Procedure Steps</h3>
                        <div style="line-height: 2; margin-top: 20px;">
                            ${data.procedure_steps.replace(/\n/g, '<br>')}
                        </div>
                    `;
                    
                    // Update simulation controls
                    document.getElementById('sim-controls-content').innerHTML = `
                        <div class="control-group">
                            <label class="control-label">Parameter 1</label>
                            <input type="range" min="0" max="100" value="50" oninput="updateSimulation()">
                        </div>
                        <div class="control-group">
                            <label class="control-label">Parameter 2</label>
                            <input type="range" min="0" max="100" value="50" oninput="updateSimulation()">
                        </div>
                        <button class="btn btn-primary" onclick="startSimulation()">
                            <i class="fas fa-play"></i> Start Simulation
                        </button>
                    `;
                    
                    // Start with theory tab
                    switchTab('theory');
                    
                    // Mark as started in database
                    updateExperimentStatus('started');
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Switch between tabs
        function switchTab(tabName) {
            currentTab = tabName;
            
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Show selected tab content
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            document.getElementById(`${tabName}-tab`).classList.add('active');
            
            // Initialize simulation canvas if needed
            if (tabName === 'simulation' && currentExperimentId) {
                initializeSimulation();
            }
        }
        
        // Simulation functions
        function initializeSimulation() {
            const canvas = document.getElementById('simulationCanvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas size
            canvas.width = canvas.parentElement.clientWidth;
            canvas.height = canvas.parentElement.clientHeight;
            
            // Draw initial simulation based on experiment
            drawSimulation(ctx, canvas);
        }
        
        function drawSimulation(ctx, canvas) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw background
            ctx.fillStyle = '#000';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Draw grid
            ctx.strokeStyle = '#222';
            ctx.lineWidth = 1;
            
            for (let x = 0; x < canvas.width; x += 50) {
                ctx.beginPath();
                ctx.moveTo(x, 0);
                ctx.lineTo(x, canvas.height);
                ctx.stroke();
            }
            
            for (let y = 0; y < canvas.height; y += 50) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(canvas.width, y);
                ctx.stroke();
            }
            
            // Draw experiment-specific content
            if (currentExperimentId) {
                ctx.fillStyle = '#4361ee';
                ctx.font = '20px Poppins';
                ctx.fillText('Simulation Active', 50, 50);
            }
        }
        
        function updateSimulation() {
            // Update simulation based on control values
            console.log('Updating simulation...');
        }
        
        function startSimulation() {
            console.log('Starting simulation...');
        }
        
        // Update experiment status in database
        function updateExperimentStatus(status) {
            if (!currentExperimentId) return;
            
            fetch('update_experiment_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    experiment_id: currentExperimentId,
                    status: status
                })
            });
        }
    </script>
</body>
</html>