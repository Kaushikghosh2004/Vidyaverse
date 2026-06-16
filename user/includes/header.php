<?php
// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$uid = $_SESSION['ocasuid'];
?>

<link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    :root {
        --glass-bg: rgba(10, 15, 25, 0.45);
        --glass-border: rgba(255, 255, 255, 0.08);
        --glass-highlight: rgba(255, 255, 255, 0.03);
        --cyber-cyan: #00e5ff;
        --cyber-blue: #3b82f6;
    }

    /* --- HEADER STYLING (AERO-GLASS) --- */
    .header {
        background: var(--glass-bg);
        backdrop-filter: blur(24px) saturate(150%);
        -webkit-backdrop-filter: blur(24px) saturate(150%);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5), inset 0 -1px 0 0 var(--glass-highlight);
        height: 75px;
        position: fixed;
        top: 0; left: 0; right: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 40px;
        border-bottom: 1px solid var(--glass-border);
        transition: all 0.3s ease;
    }

    /* Logo Area */
    .pull-left { display: flex; align-items: center; }
    .logo a { text-decoration: none; display: flex; align-items: center; gap: 12px; }
    .logo span { 
        font-family: 'Orbitron', sans-serif;
        font-weight: 800; font-size: 20px; color: #fff; 
        text-transform: uppercase; letter-spacing: 2px;
        background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        text-shadow: 0 0 20px rgba(0, 229, 255, 0.3);
    }
    .logo i { font-size: 24px; color: var(--cyber-cyan); filter: drop-shadow(0 0 10px rgba(0,229,255,0.5)); }

    /* Right Icons Area */
    .pull-right ul {
        list-style: none !important; margin: 0; padding: 0;
        display: flex; align-items: center; gap: 20px; /* Reduced gap slightly to fit button */
    }
    
    .header-icon { position: relative; cursor: pointer; display: flex; align-items: center; }
    
    /* Notification Bell */
    .bell-wrapper {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--glass-border);
        width: 40px; height: 40px;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        transition: 0.3s;
    }
    .bell-icon { font-size: 18px; color: #cbd5e1; transition: 0.3s; }
    .header-icon:hover .bell-wrapper { background: rgba(0, 229, 255, 0.1); border-color: rgba(0, 229, 255, 0.3); box-shadow: 0 0 15px rgba(0, 229, 255, 0.2); }
    .header-icon:hover .bell-icon { color: var(--cyber-cyan); }
    
    .badge-counter {
        position: absolute; top: -2px; right: -2px;
        background: #ef4444; color: white;
        font-size: 10px; font-weight: 800;
        padding: 3px 6px; border-radius: 50%;
        box-shadow: 0 0 15px rgba(239, 68, 68, 0.8);
        border: 2px solid #0f172a;
        display: none;
    }

    /* --- GLASSMORPHISM DROPDOWNS --- */
    .drop-down {
        display: none;
        position: absolute; top: 65px; right: 0;
        width: 320px;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(25px) saturate(150%);
        -webkit-backdrop-filter: blur(25px) saturate(150%);
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.6), inset 0 0 0 1px var(--glass-highlight);
        border: 1px solid var(--glass-border);
        z-index: 1001; overflow: hidden;
    }
    
    .drop-down.active { display: block; animation: glassFadeIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    @keyframes glassFadeIn { from { opacity:0; transform:translateY(15px) scale(0.95); } to { opacity:1; transform:translateY(0) scale(1); } }

    .dropdown-content-heading {
        padding: 18px 20px; background: rgba(0,0,0,0.3);
        border-bottom: 1px solid var(--glass-border);
        font-weight: 700; font-size: 12px; color: #fff; text-transform: uppercase; letter-spacing: 1px;
    }

    /* FIX: Force vertical list to stop your theme from rendering items horizontally */
    .dropdown-list { list-style: none !important; padding: 0; margin: 0; display: flex !important; flex-direction: column !important; }
    .dropdown-list li { border-bottom: 1px solid rgba(255, 255, 255, 0.03); display: block !important; width: 100%; }
    .dropdown-list li:last-child { border-bottom: none; }

    .dropdown-list li a {
        display: flex; align-items: center; gap: 12px;
        padding: 16px 20px; text-decoration: none; color: #cbd5e1;
        font-size: 13px; font-weight: 500; transition: 0.3s;
        width: 100%; box-sizing: border-box;
    }
    .dropdown-list li a i { font-size: 16px; color: #64748b; transition: 0.3s; }
    .dropdown-list li a:hover { background: rgba(0, 229, 255, 0.05); color: #fff; padding-left: 25px; }
    .dropdown-list li a:hover i { color: var(--cyber-cyan); filter: drop-shadow(0 0 8px var(--cyber-cyan)); }
    
    /* Notification Text */
    .notification-content { width: 100%; }
    .notification-heading { font-size: 13px; color: #e2e8f0; margin-bottom: 6px; line-height: 1.5; font-weight: 500; }
    .notification-timestamp { font-size: 10px; color: var(--cyber-cyan); text-align: right; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; }

    /* Profile Section */
    .profile-toggle {
        display: flex; align-items: center; gap: 12px;
        padding: 6px 16px 6px 6px; border-radius: 30px; 
        background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border);
        transition: 0.3s; box-shadow: inset 0 0 10px rgba(255,255,255,0.01);
    }
    .profile-toggle:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); }

    .user-avatar { font-size: 13px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }
    .avatar-img { 
        width: 36px; height: 36px; border-radius: 50%; object-fit: cover; 
        border: 2px solid var(--cyber-blue); box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);
    }

    /* --- DEDICATED LOGOUT BUTTON --- */
    .btn-header-logout {
        display: flex; align-items: center; gap: 8px;
        padding: 10px 20px; border-radius: 8px;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.4);
        color: #ef4444; text-decoration: none;
        font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;
        transition: 0.3s ease; margin-left: 10px;
    }
    .btn-header-logout:hover {
        background: #ef4444; color: #fff;
        box-shadow: 0 0 20px rgba(239, 68, 68, 0.5);
        transform: translateY(-2px);
    }
</style>

<div class="header">
    
    <div class="pull-left">
        <div class="logo">
            <a href="dashboard.php">
                <i class="fas fa-atom"></i>
                <span>VidyaVerse Student</span>
            </a>
        </div>
    </div>

    <div class="pull-right">
        <ul>
            
            <!-- NOTIFICATIONS -->
            <li class="header-icon">
                <a href="javascript:void(0);" onclick="toggleDropdown('notif-drop')">
                    <div class="bell-wrapper">
                        <i class="fas fa-bell bell-icon"></i>
                    </div>
                    <span class="badge-counter" id="notification-count">0</span>
                </a>
                
                <div class="drop-down" id="notif-drop">
                    <div class="dropdown-content-heading"><i class="fas fa-bell" style="color:var(--cyber-cyan); margin-right:8px;"></i> Notifications</div>
                    <div style="max-height: 320px; overflow-y: auto;">
                        <ul class="dropdown-list" id="notification-panel">
                            <li style="padding:30px; text-align:center; color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:2px;"><i class="fas fa-circle-notch fa-spin"></i> Loading...</li>
                        </ul>
                    </div>
                </div>
            </li>

            <?php
            $sql = "SELECT FullName FROM tbluser where ID=:uid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':uid', $uid, PDO::PARAM_STR);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_OBJ);
            $name = $result ? $result->FullName : 'Student Node';
            ?>
            
            <!-- PROFILE TOGGLE -->
            <li class="header-icon">
                <div class="profile-toggle" onclick="toggleDropdown('profile-drop')">
                    <img class="avatar-img" src="../assets/images/avatar/images (1).png" alt="User" />
                    <span class="user-avatar"><?php echo htmlentities($name); ?></span>
                    <i class="ti-angle-down" style="font-size:10px; color:#94a3b8; font-weight:900;"></i>
                </div>
                
                <!-- Fixed Dropdown Layout -->
                <div class="drop-down" id="profile-drop" style="width: 200px;">
                    <ul class="dropdown-list">
                        <li><a href="profile.php"><i class="ti-user"></i> My Identity</a></li>
                        <li><a href="change-password.php"><i class="ti-key"></i> Security Keys</a></li>
                    </ul>
                </div>
            </li>

            <!-- DIRECT LOGOUT BUTTON (Always Visible) -->
            <li>
                <a href="logout.php" class="btn-header-logout">
                    <i class="fas fa-sign-out-alt"></i> Disconnect
                </a>
            </li>

        </ul>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
<script>
    // 1. Dropdown Toggler
    function toggleDropdown(id) {
        $('.drop-down').not('#' + id).removeClass('active');
        $('#' + id).toggleClass('active');
    }

    // Close when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('.header-icon').length) {
            $('.drop-down').removeClass('active');
        }
    });

    // 2. Notifications Logic
    $(document).ready(function() {
        function loadNotifications() {
            $.ajax({
                url: 'api_get_notifications.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var panel = $('#notification-panel');
                    var badge = $('#notification-count');
                    panel.empty(); 

                    if (response.unread_count > 0) {
                        badge.text(response.unread_count).fadeIn();
                    } else {
                        badge.fadeOut();
                    }

                    if (response.notifications && response.notifications.length > 0) {
                        $.each(response.notifications, function(i, item) {
                            var date = new Date(item.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            var html = `
                                <li>
                                    <a href="javascript:void(0);">
                                        <div class="notification-content">
                                            <div class="notification-heading">${item.message}</div>
                                            <div class="notification-timestamp">${date}</div>
                                        </div>
                                    </a>
                                </li>`;
                            panel.append(html);
                        });
                    } else {
                        panel.html('<li style="padding:30px; text-align:center; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:1px;">No new notifications</li>');
                    }
                }
            });
        }

        $('#notif-drop').parent().click(function() {
            $('#notification-count').fadeOut();
            $.ajax({ url: 'api_mark_read.php', type: 'POST' });
        });

        loadNotifications();
        setInterval(loadNotifications, 10000);
    });
</script>