# ELECTRONIC HEALTHCARE APPOINTMENT SYSTEM (EHAS)

EHAS is a web-based application designed to streamline the process of managing healthcare appointments. It provides functionalities for administrators, doctors, and patients, offering a comprehensive solution for scheduling, monitoring, and managing medical appointments efficiently.

## Features

**Patient Features:**
*   **User Registration & Profile Management:** Patients can register new accounts, log in, and manage their personal profiles (username, email, phone, address, date of birth, gender, region, district).
*   **Doctor Search & Appointment Request:** Patients can search for doctors based on specialty and hospital, and request appointments with available doctors.
*   **View Appointments:** Patients can view their upcoming and past appointments.

**Doctor Features:**
*   **Profile Management:** Doctors can manage their personal profiles (username, email, phone, address, date of birth, gender, specialty, hospital).
*   **Manage Appointments:** Doctors can view their scheduled appointments and update the status of pending appointments (approve, reject, or mark as completed).

**Administrator Features:**
*   **Admin Registration:** Ability to register new admin users.
*   **User Management:** Manage doctors and patients (add, edit, delete).
*   **Hospital Management:** Add, edit, and delete hospital information.
*   **Specialty Management:** Add, edit, and delete medical specialties.
*   **Region & District Management:** Manage geographical regions and their associated districts.
*   **Appointment Monitoring:** Overview of all appointments, with filtering options by status.

## Technologies Used

*   **Backend:** PHP (with MySQLi for database interaction)
*   **Database:** MySQL
*   **Frontend:** HTML, CSS (modern styling applied), JavaScript
*   **Web Server:** MAMP/XAMPP (or any Apache/Nginx with PHP & MySQL support)

## Setup Instructions

Follow these steps to set up EHAS on your local machine.

### Prerequisites

*   **MAMP/XAMPP:** A local server environment (Apache, MySQL, PHP).
*   **Web Browser:** A modern web browser.

### 1. Database Setup

1.  **Access phpMyAdmin:** Open your web browser and go to `http://localhost/phpmyadmin` (or the equivalent URL for your MAMP/XAMPP setup).
2.  **Create Database:** Create a new database named `ehas`.
3.  **Import Schema:**
    *   Select the `ehas` database.
    *   Go to the `Import` tab.
    *   Click `Choose File` and select the `health_appointments_schema.sql` file located in the project root.
    *   Click `Go` to import the schema and initial data (regions and districts).
4.  **Run SQL Migrations (Important!):** After importing, execute the following SQL commands within the `ehas` database in phpMyAdmin to add necessary columns that were implemented during development:

    ```sql
    -- For the 'hospitals' table:
    -- Rename 'location' to 'address'
    ALTER TABLE hospitals CHANGE COLUMN location address VARCHAR(255);

    -- Add 'phone' and 'email' columns
    ALTER TABLE hospitals ADD COLUMN phone VARCHAR(20) AFTER address;
    ALTER TABLE hospitals ADD COLUMN email VARCHAR(100) AFTER phone;

    -- For the 'appointments' table:
    -- Add 'reason' column
    ALTER TABLE appointments ADD COLUMN reason TEXT AFTER appointment_time;
    ```

### 2. Application Setup

1.  **Place Project Files:**
    *   Locate your web server's document root (e.g., `htdocs` for XAMPP or `htdocs` within your MAMP directory).
    *   Place the entire `ehas/` project folder (which contains `admin/`, `patient/`, `includes/`, etc.) into your document root. The path should look something like `Y:/pro/MAMP/htdocs/ehas/`.

2.  **Configure `includes/config.php`:**
    *   Open `ehas/includes/config.php`.
    *   Ensure the `BASE_URL` constant is correctly set to your application's base URL. For example:
        ```php
        <?php
        define('BASE_URL', 'http://localhost/ehas/');
        ?>
        ```

### 3. Initial Admin User (Manual Creation)

For initial testing, you can manually create an admin user directly in the `users` table via phpMyAdmin:

1.  Go to the `ehas` database in phpMyAdmin.
2.  Click on the `users` table.
3.  Go to the `Insert` tab.
4.  Fill in the details for a new admin user.
    *   `username`: `admin` (or any desired username)
    *   `email`: `admin@example.com` (or any valid email)
    *   `password`: To create a hashed password, you can use PHP's `password_hash()` function. For example, in a temporary PHP file, run `echo password_hash('your_password_here', PASSWORD_DEFAULT);` and paste the output here.
        *   **Example (for `password` field):** `\$2y\$10\$....................................` (replace with actual hashed password)
    *   `role`: `admin`
    *   Fill other fields as desired.
5.  Click `Go` to insert the new admin user.

## Usage

1.  **Access the Application:** Open your web browser and navigate to `http://localhost/ehas/`.
2.  **Login:**
    *   **Admin:** Use the admin credentials you created.
    *   **Patient:** Register a new patient account or manually create one in the database.
    *   **Doctor:** Manually create a doctor account in the `users` table (similar to admin, but set `role` to `doctor`, and optionally set `specialty_id` and `hospital_id` if you have data for those tables).
3.  **Explore:** Navigate through the various sections based on your user role.

## Future Enhancements (Ideas)

*   Implement a proper doctor registration flow from the admin panel.
*   Add a password reset functionality.
*   Improve error logging and display for better debugging.
*   Implement notification system for appointment status changes.
*   Add a search/filter feature for appointments on doctor/patient dashboards.
*   Enhance UI/UX further with more interactive elements and responsive design.

