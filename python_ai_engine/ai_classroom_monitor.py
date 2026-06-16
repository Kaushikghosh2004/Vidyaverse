import cv2
import face_recognition
import mysql.connector
import datetime
import time
import numpy as np
import json
import math
import sys
import threading
from flask import Flask, Response

# --- CONFIGURATION ---
DB_CONFIG = {
    'user': 'root', 'password': '', 'host': 'localhost', 'database': 'lexclassroom'
}
MATCH_TOLERANCE = 0.55
TARGET_RADIUS = 130
CAMERA_INDEX = 0  # <--- LAPTOP WEBCAM

# --- SHARED GLOBAL VARIABLES ---
current_frame = None
processed_faces = [] 
lock = threading.Lock()
video_active = True

app = Flask(__name__)

def get_db_connection():
    try: return mysql.connector.connect(**DB_CONFIG)
    except: return None

# --- LOAD FACES ---
known_face_encodings = []
known_face_ids = []
student_info = {} 

print("\n--- 1. LOADING DATABASE ---")
try:
    conn = get_db_connection()
    if conn:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT ID, FullName, RollNumber, FaceEncoding FROM tbluser WHERE FaceEncoding IS NOT NULL AND FaceEncoding != ''")
        students = cursor.fetchall()
        for student in students:
            try:
                encoding = np.array(json.loads(student['FaceEncoding']), dtype=np.float64)
                known_face_encodings.append(encoding)
                known_face_ids.append(student['ID'])
                
                # --- RESTORED ROLL NUMBER LOGIC ---
                roll_no = student['RollNumber'] if student['RollNumber'] else ""
                display_label = f"{student['FullName']}"
                if roll_no:
                    display_label += f" ({roll_no})"
                
                student_info[student['ID']] = {
                    "clean_name": student['FullName'],
                    "video_label": display_label # Now includes Roll No
                }
            except: pass
        conn.close()
        print(f"--- SUCCESS: System knows {len(known_face_ids)} students ---\n")
except Exception as e:
    print(f"CRITICAL DB ERROR: {e}")

# --- THREAD 1: BACKGROUND AI WORKER ---
def ai_worker():
    global current_frame, processed_faces, video_active
    last_kiosk_update = 0
    
    print("--- AI BACKGROUND WORKER STARTED ---")
    
    while video_active:
        with lock:
            if current_frame is None:
                time.sleep(0.01)
                continue
            frame_to_process = current_frame.copy()

        # Resize for speed
        small_frame = cv2.resize(frame_to_process, (0, 0), fx=0.25, fy=0.25)
        rgb_small_frame = cv2.cvtColor(small_frame, cv2.COLOR_BGR2RGB)

        # Find Faces
        face_locations = face_recognition.face_locations(rgb_small_frame)
        face_encodings = face_recognition.face_encodings(rgb_small_frame, face_locations)

        new_results = []
        center_x, center_y = 320, 240 

        for (top, right, bottom, left), face_encoding in zip(face_locations, face_encodings):
            # Scale up
            top *= 4; right *= 4; bottom *= 4; left *= 4
            
            # Distance Logic (Invisible Zone)
            face_center_x = left + (right - left) // 2
            face_center_y = top + (bottom - top) // 2
            dist = math.sqrt((center_x - face_center_x)**2 + (center_y - face_center_y)**2)
            is_in_zone = dist < TARGET_RADIUS

            matches = face_recognition.compare_faces(known_face_encodings, face_encoding, tolerance=MATCH_TOLERANCE)
            face_distances = face_recognition.face_distance(known_face_encodings, face_encoding)
            
            name_label = "Unknown"
            clean_name = "Unknown"
            color = (0, 0, 255) # Red for Unknown

            if len(face_distances) > 0:
                best_match_index = np.argmin(face_distances)
                if matches[best_match_index]:
                    student_id = known_face_ids[best_match_index]
                    data = student_info.get(student_id, {"clean_name": "Unknown", "video_label": "ID?"})
                    
                    name_label = data['video_label']
                    clean_name = data['clean_name']

                    if is_in_zone:
                        color = (0, 255, 0) # Green for Success
                        
                        # --- DB UPDATE ---
                        curr_time = time.time()
                        if curr_time - last_kiosk_update > 2:
                            try:
                                db = get_db_connection()
                                if db:
                                    cur = db.cursor()
                                    sql_kiosk = "INSERT INTO tbl_kiosk_live (id, StudentName, UserImage, ScanTime, Mode) VALUES (1, %s, '', NOW(), 'Normal') ON DUPLICATE KEY UPDATE StudentName=%s, ScanTime=NOW()"
                                    cur.execute(sql_kiosk, (clean_name, clean_name))
                                    cur.execute("INSERT IGNORE INTO tbl_live_attendance (StudentID, Date, SlotID, FirstSeen, LastSeen, Status) VALUES (%s, CURDATE(), 999, CURTIME(), CURTIME(), 'Present')", (student_id,))
                                    db.commit()
                                    db.close()
                                    print(f" > LOGGED: {clean_name}")
                                    last_kiosk_update = curr_time
                            except: pass
                    else:
                        color = (0, 165, 255) # Orange
                        name_label = "Come Closer"

            new_results.append((top, right, bottom, left, name_label, color))

        with lock:
            processed_faces = new_results
        
        # Sleep to prevent lag
        time.sleep(0.1)

# --- THREAD 2: MAIN VIDEO STREAM ---
def generate_frames():
    global current_frame, video_active
    
    cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)
    if not cap.isOpened():
        cap = cv2.VideoCapture(0)
    
    t = threading.Thread(target=ai_worker)
    t.daemon = True
    t.start()

    print("--- VIDEO STREAM ACTIVE (ROLL NO ENABLED) ---")
    
    while True:
        success, frame = cap.read()
        if not success:
            time.sleep(1)
            continue

        frame = cv2.flip(frame, 1)
        frame = cv2.resize(frame, (640, 480))
        
        with lock:
            current_frame = frame.copy()

        # Draw Face Boxes with Roll Numbers
        with lock:
            faces_to_draw = processed_faces

        for (top, right, bottom, left, name, color) in faces_to_draw:
            cv2.rectangle(frame, (left, top), (right, bottom), color, 2)
            # This 'name' variable now contains "Name (RollNo)"
            cv2.putText(frame, name, (left, top - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, color, 2)

        try:
            ret, buffer = cv2.imencode('.jpg', frame, [int(cv2.IMWRITE_JPEG_QUALITY), 60])
            frame_bytes = buffer.tobytes()
            yield (b'--frame\r\nContent-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')
        except: pass

@app.route('/')
def index(): return "High-Performance Neural Core Active."
@app.route('/video_feed')
def video_feed(): return Response(generate_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

if __name__ == '__main__':
    try: app.run(host='0.0.0.0', port=5000, threaded=True)
    except: print("ERROR: Port 5000 busy.")