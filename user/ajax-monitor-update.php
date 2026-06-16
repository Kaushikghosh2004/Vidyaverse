<?php
session_start();
include('includes/dbconnection.php');

// 1. ENABLE DEBUGGING LOG
// This will create a file named 'debug_log.txt' in your user folder.
function logDebug($msg) {
    $logfile = __DIR__ . '/debug_log.txt';
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logfile, "[$timestamp] $msg" . PHP_EOL, FILE_APPEND);
}

header('Content-Type: application/json');
$response = array('status' => 'success');

try {
    if(isset($_POST['sid'])) {
        
        $sid = $_POST['sid'];
        $img = $_POST['image'] ?? ''; 
        $tabSwitches = $_POST['tab_switches'];
        $movementAlert = $_POST['movement_alert'];

        // Log Incoming Data Size
        logDebug("Received Data for Session ID: $sid");
        logDebug("Image Data Length: " . strlen($img));

        // --- IMAGE SAVING LOGIC ---
        $dbImagePath = "";
        
        if(!empty($img) && $img != "undefined" && strlen($img) > 100) {
            
            // Define Path
            $targetDir = __DIR__ . '/evidence/';
            
            // 1. Check/Create Folder
            if (!is_dir($targetDir)) {
                logDebug("Folder 'evidence' not found. Attempting to create...");
                if (!mkdir($targetDir, 0777, true)) {
                    logDebug("ERROR: Failed to create directory: $targetDir");
                    $response['error'] = "Directory creation failed";
                } else {
                    logDebug("Directory created successfully.");
                }
            }

            // 2. Decode Image
            $img = str_replace('data:image/jpeg;base64,', '', $img);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            
            if($data === false) {
                logDebug("ERROR: Base64 decode failed.");
            } else {
                // 3. Save File
                $fileName = 'snap_' . $sid . '_' . time() . '.jpg';
                $fileFullPath = $targetDir . $fileName;
                
                if(file_put_contents($fileFullPath, $data)) {
                    logDebug("SUCCESS: Saved image to $fileFullPath");
                    // Path for DB (Relative to admin folder)
                    $dbImagePath = "evidence/" . $fileName;
                } else {
                    logDebug("ERROR: Permission denied. Could not write to $fileFullPath");
                }
            }
        } else {
            logDebug("No valid image data received.");
        }

        // --- DATABASE UPDATE ---
        if($dbImagePath != "") {
            $sql = "UPDATE tblexam_sessions SET 
                    LastSnapshot = :img, 
                    TabSwitchCount = :tabs, 
                    MovementWarnings = MovementWarnings + :move,
                    LastHeartbeat = NOW() 
                    WHERE ID = :sid";
            $params = [':img'=>$dbImagePath, ':tabs'=>$tabSwitches, ':move'=>$movementAlert, ':sid'=>$sid];
        } else {
            $sql = "UPDATE tblexam_sessions SET 
                    TabSwitchCount = :tabs, 
                    MovementWarnings = MovementWarnings + :move,
                    LastHeartbeat = NOW() 
                    WHERE ID = :sid";
            $params = [':tabs'=>$tabSwitches, ':move'=>$movementAlert, ':sid'=>$sid];
        }

        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        logDebug("Database updated successfully.");

        // Check for Admin Commands
        $chk = $dbh->prepare("SELECT Status, AdminMessage FROM tblexam_sessions WHERE ID = :sid");
        $chk->execute([':sid' => $sid]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);

        $response['exam_status'] = $row['Status'];
        $response['warning_msg'] = $row['AdminMessage'];

    } else {
        logDebug("ERROR: No 'sid' in POST request.");
    }

} catch (Exception $e) {
    logDebug("CRITICAL ERROR: " . $e->getMessage());
}

echo json_encode($response);
?>