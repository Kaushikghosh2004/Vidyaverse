# VidyaVerse 🎓🤖

VidyaVerse is an AI-powered full-stack campus ecosystem developed to streamline academic administration, event management, digital engagement, and intelligent campus services through a unified platform.

---

## 🚀 Features

### 📚 Academic Management

* Student, Teacher, and Administrator portals.
* Course and educational resource management.
* AI-powered educational labs and learning modules.
* Personalized dashboards for different user roles.

### 🎉 Event & Cultural Management

* Event registration and participation management.
* Digital ticket generation.
* Institutional email verification.
* Secure voting system for competitions and elections.

### 💬 Feedback & Engagement

* Real-time feedback collection.
* Student interaction modules.
* Faculty insights and analytics.

### 🎤 AI-Powered Features

* Voice-enabled administrative workflows.
* AI educational assistance.
* Smart recommendations.
* Python-powered intelligent services.

### 📖 Library Management

* Book catalog and inventory tracking.
* Issue and return management.
* Search and filtering capabilities.
* Voice-assisted library operations.

### 🔔 Notification System

* Automated notification generation.
* Scheduled reminders using cron jobs.
* Real-time updates and announcements.

### 🔒 Security

* Role-Based Access Control (RBAC).
* Session management.
* Secure authentication.
* Institutional verification mechanisms.

---

## 🛠️ Setup and Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Kaushikghosh2004/VidyaVerse.git
cd VidyaVerse
```

### 2. Move Project to Web Server

Copy the `Vidyaverse` folder into:

```text
XAMPP : xampp/htdocs/
WAMP  : wamp/www/
LAMP  : /var/www/html/
```

### 3. Import Database

Open PHPMyAdmin:

```text
http://localhost/phpmyadmin
```

Create a database named:

```text
vidyaverse
```

Import the SQL file located inside:

```text
SQL File/
```

---

### 4. Configure Database

Open:

```text
includes/config.php
```

Update your database credentials:

```php
Host     : localhost
Database : vidyaverse
Username : root
Password :
```

---

### 5. Install Python Dependencies (AI Features)

Navigate to:

```text
python_ai_engine/
```

Install dependencies:

```bash
pip install -r requirements.txt
```

If using a virtual environment:

```bash
python -m venv venv
venv\Scripts\activate      # Windows
source venv/bin/activate   # Linux/macOS
```

---

### 6. Run the Application

Start Apache and MySQL from XAMPP/WAMP.

Open your browser:

```text
http://localhost/Vidyaverse
```

---

## 🔑 Default Credentials

### Administrator

```text
Username: admin
Password: admin
```

> ⚠️ Change all default passwords after first login.

---

## 📦 Project Structure

```text
VidyaVerse/
├── admin/                          # Administrator Panel
├── teacher/                        # Teacher Dashboard
├── user/                           # Student Portal
├── assets/                         # Static Assets
├── css/                            # Stylesheets
├── js/                             # JavaScript Files
├── fonts/                          # Font Resources
├── images/                         # Images and Icons
├── includes/                       # Shared Components & Configurations
├── uploads/                        # User Uploads
├── python_ai_engine/               # AI Modules and Python Services
├── vidyaverse_educational_labs/    # Educational Lab Modules
├── SQL File/                       # Database Files
├── index.php                       # Application Entry Point
├── library.php                     # Digital Library Module
├── kiosk_display.php               # Kiosk Display Interface
├── check_kiosk_status.php          # Kiosk Monitoring
├── get_latest_scan.php             # Scan Retrieval Service
├── cron_generate_notifications.php # Scheduled Notifications
├── make_logos.py                   # Logo Generation Utility
└── README.md                       # Documentation
```

---

## 💻 Technology Stack

### Backend

* PHP
* MySQL
* Python

### Frontend

* HTML5
* CSS3
* JavaScript
* AJAX

### AI & Automation

* Python AI Engine
* Voice-Based Assistance
* Educational Intelligence Modules

### Tools

* Git
* GitHub
* XAMPP

---

## 👥 User Roles

### Admin

* Manage the entire ecosystem.
* Monitor users and activities.
* Generate reports and notifications.
* Configure institutional settings.

### Teacher

* Manage educational activities.
* Access feedback and analytics.
* Interact with students.
* Monitor learning resources.

### Student

* Access academic services.
* Participate in events and voting.
* Use digital library facilities.
* Receive notifications and updates.

---

## 📌 About

VidyaVerse is a next-generation digital campus ecosystem that integrates academics, intelligent services, library management, event operations, AI-powered workflows, and secure user engagement into a single scalable platform designed for modern educational institutions.
