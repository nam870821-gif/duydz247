<?php
require_once '../includes/auth.php';
require_once '../database/config.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($course_id <= 0) {
    header('Location: ../dashboard.php');
    exit();
}

// Lấy thông tin khóa học và giáo viên
$course_query = "SELECT c.*, u.full_name AS teacher_name, u.id AS teacher_user_id
                 FROM courses c
                 JOIN users u ON c.teacher_id = u.id
                 WHERE c.id = :course_id";
$course_stmt = $db->prepare($course_query);
$course_stmt->bindParam(':course_id', $course_id);
$course_stmt->execute();
$course = $course_stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: ../dashboard.php');
    exit();
}

$is_teacher_owner = ($user['role'] === 'teacher' && $user['id'] == $course['teacher_user_id']);
$is_student_enrolled = false;

if ($user['role'] === 'student') {
    $enroll_check = "SELECT 1 FROM enrollments WHERE student_id = :sid AND course_id = :cid";
    $enroll_stmt = $db->prepare($enroll_check);
    $enroll_stmt->bindParam(':sid', $user['id']);
    $enroll_stmt->bindParam(':cid', $course_id);
    $enroll_stmt->execute();
    $is_student_enrolled = (bool)$enroll_stmt->fetchColumn();
}

// Giáo viên tạo bài học mới
$lesson_message = '';
if ($is_teacher_owner && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_lesson'])) {
    $lesson_title = trim($_POST['lesson_title'] ?? '');
    $lesson_content = trim($_POST['lesson_content'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');

    if (!empty($lesson_title)) {
        $insert_lesson = "INSERT INTO lessons (course_id, title, content, video_url, order_number) 
                          VALUES (:course_id, :title, :content, :video_url, 
                                  (SELECT COALESCE(MAX(order_number),0)+1 FROM lessons WHERE course_id = :course_id2))";
        $stmt = $db->prepare($insert_lesson);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->bindParam(':course_id2', $course_id);
        $stmt->bindParam(':title', $lesson_title);
        $stmt->bindParam(':content', $lesson_content);
        $stmt->bindParam(':video_url', $video_url);
        if ($stmt->execute()) {
            $lesson_message = 'Đã thêm bài học mới!';
        } else {
            $lesson_message = 'Không thể thêm bài học.';
        }
    } else {
        $lesson_message = 'Vui lòng nhập tiêu đề bài học.';
    }
}

// Lấy danh sách bài học
$lessons_query = "SELECT * FROM lessons WHERE course_id = :course_id ORDER BY order_number ASC, created_at ASC";
$lessons_stmt = $db->prepare($lessons_query);
$lessons_stmt->bindParam(':course_id', $course_id);
$lessons_stmt->execute();
$lessons = $lessons_stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách bài tập trong khóa học
$assign_query = "SELECT a.*, 
                        (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS submissions_count
                 FROM assignments a WHERE a.course_id = :course_id ORDER BY a.created_at DESC";
$assign_stmt = $db->prepare($assign_query);
$assign_stmt->bindParam(':course_id', $course_id);
$assign_stmt->execute();
$assignments = $assign_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Chi tiết khóa học</title>
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
                <li><a href="../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="dashboard-header">
            <h1><?php echo htmlspecialchars($course['title']); ?></h1>
            <p>👨‍🏫 Giáo viên: <?php echo htmlspecialchars($course['teacher_name']); ?></p>
        </div>

        <?php if ($user['role'] === 'student' && !$is_student_enrolled): ?>
            <div class="message error">Bạn chưa đăng ký khóa học này.</div>
        <?php endif; ?>

        <div class="grid grid-2">
            <div class="card">
                <h3>📖 Mô tả khóa học</h3>
                <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
            </div>

            <div class="card">
                <h3>📝 Bài tập</h3>
                <?php if (empty($assignments)): ?>
                    <p class="text-center" style="color:#666;">Chưa có bài tập nào.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($assignments as $a): ?>
                            <li style="margin-bottom: .5rem;">
                                <strong><?php echo htmlspecialchars($a['title']); ?></strong>
                                <?php if (!empty($a['due_date'])): ?>
                                    <span style="color:#666;">(Hạn: <?php echo date('d/m/Y H:i', strtotime($a['due_date'])); ?>)</span>
                                <?php endif; ?>
                                <?php if ($user['role'] === 'teacher'): ?>
                                    <span style="margin-left: .5rem; color:#28a745;">
                                        Nộp bài: <?php echo (int)$a['submissions_count']; ?>
                                    </span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div style="margin-top: .75rem;">
                        <?php if ($user['role'] === 'student'): ?>
                            <a href="student/assignments.php?course_id=<?php echo $course_id; ?>" class="btn">📝 Làm bài tập</a>
                        <?php else: ?>
                            <a href="teacher/assignments.php?course_id=<?php echo $course_id; ?>" class="btn">📋 Quản lý bài tập</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h3>🧩 Danh sách bài học</h3>
            <?php if (empty($lessons)): ?>
                <p class="text-center" style="color:#666;">Chưa có bài học nào.</p>
            <?php else: ?>
                <ol>
                    <?php foreach ($lessons as $l): ?>
                        <li style="margin-bottom: .75rem;">
                            <strong><?php echo htmlspecialchars($l['title']); ?></strong>
                            <?php if (!empty($l['video_url'])): ?>
                                <div><a href="<?php echo htmlspecialchars($l['video_url']); ?>" target="_blank">🎬 Xem video</a></div>
                            <?php endif; ?>
                            <?php if (!empty($l['content'])): ?>
                                <div style="color:#555; margin-top: .25rem;"><?php echo nl2br(htmlspecialchars(substr($l['content'],0,200))) . (strlen($l['content'])>200?'...':''); ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>

        <?php if ($is_teacher_owner): ?>
            <div class="card">
                <h3>➕ Thêm bài học mới</h3>
                <?php if ($lesson_message): ?>
                    <div class="message success"><?php echo $lesson_message; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Tiêu đề bài học</label>
                        <input type="text" name="lesson_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nội dung</label>
                        <textarea name="lesson_content" class="form-control" rows="5" placeholder="Nội dung bài học..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Video URL (tùy chọn)</label>
                        <input type="url" name="video_url" class="form-control" placeholder="https://...">
                    </div>
                    <button type="submit" name="create_lesson" class="btn">Thêm bài học</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>