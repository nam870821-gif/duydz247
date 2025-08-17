<?php
require_once '../includes/auth.php';
require_once '../database/config.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

// Lấy course_id từ URL
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Kiểm tra quyền truy cập khóa học
$access_check = false;
if ($user['role'] == 'teacher') {
    $query = "SELECT * FROM courses WHERE id = :course_id AND teacher_id = :user_id";
} else {
    $query = "SELECT c.* FROM courses c 
              JOIN enrollments e ON c.id = e.course_id 
              WHERE c.id = :course_id AND e.student_id = :user_id";
}

$stmt = $db->prepare($query);
$stmt->bindParam(':course_id', $course_id);
$stmt->bindParam(':user_id', $user['id']);
$stmt->execute();
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: forum.php');
    exit();
}

$message = '';
$success = false;

// Xử lý tạo topic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $is_pinned = isset($_POST['is_pinned']) && $user['role'] == 'teacher' ? 1 : 0;
    
    if (!empty($title) && !empty($content)) {
        try {
            $query = "INSERT INTO forum_topics (course_id, user_id, title, content, is_pinned) 
                     VALUES (:course_id, :user_id, :title, :content, :is_pinned)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->bindParam(':user_id', $user['id']);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':is_pinned', $is_pinned);
            
            if ($stmt->execute()) {
                $topic_id = $db->lastInsertId();
                $message = 'Chủ đề đã được tạo thành công!';
                $success = true;
            } else {
                $message = 'Có lỗi xảy ra khi tạo chủ đề!';
            }
        } catch(PDOException $e) {
            $message = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    } else {
        $message = 'Vui lòng điền đầy đủ thông tin!';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Chủ Đề - Forum - E-Learning Platform</title>
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
                <li><a href="messages.php">Tin nhắn</a></li>
                <li><a href="forum.php">🗣️ Forum</a></li>
                <li><a href="chatbot.php">🤖 AI Bot</a></li>
                <li><a href="../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="dashboard-header">
            <h1>➕ Tạo Chủ Đề Mới</h1>
            <p>Tạo chủ đề thảo luận cho khóa học: <strong><?php echo htmlspecialchars($course['title']); ?></strong></p>
        </div>

        <nav class="breadcrumb">
            <a href="forum.php">Forum</a> → 
            <a href="forum_course.php?course_id=<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></a> → 
            <span>Tạo chủ đề</span>
        </nav>

        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
                <?php if ($success): ?>
                    <a href="forum_course.php?course_id=<?php echo $course_id; ?>" style="margin-left: 1rem;">Xem forum</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" id="topicForm">
                <div class="form-group">
                    <label for="title">📝 Tiêu đề chủ đề: <span style="color: red;">*</span></label>
                    <input type="text" id="title" name="title" class="form-control" required 
                           placeholder="Nhập tiêu đề rõ ràng và mô tả chính xác nội dung..." maxlength="200">
                    <small class="form-help">Tiêu đề nên ngắn gọn, rõ ràng và mô tả chính xác nội dung bạn muốn thảo luận</small>
                </div>

                <div class="form-group">
                    <label for="content">💬 Nội dung: <span style="color: red;">*</span></label>
                    <textarea id="content" name="content" class="form-control" rows="10" required 
                              placeholder="Mô tả chi tiết câu hỏi, vấn đề hoặc chủ đề bạn muốn thảo luận..."></textarea>
                    <small class="form-help">Cung cấp thông tin chi tiết giúp mọi người hiểu rõ và trả lời tốt hơn</small>
                </div>

                <?php if ($user['role'] == 'teacher'): ?>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_pinned" value="1">
                            📌 Ghim chủ đề (hiển thị ở đầu danh sách)
                        </label>
                        <small class="form-help">Chỉ giáo viên mới có thể ghim chủ đề quan trọng</small>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn">✨ Tạo Chủ Đề</button>
                    <a href="forum_course.php?course_id=<?php echo $course_id; ?>" class="btn btn-secondary">❌ Hủy</a>
                </div>
            </form>
        </div>

        <!-- Hướng dẫn viết bài -->
        <div class="card">
            <h3>💡 Hướng dẫn viết chủ đề hiệu quả</h3>
            <div class="grid grid-2">
                <div>
                    <h4>📝 Tiêu đề tốt</h4>
                    <ul>
                        <li>✅ "Làm thế nào để kết nối MySQL với PHP?"</li>
                        <li>✅ "Lỗi 404 khi chạy trang web trên localhost"</li>
                        <li>✅ "Thảo luận về bài tập tuần 3 - CSS Grid"</li>
                        <li>❌ "Cần giúp đỡ" (quá mơ hồ)</li>
                        <li>❌ "URGENT!!!" (không mô tả vấn đề)</li>
                    </ul>
                </div>
                <div>
                    <h4>💬 Nội dung chi tiết</h4>
                    <ul>
                        <li>Mô tả rõ vấn đề gặp phải</li>
                        <li>Cung cấp code/screenshot nếu cần</li>
                        <li>Nêu những gì đã thử</li>
                        <li>Đặt câu hỏi cụ thể</li>
                        <li>Sử dụng định dạng rõ ràng</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="card" id="previewCard" style="display: none;">
            <h3>👁️ Xem trước</h3>
            <div class="topic-preview">
                <div class="preview-header">
                    <h4 id="previewTitle">Tiêu đề sẽ hiển thị ở đây</h4>
                    <div class="preview-meta">
                        <span>👤 <?php echo htmlspecialchars($user['full_name']); ?></span>
                        <span>📚 <?php echo htmlspecialchars($course['title']); ?></span>
                        <span>🕒 Vừa xong</span>
                    </div>
                </div>
                <div class="preview-content" id="previewContent">
                    Nội dung sẽ hiển thị ở đây
                </div>
            </div>
        </div>
    </main>

    <style>
        .breadcrumb {
            background: #f8f9fa;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .form-help {
            display: block;
            margin-top: 0.25rem;
            color: #666;
            font-size: 0.9rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-start;
        }

        .topic-preview {
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            padding: 1.5rem;
            background: white;
        }

        .preview-header {
            margin-bottom: 1rem;
        }

        .preview-header h4 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .preview-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .preview-content {
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .char-counter {
            text-align: right;
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .char-counter.warning {
            color: #ffc107;
        }

        .char-counter.danger {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }

            .preview-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>

    <script>
        const titleInput = document.getElementById('title');
        const contentInput = document.getElementById('content');
        const previewCard = document.getElementById('previewCard');
        const previewTitle = document.getElementById('previewTitle');
        const previewContent = document.getElementById('previewContent');

        // Live preview
        function updatePreview() {
            const title = titleInput.value.trim();
            const content = contentInput.value.trim();
            
            if (title || content) {
                previewCard.style.display = 'block';
                previewTitle.textContent = title || 'Tiêu đề sẽ hiển thị ở đây';
                previewContent.textContent = content || 'Nội dung sẽ hiển thị ở đây';
            } else {
                previewCard.style.display = 'none';
            }
        }

        titleInput.addEventListener('input', updatePreview);
        contentInput.addEventListener('input', updatePreview);

        // Character counter for title
        titleInput.addEventListener('input', function() {
            const remaining = 200 - this.value.length;
            let counter = this.parentNode.querySelector('.char-counter');
            
            if (!counter) {
                counter = document.createElement('div');
                counter.className = 'char-counter';
                this.parentNode.appendChild(counter);
            }
            
            counter.textContent = `${remaining} ký tự còn lại`;
            counter.className = 'char-counter';
            
            if (remaining < 20) {
                counter.classList.add(remaining < 5 ? 'danger' : 'warning');
            }
        });

        // Form validation
        document.getElementById('topicForm').addEventListener('submit', function(e) {
            const title = titleInput.value.trim();
            const content = contentInput.value.trim();
            
            if (!title || !content) {
                e.preventDefault();
                alert('Vui lòng điền đầy đủ tiêu đề và nội dung!');
                return;
            }
            
            if (title.length < 10) {
                e.preventDefault();
                alert('Tiêu đề quá ngắn! Vui lòng nhập ít nhất 10 ký tự.');
                titleInput.focus();
                return;
            }
            
            if (content.length < 20) {
                e.preventDefault();
                alert('Nội dung quá ngắn! Vui lòng mô tả chi tiết hơn (ít nhất 20 ký tự).');
                contentInput.focus();
                return;
            }
        });

        // Auto-resize textarea
        contentInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html>