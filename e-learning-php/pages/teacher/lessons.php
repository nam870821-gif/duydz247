<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('teacher');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

// Lấy danh sách khóa học của giáo viên (để filter và chọn khi tạo bài học)
$courses_stmt = $db->prepare("SELECT id, title FROM courses WHERE teacher_id = :tid ORDER BY title");
$courses_stmt->bindParam(':tid', $user['id']);
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

$filter_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$message = '';

// Xóa lesson
if (isset($_GET['delete'])) {
    $lesson_id = (int)$_GET['delete'];
    $del = $db->prepare("DELETE l FROM lessons l JOIN courses c ON l.course_id = c.id WHERE l.id = :lid AND c.teacher_id = :tid");
    $del->bindParam(':lid', $lesson_id);
    $del->bindParam(':tid', $user['id']);
    $del->execute();
    header('Location: lessons.php' . ($filter_course_id ? ('?course_id=' . $filter_course_id) : ''));
    exit();
}

// Tạo bài học
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_lesson'])) {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');

    if ($course_id > 0 && $title !== '') {
        $ins = $db->prepare("INSERT INTO lessons (course_id, title, content, video_url, order_number)
                             VALUES (:cid, :title, :content, :video_url,
                                     (SELECT COALESCE(MAX(order_number),0)+1 FROM lessons WHERE course_id = :cid2))");
        $ins->bindParam(':cid', $course_id);
        $ins->bindParam(':cid2', $course_id);
        $ins->bindParam(':title', $title);
        $ins->bindParam(':content', $content);
        $ins->bindParam(':video_url', $video_url);
        if ($ins->execute()) {
            $message = 'Đã tạo bài học mới.';
            $filter_course_id = $course_id;
        } else {
            $message = 'Không thể tạo bài học.';
        }
    } else {
        $message = 'Vui lòng chọn khóa học và nhập tiêu đề.';
    }
}

// Lấy danh sách bài học theo filter
$lessons_q = "SELECT l.*, c.title AS course_title
              FROM lessons l JOIN courses c ON l.course_id = c.id
              WHERE c.teacher_id = :tid";
if ($filter_course_id > 0) {
    $lessons_q .= " AND c.id = :cid";
}
$lessons_q .= " ORDER BY c.title, l.order_number, l.created_at";

$lessons_stmt = $db->prepare($lessons_q);
$lessons_stmt->bindParam(':tid', $user['id']);
if ($filter_course_id > 0) {
    $lessons_stmt->bindParam(':cid', $filter_course_id);
}
$lessons_stmt->execute();
$lessons = $lessons_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Bài Giảng - E-Learning Platform</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php $ROOT = '../..'; include __DIR__ . '/../../includes/header.php'; ?>
    <main class="container">
        <div class="dashboard-header">
            <h1>📖 Quản Lý Bài Giảng</h1>
            <p>Tạo và quản lý bài học cho các khóa học</p>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="GET" style="display:flex; gap:1rem; align-items:center;">
                <label>Lọc theo khóa học:</label>
                <select name="course_id" class="form-control" style="max-width:300px;">
                    <option value="0">Tất cả</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $filter_course_id==$c['id']?'selected':''; ?>>
                            <?php echo htmlspecialchars($c['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Lọc</button>
            </form>
        </div>

        <div class="card">
            <h3>➕ Thêm bài học</h3>
            <form method="POST">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Khóa học</label>
                        <select name="course_id" class="form-control" required>
                            <option value="">-- Chọn khóa học --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tiêu đề bài học</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Nội dung</label>
                    <textarea name="content" class="form-control" rows="5" placeholder="Nội dung bài học..."></textarea>
                </div>
                <div class="form-group">
                    <label>Video URL (tùy chọn)</label>
                    <input type="url" name="video_url" class="form-control" placeholder="https://...">
                </div>
                <button type="submit" name="create_lesson" class="btn">Tạo bài học</button>
            </form>
        </div>

        <div class="card">
            <h3>📚 Danh sách bài học</h3>
            <?php if (empty($lessons)): ?>
                <div class="text-center" style="color:#666; padding:1rem;">Chưa có bài học.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Khóa học</th>
                                <th>Tiêu đề</th>
                                <th>Video</th>
                                <th>Thứ tự</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lessons as $l): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($l['course_title']); ?></td>
                                    <td><?php echo htmlspecialchars($l['title']); ?></td>
                                    <td><?php echo $l['video_url'] ? '<a href="'.htmlspecialchars($l['video_url']).'" target="_blank">Xem</a>' : '-'; ?></td>
                                    <td><?php echo (int)$l['order_number']; ?></td>
                                    <td>
                                        <a href="?delete=<?php echo $l['id']; ?><?php echo $filter_course_id?('&course_id='.$filter_course_id):''; ?>" class="btn btn-danger" style="padding:.5rem;" onclick="return confirm('Xóa bài học này?')">🗑️ Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php $ROOT = '../..'; include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>