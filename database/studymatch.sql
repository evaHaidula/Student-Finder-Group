-- =====================================================================
-- StudyMatch database  (MySQL works with XAMPP + phpMyAdmin)
-- WARNING: this script first DELETES any existing "studymatch" database,
--          so you can re-import with the demo data in it cleanly at any time.
-- Demo logins:  admin@nust.na / Admin@123
--               student@students.nust.na / Student@123  (also student2@students.nust.na)
-- =====================================================================

DROP DATABASE IF EXISTS studymatch;
CREATE DATABASE studymatch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studymatch;

-- users: one row per student or admin (register.php, login.php)
CREATE TABLE users (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  full_name        VARCHAR(100) NOT NULL,
  email            VARCHAR(150) NOT NULL UNIQUE,
  student_number   VARCHAR(9)   NOT NULL UNIQUE,
  password_hash    VARCHAR(255) NOT NULL,
  programme        VARCHAR(100) NOT NULL,
  year_of_study    TINYINT      NOT NULL DEFAULT 1,
  current_modules  VARCHAR(255) NOT NULL DEFAULT '',   -- e.g. "CPS 212S, MAT 201S"
  availability     VARCHAR(255) NOT NULL DEFAULT '',   -- e.g. "Tue 16:00-20:00, Thu 17:00-20:00"
  preferred_format VARCHAR(30)  NOT NULL DEFAULT 'In person and online',
  role             ENUM('student','admin') NOT NULL DEFAULT 'student',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- modules: NUST module directory, managed by the admin (code like "PRG 202S")
CREATE TABLE modules (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(10)  NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

-- study_groups: groups created by students, approved by an admin
CREATE TABLE study_groups (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  module_id    INT NOT NULL,
  host_id      INT NOT NULL,
  title        VARCHAR(100) NOT NULL,
  study_goal   VARCHAR(150) NOT NULL,
  description  TEXT NOT NULL,
  day_of_week  ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  start_time   TIME NOT NULL,
  end_time     TIME NOT NULL,
  study_format ENUM('In person','Online') NOT NULL DEFAULT 'In person',
  location     VARCHAR(120) NOT NULL,
  max_members  INT NOT NULL DEFAULT 6,
  status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (module_id) REFERENCES modules(id),
  FOREIGN KEY (host_id)   REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- group_members: the host (approved) plus everyone who joined or asked to join
CREATE TABLE group_members (
  group_id     INT NOT NULL,
  user_id      INT NOT NULL,
  status       ENUM('pending','approved') NOT NULL DEFAULT 'pending',
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (group_id, user_id),
  FOREIGN KEY (group_id) REFERENCES study_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)        ON DELETE CASCADE
) ENGINE=InnoDB;


-- reports: students flag a group, admins resolve it (group.php, admin.php)
CREATE TABLE reports (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  group_id    INT NOT NULL,
  reporter_id INT NOT NULL,
  reason      VARCHAR(255) NOT NULL,
  status      ENUM('open','resolved') NOT NULL DEFAULT 'open',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id)    REFERENCES study_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (reporter_id) REFERENCES users(id)        ON DELETE CASCADE
) ENGINE=InnoDB;


-- contact_messages: support requests from contact.php (user_id empty if not logged in)
CREATE TABLE contact_messages (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NULL,
  category   VARCHAR(50)  NOT NULL,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  subject    VARCHAR(150) NOT NULL,
  message    TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;


-- v_group_cards: one row per group with module, host and confirmed member count.
-- Used by browse.php, group.php, dashboard.php, admin.php and helpers.php
CREATE VIEW v_group_cards AS
SELECT g.id, g.module_id, g.host_id, g.title, g.study_goal, g.description,
       g.day_of_week, g.start_time, g.end_time, g.study_format, g.location,
       g.max_members, g.status, g.created_at,
       m.code AS module_code, m.name AS module_name,
       u.full_name AS host_name, u.programme AS host_programme, u.year_of_study AS host_year,
       (SELECT COUNT(*) FROM group_members gm
         WHERE gm.group_id = g.id AND gm.status = 'approved') AS member_count
FROM study_groups g
JOIN modules m ON m.id = g.module_id
JOIN users   u ON u.id = g.host_id;

-- SAMPLE DATA (so every page has something to show when you first import the database)
INSERT INTO modules (code, name) VALUES
 ('CPS 212S', 'Data Structures and Algorithms'),
 ('MAT 201S', 'Discrete Mathematics II'),
 ('NET 311S', 'Computer Networks'),
 ('PRG 202S', 'Programming 2'),
 ('WAD 621S', 'Web Application Development');

-- Passwords: Admin@123 (admin) and Student@123 (both students)
INSERT INTO users (full_name, email, student_number, password_hash, programme, year_of_study, current_modules, availability, preferred_format, role) VALUES
 ('Demo Admin',    'admin@nust.na', '202600001', '$2y$10$pJ5ksU8fIxNC2hkf.I28rOPx/F57isoBRmFwSS/SiL4WKTG.S6ocK', 'BSc Software Engineering', 3, 'WAD 621S','Mon, Fri', 'In person and online', 'admin'),
 ('Demo Student',  'student@nust.na','202600002', '$2y$10$913bT2n/WUtaZ568NvmtnOcUDfqfE9JEaOt8jrgbD57JUu/JO0ei2', 'BSc Computer Science',     2, 'CPS 212S, MAT 201S, NET 311S', 'Tue 16:00-20:00, Thu 17:00-20:00', 'In person and online', 'student'),
 ('Second Student','student2@nust.na','202600003', '$2y$10$913bT2n/WUtaZ568NvmtnOcUDfqfE9JEaOt8jrgbD57JUu/JO0ei2', 'BSc Computer Science',     2, 'CPS 212S, MAT 201S', 'Tue 16:00-20:00, Sat 09:00-13:00', 'In person','student');

-- Groups: 1 and 2 are approved (visible on Browse), 3 is pending (shows in the admin approvals tab)
INSERT INTO study_groups (module_id, host_id, title, study_goal, description, day_of_week, start_time, end_time, study_format, location, max_members, status) VALUES
 (1, 2, 'Data Structures & Algorithms', 'Exam prep: trees, graphs and complexity',
        'Weekly revision session. We work through past papers and explain solutions to each other.', 'Tuesday',  '16:00:00', '18:00:00', 'In person', 'NUST Library · Room 3', 6, 'approved'),
 (2, 3, 'Discrete Maths Study Circle', 'Practice sets on logic, relations and graphs',
        'Online session where we solve tutorial questions together and share notes.',                'Thursday', '17:00:00', '19:00:00', 'Online',    'Microsoft Teams',       5, 'approved'),
 (3, 2, 'Networks Lab Prep', 'Prepare for the subnetting practical',
        'Hands-on practice with subnetting and packet tracing before lab tests.',                    'Saturday', '09:00:00', '11:00:00', 'In person', 'Computer Lab 2',        4, 'pending');

-- Hosts are approved members of their own groups; student 2 joined group 2; student 3 asked to join group 1
INSERT INTO group_members (group_id, user_id, status) VALUES
 (1, 2, 'approved'),
 (2, 3, 'approved'),
 (3, 2, 'approved'),
 (2, 2, 'approved'),
 (1, 3, 'pending');

INSERT INTO reports (group_id, reporter_id, reason) VALUES
 (2, 2, 'The listed meeting link was not working during the last session.');

INSERT INTO contact_messages (user_id, category, name, email, subject, message) VALUES
 (2, 'General question', 'Demo Student', 'student@nust.na', 'How long does approval take?', 'I created a group a few days ago and it is still pending. How long does admin approval usually take?');