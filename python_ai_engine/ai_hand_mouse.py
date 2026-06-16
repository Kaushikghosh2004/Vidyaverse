import cv2
import mediapipe as mp
import pyautogui
import numpy as np
import threading
import time
import math
from flask import Flask, Response

# --- CONFIGURATION ---
CAMERA_INDEX = 0
SMOOTHING = 5  # Higher = Smoother mouse, but slightly slower
FRAME_WIDTH = 640
FRAME_HEIGHT = 480

# --- GLOBAL VARIABLES ---
current_frame = None
video_active = True
lock = threading.Lock()

app = Flask(__name__)

# --- MEDIA PIPE SETUP ---
mp_hands = mp.solutions.hands
# max_num_hands=1 ensures ONLY the presenter controls the mouse
hands = mp_hands.Hands(max_num_hands=1, min_detection_confidence=0.7, min_tracking_confidence=0.7)
mp_draw = mp.solutions.drawing_utils
screen_w, screen_h = pyautogui.size()

def generate_mouse_frames():
    global current_frame
    
    # Force DirectShow for faster camera loading
    cap = cv2.VideoCapture(CAMERA_INDEX, cv2.CAP_DSHOW)
    if not cap.isOpened():
        cap = cv2.VideoCapture(CAMERA_INDEX)

    # Variables for smoothing mouse movement
    plocX, plocY = 0, 0 
    clocX, clocY = 0, 0 

    print("--- PRESENTATION MODE: HAND GESTURES ACTIVE ---")
    
    while True:
        success, frame = cap.read()
        if not success:
            time.sleep(0.01)
            continue
        
        # 1. Flip frame (Mirror view is easier for hand-eye coordination)
        frame = cv2.flip(frame, 1)
        frame = cv2.resize(frame, (FRAME_WIDTH, FRAME_HEIGHT))
        frame_h, frame_w, _ = frame.shape
        
        # 2. Detect Hands
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        result = hands.process(rgb_frame)
        
        if result.multi_hand_landmarks:
            for hand_landmarks in result.multi_hand_landmarks:
                mp_draw.draw_landmarks(frame, hand_landmarks, mp_hands.HAND_CONNECTIONS)
                landmarks = hand_landmarks.landmark
                
                # Finger Tips IDs: 8=Index, 4=Thumb, 12=Middle
                index_x = int(landmarks[8].x * frame_w)
                index_y = int(landmarks[8].y * frame_h)
                thumb_x = int(landmarks[4].x * frame_w)
                thumb_y = int(landmarks[4].y * frame_h)
                middle_y = int(landmarks[12].y * frame_h)
                
                # --- A. MOVEMENT ZONE (Index Finger) ---
                # Draw a box. Moving finger inside this box maps to the full screen.
                margin = 100
                cv2.rectangle(frame, (margin, margin), (frame_w - margin, frame_h - margin), (255, 0, 255), 2)
                
                # Map coordinates from Camera Box -> Laptop Screen
                x3 = np.interp(index_x, (margin, frame_w - margin), (0, screen_w))
                y3 = np.interp(index_y, (margin, frame_h - margin), (0, screen_h))
                
                # Smooth the movement
                clocX = plocX + (x3 - plocX) / SMOOTHING
                clocY = plocY + (y3 - plocY) / SMOOTHING
                
                # Move Mouse
                try: pyautogui.moveTo(clocX, clocY)
                except: pass
                
                plocX, plocY = clocX, clocY
                
                # --- B. LEFT CLICK (Pinch Index + Thumb) ---
                dist_click = math.hypot(index_x - thumb_x, index_y - thumb_y)
                if dist_click < 30:
                    cv2.circle(frame, (index_x, index_y), 15, (0, 255, 0), cv2.FILLED)
                    pyautogui.click()
                    time.sleep(0.2) # Prevent double-clicks

                # --- C. SCROLL (Index + Middle Finger Up) ---
                # Calculate distance between Index and Middle finger
                index_middle_dist = math.hypot(index_x - int(landmarks[12].x * frame_w), index_y - middle_y)
                
                if index_middle_dist < 40:
                    cv2.putText(frame, "SCROLL MODE", (20, 50), cv2.FONT_HERSHEY_PLAIN, 2, (0, 255, 255), 2)
                    if index_y < frame_h // 2:
                        pyautogui.scroll(30) # Scroll Up
                    else:
                        pyautogui.scroll(-30) # Scroll Down

        # 3. Stream to Web Interface
        try:
            ret, buffer = cv2.imencode('.jpg', frame, [int(cv2.IMWRITE_JPEG_QUALITY), 50])
            frame_bytes = buffer.tobytes()
            yield (b'--frame\r\nContent-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')
        except: pass

@app.route('/')
def index(): return "Presentation Mode Active."

@app.route('/video_feed')
def video_feed(): return Response(generate_mouse_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

if __name__ == '__main__':
    try: app.run(host='0.0.0.0', port=5000, threaded=True)
    except: print("ERROR: Port 5000 busy.")