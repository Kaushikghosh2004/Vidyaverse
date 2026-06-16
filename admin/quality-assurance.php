<?php
session_start();
include('includes/dbconnection.php');
if (empty($_SESSION['admin_id'])) { header('location:logout.php'); exit; }

$teachers = $dbh->query("SELECT ID, FirstName, LastName FROM tblteacher")->fetchAll(PDO::FETCH_OBJ);
$courses = $dbh->query("SELECT ID, CourseName, BranchName FROM tblcourse")->fetchAll(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Analytics HQ | VidyaVerse</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- THEME --- */
        :root {
            --bg-deep: #050505; --bg-card: rgba(30, 41, 59, 0.6);
            --accent-primary: #8b5cf6; --accent-success: #10b981; --accent-warn: #f59e0b;
            --text-main: #ffffff; --text-muted: #94a3b8;
            --font-main: 'Outfit', sans-serif;
        }
        * { box-sizing: border-box; }
        body { 
            background: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #050505 60%);
            color: var(--text-main); font-family: var(--font-main); margin: 0; height: 100vh; display: flex; overflow: hidden;
        }

        /* --- SIDEBAR --- */
        .sidebar-controls {
            width: 350px; background: rgba(15, 23, 42, 0.95);
            border-right: 1px solid rgba(255,255,255,0.1); padding: 40px 30px; 
            display: flex; flex-direction: column; z-index: 50; box-shadow: 10px 0 50px rgba(0,0,0,0.5);
        }
        .brand-title { font-size: 24px; font-weight: 800; margin-bottom: 30px; color: white; }
        
        label { color: var(--text-muted); font-size: 11px; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 8px; }
        select {
            width: 100%; background: rgba(0,0,0,0.3); border: 1px solid #334155; 
            color: white; padding: 15px; border-radius: 12px; margin-bottom: 20px; outline: none;
        }
        .btn-broadcast {
            width: 100%; background: var(--accent-primary); color: white; border: none;
            padding: 18px; border-radius: 12px; font-weight: 700; cursor: pointer;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3); transition: 0.3s;
        }
        .btn-broadcast:hover { transform: translateY(-2px); }
        .btn-stop {
            width: 100%; background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444;
            padding: 15px; border-radius: 12px; font-weight: 700; cursor: pointer; display: none; margin-top: 10px;
        }

        /* --- RIGHT AREA --- */
        .monitor-area { flex-grow: 1; padding: 30px; display: grid; grid-template-rows: auto 1fr; gap: 20px; overflow: hidden; }

        /* BIG STATS ROW */
        .stats-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .stat-card {
            background: var(--bg-card); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px;
            padding: 20px; display: flex; align-items: center; gap: 20px;
        }
        .stat-val { font-size: 36px; font-weight: 800; }
        .stat-label { font-size: 12px; text-transform: uppercase; color: var(--text-muted); }

        /* CONTENT GRID (Graph + Reviews) */
        .content-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; overflow: hidden; height: 100%; }
        
        /* GRAPH ZONE */
        .graph-panel {
            background: var(--bg-card); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px;
            padding: 25px; display: flex; flex-direction: column; position: relative;
        }
        .chart-controls { display: flex; gap: 10px; margin-bottom: 15px; justify-content: flex-end; }
        .chart-btn {
            background: rgba(255,255,255,0.05); border: none; color: var(--text-muted);
            padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;
        }
        .chart-btn.active { background: var(--accent-primary); color: white; }
        
        .chart-wrapper { flex-grow: 1; position: relative; width: 100%; min-height: 0; }

        /* REVIEWS ZONE */
        .reviews-panel {
            background: var(--bg-card); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px;
            padding: 25px; display: flex; flex-direction: column;
        }
        .review-list { overflow-y: auto; flex-grow: 1; padding-right: 5px; }
        .review-item {
            background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; margin-bottom: 10px;
            border-left: 3px solid var(--accent-success); animation: fadeIn 0.3s;
        }
        .stars { color: var(--accent-warn); font-size: 14px; letter-spacing: 2px; }
        .review-text { font-size: 14px; color: #e2e8f0; margin-top: 5px; line-height: 1.4; font-style: italic; }

        /* LOCK SCREEN */
        .lock-overlay {
            position: absolute; top:0; left:0; width:100%; height:100%;
            background: rgba(5,5,5,0.85); backdrop-filter: blur(10px); z-index: 40;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: var(--text-muted);
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <div class="sidebar-controls">
        <div class="brand-title">Analytics HQ</div>
        
        <label>Target Faculty</label>
        <select id="tSelect">
            <option value="">-- Select Professor --</option>
            <?php foreach($teachers as $t) { echo "<option value='{$t->ID}'>{$t->FirstName} {$t->LastName}</option>"; } ?>
        </select>

        <label>Target Batch</label>
        <select id="cSelect">
            <option value="">-- Select Batch --</option>
            <?php foreach($courses as $c) { echo "<option value='{$c->ID}'>{$c->CourseName}</option>"; } ?>
        </select>

        <button id="btnBroadcast" class="btn-broadcast" onclick="startSurvey()">
            <i class='bx bx-radar'></i> START SURVEY
        </button>
        <button id="btnStop" class="btn-stop" onclick="stopSurvey()">STOP SURVEY</button>
        
        <a href="dashboard.php" style="margin-top:auto; color:var(--text-muted); text-decoration:none; text-align:center;">&larr; Exit Console</a>
    </div>

    <div class="monitor-area">
        
        <div id="lockScreen" class="lock-overlay">
            <i class='bx bx-lock-alt' style="font-size:50px; margin-bottom:20px; opacity:0.5;"></i>
            <h2>System Offline</h2>
            <p>Select a target batch to activate analytics.</p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <i class='bx bxs-star stat-icon' style="color:#f59e0b;"></i>
                <div><div id="avgRating" class="stat-val">0.0</div><div class="stat-label">Average Rating</div></div>
            </div>
            <div class="stat-card">
                <i class='bx bxs-group stat-icon' style="color:#10b981;"></i>
                <div><div id="totalVotes" class="stat-val">0</div><div class="stat-label">Live Responses</div></div>
            </div>
        </div>

        <div class="content-grid">
            <div class="graph-panel">
                <div class="chart-controls">
                    <button class="chart-btn active" onclick="setChartType('bar')">BAR</button>
                    <button class="chart-btn" onclick="setChartType('pie')">PIE</button>
                    <button class="chart-btn" onclick="setChartType('doughnut')">DONUT</button>
                    <button class="chart-btn" onclick="setChartType('line')">LINE</button>
                </div>
                <div class="chart-wrapper">
                    <canvas id="liveChart"></canvas>
                </div>
            </div>

            <div class="reviews-panel">
                <h3 style="margin:0 0 15px 0; color:white; font-size:16px;">Student Reviews</h3>
                <div id="reviewList" class="review-list">
                    <div style="text-align:center; color:#64748b; margin-top:50px;">Waiting for reviews...</div>
                </div>
            </div>
        </div>

    </div>

    <script>
        let surveyID = null;
        let poller = null;
        let myChart = null;
        let currentChartType = 'bar';

        // --- CHART JS SETUP ---
        function initChart() {
            const ctx = document.getElementById('liveChart').getContext('2d');
            
            // Destroy if exists (to switch types)
            if(myChart) myChart.destroy();

            myChart = new Chart(ctx, {
                type: currentChartType,
                data: {
                    labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                    datasets: [{
                        label: '# of Students',
                        data: [0, 0, 0, 0, 0],
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.6)',  // Red
                            'rgba(249, 115, 22, 0.6)', // Orange
                            'rgba(234, 179, 8, 0.6)',  // Yellow
                            'rgba(59, 130, 246, 0.6)', // Blue
                            'rgba(16, 185, 129, 0.6)'  // Green
                        ],
                        borderColor: [
                            '#ef4444', '#f97316', '#eab308', '#3b82f6', '#10b981'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: (currentChartType !== 'bar') } }, // Hide legend for bar chart
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            display: (currentChartType === 'bar' || currentChartType === 'line'),
                            grid: { color: 'rgba(255,255,255,0.1)' }
                        },
                        x: { display: (currentChartType === 'bar' || currentChartType === 'line') }
                    }
                }
            });
        }

        function setChartType(type) {
            currentChartType = type;
            $('.chart-btn').removeClass('active');
            event.target.classList.add('active');
            initChart(); // Re-render with existing data logic
            fetchStats(); // Force refresh data
        }

        // --- SURVEY LOGIC ---
        function startSurvey() {
            let tid = $('#tSelect').val();
            let cid = $('#cSelect').val();

            if(!tid || !cid) { alert("Select Teacher & Batch!"); return; }

            $('#btnBroadcast').html('Starting...');
            
            $.post('../includes/survey-handler.php', { action: 'broadcast_survey', teacher_id: tid, course_id: cid }, function(data) {
                surveyID = data.trim();
                activateUI();
            });
        }

        function activateUI() {
            $('#lockScreen').fadeOut();
            $('#btnBroadcast').hide();
            $('#btnStop').show();
            $('#tSelect, #cSelect').prop('disabled', true);
            
            initChart(); // Draw empty chart
            poller = setInterval(fetchStats, 2000);
        }

        function stopSurvey() {
            if(!confirm("Stop Survey?")) return;
            $.post('../includes/survey-handler.php', { action: 'end_survey', survey_id: surveyID }, function() {
                location.reload();
            });
        }

        function fetchStats() {
            if(!surveyID) return;

            $.post('../includes/survey-handler.php', { action: 'get_live_stats', survey_id: surveyID }, function(resp) {
                let data = JSON.parse(resp);
                
                // 1. Update Numbers
                $('#avgRating').text(parseFloat(data.stats.avg_rating || 0).toFixed(1));
                $('#totalVotes').text(data.stats.total_votes);

                // 2. Update Graph
                if(myChart) {
                    myChart.data.datasets[0].data = data.graph_data;
                    myChart.update('none'); // Update without full re-animation
                }

                // 3. Update Reviews
                if(data.comments.length > 0) {
                    let html = "";
                    data.comments.forEach(c => {
                        let stars = "★".repeat(c.Rating);
                        html += `
                            <div class="review-item">
                                <div style="display:flex; justify-content:space-between;">
                                    <span class="stars">${stars}</span>
                                    <span style="font-size:11px; opacity:0.5;">${c.Timestamp.split(' ')[1]}</span>
                                </div>
                                <div class="review-text">"${c.Feedback}"</div>
                            </div>`;
                    });
                    $('#reviewList').html(html);
                }
            });
        }
    </script>
</body>
</html>