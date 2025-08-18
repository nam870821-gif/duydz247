<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('teacher');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

$query = "SELECT u.id, u.full_name, u.email,
                 COUNT(DISTINCT e.course_id) AS courses_count,
                 ROUND(AVG(e.progress), 0) AS avg_progress
          FROM users u
          JOIN enrollments e ON u.id = e.student_id
          JOIN courses c ON e.course_id = c.id
          WHERE c.teacher_id = :tid
          GROUP BY u.id, u.full_name, u.email
          ORDER BY u.full_name";

$stmt = $db->prepare($query);
$stmt->bindParam(':tid', $user['id']);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Học Sinh - E-Learning Platform</title>
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
                <li><a href="students.php" style="opacity:.8;">Học sinh</a></li>
                <li><a href="../messages.php">Tin nhắn</a></li>
                <li><a href="../../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="dashboard-header">
            <h1>👥 Học Sinh</h1>
            <p>Danh sách học sinh đăng ký các khóa học của bạn</p>
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
                                <th>Số khóa học</th>
                                <th>Tiến độ trung bình</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($s['email']); ?></td>
                                    <td><?php echo (int)$s['courses_count']; ?></td>
                                    <td><?php echo (int)$s['avg_progress']; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>