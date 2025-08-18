<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('teacher');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

// Xử lý xóa khóa học
if (isset($_GET['delete'])) {
    $course_id = $_GET['delete'];
    $query = "DELETE FROM courses WHERE id = :id AND teacher_id = :teacher_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $course_id);
    $stmt->bindParam(':teacher_id', $user['id']);
    $stmt->execute();
    header('Location: courses.php');
    exit();
}

// Lấy danh sách khóa học
$query = "SELECT c.*, COUNT(e.student_id) as student_count 
          FROM courses c 
          LEFT JOIN enrollments e ON c.id = e.course_id 
          WHERE c.teacher_id = :teacher_id 
          GROUP BY c.id 
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':teacher_id', $user['id']);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Khóa Học - E-Learning Platform</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php $ROOT = '../..'; include __DIR__ . '/../../includes/header.php'; ?>

    <main class="container">
        <div class="dashboard-header">
            <h1>Quản Lý Khóa Học</h1>
            <p>Quản lý tất cả khóa học của bạn</p>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3>Danh sách khóa học (<?php echo count($courses); ?>)</h3>
                <a href="create_course.php" class="btn">➕ Tạo khóa học mới</a>
            </div>

            <?php if (empty($courses)): ?>
                <div class="text-center" style="padding: 3rem; color: #666;">
                    <h3>📚 Chưa có khóa học nào</h3>
                    <p>Tạo khóa học đầu tiên để bắt đầu chia sẻ kiến thức!</p>
                    <a href="create_course.php" class="btn">Tạo khóa học ngay</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tên khóa học</th>
                                <th>Mô tả</th>
                                <th>Học sinh</th>
                                <th>Ngày tạo</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?>
                                    </td>
                                    <td>
                                        <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.9rem;">
                                            👥 <?php echo $course['student_count']; ?> học sinh
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($course['created_at'])); ?></td>
                                    <td>
                                        <span class="<?php echo $course['status'] == 'active' ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $course['status'] == 'active' ? '✅ Hoạt động' : '❌ Tạm dừng'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../course_detail.php?id=<?php echo $course['id']; ?>" class="btn" style="padding: 0.5rem; margin-right: 0.5rem;">👁️ Xem</a>
                                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-secondary" style="padding: 0.5rem; margin-right: 0.5rem;">✏️ Sửa</a>
                                        <a href="../teacher/manage_course_students.php?course_id=<?php echo $course['id']; ?>" class="btn" style="padding: 0.5rem; margin-right: 0.5rem;">👥 Học sinh</a>
                                        <a href="../teacher/lessons.php?course_id=<?php echo $course['id']; ?>" class="btn" style="padding: 0.5rem; margin-right: 0.5rem;">📖 Bài giảng</a>
                                        <a href="?delete=<?php echo $course['id']; ?>" class="btn btn-danger" style="padding: 0.5rem;" onclick="return confirm('Bạn có chắc muốn xóa khóa học này?')">🗑️ Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Thống kê nhanh -->
        <div class="grid grid-3">
            <div class="card">
                <h4>📊 Tổng khóa học</h4>
                <p style="font-size: 2rem; font-weight: bold; color: #667eea; text-align: center;">
                    <?php echo count($courses); ?>
                </p>
            </div>
            <div class="card">
                <h4>👥 Tổng học sinh</h4>
                <p style="font-size: 2rem; font-weight: bold; color: #28a745; text-align: center;">
                    <?php echo array_sum(array_column($courses, 'student_count')); ?>
                </p>
            </div>
            <div class="card">
                <h4>📈 Khóa học phổ biến</h4>
                <?php 
                $popular = null;
                if (!empty($courses)) {
                    $popular = $courses[0];
                    foreach ($courses as $c) {
                        if ((int)$c['student_count'] > (int)$popular['student_count']) {
                            $popular = $c;
                        }
                    }
                }
                ?>
                <p style="font-size: 1.1rem; font-weight: bold; color: #dc3545; text-align: center;">
                    <?php echo $popular ? htmlspecialchars($popular['title']) : 'Chưa có'; ?>
                </p>
            </div>
        </div>
    </main>
    <?php $ROOT = '../..'; include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>