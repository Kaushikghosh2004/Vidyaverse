import face_recognition
import mysql.connector
import json
import os
import numpy as np
import cv2  # Using OpenCV for robust loading

# --- CONFIGURATION ---
DB_CONFIG = {
    'user': 'root',
    'password': '',
    'host': 'localhost',
    'database': 'lexclassroom'
}

# Path to the uploads folder
UPLOADS_DIR = os.path.join(os.path.dirname(__file__), "../uploads")

def get_db_connection():
    return mysql.connector.connect(**DB_CONFIG)

def enroll_all_students():
    print("--- STARTING BATCH ENROLLMENT ---")
    
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
    except mysql.connector.Error as err:
        print(f"Error connecting to database: {err}")
        return

    # Select students who have an image but NO face encoding yet
    query = "SELECT ID, UserImage FROM tbluser WHERE UserImage IS NOT NULL AND (FaceEncoding IS NULL OR FaceEncoding = '')"
    cursor.execute(query)
    students_to_process = cursor.fetchall()

    if not students_to_process:
        print("No pending enrollments found. All students are up to date.")
        conn.close()
        return

    print(f"Found {len(students_to_process)} students pending enrollment.")

    count_success = 0
    count_fail = 0

    for student in students_to_process:
        student_id = student['ID']
        image_filename = student['UserImage']
        image_path = os.path.join(UPLOADS_DIR, image_filename)
        
        print(f"Processing Student ID {student_id} ({image_filename})...", end=" ")

        if not os.path.exists(image_path):
            print(f"FAILED: Image file not found at {image_path}")
            count_fail += 1
            continue

        try:
            # --- 1. Load using OpenCV (Robust Method) ---
            image = cv2.imread(image_path)
            
            if image is None:
                print("FAILED: OpenCV could not read the image. File might be corrupted.")
                count_fail += 1
                continue

            # --- 2. Convert BGR to RGB ---
            rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)

            # --- 3. CRITICAL FIX: Force 8-bit & Contiguous Memory ---
            # This specific line fixes the "Unsupported image type" crash on Windows
            rgb_image = np.ascontiguousarray(rgb_image, dtype=np.uint8)

            # --- 4. Detect faces ---
            face_encodings = face_recognition.face_encodings(rgb_image)

            if len(face_encodings) > 0:
                encoding = face_encodings[0]
                encoding_list = encoding.tolist()
                encoding_json = json.dumps(encoding_list)

                # Update Database
                update_sql = "UPDATE tbluser SET FaceEncoding = %s WHERE ID = %s"
                cursor.execute(update_sql, (encoding_json, student_id))
                conn.commit()
                
                print("SUCCESS")
                count_success += 1
            else:
                print("FAILED: No face detected. Please use a clear front-facing photo.")
                count_fail += 1
                
        except Exception as e:
            print(f"ERROR: {str(e)}")
            count_fail += 1

    conn.close()
    print("--- BATCH ENROLLMENT COMPLETE ---")
    print(f"Successfully Enrolled: {count_success}")
    print(f"Failed: {count_fail}")

if __name__ == "__main__":
    enroll_all_students()