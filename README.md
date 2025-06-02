# SWE30003-SOFTWARE-ARCHITECTURE-AND-DESIGN
# Kuching ART Website (Group_21 A3)

A web-based management and booking system for the Kuching Autonomous Rapid Transit (ART), built with PHP (OOP), MySQL, and Bootstrap.

---

## 🚀 Features

- User and Admin authentication
- Trip booking and management
- Merchandise and promotion management
- Points and rewards system
- Feedback and notification modules
- Modern, responsive UI

---

## 🛠️ Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) or any LAMP/WAMP stack with PHP 8+ and MySQL
- Composer (optional, if you want to manage dependencies)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/K3NN3TH-KUAN/SWE30003-SOFTWARE-ARCHITECTURE-AND-DESIGN.git
   ```

2. **Move the project to your web server root:**
   - For XAMPP, place the project folder in `C:/xampp/htdocs/`

3. **Start Apache and MySQL** from your XAMPP control panel.

4. **Database Setup:**
   - The database and tables will be created automatically when you first visit the login page (`/software_application/pages/login.php`).
   - Dummy data will be loaded from `software_application/dummy_data.json`.

5. **Access the application:**
   - Open your browser and go to:  
     `http://localhost/SWE30003-SOFTWARE-ARCHITECTURE-AND-DESIGN/software_application/pages/login.php`

---

## 👤 Default Accounts

### Admins
- **Email:** `kenneth@example.com`  
  **Password:** `admin`
- (See `dummy_data.json` for more admin accounts)

### Users
- **Email:** `john.smith@email.com`  
  **Password:** `password123`
- (See `dummy_data.json` for more user accounts)

---

## 🛑 Stopping the Project

1. **Log out** from the application.
2. **Stop Apache and MySQL** from your XAMPP control panel.

---

## 📁 Project Structure
```
software_application/
├── classes/ # PHP OOP classes (Account, Admin, Trip, etc.)
├── pages/ # All PHP pages (UI, logic, controllers)
├── dummy_data.json # Dummy data for initial database setup
├── setup_database.php # Script to create DB schema and load dummy data
├── uploads/ # Uploaded files (e.g., identity documents)
└── ... # Other supporting files
```

---

## 📝 Notes

- If you want to reset the database, delete the `software_app_db` database from phpMyAdmin or MySQL, then reload the login page.
- All uploads (e.g., identity documents) are stored in the `uploads/` folder.

---

## 📢 License

This project is for academic purposes (SWE30003 Software Architecture and Design, Group 21).

---
