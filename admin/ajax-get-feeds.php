<?php
session_start();
include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) { exit; }

// Fetch Active Sessions
// We join with tbluser to get names, and tblexams to get exam titles
// Ordered by Risk (TabSwitchCount) so high-risk students appear first
$sql = "SELECT s.ID as SessID, s.LastSnapshot, s.TabSwitchCount, s.MovementWarnings,
        u.FullName, u.RollNumber, e.ExamTitle 
        FROM tblexam_sessions s
        JOIN tbluser u ON s.StudentID = u.ID
        JOIN tblexams e ON s.ExamID = e.ID
        WHERE s.Status = 'Ongoing'
        ORDER BY s.TabSwitchCount DESC"; 

$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);

if($query->rowCount() > 0) {
    foreach($results as $row) {
        
        // 1. Image Path Logic
        // The AJAX monitor in user folder saves to 'user/evidence/filename.jpg'
        // Admin path needs to step back: '../user/evidence/filename.jpg'
        $imgSrc = "../user/" . $row->LastSnapshot;
        
        // Fallback if image doesn't exist yet
        if(empty($row->LastSnapshot) || !file_exists("../user/" . $row->LastSnapshot)) {
            $imgSrc = "https://via.placeholder.com/400x250/000000/FFFFFF?text=Waiting+for+Stream...";
        }

        // 2. Risk Calculation (Must match main file logic)
        $tabs = intval($row->TabSwitchCount);
        $move = intval($row->MovementWarnings);
        $risk = ($tabs * 10) + ($move * 5); 

        // Determine CSS Class
        $css = "risk-low";
        $badge = "";
        
        if($risk > 20) $css = "risk-med";
        if($risk > 50) {
            $css = "risk-high";
            $badge = '<span class="badge bg-danger">HIGH RISK</span>';
        }

        // 3. Generate HTML Card
        echo '
        <div class="card '.$css.'">
            <div class="cam-feed">
                <img src="'.$imgSrc.'?t='.time().'" alt="Live Feed"> 
                <div class="overlay">
                    <span class="badge">ID: '.htmlentities($row->RollNumber).'</span>
                    '.$badge.'
                </div>
            </div>

            <div class="card-body">
                <div class="st-name">'.htmlentities($row->FullName).'</div>
                <div class="exam-name">'.htmlentities($row->ExamTitle).'</div>

                <div class="metrics">
                    <div class="metric">
                        <span class="m-val '.($tabs>0 ? 'warn-text' : '').'">'.$tabs.'</span>
                        <span class="m-lbl">Switches</span>
                    </div>
                    <div class="metric">
                        <span class="m-val '.($move>0 ? 'warn-text' : '').'">'.$move.'</span>
                        <span class="m-lbl">Movements</span>
                    </div>
                    <div class="metric">
                        <span class="m-val">'.$risk.'%</span>
                        <span class="m-lbl">Risk Score</span>
                    </div>
                </div>
            </div>

            <div class="actions">
                <form method="POST" target="hidden-frame">
                    <input type="hidden" name="session_id" value="'.$row->SessID.'">
                    <button type="submit" name="warn" class="btn btn-warn">⚠ Warn</button>
                </form>
                
                <form method="POST" onsubmit="return confirm(\'Terminate this exam immediately?\');">
                    <input type="hidden" name="session_id" value="'.$row->SessID.'">
                    <button type="submit" name="terminate" class="btn btn-kill">✖ Terminate</button>
                </form>
            </div>
        </div>';
    }
} else {
    echo '<div style="grid-column:1/-1; text-align:center; padding:50px; color:#64748b;">
            <h2>No Active Exams</h2>
            <p>Waiting for students to begin...</p>
          </div>';
}
?>
<iframe name="hidden-frame" style="display:none;"></iframe>