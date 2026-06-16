<?php
include('includes/dbconnection.php');

if(!empty($_POST["action"])) {
    
    // --- 1. FETCH SUBJECTS (Based on Course & Semester) ---
    if($_POST["action"] == "get_subjects") {
        $cid = $_POST['course_id'];
        $sem = $_POST['semester'];
        
        $sql = "SELECT * FROM tblsubject WHERE CourseID = :cid AND Semester = :sem";
        $query = $dbh->prepare($sql);
        $query->bindParam(':cid', $cid, PDO::PARAM_INT);
        $query->bindParam(':sem', $sem, PDO::PARAM_STR);
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        
        echo '<option value="">Select Subject...</option>';
        foreach($results as $row) {
            echo '<option value="'.$row->ID.'">'.$row->SubjectFullname.' ('.$row->SubjectCode.')</option>';
        }
    }

    // --- 2. FETCH BATCHES (Based on Course & Semester) ---
    if($_POST["action"] == "get_batches") {
        $cid = $_POST['course_id'];
        $sem = $_POST['semester'];
        
        // Filter by matching "Sem X" in the batch name
        $search = "Sem " . $sem . "%"; 
        
        $sql = "SELECT * FROM batches WHERE CourseID = :cid AND batch_name LIKE :search";
        $query = $dbh->prepare($sql);
        $query->bindParam(':cid', $cid, PDO::PARAM_INT);
        $query->bindParam(':search', $search, PDO::PARAM_STR);
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        
        echo '<option value="all">All Batches (Entire Semester)</option>';
        foreach($results as $row) {
            echo '<option value="'.$row->id.'">'.$row->batch_name.'</option>';
        }
    }

    // --- 3. GENERATE PREVIEW PAPER (Smart Logic) ---
    if($_POST["action"] == "generate_preview_paper") {
        $subject_id = $_POST['subject_id'];
        // Decode the JSON sent from Javascript
        $sections = json_decode($_POST['sections'], true); 
        
        $htmlOutput = "";

        // Loop through every Section defined in the "Smart Structure" table
        foreach($sections as $sec) {
            $groupName = $sec['group'];   // e.g. "Section A"
            $qType = $sec['type'];        // 'MCQ', 'Short', or 'Long'
            $poolSize = $sec['pool'];     // How many to fetch from DB
            $attempt = $sec['attempt'];   // How many student must answer
            $marks = $sec['marks'];       // Marks per question
            $totalSecMarks = $marks * $attempt;

            // Start Section Wrapper
            $htmlOutput .= "<div class='exam-section' style='margin-bottom:30px; page-break-inside: avoid;'>";
            
            // Section Header
            $htmlOutput .= "<h4 style='text-align:center; background:#f3f4f6; padding:8px; border:1px solid #000; text-transform:uppercase; margin-bottom:15px;'>
                            $groupName ($qType Type) - Answer any $attempt - [$marks x $attempt = $totalSecMarks Marks]
                            </h4>";
            
            $htmlOutput .= "<ol style='padding-left: 20px;'>";

            // --- FETCH RANDOM QUESTIONS FROM DB ---
            $sql = "SELECT * FROM tblquestions WHERE SubjectID = :sid AND QuestionType = :qtype ORDER BY RAND() LIMIT :limit";
            $query = $dbh->prepare($sql);
            $query->bindParam(':sid', $subject_id);
            $query->bindParam(':qtype', $qType);
            $query->bindParam(':limit', $poolSize, PDO::PARAM_INT);
            $query->execute();
            $questions = $query->fetchAll(PDO::FETCH_OBJ);

            // Error Check: Not enough questions?
            if(count($questions) < $poolSize) {
                $htmlOutput .= "<p style='color:red; font-weight:bold;'>
                                [Error: You requested $poolSize '$qType' questions, but only found " . count($questions) . " in the Question Bank. Please add more.]
                                </p>";
            }

            // --- RENDER EACH QUESTION BASED ON TYPE ---
            foreach($questions as $q) {
                // 1. Display Question Text
                $htmlOutput .= "<li style='margin-bottom:15px; font-weight:bold; line-height:1.5;'>" . nl2br(htmlentities($q->QuestionText)) . "</li>";
                
                // 2. Display Answer Space / Options
                if($qType == 'MCQ') {
                    // --- MCQ GRID LAYOUT ---
                    $htmlOutput .= "<div style='font-weight:normal; margin-left:15px; margin-bottom:15px; display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size:14px;'>";
                    $htmlOutput .= "<span>(A) " . htmlentities($q->OptionA ?? '') . "</span>";
                    $htmlOutput .= "<span>(B) " . htmlentities($q->OptionB ?? '') . "</span>";
                    $htmlOutput .= "<span>(C) " . htmlentities($q->OptionC ?? '') . "</span>";
                    $htmlOutput .= "<span>(D) " . htmlentities($q->OptionD ?? '') . "</span>";
                    $htmlOutput .= "</div>";
                } 
                elseif($qType == 'Short') {
                    // --- SHORT ANSWER LINES ---
                    $htmlOutput .= "<div style='border-left: 3px solid #e5e7eb; margin-left: 5px; padding-left: 15px; color:#d1d5db; line-height:2;'>";
                    $htmlOutput .= "__________________________________________________________________________<br>";
                    $htmlOutput .= "__________________________________________________________________________<br>";
                    $htmlOutput .= "__________________________________________________________________________<br>";
                    $htmlOutput .= "</div><br>";
                } 
                elseif($qType == 'Long') {
                    // --- LONG ANSWER BOX ---
                    $htmlOutput .= "<div style='border: 1px solid #9ca3af; height: 250px; margin-bottom: 20px; background-color: #f9fafb;'></div>"; 
                    // Optional: Force page break after long questions if needed
                    // $htmlOutput .= "<div style='page-break-after: always;'></div>";
                }
            }

            $htmlOutput .= "</ol></div>"; // End Section
        }
        
        echo $htmlOutput;
    }
}
?>