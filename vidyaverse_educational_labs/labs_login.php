<!DOCTYPE html>
<html lang="en">
<head>
    <title>VIDYAVERSE | Educational Labs</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --success: #4cc9f0;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --gradient: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(67, 97, 238, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(247, 37, 133, 0.1) 0%, transparent 50%);
        }
        
        .login-container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            height: 700px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-left {
            flex: 1;
            background: var(--gradient);
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }
        
        .brand-logo {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .brand-logo i {
            color: var(--accent);
            background: white;
            padding: 15px;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .tagline {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .features {
            display: flex;
            flex-direction: column;
            gap: 25px;
            margin-top: 40px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 16px;
        }
        
        .feature-item i {
            background: rgba(255, 255, 255, 0.2);
            padding: 12px;
            border-radius: 12px;
            font-size: 20px;
        }
        
        .login-right {
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-form {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .form-header h2 {
            font-size: 32px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #666;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
            font-size: 14px;
        }
        
        .input-group {
            position: relative;
        }
        
        .form-control {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .education-level {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 25px 0;
        }
        
        .level-card {
            padding: 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .level-card:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
        }
        
        .level-card.active {
            border-color: var(--primary);
            background: rgba(67, 97, 238, 0.05);
        }
        
        .level-card i {
            font-size: 32px;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .btn-login {
            width: 100%;
            padding: 18px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e5e9;
        }
        
        .divider span {
            background: white;
            padding: 0 20px;
            color: #666;
            font-size: 14px;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }
        
        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                height: auto;
                max-width: 500px;
            }
            
            .login-left {
                padding: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="brand-logo">
                <i class="fas fa-flask"></i>
                VIDYAVERSE LABS
            </div>
            <div class="tagline">
                Immersive Virtual Laboratories for Modern Education. 
                Experience interactive simulations, real-time experiments, 
                and AI-assisted learning across all subjects.
            </div>
            
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-vr-cardboard"></i>
                    <span>300+ Interactive 3D Simulations</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-robot"></i>
                    <span>AI-Powered Learning Assistant</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Real-time Progress Analytics</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Curriculum-Aligned Content</span>
                </div>
            </div>
        </div>
        
        <div class="login-right">
            <form class="login-form" method="POST" action="labs_dashboard.php">
                <div class="form-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to access your virtual laboratories</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Username or Email</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="username" placeholder="Enter username or email" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select Education Level</label>
                    <div class="education-level">
                        <div class="level-card active" onclick="selectLevel('school')">
                            <i class="fas fa-school"></i>
                            <div>School Level</div>
                            <small>K-12 Curriculum</small>
                        </div>
                        <div class="level-card" onclick="selectLevel('college')">
                            <i class="fas fa-university"></i>
                            <div>College Level</div>
                            <small>Engineering & Science</small>
                        </div>
                    </div>
                    <input type="hidden" name="education_level" id="eduLevel" value="school">
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Access Virtual Labs
                </button>
                
                <div class="register-link">
                    Don't have an account? <a href="#">Register here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectLevel(level) {
            document.querySelectorAll('.level-card').forEach(card => {
                card.classList.remove('active');
            });
            event.target.closest('.level-card').classList.add('active');
            document.getElementById('eduLevel').value = level;
        }
    </script>
</body>
</html>