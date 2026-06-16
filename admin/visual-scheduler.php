<?php
ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (empty($_SESSION['admin_id'])) {
    header('location:logout.php');
    exit;
}

// =========================================================================
// EMBEDDED AJAX API: Handles Real-Time Saving/Loading/Deleting
// =========================================================================
if(isset($_POST['ajax_action'])) {
    ob_clean();
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];

    try {
        // --- LOAD EVENTS ---
        if($action == 'load') {
            $batch_id = intval($_POST['batch_id']);
            $sql = "SELECT ts.id, ts.day_of_week, ts.start_time, ts.end_time, s.SubjectFullname, t.FirstName, c.room_name_or_number, ts.subject_id, ts.teacher_id, ts.classroom_id 
                    FROM timetable_schedule ts 
                    LEFT JOIN tblsubject s ON ts.subject_id = s.ID 
                    LEFT JOIN tblteacher t ON ts.teacher_id = t.ID
                    LEFT JOIN classrooms c ON ts.classroom_id = c.id
                    WHERE ts.batch_id = :bid";
            $stmt = $dbh->prepare($sql);
            $stmt->execute(['bid' => $batch_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $events = [];
            // Map String Day to FullCalendar Integer (0=Sun, 1=Mon, 2=Tue...)
            $dayMap = ['Monday'=>1, 'Tuesday'=>2, 'Wednesday'=>3, 'Thursday'=>4, 'Friday'=>5, 'Saturday'=>6];
            
            foreach($rows as $row) {
                // Build a descriptive title for the block
                $title = $row['SubjectFullname'] . "\n" . $row['FirstName'] . " | Rm: " . $row['room_name_or_number'];
                
                $events[] = [
                    'id' => $row['id'],
                    'title' => $title,
                    'startTime' => $row['start_time'],
                    'endTime' => $row['end_time'],
                    'daysOfWeek' => isset($dayMap[$row['day_of_week']]) ? [$dayMap[$row['day_of_week']]] : [],
                    'extendedProps' => [
                        'subject_id' => $row['subject_id'],
                        'teacher_id' => $row['teacher_id'],
                        'classroom_id' => $row['classroom_id']
                    ],
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)', // Emerald tint
                    'borderColor' => '#10b981',
                    'textColor' => '#fff'
                ];
            }
            echo json_encode($events);
            exit;
        }

        // --- SAVE/UPDATE EVENT ---
        if($action == 'save') {
            $id = intval($_POST['id'] ?? 0);
            $batch_id = intval($_POST['batch_id']);
            $subject_id = intval($_POST['subject_id']);
            $teacher_id = intval($_POST['teacher_id']);
            $classroom_id = intval($_POST['classroom_id']);
            $day_of_week = $_POST['day_of_week'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];

            if($id > 0) {
                // Update Existing Block
                $sql = "UPDATE timetable_schedule SET day_of_week=?, start_time=?, end_time=?, teacher_id=?, classroom_id=? WHERE id=?";
                $stmt = $dbh->prepare($sql);
                $stmt->execute([$day_of_week, $start_time, $end_time, $teacher_id, $classroom_id, $id]);
                echo json_encode(['success'=>true, 'id'=>$id]);
            } else {
                // Insert New Block
                $sql = "INSERT INTO timetable_schedule (batch_id, day_of_week, start_time, end_time, subject_id, teacher_id, classroom_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $dbh->prepare($sql);
                $stmt->execute([$batch_id, $day_of_week, $start_time, $end_time, $subject_id, $teacher_id, $classroom_id]);
                echo json_encode(['success'=>true, 'id'=>$dbh->lastInsertId()]);
            }
            exit;
        }

        // --- DELETE EVENT ---
        if($action == 'delete') {
            $id = intval($_POST['id']);
            $sql = "DELETE FROM timetable_schedule WHERE id=?";
            $stmt = $dbh->prepare($sql);
            $stmt->execute([$id]);
            echo json_encode(['success'=>true]);
            exit;
        }

    } catch (Exception $e) {
        echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
        exit;
    }
}
// =========================================================================

// Fetch Data for Modals/Sidebar
$subjects = $dbh->query("SELECT * FROM tblsubject ORDER BY SubjectFullname ASC")->fetchAll(PDO::FETCH_OBJ);
$teachers = $dbh->query("SELECT ID, FirstName, LastName FROM tblteacher ORDER BY FirstName ASC")->fetchAll(PDO::FETCH_OBJ);
$classrooms = $dbh->query("SELECT id, room_name_or_number FROM classrooms ORDER BY room_name_or_number ASC")->fetchAll(PDO::FETCH_OBJ);
$batches = $dbh->query("SELECT id, batch_name FROM batches ORDER BY batch_name ASC")->fetchAll(PDO::FETCH_OBJ);

$pageTitle = "Visual Scheduler";
$pageSubTitle = "Drag & Drop Timetable Management";
include('includes/header.php');
?>

<div class="container-fluid">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    
    <style>
        :root {
            --glass-bg: rgba(9, 9, 11, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.08);
            --neon-blue: #3b82f6;
            --neon-green: #10b981;
            --neon-purple: #8b5cf6;
        }

        body { 
            background: #050505; 
            background-image: radial-gradient(circle at 50% 0%, #1e293b 0%, #020617 80%);
            background-attachment: fixed;
            font-family: 'Inter', sans-serif; color: #f8fafc;
        }

        /* Top Control Bar */
        .control-bar {
            background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px);
            border: var(--glass-border); border-radius: 16px; padding: 20px;
            margin-top: 20px; display: flex; justify-content: space-between; align-items: center;
        }
        
        select.modern-select {
            background: rgba(0,0,0,0.5); color: #fff; border: 1px solid var(--neon-blue);
            padding: 10px 20px; border-radius: 10px; outline: none; font-weight: 600;
        }

        .scheduler-grid {
            display: grid; grid-template-columns: 260px 1fr; gap: 20px;
            margin-top: 20px; height: calc(100vh - 200px);
        }

        .glass-card {
            background: var(--glass-bg); backdrop-filter: blur(15px);
            border: var(--glass-border); border-radius: 16px; padding: 20px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
            height: 100%; overflow-y: auto;
        }

        /* Subjects Sidebar */
        #external-events .fc-event {
            margin: 10px 0; cursor: grab; padding: 12px;
            background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.4);
            color: #fff; border-radius: 8px; font-size: 12px; font-weight: 700;
            transition: 0.2s; box-shadow: inset 0 2px 5px rgba(255,255,255,0.05);
        }
        #external-events .fc-event:hover { background: var(--neon-blue); border-color: var(--neon-blue); transform: translateX(5px); }

        /* FullCalendar Customizations */
        #calendar { background: rgba(0,0,0,0.3); border-radius: 12px; padding: 10px; }
        .fc-theme-standard td, .fc-theme-standard th { border-color: rgba(255,255,255,0.05) !important; }
        .fc-col-header-cell-cushion { color: #a1a1aa; text-transform: uppercase; font-size: 12px; padding: 10px !important; }
        .fc-timegrid-slot-label-cushion { color: #64748b; font-size: 11px; }
        .fc-v-event { padding: 4px; box-shadow: inset 0 2px 5px rgba(255,255,255,0.2); }
        .fc-event-title { font-weight: 800; font-size: 11px; white-space: pre-wrap; line-height: 1.4; }

        /* Glassmorphic Modal */
        .custom-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 9999;
            align-items: center; justify-content: center;
        }
        .modal-content {
            background: rgba(15, 23, 42, 0.95); border: 1px solid var(--neon-purple);
            width: 400px; border-radius: 20px; padding: 30px;
            box-shadow: 0 0 40px rgba(139, 92, 246, 0.3);
            transform: scale(0.9); transition: 0.3s; opacity: 0;
        }
        .custom-modal.show .modal-content { transform: scale(1); opacity: 1; }
        
        .modal-content h3 { font-family: 'Orbitron', sans-serif; color: #fff; margin-bottom: 20px; font-size: 18px; }
        .modal-content label { display: block; font-size: 12px; color: #94a3b8; margin-bottom: 5px; text-transform: uppercase; }
        .modal-content select { width: 100%; background: #09090b; border: 1px solid #334155; color: #fff; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-save { flex: 2; background: var(--neon-purple); color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .btn-delete { flex: 1; background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; display: none; }
        .btn-cancel { flex: 1; background: transparent; color: #94a3b8; border: 1px solid #334155; padding: 12px; border-radius: 8px; cursor: pointer; }
        
        /* Toast */
        #toast { position: fixed; bottom: 30px; right: -300px; background: var(--neon-green); color: #000; padding: 15px 25px; border-radius: 10px; font-weight: 800; transition: 0.3s; z-index: 10000; }
        #toast.show { right: 30px; }
    </style>

    <div class="control-bar">
        <h2 style="font-family:'Orbitron', sans-serif; margin:0; font-size:18px; color:#fff;">
            <i class="fas fa-sitemap" style="color:var(--neon-blue);"></i> TIMETABLE MASTER
        </h2>
        <div>
            <select id="targetBatch" class="modern-select" onchange="loadTimetable()">
                <option value="">-- SELECT TARGET BATCH --</option>
                <?php foreach($batches as $b) { echo "<option value='{$b->id}'>{$b->batch_name}</option>"; } ?>
            </select>
        </div>
    </div>

    <div class="scheduler-grid">
        
        <div class="glass-card">
            <h3 style="font-size:14px; color:#94a3b8; text-transform:uppercase; margin-bottom:15px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px;">Subject Nodes</h3>
            <div id='external-events'>
                <?php
                if(count($subjects) > 0) {
                    foreach($subjects as $sub) {
                        echo "<div class='fc-event' data-subid='".$sub->ID."'>" . htmlentities($sub->SubjectFullname) . "</div>";
                    }
                }
                ?>
            </div>
        </div>

        <div class="glass-card">
            <div id="overlay-msg" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); color:#71717a; font-weight:700; z-index:10; font-size:18px; letter-spacing:2px; text-transform:uppercase;">Select a batch to initialize.</div>
            <div id='calendar' style="height: 100%; opacity:0;"></div>
        </div>
    </div>
</div>

<div class="custom-modal" id="eventModal">
    <div class="modal-content">
        <h3>Configure Class Block</h3>
        <input type="hidden" id="modal-event-id">
        <input type="hidden" id="modal-subject-id">
        <input type="hidden" id="modal-day">
        <input type="hidden" id="modal-start">
        <input type="hidden" id="modal-end">
        
        <label>Assign Faculty</label>
        <select id="modal-teacher">
            <option value="">-- Select Teacher --</option>
            <?php foreach($teachers as $t) { echo "<option value='{$t->ID}'>{$t->FirstName} {$t->LastName}</option>"; } ?>
        </select>
        
        <label>Assign Classroom</label>
        <select id="modal-classroom">
            <option value="">-- Select Room --</option>
            <?php foreach($classrooms as $c) { echo "<option value='{$c->id}'>{$c->room_name_or_number}</option>"; } ?>
        </select>
        
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-delete" id="btn-delete" onclick="deleteEvent()">Delete</button>
            <button class="btn-save" onclick="saveEvent()">Deploy Block</button>
        </div>
    </div>
</div>

<div id="toast">Update Successful</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

<script>
    var calendar;
    var currentBatchId = null;
    var pendingEvent = null; // Holds the event temporarily until configured

    document.addEventListener('DOMContentLoaded', function() {
        var containerEl = document.getElementById('external-events');
        var calendarEl = document.getElementById('calendar');

        // Make external events draggable
        new FullCalendar.Draggable(containerEl, {
            itemSelector: '.fc-event',
            eventData: function(eventEl) {
                return {
                    title: eventEl.innerText,
                    extendedProps: { subject_id: eventEl.getAttribute('data-subid'), isNew: true },
                    create: true
                };
            }
        });

        // Initialize Empty Calendar Template (Weekly View)
        calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: false, // Hide header, we only care about Mon-Fri
            initialView: 'timeGridWeek',
            slotMinTime: '08:00:00',
            slotMaxTime: '18:00:00',
            hiddenDays: [0], // Hide Sunday
            allDaySlot: false,
            editable: true,
            droppable: true,
            dayHeaderFormat: { weekday: 'long' }, // Just show 'Monday', 'Tuesday'
            
            // ACTION: When a new subject is dropped onto the calendar
            eventReceive: function(info) {
                if(!currentBatchId) {
                    info.revert();
                    showToast("Error: Select a Batch First!", true);
                    return;
                }
                pendingEvent = info.event;
                openModal(info.event);
            },
            
            // ACTION: When an existing block is dragged/resized
            eventChange: function(info) {
                saveToDB(info.event);
            },
            
            // ACTION: When an existing block is clicked
            eventClick: function(info) {
                openModal(info.event, true);
            }
        });

        calendar.render();
    });

    function loadTimetable() {
        currentBatchId = document.getElementById('targetBatch').value;
        if(currentBatchId) {
            document.getElementById('overlay-msg').style.display = 'none';
            document.getElementById('calendar').style.opacity = '1';
            
            // Fetch events from our API block
            $.post('visual-scheduler.php', { ajax_action: 'load', batch_id: currentBatchId }, function(data) {
                calendar.removeAllEvents();
                calendar.addEventSource(data);
                showToast("Matrix Synchronized");
            });
        } else {
            document.getElementById('overlay-msg').style.display = 'block';
            document.getElementById('calendar').style.opacity = '0';
        }
    }

    // --- MODAL LOGIC ---
    function openModal(event, isEditing = false) {
        document.getElementById('eventModal').style.display = 'flex';
        setTimeout(() => document.getElementById('eventModal').classList.add('show'), 10);
        
        // Extract Database-friendly day (Monday, Tuesday...)
        let dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        let dayName = dayNames[event.start.getDay()];
        
        // Extract Times (HH:MM:SS)
        let startTime = event.start.toTimeString().split(' ')[0];
        let endTime = event.end ? event.end.toTimeString().split(' ')[0] : new Date(event.start.getTime() + 60*60000).toTimeString().split(' ')[0];

        // Populate hidden fields
        document.getElementById('modal-event-id').value = event.id || 0;
        document.getElementById('modal-subject-id').value = event.extendedProps.subject_id;
        document.getElementById('modal-day').value = dayName;
        document.getElementById('modal-start').value = startTime;
        document.getElementById('modal-end').value = endTime;

        if(isEditing) {
            document.getElementById('modal-teacher').value = event.extendedProps.teacher_id || "";
            document.getElementById('modal-classroom').value = event.extendedProps.classroom_id || "";
            document.getElementById('btn-delete').style.display = 'block';
            pendingEvent = event;
        } else {
            document.getElementById('modal-teacher').value = "";
            document.getElementById('modal-classroom').value = "";
            document.getElementById('btn-delete').style.display = 'none';
        }
    }

    function closeModal() {
        document.getElementById('eventModal').classList.remove('show');
        setTimeout(() => document.getElementById('eventModal').style.display = 'none', 300);
        // If it was a new drop and cancelled, remove it from UI
        if(pendingEvent && pendingEvent.extendedProps.isNew) {
            pendingEvent.remove();
        }
        pendingEvent = null;
    }

    // --- DATABASE ACTIONS ---
    function saveEvent() {
        let t_id = document.getElementById('modal-teacher').value;
        let c_id = document.getElementById('modal-classroom').value;
        
        if(!t_id || !c_id) {
            alert("Please assign both Faculty and Classroom.");
            return;
        }

        // Attach props to event so it looks complete in UI before DB confirms
        pendingEvent.setExtendedProp('teacher_id', t_id);
        pendingEvent.setExtendedProp('classroom_id', c_id);
        pendingEvent.setExtendedProp('isNew', false); // No longer new

        saveToDB(pendingEvent);
        closeModal();
    }

    function deleteEvent() {
        if(confirm("Purge this block from the matrix?")) {
            $.post('visual-scheduler.php', { ajax_action: 'delete', id: pendingEvent.id }, function(res) {
                if(res.success) {
                    pendingEvent.remove();
                    showToast("Block Purged");
                    closeModal();
                }
            }, 'json');
        }
    }

    function saveToDB(event) {
        // Build Data Payload
        let dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        
        let data = {
            ajax_action: 'save',
            id: event.id || 0,
            batch_id: currentBatchId,
            subject_id: event.extendedProps.subject_id,
            teacher_id: event.extendedProps.teacher_id,
            classroom_id: event.extendedProps.classroom_id,
            day_of_week: dayNames[event.start.getDay()],
            start_time: event.start.toTimeString().split(' ')[0],
            end_time: event.end ? event.end.toTimeString().split(' ')[0] : new Date(event.start.getTime() + 60*60000).toTimeString().split(' ')[0]
        };

        $.post('visual-scheduler.php', data, function(res) {
            if(res.success) {
                // If new, update the calendar event with the real DB ID
                if(data.id === 0) {
                    event.setProp('id', res.id);
                }
                showToast("Matrix Updated");
                // Reload UI to show accurate titles
                loadTimetable();
            } else {
                showToast("Database Error", true);
            }
        }, 'json');
    }

    // --- UI HELPERS ---
    function showToast(msg, isError=false) {
        let toast = document.getElementById('toast');
        toast.innerText = msg;
        toast.style.background = isError ? 'var(--sec-red)' : 'var(--neon-green)';
        toast.style.color = isError ? '#fff' : '#000';
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }
</script>

<?php include('includes/footer.php');?>