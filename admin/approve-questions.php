<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// --- HANDLE ACTIONS ---
if(isset($_GET['action'])) {
    $qid = intval($_GET['id']);
    
    if($_GET['action'] == 'approve') {
        $sql = "UPDATE tblquestions SET IsApproved=1 WHERE ID=:qid";
        $dbh->prepare($sql)->execute(['qid' => $qid]);
        echo "<script>alert('Question Approved Successfully!'); window.location.href='approve-questions.php';</script>";
    }
    
    if($_GET['action'] == 'delete') {
        $sql = "DELETE FROM tblquestions WHERE ID=:qid";
        $dbh->prepare($sql)->execute(['qid' => $qid]);
        echo "<script>alert('Question Deleted!'); window.location.href='approve-questions.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Approve Questions | VIDYAVERSE</title>
    
    <link href="../assets/css/lib/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/lib/themify-icons.css" rel="stylesheet">
    <link href="../assets/css/lib/bootstrap.min.css" rel="stylesheet">

    <style>
        /* --- GLOBAL DARK THEME --- */
        * { box-sizing: border-box; }
        body { 
            background-color: #0f172a; 
            font-family: 'Segoe UI', 'Roboto', sans-serif; 
            color: #f8fafc; 
            margin: 0; padding: 0; 
            overflow-x: hidden;
        }

        /* HEADER */
        .simple-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 80px;
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px);
            z-index: 1000; display: flex; align-items: center; justify-content: space-between;
            padding: 0 40px; border-bottom: 1px solid #334155;
        }
        .header-title { font-size: 20px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px; }
        .btn-back {
            background: #334155; color: #fff; padding: 8px 20px; border-radius: 6px;
            text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 8px;
        }
        .btn-back:hover { background: #475569; color: #fff; }

        /* CONTENT */
        .main-content {
            margin-top: 80px;
            padding: 40px;
            max-width: 1400px;
            margin-left: auto; margin-right: auto;
        }

        /* GRID SYSTEM */
        .question-grid {
            display: grid;
            grid-template-columns: 1fr; /* Single column for detailed question cards */
            gap: 25px;
        }

        /* QUESTION CARD */
        .q-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
            display: flex; flex-direction: column;
            transition: transform 0.2s;
        }
        .q-card:hover { transform: translateY(-3px); border-color: #3b82f6; }

        /* CARD HEADER */
        .q-header {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid #334155;
            display: flex; justify-content: space-between; align-items: center;
        }
        .course-badge {
            background: rgba(59, 130, 246, 0.15); color: #60a5fa;
            padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase;
            border: 1px solid #3b82f6;
        }
        .q-id { font-size: 12px; color: #64748b; font-family: monospace; }

        /* CARD BODY */
        .q-body { padding: 25px; }
        .q-text { font-size: 18px; font-weight: 600; color: #fff; margin-bottom: 20px; line-height: 1.5; }

        .options-list {
            display: grid; grid-template-columns: 1fr 1fr; gap: 15px;
        }
        .option-item {
            background: #0f172a; padding: 12px 15px; border-radius: 8px; border: 1px solid #334155;
            color: #cbd5e1; font-size: 14px;
        }
        .correct-opt { border-color: #10b981; background: rgba(16, 185, 129, 0.1); color: #10b981; font-weight: 600; }

        /* CARD FOOTER */
        .q-footer {
            padding: 15px 25px; border-top: 1px solid #334155;
            display: flex; justify-content: flex-end; gap: 10px; background: #0f172a;
        }

        .btn-action {
            padding: 10px 20px; border-radius: 6px; font-weight: 600; font-size: 13px;
            text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
            transition: 0.2s; border: none; cursor: pointer;
        }
        .btn-approve { background: #10b981; color: white; }
        .btn-approve:hover { background: #059669; }
        
        .btn-delete { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid #ef4444; }
        .btn-delete:hover { background: #ef4444; color: white; }

        /* EMPTY STATE */
        .empty-state {
            text-align: center; padding: 80px; color: #64748b;
            background: #1e293b; border-radius: 16px; border: 2px dashed #334155;
        }
    </style>
</head>
<body>

    <div class="simple-header">
        <div class="header-title">
            <i class="ti-check-box"></i> APPROVE QUESTIONS
        </div>
        <a href="dashboard.php" class="btn-back">
            <i class="ti-arrow-left"></i> Dashboard
        </a>
    </div>

    <div class="main-content">
        
        <div class="question-grid">
            <?php
            // Fetch Pending Questions
            $sql = "SELECT q.*, c.CourseName 
                    FROM tblquestions q 
                    JOIN tblcourse c ON q.CourseID = c.ID 
                    WHERE q.IsApproved = 0
                    ORDER BY q.ID DESC";
            $query = $dbh->prepare($sql);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_OBJ);

            if($query->rowCount() > 0) {
                foreach($results as $row) {
            ?>
            
            <div class="q-card">
                <div class="q-header">
                    <span class="course-badge"><?php echo htmlentities($row->CourseName); ?></span>
                    <span class="q-id">ID: #<?php echo htmlentities($row->ID); ?></span>
                </div>

                <div class="q-body">
                    <div class="q-text">
                        <?php echo htmlentities($row->QuestionText); ?>
                    </div>

                    <div class="options-list">
                        <div class="option-item <?php echo ($row->CorrectAnswer == 'A') ? 'correct-opt' : ''; ?>">
                            <span style="opacity:0.5; margin-right:5px;">A:</span> <?php echo htmlentities($row->OptionA); ?>
                        </div>
                        <div class="option-item <?php echo ($row->CorrectAnswer == 'B') ? 'correct-opt' : ''; ?>">
                            <span style="opacity:0.5; margin-right:5px;">B:</span> <?php echo htmlentities($row->OptionB); ?>
                        </div>
                        <div class="option-item <?php echo ($row->CorrectAnswer == 'C') ? 'correct-opt' : ''; ?>">
                            <span style="opacity:0.5; margin-right:5px;">C:</span> <?php echo htmlentities($row->OptionC); ?>
                        </div>
                        <div class="option-item <?php echo ($row->CorrectAnswer == 'D') ? 'correct-opt' : ''; ?>">
                            <span style="opacity:0.5; margin-right:5px;">D:</span> <?php echo htmlentities($row->OptionD); ?>
                        </div>
                    </div>
                </div>

                <div class="q-footer">
                    <a href="approve-questions.php?action=delete&id=<?php echo $row->ID; ?>" class="btn-action btn-delete" onclick="return confirm('Permanently delete this question?');">
                        <i class="ti-trash"></i> Reject
                    </a>
                    <a href="approve-questions.php?action=approve&id=<?php echo $row->ID; ?>" class="btn-action btn-approve">
                        <i class="ti-check"></i> Approve
                    </a>
                </div>
            </div>
            <?php 
                } 
            } else { 
            ?>
                <div class="empty-state">
                    <i class="ti-thumb-up" style="font-size: 50px; margin-bottom: 20px; display:block;"></i>
                    <h3>All Caught Up!</h3>
                    <p>There are no pending questions waiting for approval.</p>
                </div>
            <?php } ?>
        </div>

    </div>

    <script src="../assets/js/lib/jquery.min.js"></script>
    <script src="../assets/js/lib/bootstrap.min.js"></script>

</body>
</html>