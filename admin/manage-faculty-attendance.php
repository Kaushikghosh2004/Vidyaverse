<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// --- 1. HANDLE TIME WINDOW UPDATES & TOGGLE ---
if(isset($_POST['update_windows'])) {
    $in_start = $_POST['checkin_start'];
    $in_end = $_POST['checkin_end'];
    $out_start = $_POST['checkout_start'];
    $out_end = $_POST['checkout_end'];
    // Capture Toggle State (Checkbox: if checked it sends '1', else we save '0')
    $enforce = isset($_POST['enforce_time_windows']) ? '1' : '0';

    try {
        $stmt = $dbh->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$in_start, 'checkin_start']);
        $stmt->execute([$in_end, 'checkin_end']);
        $stmt->execute([$out_start, 'checkout_start']);
        $stmt->execute([$out_end, 'checkout_end']);
        
        // Ensure enforce row exists, then update it
        $dbh->query("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('enforce_time_windows', '0')");
        $stmt->execute([$enforce, 'enforce_time_windows']);

        $_SESSION['toast_msg'] = "Security Matrix Updated Successfully.";
        $_SESSION['toast_type'] = "success";
    } catch(Exception $e) {
        $_SESSION['toast_msg'] = "Database Error: Settings update failed.";
        $_SESSION['toast_type'] = "error";
    }
    header("Location: manage-faculty-attendance.php");
    exit;
}

// --- 2. HANDLE MANUAL ATTENDANCE LOG UPDATE ---
if(isset($_POST['update_log'])) {
    $record_id = intval($_POST['record_id']);
    $check_in = !empty($_POST['check_in']) ? $_POST['check_in'] : null;
    $check_out = !empty($_POST['check_out']) ? $_POST['check_out'] : null;
    $status = $_POST['status'];

    try {
        $sql = "UPDATE teacher_attendance SET check_in_time = :in, check_out_time = :out, status = :status WHERE id = :rid";
        $dbh->prepare($sql)->execute([':in' => $check_in, ':out' => $check_out, ':status' => $status, ':rid' => $record_id]);
        $_SESSION['toast_msg'] = "System Update: Faculty log adjusted.";
        $_SESSION['toast_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['toast_msg'] = "Error updating time log.";
        $_SESSION['toast_type'] = "error";
    }
    header("Location: manage-faculty-attendance.php");
    exit;
}

// --- FETCH CURRENT SETTINGS ---
$settings = [];
try {
    $q = $dbh->query("SELECT * FROM system_settings");
    while($row = $q->fetch(PDO::FETCH_ASSOC)) { $settings[$row['setting_key']] = $row['setting_value']; }
} catch(Exception $e) {}

// Defaults if missing
$settings['checkin_start'] = $settings['checkin_start'] ?? '08:00';
$settings['checkin_end'] = $settings['checkin_end'] ?? '09:30';
$settings['checkout_start'] = $settings['checkout_start'] ?? '16:00';
$settings['checkout_end'] = $settings['checkout_end'] ?? '18:00';
$settings['enforce_time_windows'] = $settings['enforce_time_windows'] ?? '1';

$toastMsg = $_SESSION['toast_msg'] ?? '';
$toastType = $_SESSION['toast_type'] ?? '';
unset($_SESSION['toast_msg'], $_SESSION['toast_type']);

$filter_date = isset($_GET['log_date']) ? $_GET['log_date'] : date('Y-m-d');
$pageTitle = "Faculty Attendance Logs";
include('includes/header.php');
?>

<div class="container-fluid content-wrapper">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">

    <style>
        :root { --bg-dark: #020617; --text-main: #f8fafc; --admin-gold: #fbbf24; --border-dark: #1e293b; --glass-bg: rgba(15, 23, 42, 0.6); }
        body { background-color: var(--bg-dark); background-image: radial-gradient(circle at 50% 0%, #0f172a 0%, #020617 100%); background-attachment: fixed; font-family: 'Inter', sans-serif; color: var(--text-main); }

        .admin-banner { background: linear-gradient(135deg, #0f172a 0%, #020617 100%); border: 1px solid var(--border-dark); border-left: 4px solid var(--admin-gold); border-radius: 16px; padding: 35px 40px; display: flex; align-items: center; justify-content: space-between; margin: 20px 0 30px 0; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5); }
        .banner-text h1 { margin: 0; font-size: 28px; color: #ffffff; font-family: 'Playfair Display', serif; font-weight: 700; }
        .banner-text p { margin: 8px 0 0; color: #94a3b8; font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; }

        .glass-card { background: var(--glass-bg); backdrop-filter: blur(15px); border: 1px solid var(--border-dark); border-radius: 16px; padding: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); margin-bottom: 30px; }
        
        /* --- TOGGLE SWITCH --- */
        .toggle-container { display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.4); border: 1px solid var(--border-dark); border-radius: 12px; padding: 15px 25px; margin-bottom: 20px; }
        .toggle-text h4 { margin: 0 0 4px 0; font-size: 16px; color: #fff; }
        .toggle-text p { margin: 0; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
        
        .switch { position: relative; display: inline-block; width: 60px; height: 30px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.1); border: 1px solid var(--border-dark); transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 3px; bottom: 3px; background-color: #94a3b8; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #10b981; border-color: #10b981; box-shadow: 0 0 15px rgba(16,185,129,0.4); }
        input:checked + .slider:before { transform: translateX(30px); background-color: #fff; }

        /* Time Window Matrix */
        .window-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .window-box { background: rgba(0,0,0,0.4); border: 1px solid var(--border-dark); border-radius: 12px; padding: 20px; transition: 0.3s; }
        .window-box h4 { margin: 0 0 15px 0; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display:flex; align-items:center; gap:8px; }
        .window-box.in h4 { color: #10b981; } .window-box.out h4 { color: #ef4444; }
        
        /* Dim windows when toggle is off */
        .windows-disabled .window-box { opacity: 0.4; pointer-events: none; filter: grayscale(100%); }

        .time-inputs { display: flex; gap: 15px; align-items: center; }
        .time-group { flex: 1; }
        .time-group label { display: block; font-size: 10px; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px; font-weight: 700; }
        .modern-input { width: 100%; background: #0f172a; border: 1px solid var(--border-dark); padding: 12px; border-radius: 8px; color: #fff; font-family: 'Inter', sans-serif; outline: none; }
        .modern-input:focus { border-color: var(--admin-gold); }
        input[type="time"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }

        .btn-submit { background: var(--admin-gold); color: #000; border: none; padding: 12px 25px; border-radius: 8px; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.3s; margin-top: 20px; display: inline-block; }
        .btn-submit:hover { background: #f59e0b; box-shadow: 0 5px 15px rgba(251, 191, 36, 0.2); transform: translateY(-2px); }

        /* Table & Modal styles omitted for brevity - Keep your existing ones from the previous step! */
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 15px; color: #94a3b8; font-size: 12px; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid var(--border-dark); }
        .table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.02); font-size: 14px; }
        .status-badge { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .badge-present { background: rgba(16, 185, 129, 0.15); color: #10b981; } .badge-absent { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        
        .btn-edit { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); padding: 8px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: 0.3s; cursor: pointer; text-transform: uppercase; }
        .btn-edit:hover { background: #3b82f6; color: #fff; box-shadow: 0 0 15px rgba(59, 130, 246, 0.4); }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(2, 6, 23, 0.85); backdrop-filter: blur(10px); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: 0.3s; }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-card { background: #0f172a; border-radius: 20px; padding: 40px; width: 400px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5); transform: scale(0.95); transition: 0.3s; position: relative; border: 1px solid var(--border-dark); border-top: 2px solid var(--admin-gold); }
        .modal-overlay.active .modal-card { transform: scale(1); }
        .close-modal { position: absolute; top: 20px; right: 20px; font-size: 24px; color: #64748b; cursor: pointer; transition: 0.2s; }
        .close-modal:hover { color: #ef4444; }

        .form-group { margin-top: 20px; text-align: left; }
        .form-group label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 8px; }

        .glass-toast { position: fixed; top: 90px; right: -400px; background: rgba(15, 23, 42, 0.95); border: 1px solid var(--border-dark); border-left: 4px solid var(--admin-gold); padding: 18px 25px; border-radius: 12px; display: flex; align-items: center; gap: 15px; z-index: 10000; transition: right 0.5s; }
        .glass-toast.show { right: 30px; }
    </style>

    <div id="syncToast" class="glass-toast <?php echo ($toastType == 'success') ? 'toast-success' : 'toast-error'; ?>" style="border-left-color: <?php echo ($toastType == 'success') ? '#10b981' : '#ef4444'; ?>;">
        <i class="fas <?php echo ($toastType == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>" style="font-size:24px; color: <?php echo ($toastType == 'success') ? '#10b981' : '#ef4444'; ?>;"></i>
        <div><h4 style="margin:0 0 4px; font-size:14px; font-weight:800; color:#fff; text-transform:uppercase;">System Alert</h4><p style="margin:0; font-size:12px; color:#a1a1aa;"><?php echo $toastMsg; ?></p></div>
    </div>

    <div class="admin-banner">
        <div class="banner-text">
            <h1>Faculty <span>Access Logs</span></h1>
            <p>Admin Override & Global Access Matrix</p>
        </div>
        <i class="fas fa-user-clock" style="font-size: 60px; color: var(--border-dark); opacity: 0.5;"></i>
    </div>

    <div class="glass-card">
        <form method="POST">
            
            <div class="toggle-container">
                <div class="toggle-text">
                    <h4><i class="fas fa-shield-alt" style="color:var(--admin-gold);"></i> Enforce Time Restrictions</h4>
                    <p>If turned off, faculty can scan Check-In and Check-Out at any time (24/7).</p>
                </div>
                <label class="switch">
                    <input type="checkbox" name="enforce_time_windows" id="enforceToggle" value="1" <?php echo ($settings['enforce_time_windows'] == '1') ? 'checked' : ''; ?> onchange="toggleWindows()">
                    <span class="slider"></span>
                </label>
            </div>

            <div class="window-grid" id="windowGrid" class="<?php echo ($settings['enforce_time_windows'] != '1') ? 'windows-disabled' : ''; ?>">
                <div class="window-box in">
                    <h4><i class="fas fa-sign-in-alt"></i> Check-In Window</h4>
                    <div class="time-inputs">
                        <div class="time-group"><label>Opens At</label><input type="time" name="checkin_start" value="<?php echo $settings['checkin_start']; ?>" class="modern-input" required></div>
                        <div class="time-group"><label>Closes At</label><input type="time" name="checkin_end" value="<?php echo $settings['checkin_end']; ?>" class="modern-input" required></div>
                    </div>
                </div>

                <div class="window-box out">
                    <h4><i class="fas fa-sign-out-alt"></i> Check-Out Window</h4>
                    <div class="time-inputs">
                        <div class="time-group"><label>Opens At</label><input type="time" name="checkout_start" value="<?php echo $settings['checkout_start']; ?>" class="modern-input" required></div>
                        <div class="time-group"><label>Closes At</label><input type="time" name="checkout_end" value="<?php echo $settings['checkout_end']; ?>" class="modern-input" required></div>
                    </div>
                </div>
            </div>
            <button type="submit" name="update_windows" class="btn-submit"><i class="fas fa-server"></i> Update Security Matrix</button>
        </form>
    </div>

    <div class="glass-card">
        <form method="GET" style="margin-bottom:20px; display:flex; gap:10px;">
            <input type="date" name="log_date" value="<?php echo htmlentities($filter_date); ?>" class="modern-input" style="width:200px;">
            <button type="submit" class="btn-submit" style="margin:0;"><i class="fas fa-search"></i> Fetch Logs</button>
        </form>
        
        <table class="table">
            <thead><tr><th>Faculty Member</th><th>Check-In</th><th>Check-Out</th><th>Status</th><th style="text-align:right;">Action</th></tr></thead>
            <tbody>
                <?php
                $sql = "SELECT ta.*, t.FirstName, t.LastName FROM teacher_attendance ta JOIN tblteacher t ON ta.teacher_id = t.ID WHERE ta.attendance_date = :date ORDER BY ta.check_in_time DESC";
                $query = $dbh->prepare($sql); $query->execute([':date' => $filter_date]);
                while($row = $query->fetch(PDO::FETCH_OBJ)) {
                    $inTime = !empty($row->check_in_time) ? date("h:i A", strtotime($row->check_in_time)) : 'Pending';
                    $outTime = !empty($row->check_out_time) ? date("h:i A", strtotime($row->check_out_time)) : 'Pending';
                    $sClass = strtolower($row->status) == 'present' ? 'badge-present' : 'badge-absent';
                    ?>
                    <tr>
                        <td>Prof. <?php echo $row->FirstName . " " . $row->LastName; ?></td>
                        <td style="color:#10b981; font-family:monospace;"><?php echo $inTime; ?></td>
                        <td style="color:#ef4444; font-family:monospace;"><?php echo $outTime; ?></td>
                        <td><span class="status-badge <?php echo $sClass; ?>"><?php echo $row->status; ?></span></td>
                        <td style="text-align:right;">
                            <button class="btn-edit" onclick="openTimeModal(<?php echo $row->id; ?>, '<?php echo addslashes($row->FirstName); ?>', '<?php echo $row->check_in_time; ?>', '<?php echo $row->check_out_time; ?>', '<?php echo $row->status; ?>')">Edit</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="timeModal">
        <div class="modal-card">
            <i class="fas fa-times close-modal" onclick="closeTimeModal()"></i>
            <h3 style="margin: 0 0 5px; color:#fff; font-size:22px;">Adjust Log</h3>
            <p style="color:#94a3b8; font-size:12px;">Editing for <strong id="modalFacName" style="color:#fff;"></strong></p>
            <form method="post">
                <input type="hidden" name="record_id" id="modalRecordId">
                <div style="display:flex; gap:15px; margin-top:20px;">
                    <div class="form-group" style="flex:1; margin-top:0;"><label>Check-In</label><input type="time" name="check_in" id="modalCheckIn" class="modern-input" step="1"></div>
                    <div class="form-group" style="flex:1; margin-top:0;"><label>Check-Out</label><input type="time" name="check_out" id="modalCheckOut" class="modern-input" step="1"></div>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="status" id="modalStatus" class="modern-input" required>
                        <option value="present">Present (Checked-In)</option>
                        <option value="absent">Absent</option>
                    </select>
                </div>
                <button type="submit" name="update_log" class="btn-submit" style="width:100%;"><i class="fas fa-save"></i> Save Adjustments</button>
            </form>
        </div>
    </div>

    <script>
        function toggleWindows() {
            const isChecked = document.getElementById('enforceToggle').checked;
            const grid = document.getElementById('windowGrid');
            if(isChecked) { grid.classList.remove('windows-disabled'); } 
            else { grid.classList.add('windows-disabled'); }
        }
        // Run once on load to set initial visual state
        toggleWindows();

        function openTimeModal(id, name, inTime, outTime, status) {
            document.getElementById('modalRecordId').value = id;
            document.getElementById('modalFacName').innerText = name;
            document.getElementById('modalCheckIn').value = (inTime !== 'null' && inTime !== '') ? inTime : '';
            document.getElementById('modalCheckOut').value = (outTime !== 'null' && outTime !== '') ? outTime : '';
            document.getElementById('modalStatus').value = status.toLowerCase();
            document.getElementById('timeModal').classList.add('active');
        }
        function closeTimeModal() { document.getElementById('timeModal').classList.remove('active'); }
        window.addEventListener('click', function(e) { if(e.target == document.getElementById('timeModal')) closeTimeModal(); });

        document.addEventListener("DOMContentLoaded", function() {
            const toastMsg = "<?php echo addslashes($toastMsg); ?>";
            if (toastMsg.trim() !== "") {
                const toast = document.getElementById('syncToast');
                setTimeout(() => { toast.classList.add('show'); }, 100);
                setTimeout(() => { toast.classList.remove('show'); }, 3500);
            }
        });
    </script>
</div>
<?php include('includes/footer.php');?>