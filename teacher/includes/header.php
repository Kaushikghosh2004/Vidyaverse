<?php
// Ensure session is started if not already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Assuming dbconnection.php is already included in the parent file
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">

    <style>
        /* --- 1. HEADER VARIABLES & BASE --- */
        :root {
            --header-h: 70px;
            --bg-dark: #0f172a;
            --header-bg: rgba(15, 23, 42, 0.95);
            --border-color: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --neon-red: #ef4444;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            margin: 0;
            padding-top: var(--header-h); /* Push content down */
            color: var(--text-main);
        }

        /* --- 2. HEADER LAYOUT --- */
        .global-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--header-h);
            background: var(--header-bg);
            backdrop-filter: blur(12px); /* Glass Effect */
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        /* LEFT SIDE: LOGO & TOGGLE */
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .brand-logo {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-logo span { color: var(--accent); }

        .sidebar-toggle-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: 0.2s;
            display: flex; align-items: center;
        }
        .sidebar-toggle-btn:hover { color: #fff; background: rgba(255,255,255,0.05); }

        /* RIGHT SIDE: NOTIFICATIONS & PROFILE */
        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        /* NOTIFICATION BELL */
        .notification-wrapper {
            position: relative;
            cursor: pointer;
        }
        .notification-icon {
            font-size: 20px;
            color: var(--text-muted);
            transition: 0.2s;
        }
        .notification-wrapper:hover .notification-icon { color: #fff; }
        
        .notification-badge {
            position: absolute;
            top: -5px; right: -5px;
            background: var(--neon-red);
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 50%;
            display: none; /* Hidden by default */
            box-shadow: 0 0 5px rgba(239, 68, 68, 0.6);
        }

        /* NOTIFICATION DROPDOWN */
        .notification-dropdown {
            position: absolute;
            top: 150%; right: -10px;
            width: 300px;
            background: #1e293b;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            opacity: 0; visibility: hidden;
            transform: translateY(-10px);
            transition: 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
            overflow: hidden;
            z-index: 1001;
        }
        .notification-wrapper.active .notification-dropdown {
            opacity: 1; visibility: visible;
            transform: translateY(0);
        }
        
        .notif-header {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600; font-size: 13px; color: #fff;
            background: rgba(255,255,255,0.02);
        }
        .notif-list {
            list-style: none; margin: 0; padding: 0;
            max-height: 250px; overflow-y: auto;
        }
        .notif-item {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            font-size: 13px; color: var(--text-muted);
            transition: 0.2s;
            cursor: pointer;
        }
        .notif-item:hover { background: rgba(59, 130, 246, 0.1); color: #fff; }
        .notif-empty { padding: 20px; text-align: center; color: var(--text-muted); font-size: 13px; }


        /* PROFILE DROPDOWN */
        .profile-wrapper {
            position: relative;
            cursor: pointer;
        }

        .profile-trigger {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px 10px;
            border-radius: 8px;
            transition: 0.2s;
        }
        .profile-trigger:hover { background: rgba(255,255,255,0.05); }

        .avatar-img {
            width: 35px; height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
        }
        .user-role {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* PROFILE MENU */
        .profile-menu {
            position: absolute;
            top: 140%; right: 0;
            width: 200px;
            background: #1e293b;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            opacity: 0; visibility: hidden;
            transform: translateY(-10px);
            transition: 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
            overflow: hidden;
            z-index: 1001;
        }
        
        .profile-wrapper:hover .profile-menu {
            opacity: 1; visibility: visible;
            transform: translateY(0);
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        .menu-item:last-child { border-bottom: none; }
        .menu-item:hover {
            background: var(--accent);
            color: #fff;
        }
        .menu-item i { width: 18px; text-align: center; font-size: 16px; }

        /* --- 3. SIDEBAR LOGIC (Hidden by Default) --- */
        .sidebar {
            position: fixed;
            top: var(--header-h); left: calc(var(--sidebar-width) * -1); /* Hidden */
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-h));
            background: #1e293b;
            border-right: 1px solid var(--border-color);
            transition: 0.3s ease;
            z-index: 999;
            overflow-y: auto;
        }
        
        .sidebar.active { left: 0; }

        /* Overlay for Mobile */
        .sidebar-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 998;
            opacity: 0; visibility: hidden; transition: 0.3s;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.active { opacity: 1; visibility: visible; }

    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <header class="global-header">
        
        <div class="header-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">
                <i class="ti-menu"></i>
            </button>
            
            <a href="dashboard.php" class="brand-logo">
                VIDYAVERSE<span>Teacher Section</span>
            </a>
        </div>

        <div class="header-right">
            
            <div class="notification-wrapper" id="notifWrapper">
                <i class="ti-bell notification-icon"></i>
                <span class="notification-badge" id="notification-count">0</span>
                
                <div class="notification-dropdown">
                    <div class="notif-header">Notifications</div>
                    <ul class="notif-list" id="notification-panel">
                        <li class="notif-empty">No New Notifications</li>
                    </ul>
                </div>
            </div>

            <?php
            // Fetch Teacher Data securely
            if(isset($_SESSION['ocastid'])) {
                $tid = $_SESSION['ocastid'];
                $sql = "SELECT * from tblteacher where ID=:tid";
                $query = $dbh->prepare($sql);
                $query->bindParam(':tid', $tid, PDO::PARAM_STR);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);
                
                if ($query->rowCount() > 0) {
                    foreach ($results as $row) { 
                        // Determine Avatar
                        $avatar = (!empty($row->ProfilePic)) ? "../admin/images/".$row->ProfilePic : "../assets/images/avatar/default.png";
            ?>
            <div class="profile-wrapper">
                <div class="profile-trigger">
                    <img src="<?php echo htmlentities($avatar); ?>" alt="User" class="avatar-img">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlentities($row->FirstName); ?></span>
                        <span class="user-role">Teacher</span>
                    </div>
                    <i class="ti-angle-down" style="font-size:10px; color:#94a3b8;"></i>
                </div>

                <div class="profile-menu">
                    <div style="padding:10px 15px; font-size:11px; color:#64748b; border-bottom:1px solid rgba(255,255,255,0.05);">
                        <?php echo htmlentities($row->Email); ?><br>
                        <?php echo htmlentities($row->MobileNumber); ?>
                    </div>
                    <a href="profile.php" class="menu-item">
                        <i class="ti-user"></i> My Profile
                    </a>
                    <a href="change-password.php" class="menu-item">
                        <i class="ti-settings"></i> Settings
                    </a>
                    <a href="logout.php" class="menu-item" style="color: #ef4444;">
                        <i class="ti-power-off"></i> Logout
                    </a>
                </div>
            </div>
            <?php 
                    }
                }
            } 
            ?>
        </div>

    </header>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Sidebar Toggle
            const toggleBtn = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar'); 
            const overlay = document.getElementById('sidebarOverlay');

            function toggleSidebar() {
                if(sidebar) {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                }
            }

            if(toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSidebar();
                });
            }

            if(overlay) {
                overlay.addEventListener('click', toggleSidebar);
            }

            // 2. Notification Toggle (Click to show/hide)
            const notifWrapper = document.getElementById('notifWrapper');
            if(notifWrapper) {
                notifWrapper.addEventListener('click', function(e) {
                    // Only toggle if clicking the bell icon directly
                    if(e.target.closest('.notification-icon') || e.target.closest('.notification-badge')) {
                        this.classList.toggle('active');
                        // Mark as read logic
                        $('#notification-count').hide();
                        $.ajax({ url: 'api_mark_read.php', type: 'POST' });
                    }
                });

                // Close when clicking outside
                document.addEventListener('click', function(e) {
                    if (!notifWrapper.contains(e.target)) {
                        notifWrapper.classList.remove('active');
                    }
                });
            }
        });

        // 3. AJAX Notification Fetcher (Kept from your original code)
        $(document).ready(function() {
            function fetchNotifications() {
                $.ajax({
                    url: 'api_get_notifications.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.unread_count > 0) {
                            $('#notification-count').text(response.unread_count).show();
                        } else {
                            $('#notification-count').hide();
                        }
                        
                        var panel = $('#notification-panel');
                        panel.empty();
                        
                        if (response.notifications.length > 0) {
                            response.notifications.forEach(function(notif) {
                                panel.append(`<li class="notif-item">${notif.message}</li>`);
                            });
                        } else {
                            panel.append('<li class="notif-empty">No New Notifications</li>');
                        }
                    }
                });
            }
            
            fetchNotifications();
            setInterval(fetchNotifications, 30000); 
        });
    </script>
</body>
</html>