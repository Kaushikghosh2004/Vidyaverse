import mysql.connector
import face_recognition
import json
import os
import numpy as np

# --- CONFIGURATION ---
DB_CONFIG = {
    'user': 'root', 'password': '', 'host': 'localhost', 'database': 'lexclassroom'
}
# IMPORTANT: Point this to your actual uploads folder
# If this script is in /python_ai_engine/, and uploads is in /Vidyaverse/uploads/
IMAGE_FOLDER = "../uploads/" 

print("--- STARTING DATABASE REPAIR ---")

try:
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)

    # 1. Find users who have an Image but NO Encoding
    cursor.execute("SELECT ID, FullName, UserImage FROM tbluser WHERE (FaceEncoding IS NULL OR FaceEncoding = '') AND UserImage IS NOT NULL")
    users = cursor.fetchall()

    print(f"Found {len(users)} users needing repair.\n")

    for u in users:
        print(f"Processing User: {u['FullName']} (ID: {u['ID']})...")
        
        # Construct full path
        img_path = os.path.join(IMAGE_FOLDER, u['UserImage'])
        
        # Check if file exists
        if not os.path.exists(img_path):
            print(f"   > ERROR: Image file not found at {img_path}")
            continue

        try:
            # Load Image
            image = face_recognition.load_image_file(img_path)
            
            # Generate Encoding
            encodings = face_recognition.face_encodings(image)

            if len(encodings) > 0:
                # Success! Take the first face found
                encoding_list = encodings[0].tolist()
                encoding_json = json.dumps(encoding_list)

                # Update Database
                update_cursor = conn.cursor()
                update_cursor.execute("UPDATE tbluser SET FaceEncoding=%s WHERE ID=%s", (encoding_json, u['ID']))
                conn.commit()
                print(f"   > SUCCESS: Encoding generated and saved! ✅")
            else:
                print(f"   > FAILED: No face detected in the photo. Please re-upload a clear front-facing photo. ❌")

        except Exception as e:
            print(f"   > CRITICAL ERROR: {e}")

    print("\n--- REPAIR COMPLETE ---")

except Exception as main_e:
    print(f"Database Connection Failed: {main_e}")