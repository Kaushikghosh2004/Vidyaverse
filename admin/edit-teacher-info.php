<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

$eid = intval($_GET['editid']);

// --- UPDATE LOGIC ---
if(isset($_POST['submit'])) {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $mobnum = trim($_POST['mobnum']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $religion = trim($_POST['religion']);
    $address = trim($_POST['address']);
    $course_id = ($_POST['course_id'] == 'ALL') ? 0 : $_POST['course_id']; 
    
    $raw_subjects = isset($_POST['subject_ids']) ? $_POST['subject_ids'] : []; 
    $subject_ids = array_unique($raw_subjects); 

    try {
        $dbh->beginTransaction();

        $propic = $_FILES["propic"]["name"];
        $imageUpdateSql = ""; 
        
        if(!empty($propic)) {
            $extension = strtolower(pathinfo($propic, PATHINFO_EXTENSION));
            $allowed_extensions = array("jpg","jpeg","png","gif");
            
            if(!in_array($extension, $allowed_extensions)) {
                $_SESSION['toast_msg'] = "Invalid Image Format. Only JPG/PNG allowed.";
                $_SESSION['toast_type'] = "error";
                // FIXED: Dynamic redirect regardless of filename
                header("Location: " . $_SERVER['PHP_SELF'] . "?editid=" . $eid);
                exit;
            } else {
                $propic = md5($propic).time().".".$extension;
                move_uploaded_file($_FILES["propic"]["tmp_name"], "images/".$propic);
                $imageUpdateSql = ", ProfilePic=:propic"; 
            }
        }

        $sql = "UPDATE tblteacher SET FirstName=:fname, LastName=:lname, MobileNumber=:mobnum, Email=:email, Gender=:gender, Dob=:dob, Religion=:religion, Address=:address, CourseID=:cid $imageUpdateSql WHERE ID=:eid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':fname', $fname);
        $query->bindParam(':lname', $lname);
        $query->bindParam(':mobnum', $mobnum);
        $query->bindParam(':email', $email);
        $query->bindParam(':gender', $gender);
        $query->bindParam(':dob', $dob);
        $query->bindParam(':religion', $religion);
        $query->bindParam(':address', $address);
        $query->bindParam(':cid', $course_id); 
        $query->bindParam(':eid', $eid);
        
        if(!empty($propic) && !empty($imageUpdateSql)) {
            $query->bindParam(':propic', $propic);
        }
        
        $query->execute();

        // Update Linking Table (tblteacher_subjects)
        $delSql = "DELETE FROM tblteacher_subjects WHERE TeacherID=:eid";
        $delQuery = $dbh->prepare($delSql);
        $delQuery->bindParam(':eid', $eid);
        $delQuery->execute();

        if (!empty($subject_ids)) {
            $insSql = "INSERT INTO tblteacher_subjects(TeacherID, SubjectID) VALUES(:tid, :sid)";
            $insQuery = $dbh->prepare($insSql);
            foreach($subject_ids as $sid) {
                if(!empty($sid)) {
                    $insQuery->bindParam(':tid', $eid);
                    $insQuery->bindParam(':sid', $sid);
                    $insQuery->execute();
                }
            }
        }

        $dbh->commit();
        $_SESSION['toast_msg'] = "Faculty identity updated successfully.";
        $_SESSION['toast_type'] = "success";
        
        // FIXED: Dynamic redirect back to itself safely
        header("Location: " . $_SERVER['PHP_SELF'] . "?editid=" . $eid); 
        exit;

    } catch (Exception $e) {
        $dbh->rollBack();
        $_SESSION['toast_msg'] = "Database Error: " . htmlspecialchars($e->getMessage());
        $_SESSION['toast_type'] = "error";
        
        // FIXED: Dynamic redirect back to itself safely
        header("Location: " . $_SERVER['PHP_SELF'] . "?editid=" . $eid);
        exit;
    }
}

// Fetch Toast Data
$toastMsg = $_SESSION['toast_msg'] ?? '';
$toastType = $_SESSION['toast_type'] ?? '';
unset($_SESSION['toast_msg'], $_SESSION['toast_type']);

// --- FETCH DATA ---
$sql = "SELECT * FROM tblteacher WHERE ID=:eid";
$query = $dbh->prepare($sql);
$query->bindParam(':eid', $eid);
$query->execute();
$teacher = $query->fetch(PDO::FETCH_OBJ);

if(!$teacher) {
    echo "<script>alert('Teacher not found.'); window.location.href='manage-teacher.php';</script>";
    exit;
}

// --- FETCH COURSES ---
$courseOptions = '<option value="ALL">ALL (Show All Subjects)</option>';
$sqlCourses = "SELECT * FROM tblcourse"; 
$qCourses = $dbh->prepare($sqlCourses);
$qCourses->execute();
$courses = $qCourses->fetchAll(PDO::FETCH_OBJ);
foreach($courses as $row) {
    $selected = ($teacher->CourseID == $row->ID) ? 'selected' : '';
    $courseOptions .= '<option value="'.$row->ID.'" '.$selected.'>'.$row->CourseName.' - '.$row->BranchName.'</option>';
}

// --- FETCH ASSIGNED SUBJECTS IDs ---
$assigned_subjects = [];
$sqlSubList = "SELECT SubjectID FROM tblteacher_subjects WHERE TeacherID=:eid";
$qSubList = $dbh->prepare($sqlSubList);
$qSubList->bindParam(':eid', $eid);
$qSubList->execute();
$rows = $qSubList->fetchAll(PDO::FETCH_OBJ);
foreach($rows as $r) {
    $assigned_subjects[] = $r->SubjectID;
}

// --- GENERATE INITIAL SUBJECT OPTIONS (Based on Saved Course) ---
$currentCid = $teacher->CourseID;
if($currentCid == 0 || $currentCid == 'ALL') {
    $sqlInit = "SELECT * FROM tblsubject ORDER BY SubjectFullname ASC";
    $stmtInit = $dbh->prepare($sqlInit);
} else {
    $sqlInit = "SELECT * FROM tblsubject WHERE CourseID=:cid ORDER BY SubjectFullname ASC";
    $stmtInit = $dbh->prepare($sqlInit);
    $stmtInit->bindParam(':cid', $currentCid);
}
$stmtInit->execute();
$initResults = $stmtInit->fetchAll(PDO::FETCH_OBJ);

$initial_subject_options = '<option value="" style="background:#1e293b; color:#fff;">Select Subject...</option>';
foreach($initResults as $ir) {
    $initial_subject_options .= '<option style="background:#1e293b; color:#fff;" value="'.$ir->ID.'">'.$ir->SubjectFullname.' ('.$ir->SubjectCode.')</option>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    <title>Edit Faculty | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL & THEME --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc;
            min-height: 100vh; padding-top: 80px; 
        }

        /* --- HEADER --- */
        .glass-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 40px; z-index: 1000;
        }
        .brand { font-size: 18px; font-weight: 700; color: #fff; letter-spacing: 1px; display:flex; align-items:center; gap:10px; }
        .nav-btn { text-decoration: none; color: #94a3b8; font-size: 14px; font-weight: 600; display:flex; align-items:center; gap:8px; transition:0.3s; }
        .nav-btn:hover { color: #fff; }

        /* --- LAYOUT --- */
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .page-wrapper { display: grid; grid-template-columns: 300px 1fr; gap: 30px; }
        @media(max-width: 992px) { .page-wrapper { grid-template-columns: 1fr; } }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px; padding: 35px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        .section-label {
            font-size: 13px; text-transform: uppercase; letter-spacing: 2px;
            color: #3b82f6; margin-bottom: 25px; font-weight: 700;
            border-bottom: 1px solid rgba(59, 130, 246, 0.3); padding-bottom: 10px;
        }

        /* --- IMAGE UPLOAD --- */
        .profile-upload-zone {
            width: 180px; height: 180px; margin: 0 auto 20px auto;
            border-radius: 50%; border: 2px dashed #475569;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; position: relative; background: rgba(0,0,0,0.2);
            transition: 0.3s; cursor: pointer;
        }
        .profile-upload-zone:hover { border-color: #3b82f6; box-shadow: 0 0 20px rgba(59, 130, 246, 0.2); }
        .profile-upload-zone img { width: 100%; height: 100%; object-fit: cover; }
        .upload-input { position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .upload-overlay {
            position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.6);
            color: #fff; font-size: 12px; padding: 5px; text-align: center;
            opacity: 0; transition: 0.3s;
        }
        .profile-upload-zone:hover .upload-overlay { opacity: 1; }

        /* --- INPUTS --- */
        .input-group { position: relative; margin-bottom: 25px; }
        .modern-input {
            width: 100%; background: rgba(15, 23, 42, 0.6);
            border: 1px solid #334155; color: #fff;
            padding: 14px; border-radius: 12px; font-size: 14px; transition: 0.3s;
        }
        .modern-input:focus { border-color: #3b82f6; outline: none; }
        .modern-label {
            position: absolute; top: -9px; left: 15px;
            background: #1e293b; padding: 0 6px;
            font-size: 11px; color: #94a3b8; border-radius: 4px;
        }

        /* --- SUBJECT MODULE --- */
        .subject-container { max-height: 300px; overflow-y: auto; padding-right: 5px; }
        .subject-row {
            display: flex; align-items: center; gap: 10px;
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 10px; border-radius: 10px; margin-bottom: 10px;
        }
        
        select.modern-input {
            color: #fff !important; 
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23007CB2%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat; background-position: right 15px top 50%; background-size: 12px auto; appearance: none;
        }
        select.modern-input option { background-color: #1e293b; color: #fff; padding: 10px; }
        select.modern-input option:disabled { background-color: #0f172a; color: #475569 !important; font-style: italic; }

        .btn-add-sub {
            width: 100%; padding: 12px; border-radius: 10px;
            background: rgba(255,255,255,0.05); color: #10b981;
            border: 1px dashed #10b981; cursor: pointer; font-weight: 600;
            transition: 0.2s; margin-top: 10px;
        }
        .btn-add-sub:hover { background: rgba(16, 185, 129, 0.1); }

        .btn-remove-sub { 
            width: 36px; height: 36px; background: rgba(239, 68, 68, 0.2); 
            color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 18px; line-height: 1;
        }
        .btn-remove-sub:hover { background: #ef4444; color: white; }

        .btn-glow {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white; border: none; width: 100%; padding: 16px;
            border-radius: 12px; font-size: 16px; font-weight: 700;
            letter-spacing: 1px; cursor: pointer; text-transform: uppercase;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4); margin-top: 30px;
        }
        .btn-glow:hover { transform: translateY(-3px); }

        /* --- TOAST NOTIFICATION --- */
        .glass-toast {
            position: fixed; top: 90px; right: -400px;
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1); border-left: 4px solid #06b6d4;
            padding: 18px 25px; border-radius: 12px;
            display: flex; align-items: center; gap: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6); z-index: 9999;
            transition: right 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .glass-toast.show { right: 30px; }
        .toast-icon { font-size: 24px; }
        .toast-content h4 { margin: 0 0 4px; font-size: 14px; font-weight: 800; color: #fff; letter-spacing: 1px; text-transform: uppercase; }
        .toast-content p { margin: 0; font-size: 12px; color: #a1a1aa; }
        .toast-success { border-left-color: #10b981; }
        .toast-success .toast-icon { color: #10b981; text-shadow: 0 0 15px rgba(16, 185, 129, 0.5); }
        .toast-error { border-left-color: #ef4444; }
        .toast-error .toast-icon { color: #ef4444; text-shadow: 0 0 15px rgba(239, 68, 68, 0.5); }
    </style>
</head>
<body>

    <div id="syncToast" class="glass-toast <?php echo ($toastType == 'success') ? 'toast-success' : 'toast-error'; ?>">
        <i class="fas <?php echo ($toastType == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> toast-icon"></i>
        <div class="toast-content">
            <h4><?php echo ($toastType == 'success') ? 'System Update' : 'System Alert'; ?></h4>
            <p><?php echo $toastMsg; ?></p>
        </div>
    </div>

    <header class="glass-header">
        <div class="brand"><i class="ti-pencil-alt"></i> Edit Faculty Profile</div>
        <div class="nav-actions">
            <a href="manage-teacher.php" class="nav-btn"><i class="ti-arrow-left"></i> Back to List</a>
            <a href="logout.php" class="nav-btn" style="color:#ef4444;"><i class="ti-power-off"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <form method="post" enctype="multipart/form-data">
            <div class="page-wrapper">
                
                <div class="glass-card" style="height: fit-content;">
                    <div class="section-label">Profile Image</div>
                    
                    <div class="profile-upload-zone">
                        <input type="file" name="propic" class="upload-input" accept="image/*" onchange="previewImage(this)">
                        <?php 
                            $imgSrc = (!empty($teacher->ProfilePic)) ? "images/".$teacher->ProfilePic : "images/default.jpg"; 
                        ?>
                        <img id="imgPreview" src="<?php echo $imgSrc; ?>" alt="Teacher Photo">
                        <div class="upload-overlay"><i class="ti-camera"></i> Change</div>
                    </div>
                    <p style="text-align:center; font-size:12px; color:#94a3b8;">Click image to update</p>
                    
                    <div class="input-group" style="margin-top:30px;">
                        <input type="text" value="<?php echo htmlentities($teacher->EmpID);?>" class="modern-input" readonly style="color:#94a3b8; cursor:not-allowed;">
                        <span class="modern-label">OVC ID (Locked)</span>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="section-label">Personal Information</div>
                    
                    <div class="row" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="input-group">
                            <input type="text" name="fname" class="modern-input" value="<?php echo htmlentities($teacher->FirstName);?>" required>
                            <span class="modern-label">First Name</span>
                        </div>
                        <div class="input-group">
                            <input type="text" name="lname" class="modern-input" value="<?php echo htmlentities($teacher->LastName);?>" required>
                            <span class="modern-label">Last Name</span>
                        </div>
                    </div>

                    <div class="row" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="input-group">
                            <input type="email" name="email" class="modern-input" value="<?php echo htmlentities($teacher->Email);?>" required>
                            <span class="modern-label">Email Address</span>
                        </div>
                        <div class="input-group">
                            <input type="text" name="mobnum" class="modern-input" value="<?php echo htmlentities($teacher->MobileNumber);?>" required>
                            <span class="modern-label">Contact Number</span>
                        </div>
                    </div>

                    <div class="row" style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:20px;">
                        <div class="input-group">
                            <select name="gender" class="modern-input" required>
                                <option value="Male" <?php if($teacher->Gender=='Male') echo 'selected';?>>Male</option>
                                <option value="Female" <?php if($teacher->Gender=='Female') echo 'selected';?>>Female</option>
                                <option value="Others" <?php if($teacher->Gender=='Others') echo 'selected';?>>Other</option>
                            </select>
                            <span class="modern-label">Gender</span>
                        </div>
                        <div class="input-group">
                            <input type="date" name="dob" class="modern-input" value="<?php echo htmlentities($teacher->Dob);?>" required>
                            <span class="modern-label">Date of Birth</span>
                        </div>
                        <div class="input-group">
                            <input type="text" name="religion" class="modern-input" value="<?php echo htmlentities($teacher->Religion);?>">
                            <span class="modern-label">Religion</span>
                        </div>
                        
                        <div class="input-group">
                             <select name="course_id" class="modern-input" required onchange="fetchSubjects(this.value)">
                                <option value="">Select Course & Branch</option>
                                <?php echo $courseOptions; ?>
                            </select>
                            <span class="modern-label">Course / Dept</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <input type="text" name="address" class="modern-input" value="<?php echo htmlentities($teacher->Address);?>" required>
                        <span class="modern-label">Address</span>
                    </div>

                    <div class="section-label" style="margin-top:30px; border-color:rgba(16, 185, 129, 0.3); color:#10b981;">
                        Subject Management
                    </div>
                    
                    <div class="subject-container" id="subjectContainer">
                        </div>
                    
                    <button type="button" class="btn-add-sub" onclick="addSubjectRow(null)">
                        <i class="ti-plus"></i> Add New Subject
                    </button>

                    <button type="submit" name="submit" class="btn-glow">Update Faculty Details</button>

                </div>
            </div>
        </form>
    </div>

    <script>
        // --- TOAST ANIMATION ---
        document.addEventListener("DOMContentLoaded", function() {
            const toastMsg = "<?php echo addslashes($toastMsg); ?>";
            if (toastMsg.trim() !== "") {
                const toast = document.getElementById('syncToast');
                setTimeout(() => { toast.classList.add('show'); }, 100);
                setTimeout(() => { toast.classList.remove('show'); }, 3500);
            }
        });

        // --- IMAGE PREVIEW ---
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imgPreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // --- GLOBAL VARIABLES ---
        let currentSubjectOptions = `<?php echo $initial_subject_options; ?>`;
        const assignedIDs = <?php echo json_encode($assigned_subjects); ?>;
        const container = document.getElementById('subjectContainer');

        // --- FETCH NEW SUBJECTS (AJAX) ---
        function fetchSubjects(courseId) {
            container.innerHTML = '';
            if (courseId === "") {
                currentSubjectOptions = "";
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get-subjects.php?cid=' + courseId, true);
            xhr.onload = function() {
                if (this.status === 200) {
                    currentSubjectOptions = this.responseText;
                    addSubjectRow(null); 
                }
            }
            xhr.send();
        }

        // --- ADD ROW FUNCTION ---
        function addSubjectRow(selectedValue = null) {
            if(!currentSubjectOptions) return; 

            const newRow = document.createElement('div');
            newRow.className = 'subject-row';
            
            let selectHTML = `<select name="subject_ids[]" class="modern-input" style="border:none; background:transparent; padding:0; color:#fff;" required onchange="preventDuplicateSubjects()">`;
            let options = currentSubjectOptions;
            
            if(selectedValue) {
                let search = `value="${selectedValue}"`;
                options = options.replace(search, `value="${selectedValue}" selected`);
            }
            
            selectHTML += options + `</select>`;

            newRow.innerHTML = `
                ${selectHTML}
                <button type="button" class="btn-remove-sub" onclick="removeRow(this)">&times;</button>
            `;
            container.appendChild(newRow);
            preventDuplicateSubjects();
        }

        function removeRow(btn) {
            btn.parentNode.remove();
            preventDuplicateSubjects(); 
        }

        // --- DUPLICATE PREVENTION LOGIC ---
        function preventDuplicateSubjects() {
            const allSelects = document.querySelectorAll('select[name="subject_ids[]"]');
            const selectedValues = Array.from(allSelects).map(s => s.value).filter(val => val !== "");

            allSelects.forEach(select => {
                const myValue = select.value;
                const options = select.querySelectorAll('option');

                options.forEach(opt => {
                    if (opt.value === "") return;

                    if (selectedValues.includes(opt.value) && opt.value !== myValue) {
                        opt.disabled = true;
                        opt.style.color = '#475569'; 
                    } else {
                        opt.disabled = false;
                        opt.style.color = '#fff'; 
                    }
                });
            });
        }

        // --- INITIALIZATION ---
        window.onload = function() {
            if(assignedIDs.length > 0) {
                assignedIDs.forEach(id => { addSubjectRow(id); });
            } else {
                addSubjectRow();
            }
        };
    </script>

</body>
</html>