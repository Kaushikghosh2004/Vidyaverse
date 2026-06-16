<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

// Security
if (empty($_SESSION['admin_id'])) { header('location:logout.php'); exit; }

// --- FETCH RESULT DETAILS FOR MODAL (AJAX) ---
if(isset($_POST['view_id'])) {
    $sid = $_POST['view_id'];
    
    $sql = "SELECT s.*, u.FullName, u.RollNumber, u.MobileNumber, e.ExamTitle, e.TotalQuestions 
            FROM tblexam_sessions s
            JOIN tbluser u ON s.StudentID = u.ID
            JOIN tblexams e ON s.ExamID = e.ID
            WHERE s.ID = :sid";
            
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':sid' => $sid]);
    $res = $stmt->fetch(PDO::FETCH_OBJ);
    
    if($res) {
        // Calculate Risk again for display
        $risk = ($res->TabSwitchCount * 10) + ($res->MovementWarnings * 5);
        $riskColor = ($risk > 50) ? '#ef4444' : (($risk > 20) ? '#f59e0b' : '#10b981');
        
        $img = !empty($res->LastSnapshot) ? "../user/".$res->LastSnapshot : "https://via.placeholder.com/400x250?text=No+Image";
        
        echo '
        <div style="display:flex; gap:20px;">
            <div style="flex:1;">
                <img src="'.$img.'" style="width:100%; border-radius:8px; border:2px solid #334155;">
                <div style="margin-top:10px; font-size:12px; color:#94a3b8; text-align:center;">Last Proctor Snapshot</div>
            </div>
            <div style="flex:1.5;">
                <h3 style="margin:0 0 5px 0; color:#fff;">'.htmlentities($res->FullName).'</h3>
                <div style="color:#94a3b8; font-size:13px; margin-bottom:15px;">'.htmlentities($res->ExamTitle).'</div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px;">
                    <div style="background:#0f172a; padding:10px; border-radius:6px; text-align:center;">
                        <span style="display:block; font-size:18px; font-weight:700; color:'.$riskColor.'">'.$risk.'%</span>
                        <span style="font-size:10px; color:#64748b;">RISK SCORE</span>
                    </div>
                    <div style="background:#0f172a; padding:10px; border-radius:6px; text-align:center;">
                        <span style="display:block; font-size:18px; font-weight:700; color:#fff;">'.$res->Score.' / '.$res->TotalQuestions.'</span>
                        <span style="font-size:10px; color:#64748b;">FINAL SCORE</span>
                    </div>
                    <div style="background:#0f172a; padding:10px; border-radius:6px; text-align:center;">
                        <span style="display:block; font-size:18px; font-weight:700; color:#f59e0b;">'.$res->TabSwitchCount.'</span>
                        <span style="font-size:10px; color:#64748b;">TAB SWITCHES</span>
                    </div>
                    <div style="background:#0f172a; padding:10px; border-radius:6px; text-align:center;">
                        <span style="display:block; font-size:18px; font-weight:700; color:#ef4444;">'.$res->MovementWarnings.'</span>
                        <span style="font-size:10px; color:#64748b;">MOVEMENTS</span>
                    </div>
                </div>

                <div style="font-size:13px;">
                    <strong>Student Info:</strong><br>
                    Roll: '.htmlentities($res->RollNumber).'<br>
                    Mobile: '.htmlentities($res->MobileNumber).'
                </div>
            </div>
        </div>';
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Results | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        /* --- DARK THEME --- */
        body { background-color: #0b1120; font-family: 'Segoe UI', sans-serif; color: #e2e8f0; padding: 20px; }
        
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden; margin-top: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: rgba(0,0,0,0.2); color: #94a3b8; font-size: 12px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #334155; font-size: 14px; vertical-align: middle; }
        tr:hover { background: rgba(255,255,255,0.02); }

        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
        .bg-red { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }
        .bg-green { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
        
        .btn-view {
            background: #3b82f6; color: white; border: none; padding: 8px 15px; 
            border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 5px; text-decoration: none;
        }
        .btn-view:hover { background: #2563eb; }

        /* MODAL */
        #detailModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #1e293b; padding: 30px; border-radius: 12px; width: 600px; max-width: 90%; position: relative; border: 1px solid #334155; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .close-btn { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #94a3b8; }
    </style>
</head>
<body>

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin:0; color:#fff;">Student Results Overview</h2>
        <a href="dashboard.php" style="color:#94a3b8; text-decoration:none;">Back to Dashboard</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Exam Details</th>
                    <th>Score</th>
                    <th>Proctoring Log</th>
                    <th>Completion Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch Completed/Terminated Sessions
                $sql = "SELECT s.*, u.FullName, u.RollNumber, e.ExamTitle, e.TotalQuestions 
                        FROM tblexam_sessions s
                        JOIN tbluser u ON s.StudentID = u.ID
                        JOIN tblexams e ON s.ExamID = e.ID
                        WHERE s.Status IN ('Completed', 'Terminated')
                        ORDER BY s.EndTime DESC";
                
                $query = $dbh->prepare($sql);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);

                if($query->rowCount() > 0) {
                    foreach($results as $row) {
                        
                        // 1. DATE FIX
                        // Check if EndTime exists and is not a zero date
                        if(!empty($row->EndTime) && $row->EndTime != '0000-00-00 00:00:00') {
                            $dateStr = date("d M Y", strtotime($row->EndTime)) . "<br><span style='color:#64748b; font-size:11px;'>" . date("h:i A", strtotime($row->EndTime)) . "</span>";
                        } else {
                            $dateStr = "<span style='color:#ef4444;'>Not Recorded</span>";
                        }

                        // 2. STATUS BADGE
                        $tabs = intval($row->TabSwitchCount);
                        $statusBadge = ($tabs > 2) 
                            ? '<span class="badge bg-red">'.$tabs.' Switches (High Risk)</span>' 
                            : '<span class="badge bg-green">Clean Record</span>';
                        
                        // Percentage
                        $total = intval($row->TotalQuestions);
                        $score = intval($row->Score);
                        $percent = ($total > 0) ? round(($score/$total)*100) : 0;
                        $scoreColor = ($percent < 40) ? '#ef4444' : '#10b981';
                        
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700; color:#fff;"><?php echo htmlentities($row->FullName); ?></div>
                        <div style="font-size:11px; color:#94a3b8;">Roll: <?php echo htmlentities($row->RollNumber); ?></div>
                    </td>
                    <td><?php echo htmlentities($row->ExamTitle); ?></td>
                    <td>
                        <span style="font-weight:700; font-size:16px; color:<?php echo $scoreColor; ?>"><?php echo $score; ?> / <?php echo $total; ?></span>
                        <span style="font-size:11px; color:#64748b; margin-left:5px;"><?php echo $percent; ?>%</span>
                    </td>
                    <td><?php echo $statusBadge; ?></td>
                    <td><?php echo $dateStr; ?></td>
                    <td>
                        <button class="btn-view" onclick="openModal(<?php echo $row->ID; ?>)">
                            <i class="ti-eye"></i> Details
                        </button>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                    echo '<tr><td colspan="6" style="text-align:center; padding:40px; color:#64748b;">No results found.</td></tr>';
                } 
                ?>
            </tbody>
        </table>
    </div>

    <div id="detailModal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <div id="modalBody">Loading...</div>
        </div>
    </div>

    <script>
        function openModal(sid) {
            $('#detailModal').css('display', 'flex');
            $('#modalBody').html('<div style="text-align:center; color:#94a3b8;">Fetching Data...</div>');
            
            $.post('', { view_id: sid }, function(data) {
                $('#modalBody').html(data);
            });
        }

        function closeModal() {
            $('#detailModal').hide();
        }
        
        // Close on outside click
        window.onclick = function(event) {
            if (event.target == document.getElementById('detailModal')) {
                closeModal();
            }
        }
    </script>

</body>
</html>