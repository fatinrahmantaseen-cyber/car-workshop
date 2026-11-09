# Car Workshop Appointment Management System

A web-based car workshop appointment management system developed for **CSE 391: Programming for the Internet**.

---

## 1. Project Overview

The **Car Workshop Appointment Management System** provides an organized online platform for vehicle owners to schedule maintenance and repair services directly with certified senior mechanics without long workshop queues. 

The application consists of two integrated components:
1. **User Panel:** Clients can view live daily capacity for each of the workshop's 5 senior mechanics, select a preferred specialist, and book an appointment with real-time slot checking.
2. **Admin Panel:** Workshop staff can view incoming appointments, search and filter bookings, update statuses (`active`, `completed`, `cancelled`), and safely reschedule appointments or reassign mechanics while strictly enforcing capacity limits.

---

## 2. Technologies Used

- **Front-End:** HTML5, CSS3, Vanilla JavaScript (No frameworks like Bootstrap or Tailwind)
- **Back-End:** PHP 8.x (Vanilla, object-oriented/procedural using PDO)
- **Database:** MySQL / MariaDB (InnoDB engine, utf8mb4 charset)
- **Architecture:** Procedural and modular PHP with clean separation of layout templates (`includes/`), business helpers (`functions.php`), configuration (`config/`), and actions (`actions/`).

---

## 3. Required Software

- **Web Server:** Apache (via XAMPP)
- **Database Server:** MariaDB / MySQL (via XAMPP)
- **Runtime:** PHP 8.0 or higher (PHP 8.2+ recommended)
- **Web Browser:** Any modern browser (Google Chrome, Mozilla Firefox, Microsoft Edge)

---

## 4. Installation Instructions for Windows

1. **Install XAMPP:**
   - Download and install XAMPP from [Apache Friends](https://www.apachefriends.org/).
   - Ensure the Apache and MySQL components are installed.
2. **Clone / Copy the Project:**
   - Place this project folder inside your XAMPP web root:
     ```text
     C:\xampp\htdocs\car-workshop
     ```
3. **Start Services:**
   - Open the **XAMPP Control Panel**.
   - Start both the **Apache** and **MySQL** modules (both indicators should turn green).

---

## 5. Database Creation & Import Instructions

### Option A: Using phpMyAdmin (Recommended)
1. Open your browser and navigate to:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Click on the **Import** tab on the top menu bar.
3. Under **File to import**, click **Choose File** (or **Browse**).
4. Select the `database.sql` file located at:
   ```text
   C:\xampp\htdocs\car-workshop\database.sql
   ```
5. Leave the character set as `utf-8` and format as `SQL`.
6. Scroll to the bottom and click **Import** (or **Go**).
7. phpMyAdmin will execute the script, create `car_workshop_db`, set up the three InnoDB tables (`mechanics`, `appointments`, `admins`), and insert the 5 senior mechanics.

### Option B: Using MySQL CLI
Open PowerShell or Command Prompt and run:
```bash
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\car-workshop\database.sql
```

---

## 6. How to Run the Project Locally

Once Apache and MySQL are running and the database is imported, access the project via:

- **Homepage & Booking Form:**
  ```text
  http://localhost/car-workshop/index.php
  ```
- **About Us Page:**
  ```text
  http://localhost/car-workshop/about.php
  ```
- **Help & Guidelines Page:**
  ```text
  http://localhost/car-workshop/help.php
  ```
- **Admin Portal:**
  ```text
  http://localhost/car-workshop/admin/login.php
  ```

---

## 7. Core Business & Booking Rules

### A. 4-Car Daily Limit per Mechanic
- The workshop employs 5 senior mechanics: **Rahim, Karim, Jashim, Masud, and Helal**.
- Each senior mechanic is strictly capped at a **maximum of 4 active appointments per day**.
- Dynamic badges show live remaining capacity (e.g. `Rahim — 4 slots available`).
- Once a mechanic reaches 4 active bookings on a given date, their badge switches to `Fully Booked`, and their selection is automatically disabled.
- Completed and cancelled appointments do not count toward this limit.

### B. Duplicate Appointment Prevention
- A client cannot hold more than **one active appointment on the same date**, regardless of which mechanic is selected.
- The client's **phone number** serves as their booking identifier.
- If a client attempts to book another car for the same date with the same phone number, the request is rejected with a clear notification.

### C. Concurrency & Transaction Safety
- Both client bookings (`actions/book-appointment.php`) and admin rescheduling (`admin/edit-appointment.php`) use **PDO transactions** (`beginTransaction`, `commit`, `rollBack`).
- Requests acquire exclusive locks on all active mechanic rows in ascending ID order:
  ```sql
  SELECT id, name, is_active FROM mechanics WHERE is_active = 1 ORDER BY id ASC FOR UPDATE;
  ```
- This prevents race conditions and ensures that simultaneous booking requests are strictly serialized without deadlocks.

---

## 8. Admin Account Setup & Login Instructions

1. Open `http://localhost/car-workshop/admin/login.php` in your browser.
2. If no admin account exists in the database, the system will automatically display the **🔐 Initial Admin Setup** form.
3. Enter your desired **Administrator Username** (e.g., `admin`) and **Password** (minimum 6 characters).
4. Click **Create Admin Account**. The password is encrypted securely using PHP's `password_hash($password, PASSWORD_DEFAULT)` and stored in the `admins` table.
5. Once created, setup mode is closed. Enter your credentials on the standard login form and click **Sign In to Admin Panel**.
6. From the **Admin Dashboard**, you can:
   - View real-time statistics (Total, Today's, Active, Completed, Cancelled).
   - Check each mechanic's workload for today.
   - Access **Manage Appointments** to view, search, filter, mark completed/cancelled, or edit and reschedule bookings.
   - Use the **Logout** button to destroy the session.

---

## 9. Production & Hosting Deployment Notes

When moving this project from local XAMPP to a live web hosting server (e.g., cPanel or VPS):
1. **Database Credentials:** Update `config/database.php` with your live hosting database credentials:
   ```php
   $host     = 'localhost';              // or your host DB IP
   $db_name  = 'your_cpanel_dbname';
   $username = 'your_cpanel_dbuser';
   $password = 'your_secure_password';
   ```
2. **File Permissions:** Ensure standard web permissions (directories `755`, files `644`).
3. **No Hardcoded URLs:** All links and asset inclusions use relative paths, so the application will run from any domain or subfolder without path modifications.

---

## 10. Project Structure

```text
car-workshop/
│
├── index.php                 # Landing page, Hero, Mechanics section & booking form
├── about.php                 # Workshop background and mechanic team profiles
├── help.php                  # User instructions, capacity policy, and FAQ
├── database.sql              # Database schema & initial mechanics seed data
│
├── config/
│   └── database.php          # PDO connection settings and error handling
│
├── includes/
│   ├── header.php            # Common navigation header & flash message alerts
│   ├── footer.php            # Common footer with workshop contact info
│   └── functions.php         # Reusable helpers, slot calculations & CSRF tokens
│
├── admin/
│   ├── login.php             # First-time setup & admin login
│   ├── dashboard.php         # Protected admin dashboard with metrics
│   ├── appointments.php      # Appointment records table, filtering & search
│   ├── edit-appointment.php  # Rescheduling & mechanic reassignment
│   └── logout.php            # Admin session termination
│
├── actions/
│   └── book-appointment.php  # Secure transactional booking submission handler
│
├── assets/
│   ├── css/
│   │   └── style.css         # Custom responsive stylesheet (Vanilla CSS3)
│   ├── js/
│   │   └── script.js         # Client validation & dynamic AJAX availability refresh
│   └── images/               # Image assets directory
│
└── README.md                 # Project documentation and setup guide
```
