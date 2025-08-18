<?php
if (!isset($auth) || !($auth instanceof Auth)) {
    require_once __DIR__ . '/auth.php';
    $auth = new Auth();
}
$user = $auth->getUser();
$ROOT = isset($ROOT) ? rtrim($ROOT, '/') : '';
$AI_URL = isset($AI_URL) ? $AI_URL : $ROOT . '/ai';
$FORUM_URL = isset($FORUM_URL) ? $FORUM_URL : $ROOT . '/forum';
?>
<header class="header">
    <nav class="nav">
        <div class="logo">🎓 E-Learning</div>
        <ul class="nav-menu">
            <li><a href="<?php echo $ROOT; ?>/dashboard.php">Dashboard</a></li>
            <?php if ($user && $user['role'] === 'teacher'): ?>
                <li><a href="<?php echo $ROOT; ?>/pages/teacher/courses.php">Khóa học</a></li>
                <li><a href="<?php echo $ROOT; ?>/pages/teacher/assignments.php">Bài tập</a></li>
                <li><a href="<?php echo $ROOT; ?>/pages/teacher/students.php">Học sinh</a></li>
                <li><a href="<?php echo $ROOT; ?>/pages/teacher/lessons.php">Bài giảng</a></li>
            <?php elseif ($user && $user['role'] === 'student'): ?>
                <li><a href="<?php echo $ROOT; ?>/pages/student/courses.php">Khóa học</a></li>
                <li><a href="<?php echo $ROOT; ?>/pages/student/assignments.php">Bài tập</a></li>
            <?php endif; ?>
            <?php if ($user && $user['role'] === 'admin'): ?>
                <li><a href="<?php echo $ROOT; ?>/pages/admin/dashboard.php">Admin</a></li>
            <?php endif; ?>
            <li><a href="<?php echo $ROOT; ?>/pages/messages.php">Tin nhắn</a></li>
            <li><a href="<?php echo htmlspecialchars($AI_URL); ?>">Chat với AI</a></li>
            <li><a href="<?php echo htmlspecialchars($FORUM_URL); ?>">Forum</a></li>
            <li><a href="<?php echo $ROOT; ?>/logout.php">Đăng xuất</a></li>
        </ul>
    </nav>
</header>