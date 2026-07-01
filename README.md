# Hospital Management System (PHP + MySQL)

A classic hospital management web app: manage doctors, patients, appointments,
prescriptions, billing, and view reports. Built with plain PHP (mysqli, prepared
statements) and MySQL — runs on XAMPP/WAMP with no build step.

## Features
- **Auth** — session login, hashed passwords (bcrypt), CSRF-protected forms
- **Doctors** — CRUD, department + specialty + consultation fee
- **Patients** — CRUD, search by name/phone, auto age from DOB
- **Appointments** — scheduling with **double-booking prevention** (a doctor
  can't be booked twice at the same instant), status workflow, date filters
- **Prescriptions** — diagnosis + notes + multiple medicine line items
- **Billing** — invoices with line items, auto-computed totals, payment status
- **Reports** — appointments per doctor, monthly revenue, patients by gender,
  billing summary, busiest departments
- **Dashboard** — key stats + today's schedule

## Database design (relationships)
```
departments 1─┐
              └─* doctors ─┐
patients ─────────────────┼─* appointments ─* prescriptions ─* prescription_items
              └───────────┴─* bills ─* bill_items
```
- FK constraints with `ON DELETE CASCADE` / `SET NULL` (see `db/schema.sql`)
- Unique key `(doctor_id, scheduled_at)` prevents scheduling clashes
- Indexes on lookup/foreign-key columns

## Setup (XAMPP on Windows)
1. Copy this whole folder into `C:\xampp\htdocs\` (e.g.
   `C:\xampp\htdocs\hospital`). *Tip: avoid spaces in the folder name.*
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Import the schema: open <http://localhost/phpmyadmin> → **Import** →
   choose `db/schema.sql` → **Go**. (Or run it in the SQL tab.)
4. If your MySQL root has a password, edit `includes/config.php`
   (`DB_USER` / `DB_PASS`).
5. Create the admin login: visit
   <http://localhost/hospital/install.php> once, then **delete `install.php`**.
6. Log in at <http://localhost/hospital/login.php>
   - **Username:** `admin`   **Password:** `admin123`

## Project structure
```
db/schema.sql          # database + tables + seed data
includes/
  config.php           # DB credentials & app settings  ← edit this
  db.php               # mysqli connection + query helpers
  auth.php             # sessions, login guard, CSRF, flash messages
  header.php / footer.php
assets/css/style.css
install.php            # one-time admin setup (delete after use)
login.php  logout.php
index.php              # dashboard
doctors.php  patients.php  appointments.php
prescriptions.php  billing.php  reports.php
```

## Security notes
- All queries use **prepared statements** (no SQL injection).
- Output is escaped with `htmlspecialchars` (`e()` helper).
- Forms carry a **CSRF token**; passwords stored with `password_hash`.
- This is a learning/demo project — before any real deployment, add HTTPS,
  role-based permissions, rate limiting, and turn off `display_errors`.
