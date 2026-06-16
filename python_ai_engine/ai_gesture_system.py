import cv2
import mediapipe as mp
import pyautogui
import numpy as np
import time
import math
import threading
from flask import Flask, Response

# --- 1. CONFIGURATION ---
CAMERA_INDEX = 0
SMOOTHING = 4        # Lowered slightly for faster edge movement
CLICK_THRESHOLD = 30 
BUTTON_AREA = (20, 10, 220, 70) 

# --- SAFE ZONE & EDGE OVERDRIVE ---
FRAME_REDUCTION = 80  # Smaller padding so you have more room to move
SCREEN_PADDING = 50   # "Overdrive" amount. Pushes mouse past the edges to ensure it hits.

# --- 2. KEYBOARD LAYOUT ---
keys = [
    ["Q", "W", "E", "R", "T", "Y", "U", "I", "O", "P"],
    ["A", "S", "D", "F", "G", "H", "J", "K", "L", ";"],
    ["Z", "X", "C", "V", "B", "N", "M", ",", ".", "/"]
]

# --- 3. GLOBALS ---
lock = threading.Lock()
app = Flask(__name__)
pyautogui.FAILSAFE = False # CRITICAL: Allows mouse to hit corners without crashing

# --- 4. SETUP MEDIAPIPE ---
mp_hands = mp.solutions.hands
hands = mp_hands.Hands(max_num_hands=2, min_detection_confidence=0.7, min_tracking_confidence=0.7)
mp_draw = mp.solutions.drawing_utils
screen_w, screen_h = pyautogui.size()

# --- HELPER CLASS ---
class Button():
    def __init__(self, pos, text, size=[50, 50]):
        self.pos = pos
        self.size = size
        self.text = text

buttonList = []
for i in range(len(keys)):
    for j, key in enumerate(keys[i]):
        buttonList.append(Button([60 * j + 20, 80 * i + 100], key))

def drawAll(img, buttonList):
    overlay = img.copy()
    cv2.rectangle(overlay, (10, 90), (620, 350), (0, 0, 0), cv2.FILLED)
    alpha = 0.4
    img = cv2.addWeighted(overlay, alpha, img, 1 - alpha, 0)

    for button in buttonList:
        x, y = button.pos
        w, h = button.size
        cv2.rectangle(img, (x, y), (x + w, y + h), (255, 0, 255), 2)
        cv2.rectangle(img, (x, y), (x + w, y + h), (50, 0, 50), cv2.FILLED)
        cv2.putText(img, button.text, (x + 10, y + 35), cv2.FONT_HERSHEY_PLAIN, 2, (255, 255, 255), 2)
    return img

def generate_frames():
    cap = cv2.VideoCapture(CAMERA_INDEX, cv2.CAP_DSHOW)
    if not cap.isOpened(): cap = cv2.VideoCapture(CAMERA_INDEX)

    plocX, plocY = 0, 0 
    clocX, clocY = 0, 0 
    mode = "MOUSE" 
    last_click_time = 0
    hover_start_time = 0
    is_hovering = False

    print("--- AI GESTURE SYSTEM ONLINE ---")

    while True:
        success, frame = cap.read()
        if not success: 
            time.sleep(0.01)
            continue
        
        frame = cv2.flip(frame, 1)
        frame = cv2.resize(frame, (640, 480))
        h, w, _ = frame.shape
        
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        result = hands.process(rgb_frame)
        
        # --- DRAW ACTIVE ZONE (THE BOX) ---
        cv2.rectangle(frame, (FRAME_REDUCTION, FRAME_REDUCTION), (w - FRAME_REDUCTION, h - FRAME_REDUCTION), (255, 255, 255), 2)
        
        # --- DRAW MODE BUTTON ---
        btn_color = (0, 255, 0) if mode == "MOUSE" else (255, 0, 0)
        cv2.rectangle(frame, (BUTTON_AREA[0], BUTTON_AREA[1]), (BUTTON_AREA[2], BUTTON_AREA[3]), btn_color, 2)
        cv2.putText(frame, f"MODE: {mode}", (30, 50), cv2.FONT_HERSHEY_PLAIN, 1.5, btn_color, 2)
        
        if result.multi_hand_landmarks:
            for hand_landmarks in result.multi_hand_landmarks:
                mp_draw.draw_landmarks(frame, hand_landmarks, mp_hands.HAND_CONNECTIONS)
                lm = hand_landmarks.landmark
                
                index_x, index_y = int(lm[8].x * w), int(lm[8].y * h)
                thumb_x, thumb_y = int(lm[4].x * w), int(lm[4].y * h)
                
                dist_click = math.hypot(index_x - thumb_x, index_y - thumb_y)
                is_clicking = dist_click < CLICK_THRESHOLD
                
                # --- HOVER TO SWITCH LOGIC ---
                if BUTTON_AREA[0] < index_x < BUTTON_AREA[2] and BUTTON_AREA[1] < index_y < BUTTON_AREA[3]:
                    if not is_hovering:
                        hover_start_time = time.time()
                        is_hovering = True
                    
                    elapsed = time.time() - hover_start_time
                    progress_width = int((elapsed / 2.0) * (BUTTON_AREA[2] - BUTTON_AREA[0]))
                    cv2.rectangle(frame, (BUTTON_AREA[0], BUTTON_AREA[3]-10), (BUTTON_AREA[0] + progress_width, BUTTON_AREA[3]), (255, 255, 255), -1)
                    
                    if elapsed > 2.0:
                        mode = "KEYBOARD" if mode == "MOUSE" else "MOUSE"
                        is_hovering = False
                        hover_start_time = time.time() 
                        cv2.rectangle(frame, (BUTTON_AREA[0], BUTTON_AREA[1]), (BUTTON_AREA[2], BUTTON_AREA[3]), (255, 255, 255), cv2.FILLED) 
                        time.sleep(0.5) 
                else:
                    is_hovering = False

                # --- SAFETY CHECK: IS HAND INSIDE THE BOX? ---
                hand_in_safe_zone = (FRAME_REDUCTION < index_x < w - FRAME_REDUCTION) and \
                                    (FRAME_REDUCTION < index_y < h - FRAME_REDUCTION)

                box_color = (0, 255, 0) if hand_in_safe_zone else (0, 0, 255)
                cv2.rectangle(frame, (FRAME_REDUCTION, FRAME_REDUCTION), (w - FRAME_REDUCTION, h - FRAME_REDUCTION), box_color, 2)

                # =========================================
                # LOGIC 1: MOUSE MODE
                # =========================================
                if mode == "MOUSE":
                    if not is_hovering and hand_in_safe_zone:
                        
                        # --- THE FIX: EDGE OVERDRIVE ---
                        # We map the safe zone to values LARGER than the screen size (-SCREEN_PADDING to Screen+SCREEN_PADDING)
                        # This ensures that when you are near the edge of the box, the mouse is definitely at the edge of the screen.
                        
                        screen_x = np.interp(index_x, (FRAME_REDUCTION, w-FRAME_REDUCTION), (-SCREEN_PADDING, screen_w + SCREEN_PADDING))
                        screen_y = np.interp(index_y, (FRAME_REDUCTION, h-FRAME_REDUCTION), (-SCREEN_PADDING, screen_h + SCREEN_PADDING))
                        
                        clocX = plocX + (screen_x - plocX) / SMOOTHING
                        clocY = plocY + (screen_y - plocY) / SMOOTHING
                        
                        try: pyautogui.moveTo(clocX, clocY)
                        except: pass
                        plocX, plocY = clocX, clocY
                        
                        if is_clicking:
                            cv2.circle(frame, (index_x, index_y), 15, (0, 255, 0), cv2.FILLED)
                            if time.time() - last_click_time > 0.3:
                                pyautogui.click()
                                last_click_time = time.time()
                    
                    elif not hand_in_safe_zone:
                         cv2.circle(frame, (index_x, index_y), 10, (0, 0, 255), cv2.FILLED)

                # =========================================
                # LOGIC 2: KEYBOARD MODE
                # =========================================
                elif mode == "KEYBOARD":
                    frame = drawAll(frame, buttonList)
                    for button in buttonList:
                        bx, by = button.pos
                        bw, bh = button.size
                        
                        if bx < index_x < bx + bw and by < index_y < by + bh:
                            cv2.rectangle(frame, (bx, by), (bx + bw, by + bh), (0, 255, 0), cv2.FILLED)
                            cv2.putText(frame, button.text, (bx + 10, by + 35), cv2.FONT_HERSHEY_PLAIN, 2, (255, 255, 255), 2)
                            
                            if is_clicking and (time.time() - last_click_time > 0.4):
                                pyautogui.press(button.text.lower())
                                cv2.rectangle(frame, (bx, by), (bx + bw, by + bh), (255, 255, 255), cv2.FILLED)
                                last_click_time = time.time()

        try:
            ret, buffer = cv2.imencode('.jpg', frame, [int(cv2.IMWRITE_JPEG_QUALITY), 50])
            frame_bytes = buffer.tobytes()
            yield (b'--frame\r\nContent-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')
        except: pass

@app.route('/')
def index(): return "Gesture System Active"
@app.route('/video_feed')
def video_feed(): return Response(generate_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

if __name__ == '__main__':
    try: app.run(host='0.0.0.0', port=5000, threaded=True)
    except: print("ERROR: Port 5000 busy.")