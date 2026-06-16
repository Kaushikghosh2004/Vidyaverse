# VidyaVerse 🎓🤖
### AI-Powered Full-Stack Campus Ecosystem

VidyaVerse is an intelligent, unified campus ecosystem designed to streamline academic administration, event management, digital engagement, and automated services for modern educational institutions. By merging robust web portals with a Python-based AI engine, VidyaVerse empowers students, teachers, and administrators to interact seamlessly.

Developed with ❤️ by **Kaushik Ghosh**.

---

## 🚀 Key Subsystems & Features

### 📚 Academic Management
*   **Role-Based Portals:** Personalized dashboards and interfaces for Students, Teachers, and Administrators.
*   **Resource Directory:** Efficient course management, syllabus tracking, and educational file sharing.
*   **Intelligent Laboratories:** Virtual classrooms and AI-assisted educational environments to enhance student comprehension.

### 🎉 Event & Cultural Management
*   **Digital Registrations:** Smooth online registration flow for institutional events and festivals.
*   **Secured E-Ticketing:** Generates validation-ready digital tickets upon event enrollment.
*   **Verified Voting:** Built-in anonymous voting panels for elections and campus competitions utilizing institutional email checks to prevent duplicate responses.

### 💬 Feedback & Engagement
*   **Real-Time Surveys:** Dynamic feedback loops letting students evaluate course modules and campus services.
*   **Faculty Insights:** Analytical dashboards showing feedback summaries to educators for continuous curriculum improvement.

### 🧠 Python AI Engine
*   **Voice Workflows:** Voice-controlled inputs to automate routine administrative tasks.
*   **Personalized AI Assist:** Generates smart study recommendations and academic guides.
*   **Python Microservices:** Behind-the-scenes processing of machine learning pipelines for predictive campus analytics.

### 📖 Digital Library
*   **Catalog Inventory:** Fully indexable register for digital and physical books.
*   **Smart Circulation:** Tracks book issues, returns, and overdue timelines automatically.
*   **Voice Search:** Voice-assisted title and author queries for friction-free catalog browsing.

### 🔔 Scheduled Notifications
*   **Cron-driven Triggers:** Automatically generates and pushes daily reminders, overdue book alerts, and event updates.
*   **Institutional Announcements:** Real-time broadcasts from administrative panels straight to user feeds.

### 🔒 Enterprise-Grade Security
*   **Role-Based Access Control (RBAC):** Strict permission boundaries separating administrative powers, grading panels, and student directories.
*   **Session Integrity:** Encrypted session states to prevent session hijacking and cross-site scripting vulnerabilities.

---

## 🛠️ Technology Stack

| Layer | Technologies |
| :--- | :--- |
| **Backend & Routing** | PHP, Python |
| **Database** | MySQL (with PDO connection) |
| **Frontend UI/UX** | HTML5, CSS3, JavaScript, AJAX, Tailwind CSS, Google Fonts, Font Awesome |
| **AI & Voice Services** | Python AI Engine (Natural Language Processing, Voice Recognition, Mediapipe, dlib, OpenCV) |
| **Server Environments** | XAMPP, WAMP, LAMP |
| **Version Control** | Git, GitHub |

---

## 📁 Directory Structure

```text
VidyaVerse/
├── admin/                          # Administrator Control Panel
├── teacher/                        # Educator Dashboards & Grading Panels
├── user/                           # Student Portal & Profile Settings
├── assets/                         # Global Static Assets (Images, Icons, Fonts)
│   ├── css/                        # Shared Layout Stylesheets
│   ├── js/                         # Script Modules & AJAX Functions
│   └── fonts/                      # Font Resources
├── includes/                       # Shared Configs, Database PDO Connections & Headers
├── uploads/                        # Document & Profile Image Upload Directory
├── python_ai_engine/               # AI Engine Microservices & NLP Models
├── vidyaverse_educational_labs/    # Specialized Lab Code & Virtual Environments
├── SQL File/                       # Pre-configured Database Schemas (vidyaverse.sql)
├── index.php                       # Core Landing Page & Gateway Router
├── library.php                     # Digital Library Catalog System
├── kiosk_display.php               # Dynamic Campus Kiosk Screen Interface
├── check_kiosk_status.php          # Monitor & Health Checks for Campus Kiosks
├── get_latest_scan.php             # Webhook service to fetch scanner records
├── cron_generate_notifications.php # Cron automation script for notifications
└── README.md                       # Product Documentation
```

---

## ⚙️ Quick Start Guide

### 1. Repository Setup
Clone this repository directly into your local machine:
```bash
git clone https://github.com/Kaushikghosh2004/VidyaVerse.git
cd VidyaVerse
```

### 2. Local Server Setup
Move the `VidyaVerse` root directory into your web server's public folder:
*   **XAMPP:** `C:/xampp/htdocs/`
*   **WAMP:** `C:/wamp/www/`
*   **LAMP:** `/var/www/html/`

### 3. Database Import
1. Start the **Apache** and **MySQL** services in your server control panel (e.g., XAMPP Control Panel).
2. Visit `http://localhost/phpmyadmin/` in your web browser.
3. Create a new database named `vidyaverse`.
4. Navigate to the **Import** tab, select the `.sql` schema file located inside the `SQL File/` directory of the project, and run the import.

### 4. Configuration
Open the configuration file `includes/config.php` and configure your local credentials:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Add your MySQL password here
define('DB_NAME', 'vidyaverse');
?>
```

### 5. Python AI Engine Setup (Optional)
To enable voice assistance and automated recommendation microservices, set up the virtual environment:

```bash
# Navigate to AI folder
cd python_ai_engine/

# Create a virtual environment
python -m venv venv

# Activate Virtual Environment
# On Windows:
venv\Scripts\activate
# On macOS/Linux:
source venv/bin/activate
```

> [!NOTE]
> For Windows users, since compiling `dlib` can be complex without C++ Build Tools installed, a pre-compiled wheel `dlib-19.24.1-cp311-cp311-win_amd64.whl` is included in the project root. If you are on Windows and using Python 3.11, you can install it using:
> ```bash
> pip install ..\dlib-19.24.1-cp311-cp311-win_amd64.whl
> ```

Install the remaining dependencies:
```bash
pip install -r requirements.txt
```

### 6. Run Project
Open your web browser and load the project URL:
```text
http://localhost/VidyaVerse
```

---

## 🔑 Default Credentials

| Portal Role | Default Username | Default Password |
| :--- | :--- | :--- |
| **Administrator** | `admin` | `admin` |
| **Teacher** | Refer to the database | Refer to the database |
| **Student (User)** | Refer to the database | Refer to the database |

---

Developed by **Kaushik Ghosh**
