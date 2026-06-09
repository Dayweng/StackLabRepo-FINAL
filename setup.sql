-- StackLab Academy — Enrollment System
-- Run once: mysql -u root -p < setup.sql

CREATE DATABASE IF NOT EXISTS stacklab
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE stacklab;

-- ── Admin users ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id         INT          AUTO_INCREMENT PRIMARY KEY,
  full_name  VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── Students ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS students (
  id         INT          AUTO_INCREMENT PRIMARY KEY,
  full_name  VARCHAR(100) NOT NULL,
  email      VARCHAR(150) UNIQUE,
  phone      VARCHAR(20),
  address    TEXT,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── Courses ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS courses (
  id              INT            AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(255)   NOT NULL,
  topic           VARCHAR(100),
  price_per_month DECIMAL(10,2)  NOT NULL DEFAULT 0,
  description     TEXT,
  created_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
);

-- ── Enrollments ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS enrollments (
  id         INT  AUTO_INCREMENT PRIMARY KEY,
  student_id INT  NOT NULL,
  course_id  INT  NOT NULL,
  start_date DATE NOT NULL,
  status     ENUM('active','inactive','completed') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES courses(id)  ON DELETE CASCADE
);

-- ── Schedule (fixed per course, read-only) ────────────────
CREATE TABLE IF NOT EXISTS schedule (
  id          INT         AUTO_INCREMENT PRIMARY KEY,
  course_id   INT         NOT NULL,
  day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  start_time  TIME        NOT NULL,
  end_time    TIME,
  room        VARCHAR(100),
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- ── Payments ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS payments (
  id            INT           AUTO_INCREMENT PRIMARY KEY,
  student_id    INT           NOT NULL,
  enrollment_id INT           NOT NULL,
  amount        DECIMAL(10,2) NOT NULL,
  payment_date  DATE          NOT NULL,
  notes         VARCHAR(255),
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id)    REFERENCES students(id)    ON DELETE CASCADE,
  FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
);

-- ── Student total payment summary ─────────────────────────
CREATE TABLE IF NOT EXISTS student_total_payment (
  id           INT           AUTO_INCREMENT PRIMARY KEY,
  student_id   INT           NOT NULL UNIQUE,
  total_billed DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_paid   DECIMAL(10,2) NOT NULL DEFAULT 0,
  balance      DECIMAL(10,2) GENERATED ALWAYS AS (total_billed - total_paid) STORED,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ═══════════════════════════════════════════════════════════
-- DUMMY DATA
-- Admin password: password
-- ═══════════════════════════════════════════════════════════

INSERT IGNORE INTO users (id, full_name, email, password) VALUES
  (1, 'Admin', 'admin@stacklab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT IGNORE INTO students (id, full_name, email, phone, address) VALUES
  (1, 'Budi Santoso',   'budi@example.com',   '0812-1111-2222', 'Jl. Merdeka No. 10, Jakarta'),
  (2, 'Siti Rahayu',    'siti@example.com',   '0813-3333-4444', 'Jl. Sudirman No. 5, Bandung'),
  (3, 'Andi Wijaya',    'andi@example.com',   '0821-5555-6666', 'Jl. Diponegoro No. 22, Surabaya');

INSERT IGNORE INTO courses (id, title, topic, price_per_month, description) VALUES
  (1, 'Matematika Dasar',   'Matematika',     250000, 'Kursus matematika untuk siswa SD dan SMP.'),
  (2, 'Bahasa Inggris',     'Bahasa',         300000, 'Percakapan dan tata bahasa Inggris sehari-hari.'),
  (3, 'Fisika SMA',         'Sains',          275000, 'Fisika kelas 10–12 mengikuti kurikulum Merdeka.');

INSERT IGNORE INTO enrollments (id, student_id, course_id, start_date, status) VALUES
  (1, 1, 1, '2026-01-06', 'active'),
  (2, 2, 2, '2026-01-06', 'active'),
  (3, 3, 3, '2026-02-03', 'active');

INSERT IGNORE INTO schedule (id, course_id, day_of_week, start_time, end_time, room) VALUES
  (1, 1, 'Monday',    '14:00', '15:30', 'Ruang A'),
  (2, 2, 'Wednesday', '15:00', '16:30', 'Ruang B'),
  (3, 3, 'Saturday',  '09:00', '10:30', 'Ruang A');

INSERT IGNORE INTO payments (id, student_id, enrollment_id, amount, payment_date, notes) VALUES
  (1, 1, 1, 250000, '2026-01-06', 'Pembayaran bulan Januari'),
  (2, 2, 2, 300000, '2026-01-06', 'Pembayaran bulan Januari'),
  (3, 3, 3, 275000, '2026-02-03', 'Pembayaran bulan Februari');

INSERT IGNORE INTO student_total_payment (student_id, total_billed, total_paid) VALUES
  (1, 500000, 250000),
  (2, 300000, 300000),
  (3, 275000, 275000);
