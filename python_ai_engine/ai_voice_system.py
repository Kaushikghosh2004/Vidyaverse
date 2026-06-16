import warnings
warnings.filterwarnings("ignore")
import speech_recognition as sr
import win32com.client
import datetime
import os
import sys
import time
import json
import difflib
import requests # Direct Web Connection
import ctypes # For locking system
from AppOpener import open as open_app # Universal App Opener

# --- FORCE OUTPUT FLUSHING ---
sys.stdout.reconfigure(encoding='utf-8')

# --- CONFIGURATION ---
ENGINE_NAME = "VIDYAVERSE CORE"
MIC_INDEX = 1   

# KEYS
GEMINI_API_KEY = "AIzaSyBq6GFOQ_bYcf5cv-LrREMSWQ92ykmISoE" 

STATUS_FILE = "system_status.json"
MEMORY_FILE = "brain_memory.json"

# --- LOGGING ---
def log_console(text):
    timestamp = datetime.datetime.now().strftime("%H:%M:%S")
    print(f"[{timestamp}] {text}")
    sys.stdout.flush()

def update_status(state, log_text):
    data = {"status": state, "log": log_text, "timestamp": time.time()}
    try:
        with open(STATUS_FILE, "w") as f: json.dump(data, f)
    except: pass

# --- MEMORY ---
def load_memory():
    if not os.path.exists(MEMORY_FILE): return {}
    try: 
        with open(MEMORY_FILE, "r") as f: return json.load(f)
    except: return {}

def save_memory(question, answer):
    memory = load_memory()
    memory[question.lower()] = answer
    try:
        with open(MEMORY_FILE, "w") as f: json.dump(memory, f, indent=4)
        return True
    except: return False

def check_memory(query):
    memory = load_memory()
    matches = difflib.get_close_matches(query, memory.keys(), n=1, cutoff=0.7)
    if matches: return memory[matches[0]]
    return None

# --- AUDIO ---
try:
    speaker = win32com.client.Dispatch("SAPI.SpVoice")
    speaker.Volume = 100
    speaker.Rate = 0 
except: pass

def speak(text):
    log_console(f"AI: {text}")
    update_status("SPEAKING", f"Speaking...")
    try: speaker.Speak(text)
    except: pass
    update_status("IDLE", "Standing by")

# --- DIRECT API FUNCTIONS ---
def call_gemini_direct(question):
    # HARDCODED HIGH-QUOTA MODEL
    model_name = "models/gemini-1.5-flash"
    
    url = f"https://generativelanguage.googleapis.com/v1beta/{model_name}:generateContent?key={GEMINI_API_KEY}"
    headers = {'Content-Type': 'application/json'}
    data = {
        "contents": [{
            "parts": [{"text": f"You are Vidyaverse. Answer in 1 short sentence: {question}"}]
        }]
    }
    
    try:
        response = requests.post(url, headers=headers, json=data, timeout=8)
        
        if response.status_code == 200:
            result = response.json()
            try:
                answer = result['candidates'][0]['content']['parts'][0]['text']
                return answer.strip()
            except:
                return None
        elif response.status_code == 429:
             log_console("Gemini Error: Quota Limit Reached (429)")
             return "QUOTA_LIMIT"
        elif response.status_code == 404:
             # Fallback logic if needed, but 1.5-flash is standard
             return None
        else:
             log_console(f"Gemini Error {response.status_code}")
             return None
    except Exception as e:
        log_console(f"Gemini Connection Failed: {e}")
        return None

# --- ROUTER ---
def ask_the_brain(question):
    # 1. MEMORY CHECK
    local_answer = check_memory(question)
    if local_answer:
        log_console(f"Memory Recall: {local_answer}")
        speak(local_answer)
        return

    # 2. ATTEMPT GEMINI (DIRECT)
    log_console(f"Routing to Primary (Gemini Direct)...")
    update_status("PROCESSING", "Thinking (Gemini)...")
    
    gemini_resp = call_gemini_direct(question)
    
    if gemini_resp and gemini_resp != "QUOTA_LIMIT":
        speak(gemini_resp.replace("*", ""))
        return
    elif gemini_resp == "QUOTA_LIMIT":
        speak("My daily energy limit is full.")
        return

    # 3. TOTAL FAILURE
    speak("I cannot connect to the cloud right now.")
    update_status("ERROR", "All Cores Failed")

# --- MIC ---
def take_command():
    r = sr.Recognizer()
    r.energy_threshold = 300
    r.dynamic_energy_threshold = True
    try:
        with sr.Microphone(device_index=MIC_INDEX) as source:
            log_console("Listening...")
            update_status("LISTENING", "Listening...")
            r.adjust_for_ambient_noise(source, duration=0.5)
            audio = r.listen(source, timeout=5, phrase_time_limit=8)
            
            update_status("PROCESSING", "Decoding...")
            query = r.recognize_google(audio, language='en-in')
            log_console(f"User: {query}")
            return query.lower()
    except: return "None"

# --- MAIN LOOP ---
if __name__ == "__main__":
    speak("System Online. Vidyaverse Core Active.")

    while True:
        query = take_command()
        if query == "None":
            update_status("IDLE", "Standing by")
            continue

        # --- UNIVERSAL APP LAUNCHER ---
        if 'open' in query:
            app_name = query.replace("open", "").strip()
            if app_name:
                speak(f"Opening {app_name}")
                try:
                    open_app(app_name, match_closest=True)
                except:
                    speak(f"I could not find {app_name}")
            continue

        # --- OFFLINE UTILITIES ---
        if 'lock' in query and ('system' in query or 'pc' in query or 'screen' in query):
            speak("Locking system.")
            ctypes.windll.user32.LockWorkStation()
            continue

        if 'shutdown' in query and ('system' in query or 'pc' in query):
            speak("Shutting down.")
            os.system("shutdown /s /t 10")
            continue
            
        if 'time' in query:
            speak(datetime.datetime.now().strftime("%I:%M %p"))
            continue
            
        if 'stop' in query or 'exit' in query:
            speak("System Offline. Goodbye.")
            time.sleep(2) # Allow time to speak before kill
            break
            
        if "remember that" in query:
             try:
                clean = query.replace("remember that", "").replace("learn that", "").strip()
                if " is " in clean:
                    p = clean.split(" is ", 1)
                    save_memory(p[0].strip(), p[1].strip())
                    speak("Memorized.")
             except: pass
             continue
        
        # --- AI QUERY ---
        ask_the_brain(query)