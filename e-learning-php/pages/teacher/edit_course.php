<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('teacher');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($course_id <= 0) {
    header('Location: courses.php');
    exit();
}

// Lấy thông tin khóa học
$get_course = $db->prepare("SELECT * FROM courses WHERE id = :id AND teacher_id = :tid");
$get_course->bindParam(':id', $course_id);
$get_course->bindParam(':tid', $user['id']);
$get_course->execute();
$course = $get_course->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: courses.php');
    exit();
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if (!empty($title) && !empty($description)) {
        $upd = $db->prepare("UPDATE courses SET title = :title, description = :description, status = :status WHERE id = :id AND teacher_id = :tid");
        $upd->bindParam(':title', $title);
        $upd->bindParam(':description', $description);
        $upd->bindParam(':status', $status);
        $upd->bindParam(':id', $course_id);
        $upd->bindParam(':tid', $user['id']);
        if ($upd->execute()) {
            $message = 'Cập nhật khóa học thành công!';
            $success = true;
            // Refresh data
            $get_course->execute();
            $course = $get_course->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = 'Không thể cập nhật khóa học.';
        }
    } else {
        $message = 'Vui lòng điền đầy đủ thông tin.';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh Sửa Khóa Học - E-Learning Platform</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🎓 E-Learning</div>
            <ul class="nav-menu">
                <li><a href="../../dashboard.php">Dashboard</a></li>
                <li><a href="courses.php">Khóa học</a></li>
                <li><a href="assignments.php">Bài tập</a></li>
                <li><a href="../messages.php">Tin nhắn</a></li>
                <li><a href="../../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="dashboard-header">
            <h1>✏️ Chỉnh Sửa Khóa Học</h1>
            <p><?php echo htmlspecialchars($course['title']); ?></p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label>Tên khóa học</label>
                    <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($course['title']); ?>">
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" class="form-control" rows="6" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo $course['status']==='active'?'selected':''; ?>>Hoạt động</option>
                        <option value="inactive" <?php echo $course['status']==='inactive'?'selected':''; ?>>Tạm dừng</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Lưu thay đổi</button>
                    <a href="courses.php" class="btn btn-secondary" style="margin-left:1rem;">Hủy</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>