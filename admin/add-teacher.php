<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// --- INSERT LOGIC ---
if(isset($_POST['submit'])) {
    $course_id = ($_POST['course_id'] == 'ALL') ? 0 : $_POST['course_id']; // Handle "ALL" case if saved to DB
    $empid = $_POST['empid']; 
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $mobnum = $_POST['mobnum'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $religion = $_POST['religion'];
    $address = $_POST['address'];
    $password = md5($_POST['password']); 
    
    $raw_subjects = isset($_POST['subject_ids']) ? $_POST['subject_ids'] : []; 
    $subject_ids = array_unique($raw_subjects); 
    
    // Legacy String Support
    $subject_names_arr = [];
    foreach($subject_ids as $sid) {
        $s_stmt = $dbh->prepare("SELECT SubjectFullname FROM tblsubject WHERE ID=:id");
        $s_stmt->execute(['id'=>$sid]);
        $s_row = $s_stmt->fetch(PDO::FETCH_OBJ);
        if($s_row) $subject_names_arr[] = $s_row->SubjectFullname;
    }
    $teaching_sub_str = implode(", ", $subject_names_arr);

    // Image Upload
    $propic = $_FILES["propic"]["name"];
    $extension = substr($propic,strlen($propic)-4,strlen($propic));
    $allowed_extensions = array(".jpg","jpeg",".png",".gif");

    if(!in_array(strtolower($extension), $allowed_extensions)) {
        echo "<script>alert('Invalid Image Format. Only JPG/PNG allowed.');</script>";
    } else {
        $propic = md5($propic).time().$extension;
        
        try {
            $ret = "SELECT Email FROM tblteacher WHERE Email=:email || MobileNumber=:mobnum || EmpID=:empid";
            $query = $dbh->prepare($ret);
            $query->bindParam(':empid', $empid);
            $query->bindParam(':mobnum', $mobnum);
            $query->bindParam(':email', $email);
            $query->execute();

            if($query->rowCount() == 0) {
                $dbh->beginTransaction(); 

                move_uploaded_file($_FILES["propic"]["tmp_name"], "images/".$propic);
                
                $sql = "INSERT INTO tblteacher(CourseID, EmpID, FirstName, LastName, MobileNumber, Email, Gender, Dob, Religion, Address, TeachingSub, Password, ProfilePic) 
                        VALUES(:cid, :empid, :fname, :lname, :mobnum, :email, :gender, :dob, :religion, :address, :tsub, :password, :propic)";
                
                $query = $dbh->prepare($sql);
                $query->bindParam(':cid', $course_id);
                $query->bindParam(':empid', $empid);
                $query->bindParam(':fname', $fname);
                $query->bindParam(':lname', $lname);
                $query->bindParam(':mobnum', $mobnum);
                $query->bindParam(':email', $email);
                $query->bindParam(':gender', $gender);
                $query->bindParam(':dob', $dob);
                $query->bindParam(':religion', $religion);
                $query->bindParam(':address', $address);
                $query->bindParam(':tsub', $teaching_sub_str);
                $query->bindParam(':password', $password);
                $query->bindParam(':propic', $propic);
                $query->execute();

                $lastTeacherId = $dbh->lastInsertId();

                $sql2 = "INSERT INTO tblteacher_subjects(TeacherID, SubjectID) VALUES(:tid, :sid)";
                $query2 = $dbh->prepare($sql2);
                
                foreach($subject_ids as $sid) {
                    if(!empty($sid)) {
                        $query2->bindParam(':tid', $lastTeacherId);
                        $query2->bindParam(':sid', $sid);
                        $query2->execute();
                    }
                }
                
                $dbh->commit();
                echo '<script>alert("Teacher Profile Created Successfully!"); window.location.href ="manage-teacher.php";</script>';
               
            } else {
                echo "<script>alert('Duplicate Entry: Email, OVC ID or Mobile already exists.');</script>";
            }
        } catch (Exception $e) {
            if($dbh->inTransaction()){ $dbh->rollBack(); }
            echo '<script>alert("System Error: ' . addslashes($e->getMessage()) . '");</script>';
        }
    }
}

// --- FETCH COURSES ---
$courseOptions = '<option value="ALL">ALL (Show All Subjects)</option>'; // Added ALL Option
$sqlCourse = "SELECT * FROM tblcourse ORDER BY CourseName ASC";
$qCourse = $dbh->query($sqlCourse);
while($c = $qCourse->fetch(PDO::FETCH_OBJ)) {
    $courseOptions .= '<option value="'.$c->ID.'">'.$c->CourseName.' - '.$c->BranchName.'</option>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include($_SERVER['DOCUMENT_ROOT'] . "/Vidyaverse/includes/app_headers.php"); ?>
    <title>Add Teacher | VidyaVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/themify-icons@1.0.1/css/themify-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL RESET --- */
        * { box-sizing: border-box; }
        body { 
            margin: 0; padding: 0;
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
            min-height: 100vh;
            padding-top: 80px; 
        }

        /* --- HEADER --- */
        .glass-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 70px;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 40px; z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        .brand { font-size: 18px; font-weight: 700; color: #fff; letter-spacing: 1px; display: flex; align-items: center; gap: 10px; }
        .nav-actions { display: flex; gap: 20px; }
        .nav-btn { text-decoration: none; color: #94a3b8; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .nav-btn:hover { color: #fff; }

        /* --- LAYOUT GRID --- */
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .page-wrapper { display: grid; grid-template-columns: 320px 1fr; gap: 30px; }
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

        /* --- UPLOAD ZONE --- */
        .profile-upload-zone {
            width: 160px; height: 160px; margin: 0 auto 20px auto;
            border-radius: 50%; border: 2px dashed #475569;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; position: relative; background: rgba(0,0,0,0.2);
            transition: 0.3s; cursor: pointer;
        }
        .profile-upload-zone:hover { border-color: #3b82f6; box-shadow: 0 0 20px rgba(59, 130, 246, 0.2); }
        .profile-upload-zone img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .upload-input { position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .upload-icon { font-size: 30px; color: #64748b; }

        /* --- MODERN INPUTS --- */
        .input-group { position: relative; margin-bottom: 25px; }
        .input-group i {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: #64748b; font-size: 18px; transition: 0.3s; pointer-events: none;
        }
        
        .modern-input {
            width: 100%; background: rgba(15, 23, 42, 0.6);
            border: 1px solid #334155; color: #fff;
            padding: 14px 14px 14px 48px; 
            border-radius: 12px; font-size: 14px; transition: 0.3s;
            appearance: none;
        }
        .modern-input:focus {
            border-color: #3b82f6; box-shadow: 0 0 15px rgba(59, 130, 246, 0.15); outline: none;
        }
        .modern-input:focus + i { color: #3b82f6; }
        
        /* Dropdown Styling */
        select.modern-input {
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23007CB2%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 15px top 50%;
            background-size: 12px auto;
        }
        select.modern-input option { background-color: #1e293b; color: #fff; padding: 10px; }
        select.modern-input option:disabled { color: #475569; font-style: italic; }

        .modern-label {
            position: absolute; top: -9px; left: 15px;
            background: #1e293b; padding: 0 6px;
            font-size: 11px; color: #94a3b8; border-radius: 4px;
        }

        /* --- SUBJECTS & BUTTONS --- */
        .subject-container { max-height: 300px; overflow-y: auto; padding-right: 5px; }
        .subject-row {
            display: flex; align-items: center; gap: 10px;
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 10px; border-radius: 10px; margin-bottom: 10px;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .btn-remove-sub { 
            width: 36px; height: 36px;
            background: rgba(239, 68, 68, 0.2); color: #ef4444; 
            border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.3s; font-size: 18px; line-height: 1;
        }
        .btn-remove-sub:hover { background: #ef4444; color: white; }

        .btn-add-sub {
            width: 100%; padding: 12px; border-radius: 10px;
            background: rgba(255,255,255,0.05); color: #10b981;
            border: 1px dashed #10b981; cursor: pointer; font-weight: 600;
            transition: 0.2s; margin-top: 10px;
        }
        .btn-add-sub:hover { background: rgba(16, 185, 129, 0.1); }

        .btn-glow {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white; border: none; width: 100%; padding: 16px;
            border-radius: 12px; font-size: 16px; font-weight: 700;
            letter-spacing: 1px; cursor: pointer; text-transform: uppercase;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
            transition: 0.3s; margin-top: 30px;
        }
        .btn-glow:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(139, 92, 246, 0.6); }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
    </style>
</head>
<body>

    <header class="glass-header">
        <div class="brand"><i class="ti-id-badge"></i> Admin Panel</div>
        <div class="nav-actions">
            <a href="dashboard.php" class="nav-btn"><i class="ti-arrow-left"></i> Dashboard</a>
            <a href="manage-teacher.php" class="nav-btn"><i class="ti-list"></i> Manage Faculty</a>
            <a href="logout.php" class="nav-btn" style="color:#ef4444;"><i class="ti-power-off"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <form method="post" enctype="multipart/form-data">
            <div class="page-wrapper">
                
                <div class="glass-card" style="height: fit-content;">
                    <div class="section-label">Identity Matrix</div>
                    
                    <div class="profile-upload-zone">
                        <input type="file" name="propic" class="upload-input" accept="image/*" onchange="previewImage(this)" required>
                        <div class="upload-icon" id="uploadIcon"><i class="ti-camera"></i></div>
                        <img id="imgPreview" src="" alt="Preview">
                    </div>
                    <p style="text-align:center; font-size:12px; color:#94a3b8; margin-bottom:25px;">Upload Profile Photo</p>

                    <div class="input-group">
                        <select name="course_id" id="courseDropdown" class="modern-input" required onchange="fetchSubjects(this.value)">
                            <option value="">Select Department...</option>
                            <?php echo $courseOptions; ?>
                        </select>
                        <i class="ti-book"></i>
                        <span class="modern-label">Department / Course</span>
                    </div>

                    <div class="input-group">
                        <input type="text" name="empid" class="modern-input" placeholder=" " required>
                        <i class="ti-id-badge"></i>
                        <span class="modern-label">Employee ID (OVC ID)</span>
                    </div>

                    <div class="input-group">
                        <input type="password" name="password" class="modern-input" placeholder=" " required>
                        <i class="ti-lock"></i>
                        <span class="modern-label">Secure Password</span>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="section-label">Faculty Data & Curriculum</div>

                    <div class="row" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="input-group">
                            <input type="text" name="fname" class="modern-input" placeholder=" " required>
                            <i class="ti-user"></i>
                            <span class="modern-label">First Name</span>
                        </div>
                        <div class="input-group">
                            <input type="text" name="lname" class="modern-input" placeholder=" " required>
                            <i class="ti-user"></i>
                            <span class="modern-label">Last Name</span>
                        </div>
                    </div>

                    <div class="row" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="input-group">
                            <input type="email" name="email" class="modern-input" placeholder=" " required>
                            <i class="ti-email"></i>
                            <span class="modern-label">Email Address</span>
                        </div>
                        <div class="input-group">
                            <input type="text" name="mobnum" class="modern-input" maxlength="10" pattern="[0-9]+" placeholder=" " required>
                            <i class="ti-mobile"></i>
                            <span class="modern-label">Contact Number</span>
                        </div>
                    </div>

                    <div class="row" style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:20px;">
                        <div class="input-group">
                            <select name="gender" class="modern-input" required>
                                <option value="" disabled selected></option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Others">Other</option>
                            </select>
                            <i class="ti-face-smile"></i>
                            <span class="modern-label">Gender</span>
                        </div>
                        <div class="input-group">
                            <input type="date" name="dob" class="modern-input" required>
                            <i class="ti-calendar"></i>
                            <span class="modern-label">Date of Birth</span>
                        </div>
                        <div class="input-group">
                            <input type="text" name="religion" class="modern-input" placeholder=" ">
                            <i class="ti-world"></i>
                            <span class="modern-label">Religion</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <input type="text" name="address" class="modern-input" placeholder=" " required>
                        <i class="ti-location-pin"></i>
                        <span class="modern-label">Residential Address</span>
                    </div>

                    <div class="section-label" style="margin-top:30px; border-color:rgba(16, 185, 129, 0.3); color:#10b981;">
                        Subject Allocation
                    </div>
                    
                    <div class="subject-container" id="subjectContainer">
                        <div style="text-align:center; color:#94a3b8; padding:20px; font-style:italic;" id="placeholderText">
                            Please select a Department/Course first to see available subjects.
                        </div>
                    </div>
                    
                    <button type="button" class="btn-add-sub" id="addBtn" onclick="addSubjectRow()" style="display:none;">
                        <i class="ti-plus"></i> Assign Additional Subject
                    </button>

                    <button type="submit" name="submit" class="btn-glow">
                        <i class="ti-check"></i> Register Faculty Member
                    </button>

                </div>
            </div>
        </form>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imgPreview').src = e.target.result;
                    document.getElementById('imgPreview').style.display = 'block';
                    document.getElementById('uploadIcon').style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // --- NEW AJAX LOGIC ---
        let currentSubjectOptions = ""; // Holds the currently allowed options

        function fetchSubjects(courseId) {
            const container = document.getElementById('subjectContainer');
            const addBtn = document.getElementById('addBtn');
            const placeholder = document.getElementById('placeholderText');

            // 1. Reset everything when course changes
            container.innerHTML = '';
            currentSubjectOptions = "";

            if (courseId === "") {
                if(placeholder) {
                    container.appendChild(placeholder);
                    placeholder.style.display = 'block';
                } else {
                    container.innerHTML = '<div style="text-align:center; color:#94a3b8; padding:20px; font-style:italic;" id="placeholderText">Please select a Department/Course first to see available subjects.</div>';
                }
                addBtn.style.display = 'none';
                return;
            }

            // 2. Fetch new subjects via AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get-subjects.php?cid=' + courseId, true);
            xhr.onload = function() {
                if (this.status === 200) {
                    currentSubjectOptions = this.responseText;
                    
                    // Add the first row automatically
                    addSubjectRow(); 
                    
                    // Show button
                    addBtn.style.display = 'block';
                }
            }
            xhr.send();
        }

        function addSubjectRow() {
            if(currentSubjectOptions === "") return;

            const container = document.getElementById('subjectContainer');
            const newRow = document.createElement('div');
            newRow.className = 'subject-row';
            
            newRow.innerHTML = `
                <select name="subject_ids[]" class="modern-input" style="border:none; background:transparent; padding:0;" required onchange="preventDuplicateSubjects()">
                    ${currentSubjectOptions}
                </select>
                <button type="button" class="btn-remove-sub" onclick="removeRow(this)">&times;</button>
            `;
            container.appendChild(newRow);
            preventDuplicateSubjects();
        }

        function removeRow(btn) {
            btn.parentNode.remove();
            
            // If no rows left, show placeholder? Optional UX choice.
            // keeping it simple for now.
            preventDuplicateSubjects();
        }

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
                        opt.style.color = '#64748b'; 
                    } else {
                        opt.disabled = false;
                        opt.style.color = '#fff';
                    }
                });
            });
        }
    </script>

</body>
</html>