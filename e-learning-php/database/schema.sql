-- Tạo database
CREATE DATABASE IF NOT EXISTS elearning_db;
USE elearning_db;

-- Bảng users (chung cho giáo viên và học sinh)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('teacher', 'student', 'admin') NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng courses (khóa học)
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    teacher_id INT NOT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bảng lessons (bài học)
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    video_url VARCHAR(500) DEFAULT NULL,
    order_number INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Bảng enrollments (đăng ký khóa học)
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress INT DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id)
);

-- Bảng assignments (bài tập)
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATETIME,
    max_score INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Bảng submissions (bài nộp)
CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    content TEXT,
    file_path VARCHAR(500) DEFAULT NULL,
    score INT DEFAULT NULL,
    feedback TEXT DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    graded_at TIMESTAMP NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bảng messages (tin nhắn)
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    course_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

-- Bảng forum_topics (chủ đề thảo luận)
CREATE TABLE forum_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_locked BOOLEAN DEFAULT FALSE,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bảng forum_replies (câu trả lời trong forum)
CREATE TABLE forum_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_solution BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bảng chatbot_conversations (lưu trữ cuộc trò chuyện với chatbot)
CREATE TABLE chatbot_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(100) NOT NULL,
    user_message TEXT NOT NULL,
    bot_response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_user_session (user_id, session_id)
);

-- Bảng chatbot_knowledge (cơ sở kiến thức cho chatbot)
CREATE TABLE chatbot_knowledge (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100) DEFAULT 'general',
    keywords TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Thêm dữ liệu mẫu
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@elearning.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('teacher1', 'teacher1@elearning.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn A', 'teacher'),
('student1', 'student1@elearning.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị B', 'student');

-- Dữ liệu mẫu cho chatbot knowledge base
INSERT INTO chatbot_knowledge (question, answer, category, keywords) VALUES
('Làm thế nào để đăng ký khóa học?', 'Bạn có thể đăng ký khóa học bằng cách: 1) Đăng nhập vào tài khoản, 2) Vào mục "Tìm khóa học", 3) Chọn khóa học phù hợp, 4) Click nút "Đăng ký". Sau đó bạn sẽ có thể truy cập vào nội dung khóa học.', 'course', 'đăng ký, khóa học, course, register'),
('Làm sao để liên hệ với giáo viên?', 'Bạn có thể liên hệ với giáo viên thông qua: 1) Hệ thống tin nhắn trong platform, 2) Forum thảo luận của khóa học, 3) Email được cung cấp trong thông tin khóa học.', 'communication', 'liên hệ, giáo viên, teacher, contact'),
('Tôi quên mật khẩu thì làm sao?', 'Hiện tại bạn cần liên hệ với admin để reset mật khẩu. Chúng tôi đang phát triển tính năng reset mật khẩu tự động.', 'account', 'mật khẩu, password, quên, forgot'),
('Làm thế nào để theo dõi tiến độ học tập?', 'Bạn có thể theo dõi tiến độ học tập tại mục "Khóa học của tôi". Ở đây sẽ hiển thị thanh tiến độ, phần trăm hoàn thành, và số bài tập đã làm cho mỗi khóa học.', 'progress', 'tiến độ, progress, theo dõi, học tập'),
('Platform này có những tính năng gì?', 'E-Learning Platform có các tính năng: 1) Quản lý khóa học, 2) Hệ thống bài tập, 3) Tin nhắn real-time, 4) Forum thảo luận, 5) Chatbot hỗ trợ, 6) Theo dõi tiến độ học tập, 7) Dashboard tùy theo vai trò.', 'general', 'tính năng, features, platform, hệ thống'),
('Làm thế nào để tạo khóa học mới?', 'Chỉ giáo viên mới có thể tạo khóa học. Sau khi đăng nhập với vai trò giáo viên: 1) Vào Dashboard, 2) Click "Tạo khóa học mới", 3) Điền thông tin khóa học, 4) Click "Tạo khóa học".', 'teaching', 'tạo khóa học, create course, giáo viên'),
('Có thể upload file bài tập không?', 'Hiện tại hệ thống đang trong giai đoạn phát triển tính năng upload file. Bạn có thể gửi bài tập qua tin nhắn hoặc nhập trực tiếp vào form bài tập.', 'assignment', 'upload, file, bài tập, assignment'),
('Forum thảo luận hoạt động như thế nào?', 'Forum cho phép học sinh và giáo viên thảo luận về khóa học: 1) Tạo chủ đề mới, 2) Trả lời các chủ đề, 3) Đánh dấu câu trả lời là giải pháp, 4) Xem và tương tác với cộng đồng học tập.', 'forum', 'forum, thảo luận, discussion, community');

-- Dữ liệu mẫu cho courses
INSERT INTO courses (title, description, teacher_id) VALUES
('Lập trình PHP cơ bản', 'Khóa học dạy lập trình PHP từ cơ bản đến nâng cao, bao gồm cú pháp, OOP, và kết nối database.', 2),
('Thiết kế Web với HTML/CSS', 'Học cách thiết kế website responsive với HTML5 và CSS3, bao gồm Flexbox, Grid và các kỹ thuật hiện đại.', 2);