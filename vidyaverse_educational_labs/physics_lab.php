<?php
session_start();
$education_level = $_GET['level'] ?? 'school';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Physics Virtual Lab | VIDYAVERSE</title>
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
        .lab-sidebar {
            width: 320px;
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
        
        .experiment-list {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .exp-category {
            margin-bottom: 25px;
        }
        
        .category-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .exp-item {
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .exp-item:hover {
            background: #eef4ff;
            border-left-color: var(--primary);
            transform: translateX(5px);
        }
        
        .exp-item.active {
            background: #eef4ff;
            border-left-color: var(--primary);
        }
        
        /* Main Content */
        .lab-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }
        
        .lab-header {
            padding: 20px 30px;
            background: white;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lab-tabs {
            display: flex;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 5px;
        }
        
        .tab-btn {
            padding: 12px 25px;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .tab-btn.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .lab-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        /* Theory Panel */
        .theory-panel {
            display: none;
        }
        
        .theory-panel.active {
            display: block;
        }
        
        .formula-box {
            background: linear-gradient(135deg, #f0f7ff, #e6f0ff);
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            border-left: 5px solid var(--primary);
        }
        
        /* Simulation Panel */
        .simulation-panel {
            display: none;
            height: 100%;
        }
        
        .simulation-panel.active {
            display: flex;
            gap: 30px;
        }
        
        .sim-canvas-container {
            flex: 1;
            background: #000;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
        }
        
        canvas {
            display: block;
            width: 100%;
            height: 100%;
        }
        
        .sim-controls {
            width: 320px;
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
            -webkit-appearance: none;
            background: #e0e0e0;
            border-radius: 4px;
            outline: none;
        }
        
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 22px;
            height: 22px;
            background: var(--primary);
            border-radius: 50%;
            cursor: pointer;
        }
        
        /* Quiz Panel */
        .quiz-panel {
            display: none;
        }
        
        .quiz-panel.active {
            display: block;
        }
        
        .quiz-question {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .quiz-options {
            display: grid;
            gap: 15px;
            margin: 20px 0;
        }
        
        .quiz-option {
            padding: 15px;
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
        
        .btn-primary {
            padding: 15px 30px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
    </style>
</head>
<body>
    <div class="lab-container">
        <!-- Sidebar -->
        <div class="lab-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-atom"></i> Physics Lab</h2>
                <p><?php echo ucfirst($education_level); ?> Level</p>
            </div>
            
            <div class="experiment-list">
                <?php if($education_level == 'school'): ?>
                    <div class="exp-category">
                        <div class="category-title">Mechanics</div>
                        <div class="exp-item active" onclick="loadExperiment(1)">
                            <div class="exp-title">Newton's Laws of Motion</div>
                            <small>Interactive simulation</small>
                        </div>
                        <div class="exp-item" onclick="loadExperiment(2)">
                            <div class="exp-title">Simple Pendulum</div>
                            <small>Time period calculation</small>
                        </div>
                        <div class="exp-item" onclick="loadExperiment(3)">
                            <div class="exp-title">Projectile Motion</div>
                            <small>Trajectory simulation</small>
                        </div>
                    </div>
                    
                    <div class="exp-category">
                        <div class="category-title">Optics</div>
                        <div class="exp-item" onclick="loadExperiment(4)">
                            <div class="exp-title">Refraction through Glass Slab</div>
                            <small>Snell's Law verification</small>
                        </div>
                        <div class="exp-item" onclick="loadExperiment(5)">
                            <div class="exp-title">Prism Dispersion</div>
                            <small>White light spectrum</small>
                        </div>
                        <div class="exp-item" onclick="loadExperiment(6)">
                            <div class="exp-title">Convex Lens</div>
                            <small>Image formation</small>
                        </div>
                    </div>
                    
                    <div class="exp-category">
                        <div class="category-title">Electricity</div>
                        <div class="exp-item" onclick="loadExperiment(7)">
                            <div class="exp-title">Ohm's Law</div>
                            <small>V-I Characteristics</small>
                        </div>
                        <div class="exp-item" onclick="loadExperiment(8)">
                            <div class="exp-title">Series & Parallel Circuits</div>
                            <small>Resistance calculation</small>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- College Level Experiments -->
                    <div class="exp-category">
                        <div class="category-title">Advanced Mechanics</div>
                        <div class="exp-item active" onclick="loadExperiment(101)">
                            <div class="exp-title">Young's Double Slit</div>
                            <small>Interference pattern</small>
                        </div>
                        <div class="exp-item" onclick="loadExperiment(102)">
                            <div class="exp-title">Newton's Rings</div>
                            <small>Wave optics</small>
                        </div>
                        <div class="exp-item" onclick="loadExperiment(103)">
                            <div class="exp-title">LASER Diffraction</div>
                            <small>Single slit diffraction</small>
                        </div>
                    </div>
                    
                    <div class="exp-category">
                        <div class="category-title">Modern Physics</div>
                        <div class="exp-item" onclick="loadExperiment(104)">
                            <div class="exp-title">Photoelectric Effect</div>
                            <small>Quantum physics</small>
                        </div>
                        <div class="exp-item" onclick="loadExperiment(105)">
                            <div class="exp-title">Hall Effect</div>
                            <small>Semiconductor physics</small>
                        </div>
                        <div class="exp-item" onclick="loadExperiment(106)">
                            <div class="exp-title">Franck-Hertz Experiment</div>
                            <small>Atomic energy levels</small>
                        </div>
                    </div>
                    
                    <div class="exp-category">
                        <div class="category-title">Engineering Physics</div>
                        <div class="exp-item" onclick="loadExperiment(107)">
                            <div class="exp-title">Ultrasonic Interferometer</div>
                            <small>Velocity of sound</small>
                        </div>
                        <div class="exp-item" onclick="loadExperiment(108)">
                            <div class="exp-title">Stefan's Constant</div>
                            <small>Black body radiation</small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="lab-main">
            <div class="lab-header">
                <div>
                    <h3 id="current-exp-title">Newton's Laws of Motion</h3>
                    <p id="current-exp-desc">Interactive simulation of Newton's three laws</p>
                </div>
                
                <div class="lab-tabs">
                    <button class="tab-btn active" onclick="switchTab('theory')">
                        <i class="fas fa-book"></i> Theory
                    </button>
                    <button class="tab-btn" onclick="switchTab('simulation')">
                        <i class="fas fa-play-circle"></i> Simulation
                    </button>
                    <button class="tab-btn" onclick="switchTab('procedure')">
                        <i class="fas fa-list-ol"></i> Procedure
                    </button>
                    <button class="tab-btn" onclick="switchTab('quiz')">
                        <i class="fas fa-question-circle"></i> Quiz
                    </button>
                    <button class="btn-primary" onclick="saveProgress()">
                        <i class="fas fa-save"></i> Save Progress
                    </button>
                </div>
            </div>
            
            <div class="lab-content">
                <!-- Theory Panel -->
                <div id="theory-panel" class="theory-panel active">
                    <h3>Newton's Laws of Motion</h3>
                    <p>Sir Isaac Newton's three laws of motion describe the relationship between a body and the forces acting upon it, and its motion in response to those forces.</p>
                    
                    <div class="formula-box">
                        <h4>First Law (Law of Inertia)</h4>
                        <p>An object at rest stays at rest, and an object in motion stays in motion with the same speed and in the same direction unless acted upon by an unbalanced force.</p>
                        <p>Formula: F = 0 ⇒ a = 0</p>
                    </div>
                    
                    <div class="formula-box">
                        <h4>Second Law (F = ma)</h4>
                        <p>The acceleration of an object is directly proportional to the net force acting on it and inversely proportional to its mass.</p>
                        <p>Formula: F = m × a</p>
                        <p>Where: F = Force (N), m = mass (kg), a = acceleration (m/s²)</p>
                    </div>
                    
                    <div class="formula-box">
                        <h4>Third Law (Action-Reaction)</h4>
                        <p>For every action, there is an equal and opposite reaction.</p>
                        <p>Formula: F₁₂ = -F₂₁</p>
                    </div>
                </div>
                
                <!-- Simulation Panel -->
                <div id="simulation-panel" class="simulation-panel">
                    <div class="sim-canvas-container">
                        <canvas id="physicsCanvas"></canvas>
                    </div>
                    <div class="sim-controls">
                        <h4>Simulation Controls</h4>
                        
                        <div class="control-group">
                            <label class="control-label">Mass of Object (kg): <span id="mass-value">5</span></label>
                            <input type="range" id="mass-slider" min="1" max="20" value="5" oninput="updateSimulation()">
                        </div>
                        
                        <div class="control-group">
                            <label class="control-label">Applied Force (N): <span id="force-value">10</span></label>
                            <input type="range" id="force-slider" min="0" max="50" value="10" oninput="updateSimulation()">
                        </div>
                        
                        <div class="control-group">
                            <label class="control-label">Friction Coefficient: <span id="friction-value">0.1</span></label>
                            <input type="range" id="friction-slider" min="0" max="0.5" step="0.01" value="0.1" oninput="updateSimulation()">
                        </div>
                        
                        <div class="control-group">
                            <label class="control-label">Gravity (m/s²): <span id="gravity-value">9.8</span></label>
                            <input type="range" id="gravity-slider" min="1" max="20" value="9.8" oninput="updateSimulation()">
                        </div>
                        
                        <button class="btn-primary" onclick="startSimulation()">
                            <i class="fas fa-play"></i> Start Simulation
                        </button>
                        <button class="btn-primary" style="background: #666; margin-top: 10px;" onclick="resetSimulation()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>
                
                <!-- Procedure Panel -->
                <div id="procedure-panel" class="theory-panel">
                    <h3>Procedure</h3>
                    <ol style="line-height: 2; margin: 20px 0;">
                        <li>Set up the simulation parameters using the controls panel</li>
                        <li>Adjust the mass of the object using the mass slider</li>
                        <li>Set the applied force value</li>
                        <li>Configure friction coefficient and gravity</li>
                        <li>Click "Start Simulation" to observe the motion</li>
                        <li>Record the acceleration values from the display</li>
                        <li>Verify F = ma by comparing calculated and observed values</li>
                        <li>Repeat with different parameters to understand the relationship</li>
                    </ol>
                </div>
                
                <!-- Quiz Panel -->
                <div id="quiz-panel" class="quiz-panel">
                    <div class="quiz-question">
                        <h4>Question 1: According to Newton's Second Law, what happens to acceleration if mass is doubled while force remains constant?</h4>
                        <div class="quiz-options">
                            <div class="quiz-option" onclick="selectOption(this)">Acceleration doubles</div>
                            <div class="quiz-option" onclick="selectOption(this)">Acceleration halves</div>
                            <div class="quiz-option" onclick="selectOption(this)">Acceleration remains same</div>
                            <div class="quiz-option" onclick="selectOption(this)">Acceleration quadruples</div>
                        </div>
                    </div>
                    
                    <div class="quiz-question">
                        <h4>Question 2: What is the unit of force in SI system?</h4>
                        <div class="quiz-options">
                            <div class="quiz-option" onclick="selectOption(this)">Joule</div>
                            <div class="quiz-option" onclick="selectOption(this)">Watt</div>
                            <div class="quiz-option" onclick="selectOption(this)">Newton</div>
                            <div class="quiz-option" onclick="selectOption(this)">Pascal</div>
                        </div>
                    </div>
                    
                    <button class="btn-primary" onclick="submitQuiz()">
                        <i class="fas fa-paper-plane"></i> Submit Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Physics Simulation
        let canvas = document.getElementById('physicsCanvas');
        let ctx = canvas.getContext('2d');
        
        // Set canvas dimensions
        canvas.width = canvas.parentElement.clientWidth;
        canvas.height = canvas.parentElement.clientHeight;
        
        // Physics variables
        let box = {
            x: 50,
            y: canvas.height - 100,
            width: 80,
            height: 80,
            velocity: 0,
            acceleration: 0,
            mass: 5,
            color: '#4361ee'
        };
        
        let animationId = null;
        let isRunning = false;
        
        function drawBox() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw ground
            ctx.fillStyle = '#666';
            ctx.fillRect(0, canvas.height - 20, canvas.width, 20);
            
            // Draw box
            ctx.fillStyle = box.color;
            ctx.fillRect(box.x, box.y, box.width, box.height);
            
            // Draw force arrow
            ctx.strokeStyle = '#f72585';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.moveTo(box.x + box.width, box.y + box.height/2);
            ctx.lineTo(box.x + box.width + 50, box.y + box.height/2);
            ctx.stroke();
            
            // Draw text
            ctx.fillStyle = 'white';
            ctx.font = '16px Poppins';
            ctx.fillText(`Mass: ${box.mass} kg`, 20, 30);
            ctx.fillText(`Force: ${document.getElementById('force-slider').value} N`, 20, 60);
            ctx.fillText(`Acceleration: ${box.acceleration.toFixed(2)} m/s²`, 20, 90);
            ctx.fillText(`Velocity: ${box.velocity.toFixed(2)} m/s`, 20, 120);
        }
        
        function updateSimulation() {
            // Update values from sliders
            box.mass = parseFloat(document.getElementById('mass-slider').value);
            let force = parseFloat(document.getElementById('force-slider').value);
            let friction = parseFloat(document.getElementById('friction-slider').value);
            let gravity = parseFloat(document.getElementById('gravity-slider').value);
            
            // Update display values
            document.getElementById('mass-value').textContent = box.mass;
            document.getElementById('force-value').textContent = force;
            document.getElementById('friction-value').textContent = friction;
            document.getElementById('gravity-value').textContent = gravity;
            
            // Calculate acceleration (F = ma, considering friction)
            let frictionForce = friction * box.mass * gravity;
            let netForce = Math.max(0, force - frictionForce);
            box.acceleration = netForce / box.mass;
            
            if (isRunning) {
                updatePhysics();
            }
            
            drawBox();
        }
        
        function updatePhysics() {
            box.velocity += box.acceleration;
            box.x += box.velocity;
            
            // Boundary check
            if (box.x > canvas.width - box.width) {
                box.x = canvas.width - box.width;
                box.velocity = 0;
            }
            
            drawBox();
            
            if (box.x < canvas.width - box.width) {
                animationId = requestAnimationFrame(updatePhysics);
            }
        }
        
        function startSimulation() {
            if (!isRunning) {
                isRunning = true;
                updatePhysics();
            }
        }
        
        function resetSimulation() {
            isRunning = false;
            if (animationId) {
                cancelAnimationFrame(animationId);
            }
            box.x = 50;
            box.velocity = 0;
            updateSimulation();
        }
        
        // Tab switching
        function switchTab(tabName) {
            // Hide all panels
            document.querySelectorAll('.theory-panel, .simulation-panel, .quiz-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            // Deactivate all tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Activate selected tab
            event.target.classList.add('active');
            document.getElementById(tabName + '-panel').classList.add('active');
            
            // Reset canvas size when showing simulation
            if (tabName === 'simulation') {
                canvas.width = canvas.parentElement.clientWidth;
                canvas.height = canvas.parentElement.clientHeight;
                drawBox();
            }
        }
        
        // Experiment loading
        function loadExperiment(expId) {
            // Update active item
            document.querySelectorAll('.exp-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.exp-item').classList.add('active');
            
            // Load experiment data based on ID
            const experiments = {
                1: {
                    title: "Newton's Laws of Motion",
                    desc: "Interactive simulation of Newton's three laws"
                },
                2: {
                    title: "Simple Pendulum",
                    desc: "Time period calculation and oscillation analysis"
                },
                4: {
                    title: "Refraction through Glass Slab",
                    desc: "Snell's Law verification and lateral shift measurement"
                },
                101: {
                    title: "Young's Double Slit Experiment",
                    desc: "Interference pattern and wavelength calculation"
                }
            };
            
            if (experiments[expId]) {
                document.getElementById('current-exp-title').textContent = experiments[expId].title;
                document.getElementById('current-exp-desc').textContent = experiments[expId].desc;
            }
            
            // Switch to theory tab
            switchTab('theory');
        }
        
        // Quiz functions
        function selectOption(element) {
            element.parentElement.querySelectorAll('.quiz-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            element.classList.add('selected');
        }
        
        function submitQuiz() {
            alert('Quiz submitted! Your score has been recorded.');
        }
        
        function saveProgress() {
            alert('Progress saved successfully!');
        }
        
        // Initialize
        window.addEventListener('resize', () => {
            if (document.getElementById('simulation-panel').classList.contains('active')) {
                canvas.width = canvas.parentElement.clientWidth;
                canvas.height = canvas.parentElement.clientHeight;
                drawBox();
            }
        });
        
        updateSimulation();
    </script>
</body>
</html>