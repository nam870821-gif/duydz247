<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('teacher');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($course_id <= 0) {
    header('Location: courses.php');
    exit();
}

// Kiểm tra course thuộc giáo viên
$course_stmt = $db->prepare("SELECT id, title FROM courses WHERE id = :cid AND teacher_id = :tid");
$course_stmt->bindParam(':cid', $course_id);
$course_stmt->bindParam(':tid', $user['id']);
$course_stmt->execute();
$course = $course_stmt->fetch(PDO::FETCH_ASSOC);
if (!$course) {
    header('Location: courses.php');
    exit();
}

// Hủy enroll
if (isset($_GET['remove']) && ($sid = (int)$_GET['remove'])) {
    $del = $db->prepare("DELETE e FROM enrollments e WHERE e.course_id = :cid AND e.student_id = :sid");
    $del->bindParam(':cid', $course_id);
    $del->bindParam(':sid', $sid);
    $del->execute();
    header('Location: manage_course_students.php?course_id=' . $course_id);
    exit();
}

// Lấy danh sách học sinh
$students_stmt = $db->prepare("SELECT u.id, u.full_name, u.email, e.enrolled_at, e.progress
                               FROM enrollments e JOIN users u ON e.student_id = u.id
                               WHERE e.course_id = :cid ORDER BY u.full_name");
$students_stmt->bindParam(':cid', $course_id);
$students_stmt->execute();
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Học sinh - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php $ROOT = '../..'; include __DIR__ . '/../../includes/header.php'; ?>
    <main class="container">
        <div class="dashboard-header">
            <h1>👥 Học sinh: <?php echo htmlspecialchars($course['title']); ?></h1>
            <p>Quản lý học sinh đăng ký khóa học này</p>
        </div>

        <?php if (empty($students)): ?>
            <div class="card text-center" style="padding:2rem; color:#666;">Chưa có học sinh.</div>
        <?php else: ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Họ và tên</th>
                                <th>Email</th>
                                <th>Đăng ký</th>
                                <th>Tiến độ</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($s['enrolled_at'])); ?></td>
                                <td><?php echo (int)$s['progress']; ?>%</td>
                                <td>
                                    <a class="btn btn-danger" style="padding:.5rem;" href="?course_id=<?php echo $course_id; ?>&remove=<?php echo $s['id']; ?>" onclick="return confirm('Hủy đăng ký học sinh này?')">🗑️ Hủy</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <?php $ROOT = '../..'; include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>