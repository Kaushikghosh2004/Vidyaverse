<?php
session_start();

// Connect to your lexclassroom database
$servername = "localhost";
$username = "root"; // Change as per your setup
$password = ""; // Change as per your setup
$dbname = "lexclassroom";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $education_level = $_POST['education_level'] ?? 'school';
    
    // Check if user exists in your existing users table
    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password (assuming you have password_hash in your users table)
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
            $_SESSION['education_level'] = $education_level;
            $_SESSION['user_type'] = $user['user_type'] ?? 'student';
            
            // Check if user exists in vlabs_users, if not, create entry
            $check_vlab = "SELECT * FROM vlabs_users WHERE user_id = ?";
            $stmt2 = $conn->prepare($check_vlab);
            $stmt2->bind_param("i", $user['id']);
            $stmt2->execute();
            
            if ($stmt2->get_result()->num_rows == 0) {
                $insert_vlab = "INSERT INTO vlabs_users (user_id, education_level) VALUES (?, ?)";
                $stmt3 = $conn->prepare($insert_vlab);
                $stmt3->bind_param("is", $user['id'], $education_level);
                $stmt3->execute();
            } else {
                // Update education level if changed
                $update_vlab = "UPDATE vlabs_users SET education_level = ? WHERE user_id = ?";
                $stmt4 = $conn->prepare($update_vlab);
                $stmt4->bind_param("si", $education_level, $user['id']);
                $stmt4->execute();
            }
            
            header("Location: labs_dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>LEXCLASSROOM | Virtual Labs</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            padding: 50px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .brand-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .tagline {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
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
        
        .level-toggle {
            display: flex;
            background: #f0f0f0;
            border-radius: 15px;
            padding: 5px;
            margin-bottom: 30px;
        }
        
        .level-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .level-btn.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
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
        
        .error-message {
            background: #ffe6e6;
            color: #d32f2f;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            display: <?php echo isset($error) ? 'block' : 'none'; ?>;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand-header">
            <div class="brand-logo">
                <i class="fas fa-flask"></i>
                LEXCLASSROOM LABS
            </div>
            <div class="tagline">Virtual Laboratory System</div>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Username or Email</label>
                <input type="text" class="form-control" name="username" placeholder="Enter your username or email" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Education Level</label>
                <div class="level-toggle">
                    <button type="button" class="level-btn active" onclick="selectLevel('school')">
                        <i class="fas fa-school"></i> School Level
                    </button>
                    <button type="button" class="level-btn" onclick="selectLevel('college')">
                        <i class="fas fa-university"></i> College Level
                    </button>
                </div>
                <input type="hidden" name="education_level" id="eduLevel" value="school">
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Access Virtual Labs
            </button>
        </form>
    </div>

    <script>
        function selectLevel(level) {
            document.querySelectorAll('.level-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            document.getElementById('eduLevel').value = level;
        }
    </script>
</body>
</html>