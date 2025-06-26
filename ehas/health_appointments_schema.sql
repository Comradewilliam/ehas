-- Regions
CREATE TABLE regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Districts
CREATE TABLE districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    region_id INT NOT NULL,
    FOREIGN KEY (region_id) REFERENCES regions(id)
);

-- Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    gender ENUM('male', 'female', 'other'),
    phone VARCHAR(20),
    address VARCHAR(255),
    region_id INT,
    district_id INT,
    role ENUM('admin', 'doctor', 'patient'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (region_id) REFERENCES regions(id),
    FOREIGN KEY (district_id) REFERENCES districts(id)
);

-- Hospitals
CREATE TABLE hospitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    location VARCHAR(255)
);

-- Specialties
CREATE TABLE specialties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100)
);

-- Doctors
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Doctor-Hospital
CREATE TABLE doctor_hospital (
    doctor_id INT,
    hospital_id INT,
    PRIMARY KEY (doctor_id, hospital_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id)
);

-- Doctor-Specialty
CREATE TABLE doctor_specialty (
    doctor_id INT,
    specialty_id INT,
    PRIMARY KEY (doctor_id, specialty_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (specialty_id) REFERENCES specialties(id)
);

-- Appointments
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    doctor_id INT,
    hospital_id INT,
    specialty_id INT,
    status ENUM('pending', 'approved', 'canceled') DEFAULT 'pending',
    appointment_time DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id),
    FOREIGN KEY (specialty_id) REFERENCES specialties(id)
);

-- Sample Regions
INSERT INTO regions (name) VALUES ('Dar es Salaam'), ('Arusha'), ('Mwanza');

-- Sample Districts
INSERT INTO districts (name, region_id) VALUES
('Ilala', 1),
('Kinondoni', 1),
('Temeke', 1),
('Arusha Urban', 2),
('Arumeru', 2),
('Ilemela', 3),
('Nyamagana', 3); 