<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('admin');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

$stats = [
    'users' => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'teachers' => (int)$db->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn(),
    'students' => (int)$db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
    'courses' => (int)$db->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
    'enrollments' => (int)$db->query("SELECT COUNT(*) FROM enrollments")->fetchColumn(),
    'assignments' => (int)$db->query("SELECT COUNT(*) FROM assignments")->fetchColumn(),
    'messages' => (int)$db->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Learning</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php $ROOT = '../..'; include __DIR__ . '/../../includes/header.php'; ?>
    <main class="container">
        <div class="dashboard-header">
            <h1>🛡️ Admin Dashboard</h1>
            <p>Thống kê toàn hệ thống</p>
        </div>

        <div class="grid grid-3">
            <div class="card"><h3>👥 Người dùng</h3><p class="text-center" style="font-size:2rem; font-weight:bold;"><?php echo $stats['users']; ?></p></div>
            <div class="card"><h3>👨‍🏫 Giáo viên</h3><p class="text-center" style="font-size:2rem; font-weight:bold; color:#667eea; "><?php echo $stats['teachers']; ?></p></div>
            <div class="card"><h3>👨‍🎓 Học sinh</h3><p class="text-center" style="font-size:2rem; font-weight:bold; color:#28a745; "><?php echo $stats['students']; ?></p></div>
            <div class="card"><h3>📚 Khóa học</h3><p class="text-center" style="font-size:2rem; font-weight:bold; color:#dc3545; "><?php echo $stats['courses']; ?></p></div>
            <div class="card"><h3>🧾 Đăng ký</h3><p class="text-center" style="font-size:2rem; font-weight:bold; "><?php echo $stats['enrollments']; ?></p></div>
            <div class="card"><h3>📝 Bài tập</h3><p class="text-center" style="font-size:2rem; font-weight:bold; "><?php echo $stats['assignments']; ?></p></div>
            <div class="card"><h3>💬 Tin nhắn</h3><p class="text-center" style="font-size:2rem; font-weight:bold; "><?php echo $stats['messages']; ?></p></div>
        </div>
    </main>
    <?php $ROOT = '../..'; include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>