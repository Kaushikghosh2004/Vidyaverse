<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Security Check
if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// INSERT LOGIC
if(isset($_POST['submit']))
{
    $classname = $_POST['classname'];
    $section = $_POST['section'];

    // Basic Validation
    if(empty($classname) || empty($section)) {
        echo "<script>alert('Please fill all fields');</script>";
    } else {
        try {
            // Adjust column names if your DB uses 'Class' instead of 'ClassName'
            $sql = "INSERT INTO tblclass(ClassName, Section) VALUES(:classname, :section)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':classname', $classname, PDO::PARAM_STR);
            $query->bindParam(':section', $section, PDO::PARAM_STR);
            $query->execute();

            $LastInsertId = $dbh->lastInsertId();
            if ($LastInsertId > 0) {
                echo '<script>alert("Class has been added successfully.")</script>';
                echo "<script>window.location.href ='manage-classrooms.php'</script>";
            } else {
                echo '<script>alert("Something went wrong. Please try again.")</script>';
            }
        } catch (Exception $e) {
            // Fallback for different column names
            echo '<script>alert("Error: ' . addslashes($e->getMessage()) . '");</script>';
        }
    }
}

include('includes/header.php');
// Sidebar removed per request
?>

<!-- === LAYOUT CONTAINER === -->
<div class="app-container">
    
    <!-- === CUSTOM DARK HEADER (Full Width) === -->
    <div class="simple-header">
        <div class="header-left">
            <div class="welcome-info">
                <span class="welcome-msg">Add New Class</span>
                <span class="welcome-sub">Create a new class section</span>
            </div>
        </div>
        <div class="header-right">
            <a href="logout.php" class="logout-link">
                <i class="ti-power-off"></i> 
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- === MAIN CONTENT WRAPPER === -->
    <div class="content-wrap" id="contentWrap">
        <div class="main">
            <div class="container-fluid">
                
                <!-- Custom Styles -->
                <style>
                    /* GLOBAL RESET */
                    * { box-sizing: border-box; }
                    body { 
                        background-color: #0f172a; 
                        font-family: 'Inter', 'Segoe UI', sans-serif; 
                        margin: 0; padding: 0; 
                        overflow-x: hidden;
                        color: #f8fafc;
                    }

                    /* HIDE OLD INCLUDES */
                    .header { display: none !important; }
                    .sidebar { display: none !important; }

                    /* VARIABLES */
                    :root {
                        --header-h: 80px;
                        --bg-dark: #0f172a;
                        --card-dark: #1e293b;
                        --text-white: #f8fafc;
                        --text-grey: #94a3b8;
                        --accent: #8b5cf6; /* Purple for Classes */
                    }

                    /* --- HEADER STYLES (Full Width) --- */
                    .simple-header {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: var(--header-h);
                        background: rgba(15, 23, 42, 0.95);
                        backdrop-filter: blur(10px);
                        z-index: 999;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 0 40px;
                        border-bottom: 1px solid #334155;
                    }

                    .header-left { display: flex; align-items: center; gap: 20px; }
                    .welcome-msg { font-size: 20px; font-weight: 700; color: var(--text-white); }
                    .welcome-sub { font-size: 13px; color: var(--text-grey); }

                    .logout-link {
                        background: #ef4444; color: #fff; padding: 8px 24px; border-radius: 6px;
                        text-decoration: none; font-weight: 600; font-size: 14px;
                        display: flex; align-items: center; gap: 8px; transition: 0.2s;
                    }
                    .logout-link:hover { background: #dc2626; transform: translateY(-1px); }

                    /* --- CONTENT WRAPPER (Full Width) --- */
                    .content-wrap {
                        margin-top: var(--header-h);
                        margin-left: 0;
                        padding: 40px;
                        min-height: calc(100vh - var(--header-h));
                        width: 100%;
                    }

                    /* --- FORM STYLES --- */
                    .card {
                        background: var(--card-dark); border: 1px solid #334155;
                        border-radius: 16px; padding: 40px; max-width: 800px; margin: 0 auto;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                    }
                    .card-title { font-size: 22px; font-weight: 700; color: var(--text-white); margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid #334155; }
                    
                    .form-group { margin-bottom: 24px; }
                    .form-group label { display: block; font-size: 14px; color: var(--text-grey); margin-bottom: 8px; font-weight: 500; }
                    
                    .form-control { 
                        width: 100%; background: #0f172a; border: 1px solid #334155; 
                        color: var(--text-white); padding: 12px 16px; border-radius: 8px; font-size: 14px;
                        transition: all 0.2s;
                    }
                    .form-control:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2); }
                    
                    .btn-submit {
                        background: var(--accent); color: white; padding: 14px 30px; border: none;
                        border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; width: 100%; margin-top: 15px;
                    }
                    .btn-submit:hover { background: #7c3aed; }

                    .footer { text-align: center; padding: 20px; color: var(--text-grey); border-top: 1px solid #334155; margin-top: 20px; }
                </style>

                <!-- Add Class Form -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <h4 class="card-title">Enter Class Details</h4>
                            <form method="post">
                                <div class="form-group">
                                    <label>Class Name</label>
                                    <input type="text" name="classname" class="form-control" placeholder="e.g., 10th Grade" required>
                                </div>
                                <div class="form-group">
                                    <label>Section</label>
                                    <input type="text" name="section" class="form-control" placeholder="e.g., A, B, Science" required>
                                </div>
                                <button type="submit" name="submit" class="btn-submit">Add Class</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12"><div class="footer"><p>2024 © VIDYAVERSE Admin Board.</p></div></div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php');?>