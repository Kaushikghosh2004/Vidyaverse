<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['ocastid'])) {
    header('location:logout.php');
    exit;
}

// --- ADD NEWS LOGIC ---
if(isset($_POST['submit'])) {
    $tid = $_SESSION['ocastid'];
    $title = $_POST['title'];
    $desc = $_POST['description'];

    if(empty($title) || empty($desc)) {
        echo "<script>alert('Please fill in all fields.');</script>";
    } else {
        try {
            $sql = "INSERT INTO tblnewsbyteacher(TeacherID, Title, Description) VALUES(:tid, :title, :desc)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':title', $title, PDO::PARAM_STR);
            $query->bindParam(':desc', $desc, PDO::PARAM_STR);
            $query->bindParam(':tid', $tid, PDO::PARAM_STR);
            $query->execute();

            $LastInsertId = $dbh->lastInsertId();
            if ($LastInsertId > 0) {
                echo '<script>alert("Announcement posted successfully.")</script>';
                echo "<script>window.location.href ='newsorannouncement.php'</script>";
            } else {
                echo '<script>alert("Something went wrong. Please try again")</script>';
            }
        } catch (Exception $e) {
            echo '<script>alert("Error: ' . addslashes($e->getMessage()) . '")</script>';
        }
    }
}

// --- DELETE LOGIC ---
if(isset($_GET['delid'])) {
    $rid = intval($_GET['delid']);
    try {
        $sql = "DELETE FROM tblnewsbyteacher WHERE ID=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_STR);
        $query->execute();
        echo "<script>alert('Announcement deleted successfully.'); window.location.href = 'newsorannouncement.php'</script>"; 
    } catch (Exception $e) {
        echo "<script>alert('Error deleting record.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Announcements | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc;
            /* Header height handled by global CSS */
        }

        /* --- LAYOUT --- */
        .page-wrapper {
            display: grid;
            grid-template-columns: 350px 1fr; /* Fixed Width Form + Flexible Table */
            gap: 30px;
            padding: 40px 20px;
            max-width: 1400px; margin: 0 auto;
        }
        @media(max-width: 992px) { .page-wrapper { grid-template-columns: 1fr; } }

        /* GLASS CARD */
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px; padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            height: 100%;
        }

        .section-header {
            border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 25px;
        }
        .header-title { font-size: 18px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }

        /* FORM STYLES */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 8px; font-weight: 500; }
        
        .modern-input {
            width: 100%; background: rgba(15, 23, 42, 0.8);
            border: 1px solid #334155; color: #fff;
            padding: 12px; border-radius: 12px; font-size: 14px; transition: 0.3s;
        }
        .modern-input:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        
        textarea.modern-input { resize: vertical; min-height: 120px; }

        .btn-glow {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white; border: none; width: 100%; padding: 14px;
            border-radius: 12px; font-size: 16px; font-weight: 700;
            letter-spacing: 1px; cursor: pointer; text-transform: uppercase;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4); margin-top: 10px;
            transition: 0.3s;
        }
        .btn-glow:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(139, 92, 246, 0.6); }

        /* LIST STYLES */
        .news-list { list-style: none; padding: 0; margin: 0; }
        .news-item {
            padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: 0.2s; position: relative;
        }
        .news-item:last-child { border-bottom: none; }
        .news-item:hover { background: rgba(255,255,255,0.02); }

        .news-title { font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 5px; display: block; }
        .news-desc { font-size: 13px; color: #94a3b8; line-height: 1.5; margin-bottom: 10px; }
        .news-meta { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 10px; }
        
        .badge-date { background: rgba(59, 130, 246, 0.1); color: #60a5fa; padding: 3px 8px; border-radius: 4px; }

        .btn-delete {
            position: absolute; top: 20px; right: 20px;
            background: rgba(239, 68, 68, 0.1); color: #f87171;
            width: 30px; height: 30px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-size: 14px; transition: 0.2s;
        }
        .btn-delete:hover { background: #ef4444; color: #fff; }

    </style>
</head>
<body>

    <?php include_once('includes/header.php');?>

    <div class="page-wrapper">
        
        <div class="glass-card">
            <div class="section-header">
                <div class="header-title">Post New Update</div>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="modern-input" placeholder="e.g. Class Rescheduled" required>
                </div>
                
                <div class="form-group">
                    <label>Description / Details</label>
                    <textarea name="description" class="modern-input" placeholder="Enter full details here..." required></textarea>
                </div>

                <button type="submit" name="submit" class="btn-glow">Publish</button>
            </form>
        </div>

        <div class="glass-card" style="padding: 0;">
            <div class="section-header" style="margin: 25px 25px 0 25px;">
                <div class="header-title">My Announcements</div>
            </div>

            <ul class="news-list">
                <?php
                $tid = $_SESSION['ocastid'];
                $sql = "SELECT * FROM tblnewsbyteacher WHERE TeacherID=:tid ORDER BY CreationDate DESC";
                $query = $dbh->prepare($sql);
                $query->bindParam(':tid', $tid);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);

                if($query->rowCount() > 0) {
                    foreach($results as $row) {
                ?>
                <li class="news-item">
                    <span class="news-title"><?php echo htmlentities($row->Title);?></span>
                    <p class="news-desc"><?php echo htmlentities($row->Description);?></p>
                    
                    <div class="news-meta">
                        <span class="badge-date"><i class="ti-calendar"></i> <?php echo date("d M Y, h:i A", strtotime($row->CreationDate)); ?></span>
                    </div>

                    <a href="newsorannouncement.php?delid=<?php echo $row->ID;?>" class="btn-delete" onclick="return confirm('Delete this announcement?');" title="Delete">
                        <i class="ti-trash"></i>
                    </a>
                </li>
                <?php 
                    }
                } else { 
                ?>
                <li style="text-align:center; padding: 40px; color:#94a3b8;">
                    <i class="ti-info-alt" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                    No announcements posted yet.
                </li>
                <?php } ?>
            </ul>
        </div>

    </div>

    <?php include('includes/footer.php');?>

</body>
</html>