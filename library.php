<?php
/**
 * VIDYAVERSE: NEBULA VOICE COMMANDER (PREMIUM EDITION)
 * Features:
 * - Full Voice Automation (Open/Play/Filter)
 * - Internal Structured Database
 * - Cinematic Sci-Fi UI/UX
 */

// --- INTERNAL DATABASE (Simulated) ---
$library_db = [
    [
        "id" => 1,
        "title" => "Quantum Physics",
        "keywords" => "quantum, physics, science",
        "author" => "Max Planck",
        "type" => "ebook",
        "stage" => "04",
        "color" => "#00e5ff",
        "link" => "https://ia800200.us.archive.org/11/items/Quantentheorie/Planck_Quantentheorie.pdf"
    ],
    [
        "id" => 2,
        "title" => "The Future of AI",
        "keywords" => "future, ai, intelligence",
        "author" => "Lex Fridman",
        "type" => "audio",
        "stage" => "05",
        "color" => "#b026ff",
        "link" => "https://ia800300.us.archive.org/1/items/cd_technology_various-artists/disc1/01.mp3"
    ],
    [
        "id" => 3,
        "title" => "NASA Robotics",
        "keywords" => "nasa, robot, space",
        "author" => "NASA Tech",
        "type" => "ebook",
        "stage" => "06",
        "color" => "#00e5ff",
        "link" => "https://ntrs.nasa.gov/api/citations/20040081829/downloads/20040081829.pdf"
    ],
    [
        "id" => 4,
        "title" => "History of Philosophy",
        "keywords" => "history, philosophy, sapiens",
        "author" => "Will Durant",
        "type" => "audio",
        "stage" => "08",
        "color" => "#b026ff",
        "link" => "https://ia802606.us.archive.org/16/items/history_of_modern_philosophy_1703_librivox/historymodernphilosophy_01_benn_128kb.mp3"
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VIDYAVERSE | NEBULA COMMANDER</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Orbitron:wght@500;700;900&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { 
            --core: #00e5ff; 
            --plasma: #b026ff; 
            --bg-dark: #05050a; 
            --glass-bg: rgba(15, 15, 25, 0.65);
            --glass-border: rgba(0, 229, 255, 0.15);
            --text-muted: #8b9bb4;
        }

        body { 
            margin: 0; background: var(--bg-dark); color: #fff; 
            font-family: 'Outfit', sans-serif; overflow: hidden; height: 100vh; 
        }
        
        /* --- HIGH-END CINEMATIC BACKGROUND --- */
        .bg-layer { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; 
            background: radial-gradient(circle at top right, #1a0b2e 0%, #05050a 60%),
                        radial-gradient(circle at bottom left, #021a24 0%, transparent 50%);
        }
        /* Cyber Texture Overlay */
        .bg-layer::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(0, 229, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 229, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -1;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
            -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }
        
        .floating-item { position: absolute; opacity: 0.15; color: var(--core); filter: blur(1px); transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); }

        /* --- LAYOUT --- */
        .app-container { display: grid; grid-template-columns: 320px 1fr; height: 100vh; padding: 25px; gap: 30px; box-sizing: border-box; }
        
        .glass-panel { 
            background: var(--glass-bg); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border); border-top: 1px solid rgba(255,255,255,0.2); border-left: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px; padding: 35px; display: flex; flex-direction: column; 
            box-shadow: 0 30px 60px rgba(0,0,0,0.8), inset 0 0 20px rgba(0, 229, 255, 0.05); 
        }

        /* --- SIDEBAR --- */
        .logo { font-family: 'Orbitron'; font-size: 26px; color: var(--core); text-align: center; margin-bottom: 40px; font-weight: 900; letter-spacing: 3px; text-shadow: 0 0 20px rgba(0, 229, 255, 0.6); }
        .logo span { color: #fff; text-shadow: none; }

        .nav-item { 
            padding: 14px 20px; margin-bottom: 12px; border-radius: 12px; cursor: pointer; 
            color: var(--text-muted); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;
            border: 1px solid transparent;
        }
        .nav-item:hover { color: #fff; background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.05); transform: translateX(5px); }
        .nav-active { 
            background: linear-gradient(90deg, rgba(0, 229, 255, 0.15) 0%, transparent 100%); 
            color: var(--core) !important; border-left: 3px solid var(--core) !important;
            box-shadow: inset 20px 0 20px -20px rgba(0, 229, 255, 0.5);
        }
        
        /* --- PREMIUM AI HUB BUTTON --- */
        .ai-hub { 
            background: linear-gradient(135deg, rgba(15, 15, 25, 0.9), rgba(5, 5, 10, 0.9)); 
            border: 1px solid rgba(0, 229, 255, 0.3); border-radius: 20px; padding: 25px; text-align: center; 
            margin-bottom: 50px; cursor: pointer; transition: 0.4s; position: relative; overflow: hidden; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5), inset 0 0 15px rgba(0, 229, 255, 0.1);
        }
        .ai-hub::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: conic-gradient(transparent, rgba(0, 229, 255, 0.3), transparent 30%);
            animation: rotate-glow 4s linear infinite; opacity: 0; transition: 0.4s;
        }
        @keyframes rotate-glow { 100% { transform: rotate(360deg); } }
        .ai-hub:hover { border-color: var(--core); box-shadow: 0 0 40px rgba(0, 229, 255, 0.3), inset 0 0 30px rgba(0, 229, 255, 0.2); transform: translateY(-3px); }
        .ai-hub:hover::before { opacity: 1; }
        
        .ai-hub-content { position: relative; z-index: 2; }
        .ai-wave { display: flex; gap: 6px; height: 20px; justify-content: center; align-items: flex-end; margin-top: 15px; }
        .ai-bar { width: 4px; height: 20%; background: var(--core); border-radius: 4px; box-shadow: 0 0 10px var(--core); }
        .active-pulse { animation: bar-pulse 0.5s ease-in-out infinite alternate; background: #fff; box-shadow: 0 0 15px #fff; }
        @keyframes bar-pulse { 0% { height: 20%; } 100% { height: 100%; } }

        /* --- MAIN CONTENT AREA --- */
        .main-view { display: flex; flex-direction: column; gap: 30px; overflow-y: auto; padding-right: 15px; padding-bottom: 100px; }
        .main-view::-webkit-scrollbar { width: 6px; }
        .main-view::-webkit-scrollbar-thumb { background: rgba(0, 229, 255, 0.3); border-radius: 10px; }
        
        .top-bar { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 20px; }
        .page-title { font-family: 'Orbitron'; font-size: 32px; margin: 0; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: #fff; }
        .page-subtitle { font-family: 'Share Tech Mono'; font-size: 12px; color: var(--core); letter-spacing: 3px; display: block; margin-top: 5px; }
        
        .search-wrapper { position: relative; }
        .search-wrapper i { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px; }
        .search-bar { 
            background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 50px; padding: 14px 20px 14px 45px; width: 350px; color: white; outline: none; 
            font-family: 'Outfit'; font-size: 14px; transition: 0.3s;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.5);
        }
        .search-bar:focus { border-color: var(--core); box-shadow: 0 0 20px rgba(0, 229, 255, 0.2), inset 0 2px 5px rgba(0,0,0,0.5); }
        .search-bar::placeholder { color: #5a6b82; }
        
        /* --- PREMIUM CARDS --- */
        .content-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
        
        .book-card { 
            background: linear-gradient(180deg, rgba(30,30,45,0.6) 0%, rgba(15,15,25,0.8) 100%);
            border: 1px solid rgba(255,255,255,0.05); border-top: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px; padding: 30px; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            position: relative; overflow: hidden; backdrop-filter: blur(10px);
            display: flex; flex-direction: column; justify-content: space-between; min-height: 240px;
        }
        .book-card::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            transform: skewX(-20deg); transition: 0.5s;
        }
        .book-card:hover { transform: translateY(-10px); border-color: var(--core); box-shadow: 0 20px 40px rgba(0,0,0,0.6), 0 0 20px rgba(0, 229, 255, 0.15); }
        .book-card:hover::before { left: 200%; }
        
        .card-stage { font-family: 'Share Tech Mono'; font-size: 11px; padding: 4px 10px; border-radius: 4px; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); position: absolute; top: 25px; right: 25px; letter-spacing: 1px; }
        .card-icon { font-size: 40px; margin-bottom: 20px; filter: drop-shadow(0 0 10px currentColor); }
        .card-title { font-weight: 700; font-size: 20px; margin-bottom: 8px; line-height: 1.3; }
        .card-author { font-size: 13px; color: var(--text-muted); font-weight: 300; }
        
        .btn-engage { 
            display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; 
            padding: 14px; margin-top: 25px; border-radius: 12px; background: transparent; 
            font-family: 'Orbitron'; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; 
            text-transform: uppercase; letter-spacing: 2px; text-decoration: none;
        }
        
        /* Dynamic Button Themes based on item type */
        .btn-ebook { border: 1px solid var(--core); color: var(--core); }
        .btn-ebook:hover { background: var(--core); color: #000; box-shadow: 0 0 20px rgba(0, 229, 255, 0.5); }
        
        .btn-audio { border: 1px solid var(--plasma); color: var(--plasma); }
        .btn-audio:hover { background: var(--plasma); color: #fff; box-shadow: 0 0 20px rgba(176, 38, 255, 0.5); }

        /* --- CINEMATIC AUDIO PLAYER --- */
        .glass-player { 
            position: fixed; bottom: 40px; right: 40px; width: 400px; 
            background: rgba(5, 5, 10, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--plasma); border-top: 1px solid rgba(255,255,255,0.2);
            border-radius: 24px; padding: 25px; display: none; align-items: center; gap: 20px; z-index: 1000; 
            box-shadow: 0 30px 60px rgba(0,0,0,0.9), 0 0 30px rgba(176, 38, 255, 0.2); 
            transform: translateY(100px); opacity: 0; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .glass-player.show { transform: translateY(0); opacity: 1; }
        
        .play-btn { 
            width: 50px; height: 50px; background: linear-gradient(135deg, var(--plasma), #6a0dad); 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; 
            color: white; font-size: 18px; box-shadow: 0 0 20px rgba(176, 38, 255, 0.6); transition: 0.2s;
        }
        .play-btn:hover { transform: scale(1.1); }
        .player-info { flex-grow: 1; overflow: hidden; }
        .player-label { font-family: 'Share Tech Mono'; font-size: 10px; color: var(--plasma); letter-spacing: 2px; margin-bottom: 4px; }
        .player-title { font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: 'Orbitron'; }
        
        .close-player { 
            width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.05); color: var(--text-muted); cursor: pointer; transition: 0.2s;
        }
        .close-player:hover { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>

    <div class="bg-layer" id="bgLayer"></div>

    <div class="app-container">
        
        <!-- SIDEBAR -->
        <div class="glass-panel">
            <div class="logo">VIDYA<span>VERSE</span></div>
            
            <div class="ai-hub" onclick="startAI()">
                <div class="ai-hub-content">
                    <div style="font-family:'Orbitron'; font-size:14px; font-weight:800; color:#fff; letter-spacing: 1px;">NEBULA CORE</div>
                    <div class="ai-wave">
                        <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                    </div>
                    <div style="font-size:11px; margin-top:12px; color:var(--core); font-family:'Share Tech Mono'; letter-spacing: 2px;" id="aiStatus">SYSTEM STANDBY</div>
                </div>
            </div>

            <nav>
                <div style="font-size:10px; font-weight:800; color:#4a5568; margin-bottom:15px; font-family:'Orbitron'; letter-spacing:2px;">DATA MODULES</div>
                <div class="nav-item nav-active" id="nav-all" onclick="filterCategory('all')"><i class="fas fa-layer-group"></i> Master Archive</div>
                <div class="nav-item" id="nav-ebook" onclick="filterCategory('ebook')"><i class="fas fa-file-pdf"></i> Text Protocols</div>
                <div class="nav-item" id="nav-audio" onclick="filterCategory('audio')"><i class="fas fa-headphones"></i> Audio Frequencies</div>
            </nav>
        </div>

        <!-- MAIN VIEW -->
        <div class="main-view">
            
            <div class="top-bar">
                <div>
                    <h2 class="page-title">Infinite Shelf</h2>
                    <span class="page-subtitle">SECURE DATA REPOSITORY</span>
                </div>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-bar" id="searchInput" placeholder="Query databanks..." onkeyup="filterLib(this.value)">
                </div>
            </div>

            <div class="content-grid">
                <?php foreach($library_db as $b): ?>
                <div class="book-card" 
                     data-title="<?php echo strtolower($b['title']); ?>" 
                     data-keywords="<?php echo $b['keywords']; ?>"
                     data-type="<?php echo $b['type']; ?>">
                    
                    <div>
                        <div class="card-stage" style="color:<?php echo $b['color']; ?>;">PHASE <?php echo $b['stage']; ?></div>
                        <div class="card-icon" style="color:<?php echo $b['color']; ?>;">
                            <i class="fas <?php echo $b['type']=='ebook'?'fa-book-open':'fa-waveform'; ?>"></i>
                        </div>
                        <div class="card-title"><?php echo $b['title']; ?></div>
                        <div class="card-author"><i class="fas fa-user-astronaut" style="margin-right:5px; opacity:0.5;"></i> <?php echo $b['author']; ?></div>
                    </div>
                    
                    <?php if($b['type'] == 'ebook'): ?>
                        <a href="<?php echo $b['link']; ?>" target="_blank" class="btn-engage btn-ebook"><i class="fas fa-external-link-alt"></i> INITIALIZE TEXT</a>
                    <?php else: ?>
                        <div class="btn-engage btn-audio" onclick="playAudio('<?php echo $b['link']; ?>', '<?php echo addslashes($b['title']); ?>')"><i class="fas fa-play"></i> STREAM AUDIO</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
        </div>
    </div>

    <!-- AUDIO PLAYER -->
    <div class="glass-player" id="player">
        <div class="play-btn" onclick="togglePlay()" id="pBtn"><i class="fas fa-pause"></i></div>
        <div class="player-info">
            <div class="player-label">LIVE STREAM ACTIVE</div>
            <div id="pTitle" class="player-title">Track Title</div>
        </div>
        <div class="close-player" onclick="closePlayer()"><i class="fas fa-times"></i></div>
        <audio id="audioNode"></audio>
    </div>

    <script>
        // --- 1. CINEMATIC 3D BACKGROUND ---
        const bg = document.getElementById('bgLayer');
        const icons = ['fa-microchip', 'fa-satellite', 'fa-atom', 'fa-code-branch', 'fa-wave-square'];
        for(let i=0; i<20; i++) {
            let el = document.createElement('i');
            el.className = `fas ${icons[Math.floor(Math.random()*icons.length)]} floating-item`;
            el.style.left = Math.random()*100 + '%';
            el.style.top = Math.random()*100 + '%';
            el.style.fontSize = (Math.random()*40 + 10) + 'px';
            el.style.opacity = Math.random() * 0.15 + 0.05;
            bg.appendChild(el);
        }
        document.addEventListener('mousemove', (e) => {
            const x = (e.clientX / window.innerWidth - 0.5) * 40;
            const y = (e.clientY / window.innerHeight - 0.5) * 40;
            document.querySelectorAll('.floating-item').forEach((el, i) => {
                const speed = (i % 3) + 1;
                el.style.transform = `translate(${x * speed}px, ${y * speed}px)`;
            });
        });

        // --- 2. FILTERING LOGIC ---
        function filterCategory(type) {
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('nav-active'));
            if(type=='all') document.getElementById('nav-all').classList.add('nav-active');
            if(type=='ebook') document.getElementById('nav-ebook').classList.add('nav-active');
            if(type=='audio') document.getElementById('nav-audio').classList.add('nav-active');

            document.querySelectorAll('.book-card').forEach(card => {
                if (type === 'all' || card.dataset.type === type) {
                    card.style.display = 'flex';
                    // Optional: Add a slight animation re-trigger here
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function filterLib(q) {
            document.querySelectorAll('.book-card').forEach(c => {
                const text = c.dataset.title + " " + c.dataset.keywords;
                c.style.display = text.includes(q.toLowerCase()) ? 'flex' : 'none';
            });
        }

        // --- 3. PREMIUM AUDIO PLAYER ---
        const audio = document.getElementById('audioNode');
        const playerUI = document.getElementById('player');
        
        function playAudio(link, title) {
            playerUI.style.display = 'flex';
            setTimeout(() => playerUI.classList.add('show'), 10);
            
            document.getElementById('pTitle').innerText = title;
            audio.src = link;
            audio.play();
            document.getElementById('pBtn').innerHTML = '<i class="fas fa-pause"></i>';
        }
        
        function togglePlay() { 
            const btn = document.getElementById('pBtn');
            if(audio.paused) { audio.play(); btn.innerHTML = '<i class="fas fa-pause"></i>'; } 
            else { audio.pause(); btn.innerHTML = '<i class="fas fa-play"></i>'; }
        }
        
        function closePlayer() { 
            audio.pause(); 
            playerUI.classList.remove('show');
            setTimeout(() => playerUI.style.display = 'none', 400); 
        }

        // --- 4. NEURAL VOICE AUTOMATION ---
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        let recognition = null;
        if(SpeechRecognition) {
            recognition = new SpeechRecognition();
            recognition.continuous = false;
        }
        const synth = window.speechSynthesis;
        let pulseTimer;

        function startAI() {
            if(!recognition) { alert("Voice API not supported in this browser."); return; }
            try {
                recognition.start();
                activatePulse("LISTENING...");
                playBeep();
            } catch(e) { console.log("AI already active"); }
        }

        if(recognition) {
            recognition.onresult = (e) => {
                const cmd = e.results[0][0].transcript.toLowerCase();
                console.log("VOICE CMD:", cmd);

                // A. Filters
                if(cmd.includes("ebook") || cmd.includes("book") || cmd.includes("text")) {
                    filterCategory('ebook');
                    speak("Filtering textual archives.");
                }
                else if(cmd.includes("audio") || cmd.includes("sound") || cmd.includes("frequency")) {
                    filterCategory('audio');
                    speak("Accessing audio frequencies.");
                }
                // B. Open
                else if(cmd.includes("open") || cmd.includes("read")) {
                    const keyword = cmd.replace("open", "").replace("read", "").trim();
                    triggerAction('ebook', keyword);
                }
                // C. Play
                else if(cmd.includes("play") || cmd.includes("stream")) {
                    const keyword = cmd.replace("play", "").replace("stream", "").trim();
                    triggerAction('audio', keyword);
                }
                else {
                    speak("Command sequence unrecognizable.");
                }
            };

            recognition.onerror = () => deactivatePulse();
            recognition.onend = () => deactivatePulse();
        }

        function triggerAction(type, keyword) {
            let found = false;
            document.querySelectorAll('.book-card').forEach(card => {
                if(found) return; 
                const title = card.dataset.title;
                const keys = card.dataset.keywords;
                const cardType = card.dataset.type;

                if((title.includes(keyword) || keys.includes(keyword)) && cardType === type) {
                    found = true;
                    speak((type === 'ebook' ? "Initializing " : "Streaming ") + title);
                    
                    const btn = card.querySelector('.btn-engage');
                    setTimeout(() => {
                        if(type === 'ebook') window.open(btn.href, '_blank');
                        else btn.click();
                    }, 1500); // Wait for speech to finish before executing
                }
            });
            if(!found) speak("Data node not found in current sector.");
        }

        function speak(text) {
            const utter = new SpeechSynthesisUtterance(text);
            utter.rate = 1.1; // Slightly faster for AI feel
            utter.pitch = 0.9;
            utter.onstart = () => activatePulse("TRANSMITTING...");
            utter.onend = () => {
                activatePulse("SYSTEM STANDBY");
                clearTimeout(pulseTimer);
                pulseTimer = setTimeout(() => deactivatePulse(), 2000);
            };
            synth.speak(utter);
        }

        function activatePulse(txt) {
            document.querySelectorAll('.ai-bar').forEach(b => b.classList.add('active-pulse'));
            document.getElementById('aiStatus').innerText = txt;
            document.getElementById('aiStatus').style.color = "#fff";
        }

        function deactivatePulse() {
            document.querySelectorAll('.ai-bar').forEach(b => b.classList.remove('active-pulse'));
            document.getElementById('aiStatus').innerText = "SYSTEM STANDBY";
            document.getElementById('aiStatus').style.color = "var(--core)";
        }

        // Optional UI Beep for sci-fi feel
        function playBeep() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.type = "sine"; osc.frequency.setValueAtTime(800, ctx.currentTime);
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1);
            osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.1);
        }
    </script>
</body>
</html>