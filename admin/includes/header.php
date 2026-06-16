<?php
// Handle Maintenance Mode Toggle (Session-based for Admin)
// In a full production environment, you might want to save this to a 'tblsettings' database table instead.
if (isset($_GET['toggle_maintenance'])) {
    if (isset($_SESSION['maintenance_mode']) && $_SESSION['maintenance_mode'] == 'ON') {
        $_SESSION['maintenance_mode'] = 'OFF';
    } else {
        $_SESSION['maintenance_mode'] = 'ON';
    }
    // Redirect to remove the GET parameter from the URL to prevent accidental toggling on refresh
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Check current status
$maintenanceStatus = isset($_SESSION['maintenance_mode']) ? $_SESSION['maintenance_mode'] : 'OFF';
?>

<style>
    /* Navbar Container */
    .vidya-header {
        background: rgba(10, 15, 30, 0.85);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border-bottom: 1px solid rgba(0, 229, 255, 0.3);
        padding: 15px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
    }

    /* Logo Area */
    .header-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }
    .header-logo span.vidya {
        color: #ffffff;
        font-weight: 800;
        font-size: 22px;
        letter-spacing: 2px;
    }
    .header-logo span.verse {
        color: #00e5ff;
        font-weight: 300;
        font-size: 22px;
        letter-spacing: 2px;
    }

    /* Right Side Controls */
    .header-controls {
        display: flex;
        align-items: center;
        gap: 30px;
    }

    /* Maintenance Mode Toggle UI */
    .maintenance-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(0, 0, 0, 0.4);
        padding: 5px 15px;
        border-radius: 30px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .maintenance-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #cbd5e1;
    }
    .switch {
        position: relative;
        display: inline-block;
        width: 46px;
        height: 24px;
    }
    .switch input { 
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: rgba(255, 255, 255, 0.2);
        transition: .4s;
        border-radius: 34px;
        border: 1px solid rgba(255,255,255,0.3);
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 16px; width: 16px;
        left: 3px; bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0,0,0,0.5);
    }
    input:checked + .slider {
        background-color: rgba(239, 68, 68, 0.8); /* Red when ON */
        border-color: #ef4444;
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
    }
    input:checked + .slider:before {
        transform: translateX(22px);
    }

    /* Admin Dropdown */
    .admin-profile-wrapper {
        position: relative;
        display: inline-block;
    }
    .admin-btn {
        background: transparent;
        border: 1px solid rgba(0, 229, 255, 0.3);
        color: #fff;
        padding: 8px 20px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
    }
    .admin-btn:hover {
        background: rgba(0, 229, 255, 0.1);
        box-shadow: 0 0 15px rgba(0, 229, 255, 0.2);
    }
    .admin-avatar {
        width: 28px;
        height: 28px;
        background: #00e5ff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #000;
        font-weight: 800;
        font-size: 12px;
    }

    /* Dropdown Content */
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        top: 120%;
        background: rgba(15, 23, 42, 0.95);
        min-width: 200px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.6);
        border: 1px solid rgba(0, 229, 255, 0.3);
        border-radius: 12px;
        overflow: hidden;
        z-index: 1001;
        backdrop-filter: blur(15px);
    }
    .admin-profile-wrapper:hover .dropdown-content {
        display: block;
        animation: fadeIn 0.2s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .dropdown-content a {
        color: #cbd5e1;
        padding: 12px 20px;
        text-decoration: none;
        display: block;
        font-size: 13px;
        font-weight: 600;
        transition: background 0.2s;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .dropdown-content a:hover {
        background: rgba(0, 229, 255, 0.15);
        color: #00e5ff;
        padding-left: 25px; /* Slight indent on hover */
    }
    .dropdown-content a.logout-text {
        color: #ef4444;
    }
    .dropdown-content a.logout-text:hover {
        background: rgba(239, 68, 68, 0.1);
        color: #f87171;
    }

    /* Global Maintenance Banner */
    .global-maintenance-banner {
        background: linear-gradient(90deg, #991b1b, #dc2626, #991b1b);
        color: #fff;
        text-align: center;
        padding: 10px;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        box-shadow: 0 4px 15px rgba(220, 38, 38, 0.5);
        animation: pulseBanner 2s infinite;
        position: sticky;
        top: 70px; /* Sits right below the navbar */
        z-index: 999;
    }
    @keyframes pulseBanner {
        0% { opacity: 0.9; }
        50% { opacity: 1; filter: brightness(1.2); }
        100% { opacity: 0.9; }
    }
</style>

<header class="vidya-header">
    <a href="dashboard.php" class="header-logo">
        <span class="vidya">VIDYA</span><span class="verse">VERSE</span>
    </a>

    <div class="header-controls">
        
        <div class="maintenance-wrapper">
            <span class="maintenance-label">Maintenance</span>
            <label class="switch">
                <input type="checkbox" id="maintenanceToggle" <?php if($maintenanceStatus == 'ON') echo 'checked'; ?> onchange="window.location.href='?toggle_maintenance=1'">
                <span class="slider"></span>
            </label>
        </div>

        <div class="admin-profile-wrapper">
            <button class="admin-btn">
                <div class="admin-avatar">AD</div>
                Admin
            </button>
            <div class="dropdown-content">
                <a href="profile.php"><i class="ti-user" style="margin-right:8px;"></i> My Profile</a>
                <a href="change-password.php"><i class="ti-key" style="margin-right:8px;"></i> Change Password</a>
                <a href="logout.php" class="logout-text"><i class="ti-power-off" style="margin-right:8px;"></i> Logout</a>
            </div>
        </div>

    </div>
</header>

<?php if ($maintenanceStatus == 'ON'): ?>
    <div class="global-maintenance-banner">
        <i class="fas fa-exclamation-triangle"></i> SYSTEM IS CURRENTLY IN MAINTENANCE MODE. USER ACCESS IS RESTRICTED.
    </div>
<?php endif; ?>