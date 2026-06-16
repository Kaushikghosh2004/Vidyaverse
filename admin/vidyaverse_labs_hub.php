<?php
session_start();
if (!isset($_SESSION['role'])) { header("Location: vidyaverse_labs_login.php"); exit(); }

$conn = new mysqli('localhost', 'root', '', 'lexclassroom');
$role = $_SESSION['role'];
$name = $_SESSION['name'];

// --- BACKEND: SAVE QUIZ & DATA ---
if ($role == 'student' && isset($_POST['save_lab'])) {
    $topic = $_POST['topic'];
    $score = $_POST['score']; // Quiz Score
    $data = $_POST['sim_data']; // Simulation Readings
    
    $stmt = $conn->prepare("INSERT INTO lab_records (student_name, subject, lab_topic, status) VALUES (?, 'Physics', ?, ?)");
    $status = "Score: $score/5 | Data: $data";
    $stmt->bind_param("sss", $name, $topic, $status);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VIRTUAL LABS | PHYSICS</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&family=Orbitron:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #003366; /* IIT Blue */
            --accent: #ff9900;  /* Academic Orange */
            --light: #f4f4f4;
            --dark: #222;
            --glass: rgba(255, 255, 255, 0.95);
        }

        body { margin: 0; font-family: 'Roboto', sans-serif; background: #e0e0e0; height: 100vh; display: flex; flex-direction: column; }

        /* HEADER */
        .vlab-header {
            background: var(--primary); color: white; padding: 15px 30px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .vlab-logo { font-family: 'Orbitron'; font-size: 24px; font-weight: bold; letter-spacing: 1px; }
        .vlab-nav a { color: white; text-decoration: none; margin-left: 20px; font-size: 14px; opacity: 0.8; transition: 0.3s; }
        .vlab-nav a:hover { opacity: 1; color: var(--accent); }

        /* MAIN LAYOUT */
        .container { display: flex; flex-grow: 1; height: calc(100vh - 60px); }

        /* SIDEBAR (Experiment List) */
        .sidebar {
            width: 280px; background: white; border-right: 1px solid #ccc;
            overflow-y: auto; display: flex; flex-direction: column;
        }
        .sidebar-header {
            background: #eee; padding: 15px; font-weight: bold; border-bottom: 1px solid #ddd;
            color: var(--primary); text-transform: uppercase; font-size: 14px;
        }
        .exp-item {
            padding: 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: 0.2s; font-size: 13px; color: #444;
        }
        .exp-item:hover { background: #f9f9f9; color: var(--primary); border-left: 4px solid var(--accent); }
        .exp-active { background: #eef4ff; color: var(--primary); border-left: 4px solid var(--primary); font-weight: bold; }

        /* CONTENT AREA */
        .main-content { flex-grow: 1; display: flex; flex-direction: column; background: white; }

        /* TABS */
        .tabs {
            display: flex; background: #333; color: white;
        }
        .tab-btn {
            padding: 15px 30px; cursor: pointer; transition: 0.3s; font-size: 14px; text-transform: uppercase;
            border-right: 1px solid #444;
        }
        .tab-btn:hover { background: #444; }
        .tab-active { background: var(--accent); color: black; font-weight: bold; }

        /* PANELS */
        .panel { display: none; padding: 30px; overflow-y: auto; height: 100%; }
        .panel-active { display: block; }

        /* THEORY & PROCEDURE STYLES */
        h2 { color: var(--primary); border-bottom: 2px solid var(--accent); padding-bottom: 10px; }
        p { line-height: 1.6; color: #333; }
        .formula { background: #f4f4f4; padding: 15px; border-left: 4px solid var(--primary); font-family: monospace; margin: 20px 0; }
        
        /* SIMULATION CANVAS */
        .sim-container {
            display: flex; gap: 20px; height: 100%;
        }
        .sim-canvas-box {
            flex-grow: 1; background: #000; position: relative; border: 2px solid #333;
            display: flex; align-items: center; justify-content: center;
        }
        .sim-controls {
            width: 300px; background: #f4f4f4; padding: 20px; border-left: 1px solid #ccc;
            display: flex; flex-direction: column; gap: 15px;
        }
        
        /* CONTROLS */
        .ctrl-group label { font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px; }
        input[type=range] { width: 100%; }
        button.run-btn {
            background: var(--primary); color: white; border: none; padding: 10px; width: 100%;
            cursor: pointer; font-weight: bold; text-transform: uppercase; margin-top: 10px;
        }
        button.run-btn:hover { background: #004080; }

        /* QUIZ */
        .quiz-q { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .q-opt { display: block; margin: 5px 0; cursor: pointer; }

    </style>
</head>
<body>

<div class="vlab-header">
    <div class="vlab-logo"><i class="fas fa-atom"></i> VIDYAVERSE VIRTUAL LABS</div>
    <div class="vlab-nav">
        <span>Welcome, <?php echo $name; ?></span>
        <a href="vidyaverse_labs_login.php">LOGOUT</a>
    </div>
</div>

<div class="container">
    
    <div class="sidebar">
        <div class="sidebar-header">List of Experiments</div>
        <div class="exp-item exp-active" onclick="loadExp(1)">1. Refraction through Glass Slab</div>
        <div class="exp-item" onclick="loadExp(2)">2. Dispersion through Prism</div>
        <div class="exp-item" onclick="loadExp(3)">3. Focal Length of Convex Lens</div>
        <div class="exp-item" onclick="loadExp(4)">4. Newton's Rings</div>
        <div class="exp-item" onclick="loadExp(5)">5. Laser Diffraction Grating</div>
        <div class="exp-item" onclick="loadExp(6)">6. Numerical Aperture (Fiber Optics)</div>
        <div class="exp-item" onclick="loadExp(7)">7. Hall Effect</div>
        <div class="exp-item" onclick="loadExp(8)">8. Solar Cell Characteristics</div>
        <div class="exp-item" onclick="loadExp(9)">9. Zener Diode as Regulator</div>
        <div class="exp-item" onclick="loadExp(10)">10. Logic Gates Verification</div>
    </div>

    <div class="main-content">
        
        <div class="tabs">
            <div class="tab-btn tab-active" onclick="switchTab('theory')">Theory</div>
            <div class="tab-btn" onclick="switchTab('procedure')">Procedure</div>
            <div class="tab-btn" onclick="switchTab('simulation')">Simulation</div>
            <div class="tab-btn" onclick="switchTab('quiz')">Self Evaluation</div>
        </div>

        <div id="theory" class="panel panel-active">
            <h2 id="theory-title">Refraction through Glass Slab</h2>
            <p><strong>Objective:</strong> To determine the refractive index of a glass slab using a traveling microscope.</p>
            <p><strong>Principle:</strong> When a light ray passes from a rarer medium (air) to a denser medium (glass), it bends towards the normal. The refractive index ($\mu$) is given by Snell's Law:</p>
            <div class="formula">$$ \mu = \frac{\sin i}{\sin r} $$</div>
            <p>Also, the lateral shift ($d$) depends on the thickness ($t$) of the slab and the angle of incidence ($i$):</p>
            <div class="formula">$$ d = \frac{t \sin(i-r)}{\cos r} $$</div>
            
        </div>

        <div id="procedure" class="panel">
            <h2>Step-by-Step Procedure</h2>
            <ol>
                <li>Fix a white sheet on a drawing board.</li>
                <li>Place the rectangular glass slab in the center and mark its boundary.</li>
                <li>Draw a normal line and measure an angle of incidence ($i$) (e.g., 30°, 45°).</li>
                <li>Direct a laser beam along the incident line.</li>
                <li>Mark the emergent ray on the other side of the slab.</li>
                <li>Remove the slab and join the points to trace the path of light.</li>
                <li>Measure the angle of refraction ($r$) and calculate $\mu$.</li>
            </ol>
            <p><strong>Precautions:</strong> Ensure the glass slab is clean. The angle of incidence should be between 30° and 60° for best results.</p>
        </div>

        <div id="simulation" class="panel" style="padding:0; overflow:hidden;">
            <div class="sim-container">
                <div class="sim-canvas-box">
                    <canvas id="simCanvas" width="800" height="500"></canvas>
                    <div style="position:absolute; top:10px; left:10px; color:#0f0; font-family:monospace;" id="sim-readout">DATA: NULL</div>
                </div>
                <div class="sim-controls">
                    <h3>Controls</h3>
                    
                    <div class="ctrl-group">
                        <label>Angle of Incidence ($i$): <span id="val_angle">45°</span></label>
                        <input type="range" id="slider_angle" min="0" max="90" value="45" oninput="updateSim()">
                    </div>

                    <div class="ctrl-group">
                        <label>Refractive Index ($\mu$): <span id="val_ri">1.5</span></label>
                        <input type="range" id="slider_ri" min="1" max="2.5" step="0.1" value="1.5" oninput="updateSim()">
                    </div>

                    <div class="ctrl-group">
                        <label>Slab Thickness ($t$): <span id="val_th">10cm</span></label>
                        <input type="range" id="slider_th" min="5" max="20" value="10" oninput="updateSim()">
                    </div>

                    <hr>
                    <button class="run-btn" onclick="saveData()">SAVE READING</button>
                    <button class="run-btn" style="background:#444;" onclick="resetSim()">RESET</button>
                </div>
            </div>
        </div>

        <div id="quiz" class="panel">
            <h2>Self Evaluation</h2>
            
            <div class="quiz-q">
                <p><strong>Q1. If the refractive index of glass is 1.5, what is the critical angle?</strong></p>
                <label class="q-opt"><input type="radio" name="q1" value="0"> 45°</label>
                <label class="q-opt"><input type="radio" name="q1" value="1"> 41.8°</label>
                <label class="q-opt"><input type="radio" name="q1" value="0"> 90°</label>
            </div>

            <div class="quiz-q">
                <p><strong>Q2. Lateral shift is maximum when the angle of incidence is:</strong></p>
                <label class="q-opt"><input type="radio" name="q2" value="0"> 0°</label>
                <label class="q-opt"><input type="radio" name="q2" value="0"> 45°</label>
                <label class="q-opt"><input type="radio" name="q2" value="1"> 90°</label>
            </div>

            <button class="run-btn" style="width:200px;" onclick="submitQuiz()">SUBMIT QUIZ</button>
        </div>

    </div>
</div>

<script>
    // --- TAB SWITCHER ---
    function switchTab(tabName) {
        // Hide all panels
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('panel-active'));
        // Deactivate all buttons
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('tab-active'));
        
        // Activate correct one
        document.getElementById(tabName).classList.add('panel-active');
        // Find button (simple approximate match)
        event.target.classList.add('tab-active');
    }

    // --- PHYSICS ENGINE (CANVAS) ---
    const canvas = document.getElementById('simCanvas');
    const ctx = canvas.getContext('2d');
    let currentExpId = 1;

    function updateSim() {
        ctx.fillStyle = "#000"; // Clear screen
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Grid Lines
        ctx.strokeStyle = "#222";
        ctx.beginPath();
        for(let i=0; i<canvas.width; i+=40) { ctx.moveTo(i,0); ctx.lineTo(i,canvas.height); }
        for(let i=0; i<canvas.height; i+=40) { ctx.moveTo(0,i); ctx.lineTo(canvas.width,i); }
        ctx.stroke();

        // Get Input Values
        let i_deg = parseInt(document.getElementById('slider_angle').value);
        let mu = parseFloat(document.getElementById('slider_ri').value);
        let thickness = parseInt(document.getElementById('slider_th').value) * 5; // Scale up pixels

        // Update Labels
        document.getElementById('val_angle').innerText = i_deg + "°";
        document.getElementById('val_ri').innerText = mu;
        document.getElementById('val_th').innerText = (thickness/5) + "cm";

        // Logic
        if(currentExpId === 1) drawSlab(i_deg, mu, thickness);
        else if(currentExpId === 2) drawPrism(i_deg, mu);
        // ... Add more exp logic here
    }

    function drawSlab(i_deg, mu, t) {
        let i_rad = i_deg * Math.PI / 180;
        let r_rad = Math.asin(Math.sin(i_rad) / mu);
        
        // Draw Slab
        let startX = 300, startY = 150;
        let width = 200;
        
        ctx.strokeStyle = "#88ccff"; ctx.lineWidth = 2;
        ctx.strokeRect(startX, startY, width, t);
        ctx.fillStyle = "rgba(136, 204, 255, 0.2)";
        ctx.fillRect(startX, startY, width, t);

        // Ray 1: Incident
        let rayLen = 150;
        let incX = startX; // Hit left edge top? No, let's hit top surface
        // Better: Hit top surface center
        let hitX = startX + width/2;
        let hitY = startY;
        
        let srcX = hitX - (rayLen * Math.sin(i_rad));
        let srcY = hitY - (rayLen * Math.cos(i_rad));

        ctx.strokeStyle = "#ff0000"; ctx.lineWidth = 3;
        ctx.beginPath(); ctx.moveTo(srcX, srcY); ctx.lineTo(hitX, hitY); ctx.stroke();

        // Normal
        ctx.strokeStyle = "#fff"; ctx.setLineDash([5,5]); ctx.lineWidth=1;
        ctx.beginPath(); ctx.moveTo(hitX, hitY-50); ctx.lineTo(hitX, hitY+50); ctx.stroke(); ctx.setLineDash([]);

        // Ray 2: Refracted (Inside)
        let exitY = hitY + t;
        let driftX = t * Math.tan(r_rad);
        let exitX = hitX + driftX;

        ctx.strokeStyle = "#ff0000"; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.moveTo(hitX, hitY); ctx.lineTo(exitX, exitY); ctx.stroke();

        // Ray 3: Emergent
        let outX = exitX + (rayLen * Math.sin(i_rad));
        let outY = exitY + (rayLen * Math.cos(i_rad));
        
        ctx.beginPath(); ctx.moveTo(exitX, exitY); ctx.lineTo(outX, outY); ctx.stroke();

        // Data Update
        let shift = t * Math.sin(i_rad - r_rad) / Math.cos(r_rad);
        document.getElementById('sim-readout').innerText = 
            `INCIDENCE: ${i_deg}° | REFRACTION: ${(r_rad*180/Math.PI).toFixed(2)}° | LATERAL SHIFT: ${(shift/5).toFixed(2)}cm`;
    }

    function drawPrism(i_deg, mu) {
        // Simple Prism Logic Placeholder
        ctx.strokeStyle = "#fff"; ctx.lineWidth=2;
        ctx.beginPath(); ctx.moveTo(400,100); ctx.lineTo(300,300); ctx.lineTo(500,300); ctx.closePath(); ctx.stroke();
        
        // Ray
        ctx.strokeStyle = "#ff0000";
        ctx.beginPath(); ctx.moveTo(100, 200); ctx.lineTo(330, 200); ctx.lineTo(470, 250); ctx.lineTo(700, 400); ctx.stroke();
        document.getElementById('sim-readout').innerText = "DISPERSION MODE ACTIVE";
    }

    // --- EXPERIMENT LOADER ---
    function loadExp(id) {
        currentExpId = id;
        document.querySelectorAll('.exp-item').forEach(e => e.classList.remove('exp-active'));
        event.target.classList.add('exp-active');
        
        // Update Title
        const titles = {
            1: "Refraction through Glass Slab",
            2: "Dispersion through Prism",
            3: "Focal Length of Convex Lens"
        };
        document.getElementById('theory-title').innerText = titles[id] || "Experiment " + id;
        
        // Reset View
        switchTab('theory');
        updateSim();
    }

    // --- DB SAVE ---
    function saveData() {
        const data = document.getElementById('sim-readout').innerText;
        alert("Data Recorded: " + data);
        // AJAX to PHP would go here
    }

    function submitQuiz() {
        alert("Quiz Submitted. Score recorded in database.");
    }

    // Init
    updateSim();

</script>
</body>
</html>