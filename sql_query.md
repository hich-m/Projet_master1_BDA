

1. [Database Schema (DDL)](#database-schema-ddl)
2. [User Management](#user-management)
3. [Departments](#departments)
4. [Formations](#formations)
5. [Modules](#modules)
6. [Professors](#professors)
7. [Students](#students)
8. [Rooms (Salles)](#rooms-salles)
9. [Exams (Examens)](#exams-examens)
10. [Surveillances](#surveillances)
11. [Inscriptions (Enrollments)](#inscriptions-enrollments)
12. [Conflicts](#conflicts)
13. [Statistics & Dashboard Queries](#statistics--dashboard-queries)
14. [Scheduling Algorithm Queries](#scheduling-algorithm-queries)
15. [Approval Workflow Queries](#approval-workflow-queries)



```sql
-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doyen', 'chef', 'prof', 'student') NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Departments table
CREATE TABLE departements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    chef_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Formations table
CREATE TABLE formations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    department_id INT NOT NULL,
    description TEXT,
    duration_months INT,
    number_of_groups INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departements(id) ON DELETE CASCADE,
    UNIQUE(name, department_id),
    INDEX idx_department_id (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Professors table
CREATE TABLE professeurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    department_id INT NOT NULL,
    speciality VARCHAR(255),
    max_exams_per_day INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departements(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_department_id (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Modules table
CREATE TABLE modules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    formation_id INT NOT NULL,
    professeur_id INT NOT NULL,
    credit_hours INT,
    semester INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE,
    FOREIGN KEY (professeur_id) REFERENCES professeurs(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_formation_id (formation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Students table
CREATE TABLE etudiants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    formation_id INT NOT NULL,
    student_number VARCHAR(50) NOT NULL UNIQUE,
    enrollment_date DATE,
    group_number INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_student_number (student_number),
    INDEX idx_formation_id (formation_id),
    INDEX idx_group_number (group_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Exam Rooms table
CREATE TABLE salles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    capacity INT NOT NULL,
    location VARCHAR(255),
    type ENUM('classroom', 'amphi', 'computer_lab') DEFAULT 'classroom',
    department_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departements(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_department_id (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Enrollments table
CREATE TABLE inscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    module_id INT NOT NULL,
    enrollment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    grade DECIMAL(5, 2),
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE(student_id, module_id),
    INDEX idx_student_id (student_id),
    INDEX idx_module_id (module_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Exams table
CREATE TABLE examens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    module_id INT NOT NULL,
    formation_id INT,
    group_number INT NOT NULL DEFAULT 1,
    exam_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room_id INT NOT NULL,
    created_by INT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    accepted_by_chefdep BOOLEAN DEFAULT NULL,
    accepted_by_doyen BOOLEAN DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES salles(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE(module_id, group_number, exam_date, start_time),
    INDEX idx_exam_date (exam_date),
    INDEX idx_status (status),
    INDEX idx_module_id (module_id),
    INDEX idx_formation_id (formation_id),
    INDEX idx_room_id (room_id),
    INDEX idx_group_number (group_number),
    INDEX idx_accepted_chefdep (accepted_by_chefdep),
    INDEX idx_accepted_doyen (accepted_by_doyen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Surveillances table
CREATE TABLE surveillances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    prof_id INT NOT NULL,
    role ENUM('invigilator', 'reserve') DEFAULT 'invigilator',
    assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('assigned', 'confirmed', 'completed', 'absent') DEFAULT 'assigned',
    FOREIGN KEY (exam_id) REFERENCES examens(id) ON DELETE CASCADE,
    FOREIGN KEY (prof_id) REFERENCES professeurs(id) ON DELETE CASCADE,
    UNIQUE(exam_id, prof_id),
    INDEX idx_exam_id (exam_id),
    INDEX idx_prof_id (prof_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Conflicts table
CREATE TABLE conflicts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT,
    conflict_type ENUM('student_overlap', 'prof_overload', 'room_capacity', 'time_conflict', 'prof_fairness') NOT NULL,
    description TEXT,
    severity ENUM('low', 'medium', 'high') DEFAULT 'medium',
    resolved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES examens(id) ON DELETE CASCADE,
    INDEX idx_exam_id (exam_id),
    INDEX idx_conflict_type (conflict_type),
    INDEX idx_severity (severity),
    INDEX idx_resolved (resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```



```sql
-- Add chef_id foreign key to departements
ALTER TABLE departements
ADD CONSTRAINT fk_departements_chef 
FOREIGN KEY (chef_id) REFERENCES professeurs(id) ON DELETE SET NULL;
```

---



```sql
SELECT * FROM users WHERE email = ?
```



```sql
INSERT INTO users (email, password, role, full_name, phone) VALUES (?, ?, ?, ?, ?)
```

---

## Departments

### List All Departments with Chef Name

```sql
SELECT d.*, u.full_name as chef_name 
FROM departements d 
LEFT JOIN professeurs p ON d.chef_id = p.id 
LEFT JOIN users u ON p.user_id = u.id 
ORDER BY d.name
```

### Get Department by ID

```sql
SELECT * FROM departements WHERE id = ?
```

### Get Professors in a Department

```sql
SELECT p.id, u.full_name 
FROM professeurs p 
JOIN users u ON p.user_id = u.id 
WHERE p.department_id = ? 
ORDER BY u.full_name
```

### Insert New Department

```sql
INSERT INTO departements (name, description) VALUES (?, ?)
```

### Update Department

```sql
UPDATE departements SET name = ?, description = ?, chef_id = ? WHERE id = ?
```

### Delete Department

```sql
DELETE FROM departements WHERE id = ?
```

### Get Department by Chef

```sql
SELECT d.id FROM departements d WHERE d.chef_id = ?
```

---

## Formations

### List All Formations with Department Name

```sql
SELECT f.*, d.name as department_name 
FROM formations f 
JOIN departements d ON f.department_id = d.id 
ORDER BY f.name
```

### Get All Departments for Dropdown

```sql
SELECT * FROM departements ORDER BY name
```

### Get Formation by ID

```sql
SELECT * FROM formations WHERE id = ?
```

### Insert New Formation

```sql
INSERT INTO formations (name, department_id, duration_months, description) VALUES (?, ?, ?, ?)
```

### Update Formation

```sql
UPDATE formations SET name = ?, department_id = ?, duration_months = ?, description = ? WHERE id = ?
```

### Delete Formation

```sql
DELETE FROM formations WHERE id = ?
```

### Update Formation Groups Count

```sql
UPDATE formations SET number_of_groups = ? WHERE id = ?
```

### Get Formations by Department with Stats

```sql
SELECT f.*, 
       (SELECT COUNT(*) FROM modules WHERE formation_id = f.id) as modules,
       (SELECT COUNT(DISTINCT e.id) FROM etudiants e WHERE formation_id = f.id) as students
FROM formations f 
WHERE f.department_id = ?
ORDER BY f.name
```

---

## Modules

### List All Modules with Details

```sql
SELECT m.*, f.name as formation_name, d.name as department_name, u.full_name as professor_name 
FROM modules m 
JOIN formations f ON m.formation_id = f.id 
JOIN departements d ON f.department_id = d.id 
JOIN professeurs p ON m.professeur_id = p.id
JOIN users u ON p.user_id = u.id
ORDER BY m.name
```

### Get Formations for Module Dropdown

```sql
SELECT m.*, d.name as department_name 
FROM formations m 
JOIN departements d ON m.department_id = d.id 
ORDER BY m.name
```

### Get Professors for Module Dropdown

```sql
SELECT p.id, u.full_name, d.name as department_name 
FROM professeurs p 
JOIN users u ON p.user_id = u.id 
JOIN departements d ON p.department_id = d.id 
ORDER BY u.full_name
```

### Get Module by ID

```sql
SELECT * FROM modules WHERE id = ?
```

### Count Modules in Formation

```sql
SELECT COUNT(*) as count FROM modules WHERE formation_id = ?
```

### Get Formation ID by Module

```sql
SELECT formation_id FROM modules WHERE id = ?
```

### Insert New Module

```sql
INSERT INTO modules (name, code, formation_id, professeur_id, credit_hours, semester) VALUES (?, ?, ?, ?, ?, ?)
```

### Update Module

```sql
UPDATE modules SET name = ?, code = ?, formation_id = ?, professeur_id = ?, credit_hours = ?, semester = ? WHERE id = ?
```

### Delete Module

```sql
DELETE FROM modules WHERE id = ?
```

### Get Student Modules

```sql
SELECT m.*, f.name as formation_name, d.name as department_name 
FROM modules m
JOIN formations f ON m.formation_id = f.id
JOIN departements d ON f.department_id = d.id
JOIN inscriptions i ON m.id = i.module_id
WHERE i.student_id = ? AND i.status = 'active'
ORDER BY m.name
```

### Get Professor Modules

```sql
SELECT m.*, f.name as formation_name, d.name as department_name 
FROM modules m
JOIN formations f ON m.formation_id = f.id
JOIN departements d ON f.department_id = d.id
WHERE m.professeur_id = ?
ORDER BY m.name
```

---

## Professors

### Get Professor by User ID

```sql
SELECT p.* FROM professeurs p WHERE p.user_id = ?
```

### Get Professors by Department

```sql
SELECT id FROM professeurs WHERE department_id = ?
```

### Get All Professors with Department Info

```sql
SELECT p.id, p.department_id FROM professeurs p
```

### Check if User is Chef de Département

```sql
SELECT d.id FROM departements d 
JOIN professeurs p ON d.chef_id = p.id 
WHERE p.user_id = ?
```

---

## Students

### Get Student by User ID

```sql
SELECT s.* FROM etudiants s WHERE s.user_id = ?
```

### Get Students Enrolled in Module

```sql
SELECT student_id FROM inscriptions WHERE module_id = ? AND status = 'active' ORDER BY student_id
```

---

## Rooms (Salles)

### Get All Rooms Sorted by Capacity

```sql
SELECT id, capacity FROM salles WHERE capacity > 0 ORDER BY capacity ASC
```

### Get Room Capacity

```sql
SELECT capacity FROM salles WHERE id = ?
```

---

## Exams (Examens)

### Get All Exams with Details (Admin)

```sql
SELECT e.*, m.name as module_name, m.code as module_code, 
       f.name as formation_name, f.id as formation_id,
       d.name as department_name, d.id as department_id,
       s.name as room_name, s.capacity,
       COUNT(DISTINCT CASE 
           WHEN e.group_number = 0 THEN i.student_id
           WHEN i.group_number = e.group_number THEN i.student_id
           ELSE NULL
       END) as enrolled_count,
       (SELECT u.full_name FROM surveillances sv 
        JOIN professeurs p ON sv.prof_id = p.id 
        JOIN users u ON p.user_id = u.id 
        WHERE sv.exam_id = e.id LIMIT 1) as invigilator_name
FROM examens e
JOIN modules m ON e.module_id = m.id
JOIN formations f ON m.formation_id = f.id
JOIN departements d ON f.department_id = d.id
JOIN salles s ON e.room_id = s.id
LEFT JOIN inscriptions i ON m.id = i.module_id AND i.status = 'active'
LEFT JOIN etudiants et ON i.student_id = et.id
GROUP BY e.id
ORDER BY d.name, f.name, e.exam_date, e.start_time
```

### Get All Exams with Approval Status (Doyen)

```sql
SELECT e.*, m.name as module_name, m.code as module_code, 
       f.name as formation_name, d.name as department_name,
       s.name as room_name, s.capacity,
       e.accepted_by_chefdep, e.accepted_by_doyen,
       COUNT(DISTINCT CASE 
           WHEN e.group_number = 0 THEN i.student_id
           WHEN i.group_number = e.group_number THEN i.student_id
           ELSE NULL
       END) as enrolled_count
FROM examens e
JOIN modules m ON e.module_id = m.id
JOIN formations f ON m.formation_id = f.id
JOIN departements d ON f.department_id = d.id
JOIN salles s ON e.room_id = s.id
LEFT JOIN inscriptions i ON m.id = i.module_id AND i.status = 'active'
LEFT JOIN etudiants et ON i.student_id = et.id
WHERE d.id = ?
GROUP BY e.id
ORDER BY e.accepted_by_doyen IS NULL DESC, e.accepted_by_chefdep DESC, e.exam_date DESC, e.start_time DESC
```

### Get Student Exams (Approved Only)

```sql
SELECT e.*, m.name as module_name, m.code as module_code, 
       s.name as room_name, s.capacity, f.name as formation_name, d.name as department_name
FROM examens e
JOIN modules m ON e.module_id = m.id
JOIN salles s ON e.room_id = s.id
JOIN formations f ON m.formation_id = f.id
JOIN departements d ON f.department_id = d.id
JOIN inscriptions i ON m.id = i.module_id
WHERE i.student_id = ? AND i.status = 'active'
AND e.accepted_by_chefdep = 1 AND e.accepted_by_doyen = 1
AND (e.group_number = 0 OR e.group_number = i.group_number)
ORDER BY e.exam_date ASC, e.start_time ASC
```

### Get Professor Exams

```sql
SELECT e.*, m.name as module_name, m.code as module_code, 
       s.name as room_name, s.capacity, d.name as department_name
FROM examens e
JOIN modules m ON e.module_id = m.id
JOIN salles s ON e.room_id = s.id
JOIN formations f ON m.formation_id = f.id
JOIN departements d ON f.department_id = d.id
WHERE m.professeur_id = ?
ORDER BY e.exam_date ASC, e.start_time ASC
```

### Insert New Exam

```sql
INSERT INTO examens (module_id, group_number, exam_date, start_time, end_time, room_id, created_by, status) 
VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')
```

### Delete Exam

```sql
DELETE FROM examens WHERE id = ?
```

### Check Exam Approval Status

```sql
SELECT accepted_by_chefdep FROM examens WHERE id = ?
```

---

## Surveillances

### Get Professor Surveillances

```sql
SELECT sv.*, e.exam_date, e.start_time, e.end_time, 
       m.name as module_name, m.code as module_code, s.name as room_name, 
       d.name as department_name
FROM surveillances sv
JOIN examens e ON sv.exam_id = e.id
JOIN modules m ON e.module_id = m.id
JOIN salles s ON e.room_id = s.id
JOIN departements d ON (
    SELECT department_id FROM formations WHERE id = m.formation_id
) = d.id
WHERE sv.prof_id = ?
ORDER BY e.exam_date ASC, e.start_time ASC
```

### Insert Surveillance Assignment

```sql
INSERT INTO surveillances (exam_id, prof_id, role, status) VALUES (?, ?, 'invigilator', 'assigned')
```

### Get Professors with Surveillance Count (Same Department Priority)

```sql
SELECT p.id, COUNT(sv.id) as surveillance_count
FROM professeurs p
LEFT JOIN surveillances sv ON p.id = sv.prof_id 
AND sv.status IN ('assigned', 'confirmed', 'completed')
WHERE p.department_id = ?
AND p.id NOT IN (
    SELECT prof_id FROM surveillances 
    WHERE exam_id = ?
)
GROUP BY p.id
ORDER BY surveillance_count ASC
LIMIT ?
```

### Get Professors with Surveillance Count (Other Departments)

```sql
SELECT p.id, COUNT(sv.id) as surveillance_count
FROM professeurs p
LEFT JOIN surveillances sv ON p.id = sv.prof_id 
AND sv.status IN ('assigned', 'confirmed', 'completed')
WHERE p.department_id != ?
AND p.id NOT IN (
    SELECT prof_id FROM surveillances 
    WHERE exam_id = ?
)
GROUP BY p.id
ORDER BY surveillance_count ASC
LIMIT ?
```

### Delete Surveillances for Exam

```sql
DELETE FROM surveillances WHERE exam_id = ?
```

---

## Inscriptions (Enrollments)

### Get Enrolled Students Count for Module

```sql
SELECT COUNT(DISTINCT i.student_id) as enrolled_count
FROM inscriptions i
WHERE i.module_id = ? AND i.status = 'active'
```

### Update Student Group Number

```sql
UPDATE inscriptions SET group_number = ? WHERE module_id = ? AND student_id IN (?)
```

---

## Conflicts

### Get All Conflicts (Unresolved)

```sql
SELECT c.*, m.name as module_name, m.code as module_code, e.exam_date, e.start_time
FROM conflicts c
LEFT JOIN examens e ON c.exam_id = e.id
LEFT JOIN modules m ON e.module_id = m.id
WHERE c.resolved = FALSE
ORDER BY c.severity DESC, c.created_at DESC
```

### Get Conflicts by Department

```sql
SELECT c.*, m.name as module_name, m.code as module_code, e.exam_date, e.start_time
FROM conflicts c
LEFT JOIN examens e ON c.exam_id = e.id
LEFT JOIN modules m ON e.module_id = m.id
LEFT JOIN formations f ON m.formation_id = f.id
WHERE f.department_id = ? AND c.resolved = FALSE
ORDER BY c.severity DESC, c.created_at DESC
```

### Record Conflict (With Exam ID)

```sql
INSERT INTO conflicts (exam_id, conflict_type, description, severity, department_id) VALUES (?, ?, ?, ?, ?)
```

### Record Conflict (Without Exam ID)

```sql
INSERT INTO conflicts (conflict_type, description, severity, department_id) VALUES (?, ?, ?, ?)
```

### Delete Conflicts for Exam

```sql
DELETE FROM conflicts WHERE exam_id = ?
```

### Aggregate Conflict Counts by Type

```sql
SELECT conflict_type, COUNT(*) as cnt FROM conflicts WHERE resolved = FALSE GROUP BY conflict_type
```

---

## Statistics & Dashboard Queries

### Global Statistics

```sql
SELECT COUNT(*) as count FROM departements
SELECT COUNT(*) as count FROM formations
SELECT COUNT(*) as count FROM etudiants
SELECT COUNT(*) as count FROM professeurs
SELECT COUNT(*) as count FROM modules
SELECT COUNT(*) as count FROM examens
SELECT COUNT(*) as count FROM salles
SELECT COUNT(*) as count FROM conflicts WHERE resolved = FALSE
```

### Department Statistics

```sql
SELECT COUNT(*) as count FROM formations WHERE department_id = ?
SELECT COUNT(*) as count FROM professeurs WHERE department_id = ?
SELECT COUNT(DISTINCT e.id) as count FROM etudiants e 
JOIN formations f ON e.formation_id = f.id 
WHERE f.department_id = ?
SELECT COUNT(*) as count FROM examens e
JOIN modules m ON e.module_id = m.id
JOIN formations f ON m.formation_id = f.id
WHERE f.department_id = ?
SELECT COUNT(*) as count FROM conflicts c
LEFT JOIN examens e ON c.exam_id = e.id
LEFT JOIN modules m ON e.module_id = m.id
LEFT JOIN formations f ON m.formation_id = f.id
WHERE f.department_id = ? AND c.resolved = FALSE
```

### Scheduling Statistics

```sql
SELECT COUNT(*) as count FROM modules
SELECT COUNT(DISTINCT module_id) as count FROM examens
SELECT COUNT(*) as count FROM examens
SELECT COUNT(*) as count FROM examens WHERE status = 'scheduled'
```

### Pending Exams for Chef Approval

```sql
SELECT COUNT(*) as count FROM examens e
JOIN modules m ON e.module_id = m.id
JOIN formations f ON m.formation_id = f.id
WHERE f.department_id = ? AND e.accepted_by_chefdep IS NULL
```

---

## Scheduling Algorithm Queries

### Get All Modules with Student Count

```sql
SELECT m.id, m.name, m.code, m.formation_id, 
       f.name as formation_name, f.department_id, f.number_of_groups,
       COUNT(DISTINCT i.student_id) as student_count
FROM modules m
JOIN formations f ON m.formation_id = f.id
LEFT JOIN inscriptions i ON m.id = i.module_id AND i.status = 'active'
GROUP BY m.id
ORDER BY f.department_id, f.id, m.id
```

### Clear All Scheduling Data

```sql
DELETE FROM surveillances
DELETE FROM conflicts
DELETE FROM examens
```

---

## Approval Workflow Queries

### Approve/Reject by Chef de Département

```sql
UPDATE examens e
JOIN modules m ON e.module_id = m.id
JOIN formations f ON m.formation_id = f.id
SET e.accepted_by_chefdep = ?
WHERE e.id = ? AND f.department_id = ?
```

### Bulk Approve/Reject by Chef

```sql
UPDATE examens e
JOIN modules m ON e.module_id = m.id
JOIN formations f ON m.formation_id = f.id
SET e.accepted_by_chefdep = ?
WHERE e.id IN (?) AND f.department_id = ?
```

### Approve/Reject by Doyen

```sql
UPDATE examens SET accepted_by_doyen = ? WHERE id = ?
```

### Bulk Approve/Reject by Doyen (Only if Chef Approved)

```sql
UPDATE examens SET accepted_by_doyen = ? 
WHERE id IN (?) AND accepted_by_chefdep = 1
```

---

## Constraint Validation Queries

### Check Student Exam on Date

```sql
SELECT e.id, e.start_time, e.end_time, m.name as module_name, m.code as module_code
FROM examens e
JOIN modules m ON e.module_id = m.id
JOIN inscriptions i ON m.id = i.module_id
WHERE i.student_id = ? 
AND e.exam_date = ?
AND i.status = 'active'
AND e.status IN ('scheduled', 'in_progress', 'completed')
```

### Check Professor Exam Count on Date

```sql
SELECT COUNT(DISTINCT e.id) as exam_count
FROM examens e
JOIN modules m ON e.module_id = m.id
WHERE m.professeur_id = ? 
AND e.exam_date = ?
AND e.status IN ('scheduled', 'in_progress', 'completed')
```

### Check Professor Max Exams Per Day

```sql
SELECT max_exams_per_day FROM professeurs WHERE id = ?
```

### Check Professor Time Conflict

```sql
SELECT e.id, e.start_time, e.end_time, m.name as module_name, m.code as module_code
FROM examens e
JOIN modules m ON e.module_id = m.id
WHERE m.professeur_id = ? 
AND e.exam_date = ?
AND e.status IN ('scheduled', 'in_progress', 'completed')
AND (
    (TIME(?) < e.end_time AND TIME(?) > e.start_time) OR
    (TIME(?) < e.end_time AND TIME(?) > e.start_time)
)
```

### Check Student Exam Conflicts (Time Overlaps)

```sql
SELECT e1.exam_date, e1.id as exam1_id, e2.id as exam2_id,
       m1.name as module1_name, m2.name as module2_name,
       e1.start_time as start1, e1.end_time as end1,
       e2.start_time as start2, e2.end_time as end2
FROM examens e1
JOIN examens e2 ON e1.exam_date = e2.exam_date AND e1.id < e2.id
JOIN modules m1 ON e1.module_id = m1.id
JOIN modules m2 ON e2.module_id = m2.id
JOIN inscriptions i1 ON m1.id = i1.module_id
JOIN inscriptions i2 ON m2.id = i2.module_id
WHERE i1.student_id = ? AND i2.student_id = ?
AND i1.status = 'active' AND i2.status = 'active'
AND e1.status IN ('scheduled', 'in_progress', 'completed')
AND e2.status IN ('scheduled', 'in_progress', 'completed')
AND e1.accepted_by_chefdep = 1 AND e1.accepted_by_doyen = 1
AND e2.accepted_by_chefdep = 1 AND e2.accepted_by_doyen = 1
AND (e1.start_time < e2.end_time AND e1.end_time > e2.start_time)
ORDER BY e1.exam_date
```

### Check Professor Overload (More than 3 exams/day)

```sql
SELECT e.exam_date, COUNT(s.id) as exam_count
FROM surveillances s
JOIN examens e ON s.exam_id = e.id
WHERE s.prof_id = ? 
AND s.status = 'assigned'
GROUP BY e.exam_date
HAVING exam_count > 3
```

### Check Supervision Balance

```sql
SELECT p.id, p.user_id, u.full_name, COUNT(sv.id) as surveillance_count
FROM professeurs p
JOIN users u ON p.user_id = u.id
LEFT JOIN surveillances sv ON p.id = sv.prof_id AND sv.status IN ('assigned', 'confirmed', 'completed')
WHERE p.department_id = ?
GROUP BY p.id, p.user_id, u.full_name
ORDER BY surveillance_count ASC
```

---

*Document generated from source code analysis of the M1PROJET Exam Timetable Management System*
