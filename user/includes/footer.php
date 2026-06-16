<style>
    .footer-wrap {
        background: #0f172a; /* Dark Navy */
        padding: 20px;
        text-align: center;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin-top: 40px;
        color: #94a3b8;
        font-size: 13px;
        position: relative;
        bottom: 0;
        width: 100%;
    }

    .footer-wrap a {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
    }

    .footer-wrap a:hover {
        color: #fff;
        text-shadow: 0 0 10px #3b82f6;
    }

    /* SURVEY MODAL STYLES */
    .star-btn { font-size: 30px; margin: 0 5px; transition: 0.2s; }
    .star-btn:hover { transform: scale(1.2); }
</style>

<div class="row">
    <div class="col-lg-12">
        <div class="footer-wrap">
            <p>
                &copy; <?php echo date('Y'); ?> <strong>VidyaVerse</strong> Student Portal. 
                All rights reserved. 
                <br>
                Designed & Developed by <a href="#">Kaushik Ghosh</a>
            </p>
        </div>
    </div>
</div>

<div id="stealthSurveyModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#1e293b; padding:30px; border-radius:15px; width:90%; max-width:400px; text-align:center; border:1px solid #3b82f6; box-shadow:0 0 50px rgba(59, 130, 246, 0.3);">
        <h2 style="color:#fff; margin-top:0;">⚡ Instant Feedback</h2>
        <p style="color:#94a3b8;">Admin requests your feedback for:</p>
        <h3 style="color:#3b82f6; margin:10px 0;" id="surveyProfName">Prof. Name</h3>
        
        <div style="margin:20px 0;">
            <label style="color:#cbd5e1; display:block; margin-bottom:10px;">Rate Session</label>
            <div style="display:flex; justify-content:center; gap:10px;">
                <span onclick="setRating(1)" class="star-btn" style="cursor:pointer; color:#475569;">★</span>
                <span onclick="setRating(2)" class="star-btn" style="cursor:pointer; color:#475569;">★</span>
                <span onclick="setRating(3)" class="star-btn" style="cursor:pointer; color:#475569;">★</span>
                <span onclick="setRating(4)" class="star-btn" style="cursor:pointer; color:#475569;">★</span>
                <span onclick="setRating(5)" class="star-btn" style="cursor:pointer; color:#475569;">★</span>
            </div>
        </div>

        <textarea id="surveyComment" rows="3" placeholder="Optional comment..." style="width:100%; background:#0f172a; color:white; border:1px solid #334155; padding:10px; border-radius:5px; outline:none; resize:none;"></textarea>
        
        <button onclick="submitSurvey()" style="background:#10b981; color:white; border:none; padding:12px; width:100%; border-radius:8px; font-weight:bold; margin-top:15px; cursor:pointer;">Submit Anonymously</button>
        <button onclick="$('#stealthSurveyModal').fadeOut()" style="background:transparent; color:#64748b; border:none; margin-top:10px; cursor:pointer; font-size:12px;">Dismiss</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
<script>
    let detectedSurveyID = null;
    let selectedRating = 0;

    // Poll for surveys every 5 seconds
    setInterval(function() {
        // Stop checking if modal is already open
        if($('#stealthSurveyModal').css('display') !== 'none') return;

        // Path assumes footer is included in a file inside /user/ (e.g. user/dashboard.php)
        $.post('../includes/survey-handler.php', { action: 'check_survey' }, function(response) {
            try {
                let res = JSON.parse(response);
                if(res.status === 'found') {
                    detectedSurveyID = res.data.ID;
                    $('#surveyProfName').text("Prof. " + res.data.FirstName + " " + res.data.LastName);
                    $('#stealthSurveyModal').css('display', 'flex');
                }
            } catch(e) {
                console.log("Survey check error: " + e);
            }
        });
    }, 5000);

    function setRating(n) {
        selectedRating = n;
        $('.star-btn').each(function(index) {
            // Highlights stars up to n
            $(this).css('color', index < n ? '#f59e0b' : '#475569');
        });
    }

    function submitSurvey() {
        if(selectedRating === 0) { alert("Please tap a star to rate."); return; }
        
        let comment = $('#surveyComment').val();
        
        $.post('../includes/survey-handler.php', { 
            action: 'submit_feedback',
            survey_id: detectedSurveyID,
            rating: selectedRating,
            comment: comment
        }, function() {
            $('#stealthSurveyModal').fadeOut();
            alert("Thank you! Feedback submitted.");
            // Reset for next time
            selectedRating = 0;
            $('#surveyComment').val('');
            setRating(0);
        });
    }
</script>