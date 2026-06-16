<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'lexclassroom');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $u = $_POST['username'];
    $p = $_POST['password'];
    
    // Simple check (In production, use password_verify)
    $sql = "SELECT * FROM lab_users WHERE username='$u' AND password='$p'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['name'] = $row['full_name'];
        header("Location: vidyaverse_labs_hub.php");
        exit();
    } else {
        $error = "ACCESS DENIED";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>VIDYAVERSE | SECURE LOGIN</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;900&display=swap" rel="stylesheet">
    <style>
        body {
            background: #020205; color: #00f3ff; font-family: 'Orbitron', sans-serif;
            height: 100vh; display: flex; align-items: center; justify-content: center;
            background-image: radial-gradient(circle, #1a0b2e 0%, #000 80%);
        }
        .login-box {
            width: 400px; padding: 40px; border: 2px solid #00f3ff;
            background: rgba(0,0,0,0.8); box-shadow: 0 0 50px rgba(0, 243, 255, 0.2);
            text-align: center;
        }
        input {
            width: 90%; padding: 15px; margin: 10px 0; background: #111; border: 1px solid #333;
            color: white; font-family: 'Orbitron'; text-align: center;
        }
        button {
            width: 100%; padding: 15px; margin-top: 20px; background: #00f3ff; border: none;
            font-weight: bold; cursor: pointer; transition: 0.3s;
        }
        button:hover { background: white; box-shadow: 0 0 30px #00f3ff; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>VIDYAVERSE</h1>
        <p>QUANTUM LAB ACCESS</p>
        <form method="post">
            <input type="text" name="username" placeholder="USER ID" required>
            <input type="password" name="password" placeholder="ACCESS KEY" required>
            <button type="submit">INITIALIZE UPLINK</button>
        </form>
        <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    </div>
</body>
</html>