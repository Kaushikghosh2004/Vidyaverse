<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (strlen($_SESSION['ocasuid'] ?? '') == 0) {
    header('location:logout.php');
    exit();
}

// --- SEARCH & DISPLAY LOGIC ---
$results = [];
$keyword = "";
$search_mode = false; // Flag to check if user searched

if(isset($_POST['search'])) {
    // --- SEARCH MODE ---
    $search_mode = true;
    $keyword = $_POST['keyword'];
    
    $sql = "SELECT n.Title, n.Description, n.CreationDate, t.FirstName, t.LastName 
            FROM tblnewsbyteacher n
            JOIN tblteacher t ON n.TeacherID = t.ID
            WHERE n.Title LIKE :key OR n.Description LIKE :key
            ORDER BY n.CreationDate DESC";
            
    $query = $dbh->prepare($sql);
    $query->bindValue(':key', '%' . $keyword . '%', PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);

} else {
    // --- DEFAULT MODE (SHOW ALL) ---
    $sql = "SELECT n.Title, n.Description, n.CreationDate, t.FirstName, t.LastName 
            FROM tblnewsbyteacher n
            JOIN tblteacher t ON n.TeacherID = t.ID
            ORDER BY n.CreationDate DESC";
            
    $query = $dbh->prepare($sql);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Announcements | VidyaVerse</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 50% 0%, #1e293b 0%, #0f172a 100%); 
            font-family: 'Outfit', sans-serif; color: #f8fafc;
            min-height: 100vh;
        }

        /* --- LAYOUT --- */
        .container { 
            padding: 100px 20px 40px 20px; /* Top padding for fixed header */
            max-width: 1000px; margin: 0 auto;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px; padding: 40px;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.5);
            margin-bottom: 30px;
            position: relative; overflow: hidden;
        }

        /* --- SEARCH BAR --- */
        .search-wrapper { display: flex; gap: 10px; margin-bottom: 30px; }
        
        .search-input {
            flex: 1;
            background: rgba(15, 23, 42, 0.6); border: 1px solid #334155;
            padding: 15px 20px; border-radius: 12px;
            color: #fff; font-size: 16px; outline: none; transition: 0.3s;
        }
        .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 15px rgba(59, 130, 246, 0.2); }

        .btn-search {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white; border: none; padding: 0 30px;
            border-radius: 12px; font-weight: 600; cursor: pointer;
            transition: 0.3s; display: flex; align-items: center; gap: 8px;
        }
        .btn-search:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(37, 99, 235, 0.4); }

        /* --- RESULTS LIST --- */
        .result-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 25px; border-radius: 16px;
            margin-bottom: 20px; transition: 0.3s; position: relative;
        }
        .result-item:hover { background: rgba(255, 255, 255, 0.05); border-left: 4px solid #3b82f6; transform: translateX(5px); }

        .res-title { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 8px; }
        
        .res-meta { 
            font-size: 13px; color: #94a3b8; margin-bottom: 15px; 
            display: flex; gap: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;
        }
        .res-meta i { color: #3b82f6; margin-right: 5px; }

        .res-desc { color: #cbd5e1; font-size: 15px; line-height: 1.6; }
        
        .highlight { color: #facc15; font-weight: bold; background: rgba(250, 204, 21, 0.1); padding: 0 2px; }
        
        .no-results { text-align: center; padding: 60px; color: #64748b; font-size: 16px; }
        .back-btn { display: inline-block; margin-bottom: 20px; color: #94a3b8; text-decoration: none; font-size: 14px; }
        .back-btn:hover { color: #fff; transform: translateX(-3px); }

    </style>
</head>
<body>

    <?php include_once('includes/header.php');?>

    <div class="container">
        
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="glass-card">
            <h2 style="margin-top:0; margin-bottom:20px;">Announcement Board</h2>
            
            <form method="post">
                <div class="search-wrapper">
                    <input type="text" name="keyword" class="search-input" placeholder="Search notices (e.g. 'Exam', 'Holiday')..." value="<?php echo htmlentities($keyword); ?>">
                    <button type="submit" name="search" class="btn-search"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>

            <div class="results-area">
                
                <?php if($search_mode): ?>
                    <div style="margin-bottom: 20px; font-size: 14px; color: #94a3b8;">
                        Found <?php echo count($results); ?> matching result(s) for "<span style="color:#fff;"><?php echo htmlentities($keyword); ?></span>"
                        <a href="search-announcements.php" style="color:#3b82f6; margin-left:10px; text-decoration:none;">(Clear Search)</a>
                    </div>
                <?php endif; ?>

                <?php if(count($results) > 0): ?>
                    <?php foreach($results as $row): 
                        // Highlight logic if searching
                        $title = htmlentities($row->Title);
                        $desc = htmlentities($row->Description);
                        
                        if($search_mode) {
                            $title = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<span class="highlight">$1</span>', $title);
                            $desc = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<span class="highlight">$1</span>', $desc);
                        }
                    ?>
                    <div class="result-item">
                        <div class="res-title"><?php echo $title; ?></div>
                        
                        <div class="res-meta">
                            <span><i class="fas fa-user-tie"></i> Posted by: <?php echo htmlentities($row->FirstName . " " . $row->LastName); ?></span>
                            <span><i class="far fa-clock"></i> <?php echo date("d M Y, h:i A", strtotime($row->CreationDate)); ?></span>
                        </div>
                        
                        <div class="res-desc"><?php echo $desc; ?></div>
                    </div>
                    <?php endforeach; ?>
                
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-inbox" style="font-size: 50px; margin-bottom: 15px; opacity: 0.5;"></i><br>
                        <?php echo $search_mode ? "No announcements matched your search." : "No announcements have been posted yet."; ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>

    <?php include('includes/footer.php');?>

</body>
</html>