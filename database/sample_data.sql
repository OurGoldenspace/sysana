-- Insert sample faculty members
INSERT INTO users (username, email, password, role, department) VALUES
('john_smith', 'john.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 'Computer Science'),
('mary_jones', 'mary.jones@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 'Mathematics');

-- Insert sample students
INSERT INTO users (username, email, password, role, enrollment_number, department) VALUES
('student1', 'student1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'CS2023001', 'Computer Science'),
('student2', 'student2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'CS2023002', 'Computer Science');

-- Insert sample courses
INSERT INTO courses (course_code, course_name, description, credits, capacity, department, instructor_id) VALUES
('CS101', 'Introduction to Programming', 'Basic programming concepts using Python', 3, 30, 'Computer Science', 
    (SELECT id FROM users WHERE username = 'john_smith')),
('CS102', 'Data Structures', 'Fundamental data structures and algorithms', 3, 25, 'Computer Science',
    (SELECT id FROM users WHERE username = 'john_smith')),
('MATH101', 'Calculus I', 'Introduction to differential calculus', 3, 35, 'Mathematics',
    (SELECT id FROM users WHERE username = 'mary_jones'));

-- Insert academic term
INSERT INTO academic_terms (term_name, start_date, end_date, registration_start, registration_end, is_active) VALUES
('Fall 2023', '2023-09-01', '2023-12-15', '2023-08-01', '2023-08-15', true);

-- Insert course offerings
INSERT INTO course_offerings (course_id, term_id, schedule_info, room_number) 
SELECT c.id, t.id, 'Mon/Wed 10:00-11:30', 'Room 101'
FROM courses c, academic_terms t
WHERE c.course_code = 'CS101' AND t.term_name = 'Fall 2023';

INSERT INTO course_offerings (course_id, term_id, schedule_info, room_number)
SELECT c.id, t.id, 'Tue/Thu 14:00-15:30', 'Room 102'
FROM courses c, academic_terms t
WHERE c.course_code = 'CS102' AND t.term_name = 'Fall 2023';

-- Insert some sample enrollments
INSERT INTO enrollments (student_id, course_id, status) 
SELECT u.id, c.id, 'approved'
FROM users u, courses c
WHERE u.username = 'student1' AND c.course_code = 'CS101';

INSERT INTO enrollments (student_id, course_id, status)
SELECT u.id, c.id, 'pending'
FROM users u, courses c
WHERE u.username = 'student1' AND c.course_code = 'CS102'; 