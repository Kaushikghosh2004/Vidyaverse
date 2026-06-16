# python_ai_engine/enroll_single.py
import sys
import face_recognition
import mysql.connector
import json
import os

# Database Config (MATCH YOUR SETTINGS)
db_config = {
    'user': 'root',
    'password': '',
    'host': 'localhost',
    'database': 'lexclassroom'
}

def enroll_student(student_id, image_path):
    try:
        if not os.path.exists(image_path):
            print("ERROR: Image file not found.")
            return

        # 1. Load Image
        image = face_recognition.load_image_file(image_path)
        
        # 2. Find Face Encoding
        encodings = face_recognition.face_encodings(image)
        
        if len(encodings) > 0:
            face_encoding = encodings[0]
            
            # 3. Connect to DB
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor()
            
            # 4. Save as JSON
            encoding_json = json.dumps(face_encoding.tolist())
            
            sql = "UPDATE tblstudents SET FaceEncoding = %s WHERE ID = %s"
            cursor.execute(sql, (encoding_json, student_id))
            conn.commit()
            
            print("SUCCESS")
            conn.close()
        else:
            print("ERROR: No face found in the image.")
            
    except Exception as e:
        print(f"ERROR: {str(e)}")

# CLI Arguments: python enroll_single.py <ID> <Path>
if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("ERROR: Missing arguments")
    else:
        enroll_student(sys.argv[1], sys.argv[2])