<?php
require_once '../includes/auth.php';
require_once '../database/config.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

$message_sent = '';

// Xử lý gửi tin nhắn
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message_content = $_POST['message'];
    $course_id = !empty($_POST['course_id']) ? $_POST['course_id'] : null;
    
    if (!empty($receiver_id) && !empty($message_content)) {
        try {
            $query = "INSERT INTO messages (sender_id, receiver_id, course_id, message) 
                     VALUES (:sender_id, :receiver_id, :course_id, :message)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':sender_id', $user['id']);
            $stmt->bindParam(':receiver_id', $receiver_id);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->bindParam(':message', $message_content);
            
            if ($stmt->execute()) {
                $message_sent = 'Tin nhắn đã được gửi thành công!';
            }
        } catch(PDOException $e) {
            $message_sent = 'Có lỗi xảy ra khi gửi tin nhắn!';
        }
    }
}

// Lấy danh sách người dùng để gửi tin nhắn
$contacts_query = "SELECT DISTINCT u.id, u.full_name, u.role 
                   FROM users u";

if ($user['role'] == 'teacher') {
    // Giáo viên có thể nhắn tin với học sinh trong khóa học của mình
    $contacts_query .= " JOIN enrollments e ON u.id = e.student_id 
                        JOIN courses c ON e.course_id = c.id 
                        WHERE c.teacher_id = :user_id AND u.role = 'student'";
} else {
    // Học sinh có thể nhắn tin với giáo viên của các khóa học đã đăng ký
    $contacts_query .= " JOIN courses c ON u.id = c.teacher_id 
                        JOIN enrollments e ON c.id = e.course_id 
                        WHERE e.student_id = :user_id AND u.role = 'teacher'";
}

$contacts_query .= " ORDER BY u.full_name";

$contacts_stmt = $db->prepare($contacts_query);
$contacts_stmt->bindParam(':user_id', $user['id']);
$contacts_stmt->execute();
$contacts = $contacts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy tin nhắn gần đây
$messages_query = "SELECT m.*, 
                   sender.full_name as sender_name, sender.role as sender_role,
                   receiver.full_name as receiver_name, receiver.role as receiver_role,
                   c.title as course_title
                   FROM messages m
                   JOIN users sender ON m.sender_id = sender.id
                   JOIN users receiver ON m.receiver_id = receiver.id
                   LEFT JOIN courses c ON m.course_id = c.id
                   WHERE m.sender_id = :user_id OR m.receiver_id = :user_id
                   ORDER BY m.sent_at DESC
                   LIMIT 20";

$messages_stmt = $db->prepare($messages_query);
$messages_stmt->bindParam(':user_id', $user['id']);
$messages_stmt->execute();
$messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

// Đánh dấu tin nhắn đã đọc
$update_read = "UPDATE messages SET is_read = TRUE 
                WHERE receiver_id = :user_id AND is_read = FALSE";
$update_stmt = $db->prepare($update_read);
$update_stmt->bindParam(':user_id', $user['id']);
$update_stmt->execute();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin Nhắn - E-Learning Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🎓 E-Learning</div>
            <ul class="nav-menu">
                <li><a href="../dashboard.php">Dashboard</a></li>
                <?php if ($user['role'] == 'teacher'): ?>
                    <li><a href="teacher/courses.php">Khóa học</a></li>
                    <li><a href="teacher/assignments.php">Bài tập</a></li>
                <?php else: ?>
                    <li><a href="student/courses.php">Khóa học</a></li>
                    <li><a href="student/assignments.php">Bài tập</a></li>
                <?php endif; ?>
                <li><a href="messages.php" style="opacity: 0.8;">Tin nhắn</a></li>
                <li><a href="../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="dashboard-header">
            <h1>💬 Tin Nhắn</h1>
            <p>Trao đổi với <?php echo $user['role'] == 'teacher' ? 'học sinh' : 'giáo viên'; ?></p>
        </div>

        <?php if ($message_sent): ?>
            <div class="message success"><?php echo $message_sent; ?></div>
        <?php endif; ?>

        <div class="grid grid-2">
            <!-- Gửi tin nhắn mới -->
            <div class="card">
                <h3>✉️ Gửi tin nhắn mới</h3>
                
                <?php if (empty($contacts)): ?>
                    <div class="text-center" style="color: #666;">
                        <p>
                            <?php if ($user['role'] == 'teacher'): ?>
                                Chưa có học sinh nào trong khóa học của bạn.
                            <?php else: ?>
                                Bạn chưa đăng ký khóa học nào.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Gửi tới:</label>
                            <select name="receiver_id" class="form-control" required>
                                <option value="">-- Chọn người nhận --</option>
                                <?php foreach ($contacts as $contact): ?>
                                    <option value="<?php echo $contact['id']; ?>">
                                        <?php echo htmlspecialchars($contact['full_name']); ?> 
                                        (<?php echo $contact['role'] == 'teacher' ? 'Giáo viên' : 'Học sinh'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tin nhắn:</label>
                            <textarea name="message" class="form-control" rows="4" required 
                                      placeholder="Nhập tin nhắn của bạn..."></textarea>
                        </div>

                        <button type="submit" name="send_message" class="btn">📤 Gửi tin nhắn</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Tin nhắn gần đây -->
            <div class="card">
                <h3>📨 Tin nhắn gần đây</h3>
                
                <?php if (empty($messages)): ?>
                    <div class="text-center" style="color: #666; padding: 2rem;">
                        <p>Chưa có tin nhắn nào.</p>
                        <p>Hãy bắt đầu cuộc trò chuyện!</p>
                    </div>
                <?php else: ?>
                    <div class="chat-container" style="max-height: 500px;">
                        <?php foreach ($messages as $msg): ?>
                            <div class="chat-message <?php echo $msg['sender_id'] == $user['id'] ? 'sent' : 'received'; ?>">
                                <div class="chat-sender">
                                    <?php if ($msg['sender_id'] == $user['id']): ?>
                                        Bạn → <?php echo htmlspecialchars($msg['receiver_name']); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($msg['sender_name']); ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($msg['course_title']): ?>
                                        <span style="font-size: 0.8rem; color: #666;">
                                            (<?php echo htmlspecialchars($msg['course_title']); ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                
                                <div class="chat-time">
                                    <?php echo date('d/m/Y H:i', strtotime($msg['sent_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Hướng dẫn -->
        <div class="card">
            <h3>💡 Hướng dẫn sử dụng tin nhắn</h3>
            <div class="grid grid-3">
                <div>
                    <h4>📝 Viết tin nhắn</h4>
                    <ul>
                        <li>Sử dụng ngôn ngữ lịch sự</li>
                        <li>Nội dung rõ ràng, cụ thể</li>
                        <li>Tránh viết tắt khó hiểu</li>
                    </ul>
                </div>
                <div>
                    <h4>⏰ Thời gian phản hồi</h4>
                    <ul>
                        <li>Trả lời trong 24-48 giờ</li>
                        <li>Thông báo nếu bận không thể trả lời</li>
                        <li>Ưu tiên câu hỏi khẩn cấp</li>
                    </ul>
                </div>
                <div>
                    <h4>🎯 Mục đích sử dụng</h4>
                    <ul>
                        <li>Hỏi đáp về bài học</li>
                        <li>Thông báo quan trọng</li>
                        <li>Hỗ trợ kỹ thuật</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Tự động cuộn xuống tin nhắn mới nhất
        const chatContainer = document.querySelector('.chat-container');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
        
        // Refresh trang mỗi 30 giây để cập nhật tin nhắn mới
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>