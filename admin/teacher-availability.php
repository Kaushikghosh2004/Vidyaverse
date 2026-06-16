<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

$pageTitle = "Teacher Availability";
$pageSubTitle = "Check Faculty Schedules & Free Slots";
include('includes/header.php');
?>

<div class="container-fluid">
    
    <style>
        /* PAGE SPECIFIC STYLES */
        :root {
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --neon-blue: #3b82f6;
            --neon-green: #10b981;
            --neon-purple: #8b5cf6;
        }

        body { 
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(10, 10, 20) 90%); 
            font-family: 'Inter', sans-serif; color: #f8fafc;
        }

        /* GLASS CARD */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 20px;
        }
        .header-title { font-size: 20px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }

        /* FORM ELEMENTS */
        .form-group label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 8px; font-weight: 500; }
        
        .form-control {
            width: 100%; background: rgba(15, 23, 42, 0.6);
            border: 1px solid #334155; color: #fff;
            padding: 12px; border-radius: 12px; font-size: 14px; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--neon-blue); box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        
        /* DROPDOWN FIX */
        select.form-control {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat; background-position: right 15px center; background-size: 16px;
            appearance: none;
        }
        select.form-control option { background-color: #1e293b; color: #fff; padding: 10px; }

        /* TABLE STYLING */
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        
        .table th { 
            text-align: left; padding: 15px; 
            color: #94a3b8; font-size: 12px; text-transform: uppercase; font-weight: 600; 
            letter-spacing: 1px;
        }
        
        .table td { 
            padding: 15px; 
            background: rgba(30, 41, 59, 0.6); 
            color: #e2e8f0; font-size: 14px; vertical-align: middle;
            border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .table tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; border-left: 1px solid rgba(255,255,255,0.05); }
        .table tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; border-right: 1px solid rgba(255,255,255,0.05); }
        .table tr:hover td { background: rgba(59, 130, 246, 0.1); }

        /* STATUS BADGES */
        .badge-booked { 
            background: rgba(239, 68, 68, 0.15); color: #f87171; 
            padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .badge-batch { 
            background: rgba(59, 130, 246, 0.15); color: #60a5fa; 
            padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;
        }
        
        .day-highlight { color: var(--neon-green); font-weight: bold; }
    </style>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="glass-card">
                <div class="section-header">
                    <div class="header-title"><i class="ti-search"></i> Select Teacher</div>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label>Choose Faculty Member</label>
                        <select class="form-control" name="teacher_id" onchange="this.form.submit()">
                            <option value="">-- Select Teacher to View Schedule --</option>
                            <?php
                            $sql="SELECT * from tblteacher ORDER BY FirstName ASC";
                            $query = $dbh->prepare($sql);
                            $query->execute();
                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                            
                            if($query->rowCount() > 0) {
                                foreach($results as $row) {
                                    $selected = (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $row->ID) ? "selected" : "";
                                    $tName = $row->FirstName . " " . $row->LastName;
                                    echo '<option value="'.$row->ID.'" '.$selected.'>'.$tName.' (ID: '.$row->EmpID.')</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if(isset($_POST['teacher_id']) && !empty($_POST['teacher_id'])) { 
                $tid = $_POST['teacher_id'];
                
                // FIXED SQL LOGIC: Switched to LEFT JOINs and added COALESCE to handle missing dependencies safely
                try {
                    $sql_sched = "SELECT ts.day_of_week, ts.start_time, ts.end_time, 
                                         COALESCE(s.SubjectFullname, 'Unknown Subject') as SubjectFullname, 
                                         COALESCE(b.batch_name, 'Unassigned Batch') as batch_name, 
                                         COALESCE(c.room_name_or_number, 'TBA') as room_name_or_number 
                                  FROM timetable_schedule ts
                                  LEFT JOIN tblsubject s ON ts.subject_id = s.ID
                                  LEFT JOIN batches b ON ts.batch_id = b.id
                                  LEFT JOIN classrooms c ON ts.classroom_id = c.id
                                  WHERE ts.teacher_id = :tid
                                  ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), ts.start_time";
                    
                    $query_sched = $dbh->prepare($sql_sched);
                    $query_sched->bindParam(':tid', $tid);
                    $query_sched->execute();
                    $schedule_data = $query_sched->fetchAll(PDO::FETCH_OBJ);
                } catch (Exception $e) {
                    $schedule_data = []; // Handle missing table error gracefully
                }
            ?>
            
            <div class="glass-card">
                <div class="section-header">
                    <div class="header-title"><i class="ti-calendar"></i> Weekly Schedule</div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time Slot</th>
                                <th>Subject</th>
                                <th>Batch</th>
                                <th>Classroom</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if(!empty($schedule_data)) {
                                foreach($schedule_data as $row) {
                                    $startTime = date("h:i A", strtotime($row->start_time));
                                    $endTime = date("h:i A", strtotime($row->end_time));
                                    ?>
                                    <tr>
                                        <td class="day-highlight"><?php echo htmlentities($row->day_of_week);?></td>
                                        <td style="color:#fff; font-weight:600;"><?php echo $startTime . " - " . $endTime;?></td>
                                        <td style="color:#cbd5e1;"><?php echo htmlentities($row->SubjectFullname);?></td>
                                        <td><span class="badge-batch"><?php echo htmlentities($row->batch_name);?></span></td>
                                        <td><i class="ti-location-pin" style="color:#f59e0b;"></i> <?php echo htmlentities($row->room_name_or_number);?></td>
                                        <td><span class="badge-booked">Booked</span></td>
                                    </tr>
                                    <?php 
                                }
                            } else { ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:40px; color:#10b981; font-size:15px;">
                                        <i class="ti-check-box" style="font-size:24px; display:block; margin-bottom:10px;"></i>
                                        No classes assigned. This teacher is fully available.
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php } ?>

        </div>
    </div>
</div>

<?php include('includes/footer.php');?>