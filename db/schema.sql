-- ============================================================
-- Hospital Management System - MySQL schema
-- Engine: InnoDB (foreign keys), charset utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS hospital_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospital_db;

-- Drop in FK-safe order (useful for re-import) ---------------
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS bill_items;
DROP TABLE IF EXISTS bills;
DROP TABLE IF EXISTS prescription_items;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Application users (staff / admin login) --------------------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(120) NOT NULL,
  role          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Departments ------------------------------------------------
CREATE TABLE departments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- Doctors ----------------------------------------------------
CREATE TABLE doctors (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  department_id INT DEFAULT NULL,
  specialty     VARCHAR(120) DEFAULT NULL,
  phone         VARCHAR(30)  DEFAULT NULL,
  email         VARCHAR(120) DEFAULT NULL,
  consult_fee   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_doctor_department
    FOREIGN KEY (department_id) REFERENCES departments(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_doctor_department (department_id)
) ENGINE=InnoDB;

-- Patients ---------------------------------------------------
CREATE TABLE patients (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  full_name    VARCHAR(120) NOT NULL,
  gender       ENUM('male','female','other') DEFAULT NULL,
  dob          DATE DEFAULT NULL,
  phone        VARCHAR(30)  DEFAULT NULL,
  email        VARCHAR(120) DEFAULT NULL,
  address      VARCHAR(255) DEFAULT NULL,
  blood_group  VARCHAR(5)   DEFAULT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_patient_name (full_name)
) ENGINE=InnoDB;

-- Appointments (patient <-> doctor scheduling) ---------------
CREATE TABLE appointments (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  patient_id   INT NOT NULL,
  doctor_id    INT NOT NULL,
  scheduled_at DATETIME NOT NULL,
  reason       VARCHAR(255) DEFAULT NULL,
  status       ENUM('scheduled','completed','cancelled','no_show')
                 NOT NULL DEFAULT 'scheduled',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_appt_patient
    FOREIGN KEY (patient_id) REFERENCES patients(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_appt_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  -- prevent double-booking the same doctor at the same instant
  UNIQUE KEY uq_doctor_slot (doctor_id, scheduled_at),
  INDEX idx_appt_patient (patient_id),
  INDEX idx_appt_date (scheduled_at)
) ENGINE=InnoDB;

-- Prescriptions (one per visit/appointment) ------------------
CREATE TABLE prescriptions (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT DEFAULT NULL,
  patient_id     INT NOT NULL,
  doctor_id      INT NOT NULL,
  diagnosis      VARCHAR(255) DEFAULT NULL,
  notes          TEXT DEFAULT NULL,
  issued_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_presc_appt
    FOREIGN KEY (appointment_id) REFERENCES appointments(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_presc_patient
    FOREIGN KEY (patient_id) REFERENCES patients(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_presc_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_presc_patient (patient_id)
) ENGINE=InnoDB;

-- Individual medicines within a prescription -----------------
CREATE TABLE prescription_items (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  prescription_id INT NOT NULL,
  medicine        VARCHAR(150) NOT NULL,
  dosage          VARCHAR(100) DEFAULT NULL,
  frequency       VARCHAR(100) DEFAULT NULL,
  duration        VARCHAR(100) DEFAULT NULL,
  CONSTRAINT fk_item_prescription
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Bills ------------------------------------------------------
CREATE TABLE bills (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  patient_id   INT NOT NULL,
  appointment_id INT DEFAULT NULL,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status       ENUM('unpaid','paid','partial') NOT NULL DEFAULT 'unpaid',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bill_patient
    FOREIGN KEY (patient_id) REFERENCES patients(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_bill_appt
    FOREIGN KEY (appointment_id) REFERENCES appointments(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_bill_patient (patient_id)
) ENGINE=InnoDB;

-- Line items within a bill -----------------------------------
CREATE TABLE bill_items (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  bill_id     INT NOT NULL,
  description VARCHAR(200) NOT NULL,
  quantity    INT NOT NULL DEFAULT 1,
  unit_price  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_billitem_bill
    FOREIGN KEY (bill_id) REFERENCES bills(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Seed data
-- ============================================================
-- NOTE: The admin login is created by running install.php in the browser
-- (it hashes the password with PHP's password_hash()). Default credentials
-- after running install.php ->  username: admin   password: admin123

INSERT INTO departments (name, description) VALUES
  ('Cardiology',   'Heart and cardiovascular care'),
  ('Neurology',    'Brain and nervous system'),
  ('Orthopedics',  'Bones, joints and muscles'),
  ('Pediatrics',   'Child healthcare'),
  ('General Medicine', 'Primary and general care');

INSERT INTO doctors (full_name, department_id, specialty, phone, email, consult_fee) VALUES
  ('Dr. Ayesha Khan',  1, 'Interventional Cardiology', '0300-1112233', 'ayesha@hospital.test', 3000.00),
  ('Dr. Bilal Ahmed',  2, 'Neurologist',               '0300-2223344', 'bilal@hospital.test',  2500.00),
  ('Dr. Sara Malik',   4, 'Pediatrician',              '0300-3334455', 'sara@hospital.test',   2000.00),
  ('Dr. Usman Tariq',  5, 'General Physician',         '0300-4445566', 'usman@hospital.test',  1500.00);

INSERT INTO patients (full_name, gender, dob, phone, email, address, blood_group) VALUES
  ('Ali Raza',    'male',   '1990-05-14', '0311-1234567', 'ali@example.test',  'Lahore',     'O+'),
  ('Fatima Noor', 'female', '1985-11-02', '0312-7654321', 'fatima@example.test','Karachi',   'A+'),
  ('Hamza Sheikh','male',   '2015-03-21', '0313-9876543', NULL,                 'Islamabad', 'B+');
