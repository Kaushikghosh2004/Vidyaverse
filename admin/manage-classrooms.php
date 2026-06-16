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

// --- 1. DELETE CLASSROOM LOGIC (Pop-up Removed) ---
if(isset($_GET['delid'])) {
    $rid = intval($_GET['delid']);
    try {
        $sql = "DELETE FROM classrooms WHERE id=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_STR);
        $query->execute();
        
        // REFRESH PAGE WITHOUT POPUP
        echo "<script>window.location.href = 'manage-classrooms.php'</script>";
    } catch (Exception $e) {
        // Only show alert if there is a CRITICAL error
        echo "<script>alert('Cannot delete: Dependency found or error occurred.');</script>"; 
    }
}

// --- 2. ADD CLASSROOM LOGIC (Pop-up Removed) ---
if(isset($_POST['submit'])) {
    $room_name = $_POST['room_name'];
    $capacity = $_POST['capacity'];
    $course_id = $_POST['course_id'];
    
    if(empty($room_name) || empty($course_id) || empty($capacity)) {
        // Keeping this alert for validation, or you can remove it too
        echo "<script>alert('Please fill all fields');</script>";
    } else {
        try {
            // Using exact column names from your phpMyAdmin screenshot
            $sql = "INSERT INTO classrooms(room_name_or_number, capacity, CourseID) VALUES(:room_name, :capacity, :course_id)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':room_name', $room_name, PDO::PARAM_STR);
            $query->bindParam(':capacity', $capacity, PDO::PARAM_INT);
            $query->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $query->execute();
            
            if ($dbh->lastInsertId() > 0) {
                // REFRESH PAGE WITHOUT POPUP
                echo "<script>window.location.href ='manage-classrooms.php'</script>";
            } else {
                echo '<script>alert("Something went wrong. Please try again.")</script>';
            }
        } catch (Exception $e) {
            echo '<script>alert("Error: ' . addslashes($e->getMessage()) . '");</script>';
        }
    }
}

include('includes/header.php');
?>

<div class="app-container">
    
    <div class="simple-header">
        <div class="header-left">
            <div class="welcome-info">
                <span class="welcome-msg">Manage Classrooms</span>
                <span class="welcome-sub">Add and allocate rooms to departments</span>
            </div>
        </div>
        <div class="header-right">
            <a href="logout.php" class="logout-link">
                <i class="ti-power-off"></i> 
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="content-wrap">
        <div class="main">
            <div class="container-fluid">
                
                <style>
                    /* GLOBAL RESET */
                    * { box-sizing: border-box; }
                    body { 
                        background-color: #0f172a; 
                        font-family: 'Inter', 'Segoe UI', sans-serif; 
                        color: #f8fafc; 
                        margin: 0; padding: 0; 
                        overflow-x: hidden;
                    }

                    /* HIDE OLD INCLUDES */
                    .header { display: none !important; }
                    .sidebar { display: none !important; }

                    /* VARIABLES */
                    :root { 
                        --header-h: 80px; 
                        --bg-dark: #0f172a; 
                        --card-dark: #1e293b; 
                        --accent: #8b5cf6; 
                        --text-muted: #94a3b8;
                    }

                    /* LAYOUT STYLES */
                    .simple-header { 
                        position: fixed; top: 0; left: 0; width: 100%; height: var(--header-h); 
                        background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); z-index: 999; 
                        display: flex; align-items: center; justify-content: space-between; 
                        padding: 0 40px; border-bottom: 1px solid #334155; 
                    }
                    .header-left .welcome-msg { font-size: 20px; font-weight: 700; color: #fff; display: block; }
                    .header-left .welcome-sub { font-size: 13px; color: var(--text-muted); }
                    .logout-link { 
                        background: #ef4444; color: #fff; padding: 8px 24px; border-radius: 6px; 
                        text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; font-size: 14px;
                        transition: background 0.2s;
                    }
                    .logout-link:hover { background: #dc2626; }
                    .content-wrap { margin-top: var(--header-h); padding: 40px; width: 100%; min-height: 100vh; }
                    
                    /* GRID */
                    .manage-grid { display: grid; grid-template-columns: 350px 1fr; gap: 30px; max-width: 1600px; margin: 0 auto; }
                    @media (max-width: 992px) { .manage-grid { grid-template-columns: 1fr; } }

                    /* CARDS */
                    .card { background: var(--card-dark); border: 1px solid #334155; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); height: 100%; }
                    .card-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #334155; }
                    .card-title { font-size: 18px; font-weight: 700; color: #fff; margin: 0; }

                    /* FORMS */
                    .form-group { margin-bottom: 20px; }
                    .form-group label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 8px; font-weight: 500; }
                    .form-control { width: 100%; background: #0f172a; border: 1px solid #334155; color: #fff; padding: 10px 15px; border-radius: 8px; font-size: 14px; transition: 0.2s; }
                    .form-control:focus { outline: none; border-color: var(--accent); }
                    .btn-submit { background: var(--accent); color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; transition: 0.2s; }
                    .btn-submit:hover { background: #7c3aed; }

                    /* TABLES */
                    .table-responsive { overflow-x: auto; }
                    .table { width: 100%; border-collapse: collapse; }
                    .table th { text-align: left; padding: 12px 15px; background: rgba(0,0,0,0.2); color: #cbd5e1; font-size: 12px; text-transform: uppercase; font-weight: 600; border-bottom: 1px solid #334155; }
                    .table td { padding: 12px 15px; border-bottom: 1px solid #334155; color: var(--text-muted); font-size: 14px; }
                    .table tr:last-child td { border-bottom: none; }
                    .table tr:hover td { background: rgba(255,255,255,0.02); color: #fff; }
                    .btn-delete { padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-block; background: rgba(239, 68, 68, 0.15); color: #f87171; }
                    .btn-delete:hover { background: #ef4444; color: white; }
                    .footer { text-align: center; padding: 20px; color: var(--text-muted); border-top: 1px solid #334155; margin-top: 20px; }
                </style>

                <div class="manage-grid">
                    
                    <div class="grid-col-left">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Add New Classroom</h4>
                            </div>
                            <form method="post">
                                <div class="form-group">
                                    <label>Room Name / Number</label>
                                    <input type="text" class="form-control" name="room_name" placeholder="e.g. Lab-101" required>
                                </div>
                                <div class="form-group">
                                    <label>Capacity</label>
                                    <input type="number" class="form-control" name="capacity" placeholder="e.g. 60" required>
                                </div>
                                <div class="form-group">
                                    <label>Allocated Department</label>
                                    <select class="form-control" name="course_id" required>
                                        <option value="">Select Branch</option>
                                        <?php
                                        try {
                                            $sql_courses="SELECT * from tblcourse"; 
                                            $query_courses=$dbh->prepare($sql_courses); 
                                            $query_courses->execute(); 
                                            $results_courses=$query_courses->fetchAll(PDO::FETCH_OBJ); 
                                            foreach($results_courses as $row_course){ 
                                        ?>
                                        <option value="<?php echo htmlentities($row_course->ID);?>">
                                            <?php echo htmlentities($row_course->CourseName . " (" . $row_course->BranchName . ")");?>
                                        </option>
                                        <?php 
                                            }
                                        } catch(Exception $e) { echo "<option disabled>Error loading courses</option>"; } 
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" name="submit" class="btn-submit">Save Classroom</button>
                            </form>
                        </div>
                    </div>

                    <div class="grid-col-right">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Allocated Classrooms</h4>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Room</th>
                                            <th>Allocated Department</th>
                                            <th>Capacity</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $sql="SELECT t.*, c.CourseName, c.BranchName 
                                                  FROM classrooms t 
                                                  JOIN tblcourse c ON t.CourseID = c.ID 
                                                  ORDER BY t.id DESC";
                                            $query = $dbh->prepare($sql);
                                            $query->execute();
                                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                                            $cnt=1;
                                            
                                            if($query->rowCount() > 0) {
                                                foreach($results as $row) { 
                                                    $ID = $row->id;
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($cnt);?></td>
                                                        <td style="font-weight: 600; color: #f8fafc;"><?php echo htmlentities($row->room_name_or_number);?></td>
                                                        <td><?php echo htmlentities($row->CourseName);?> (<?php echo htmlentities($row->BranchName);?>)</td>
                                                        <td><?php echo htmlentities($row->capacity);?></td>
                                                        <td>
                                                            <a href="manage-classrooms.php?delid=<?php echo htmlentities($ID);?>" class="btn-delete" onclick="return confirm('Delete this classroom?');">Delete</a>
                                                        </td>
                                                    </tr>
                                                    <?php $cnt++; 
                                                }
                                            } else { ?>
                                                <tr><td colspan="5" style="text-align:center; padding: 20px;">No data found</td></tr>
                                            <?php } 
                                        } catch (Exception $e) { ?>
                                            <tr><td colspan="5" style="text-align:center; padding: 20px;">No data found</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php');?>