<?php
require_once 'includes/auth.php';
require_once 'database/config.php';
require_once 'includes/gamification.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();
$gamification = new Gamification();

// Lấy thống kê
$stats = [];
if ($user['role'] == 'teacher') {
    // Thống kê cho giáo viên
    $query = "SELECT COUNT(*) as total_courses FROM courses WHERE teacher_id = :teacher_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':teacher_id', $user['id']);
    $stmt->execute();
    $stats['courses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_courses'];
    
    $query = "SELECT COUNT(DISTINCT e.student_id) as total_students 
              FROM enrollments e 
              JOIN courses c ON e.course_id = c.id 
              WHERE c.teacher_id = :teacher_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':teacher_id', $user['id']);
    $stmt->execute();
    $stats['students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];
} else {
    // Thống kê cho học sinh
    $query = "SELECT COUNT(*) as enrolled_courses FROM enrollments WHERE student_id = :student_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_id', $user['id']);
    $stmt->execute();
    $stats['enrolled'] = $stmt->fetch(PDO::FETCH_ASSOC)['enrolled_courses'];
    
    $query = "SELECT COUNT(*) as total_assignments 
              FROM assignments a 
              JOIN enrollments e ON a.course_id = e.course_id 
              WHERE e.student_id = :student_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_id', $user['id']);
    $stmt->execute();
    $stats['assignments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_assignments'];
}

// Gamification: user stats and leaderboard
$gamiStats = $gamification->getUserStats($user['id']);
$leaderboard = $gamification->getLeaderboard(5);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - E-Learning Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php $ROOT = ''; include __DIR__ . '/includes/header.php'; ?>

    <main class="container dashboard">
        <div class="dashboard-header">
            <h1>Chào mừng, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p>
                <?php if ($user['role'] == 'teacher'): ?>
                    Quản lý khóa học và học sinh của bạn
                <?php else: ?>
                    Khám phá các khóa học và hoàn thành bài tập
                <?php endif; ?>
            </p>
        </div>

        <div class="grid grid-3">
            <?php if ($user['role'] == 'teacher'): ?>
                <div class="card">
                    <h3>📚 Khóa học của tôi</h3>
                    <p class="text-center" style="font-size: 2rem; font-weight: bold; color: #667eea;">
                        <?php echo $stats['courses']; ?>
                    </p>
                    <a href="pages/teacher/courses.php" class="btn" style="width: 100%;">Quản lý khóa học</a>
                </div>
                
                <div class="card">
                    <h3>👥 Học sinh</h3>
                    <p class="text-center" style="font-size: 2rem; font-weight: bold; color: #28a745;">
                        <?php echo $stats['students']; ?>
                    </p>
                    <a href="pages/teacher/students.php" class="btn btn-success" style="width: 100%;">Xem học sinh</a>
                </div>
                
                <div class="card">
                    <h3>➕ Tạo mới</h3>
                    <p>Tạo khóa học hoặc bài tập mới</p>
                    <a href="pages/teacher/create_course.php" class="btn" style="width: 100%; margin-bottom: 0.5rem;">Tạo khóa học</a>
                    <a href="pages/teacher/create_assignment.php" class="btn btn-secondary" style="width: 100%;">Tạo bài tập</a>
                </div>
            <?php elseif ($user['role'] == 'student'): ?>
                <div class="card">
                    <h3>📚 Khóa học đã đăng ký</h3>
                    <p class="text-center" style="font-size: 2rem; font-weight: bold; color: #667eea;">
                        <?php echo $stats['enrolled']; ?>
                    </p>
                    <a href="pages/student/courses.php" class="btn" style="width: 100%;">Xem khóa học</a>
                </div>
                
                <div class="card">
                    <h3>📝 Bài tập</h3>
                    <p class="text-center" style="font-size: 2rem; font-weight: bold; color: #dc3545;">
                        <?php echo $stats['assignments']; ?>
                    </p>
                    <a href="pages/student/assignments.php" class="btn btn-danger" style="width: 100%;">Làm bài tập</a>
                </div>
                
                <div class="card">
                    <h3>🔍 Khám phá</h3>
                    <p>Tìm kiếm khóa học mới</p>
                    <a href="pages/student/browse.php" class="btn btn-success" style="width: 100%;">Tìm khóa học</a>
                </div>
            <?php else: // admin ?>
                <div class="card">
                    <h3>👥 Tổng người dùng</h3>
                    <p class="text-center" style="font-size: 2rem; font-weight: bold; color: #667eea;">
                        <?php echo (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?>
                    </p>
                </div>
                <div class="card">
                    <h3>📚 Tổng khóa học</h3>
                    <p class="text-center" style="font-size: 2rem; font-weight: bold; color: #28a745;">
                        <?php echo (int)$db->query("SELECT COUNT(*) FROM courses")->fetchColumn(); ?>
                    </p>
                </div>
                <div class="card">
                    <h3>📝 Tổng bài tập</h3>
                    <p class="text-center" style="font-size: 2rem; font-weight: bold; color: #dc3545;">
                        <?php echo (int)$db->query("SELECT COUNT(*) FROM assignments")->fetchColumn(); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Gamification -->
        <div class="grid grid-2" style="margin-top:1.5rem;">
            <div class="card">
                <h3>🏆 Gamification của bạn</h3>
                <div class="grid grid-3">
                    <div class="progress-card">
                        <h4>⭐ Level</h4>
                        <div class="big-number"><?php echo $gamiStats['level']; ?></div>
                    </div>
                    <div class="progress-card">
                        <h4>💯 Điểm</h4>
                        <div class="big-number" style="color:#28a745;">
                            <?php echo $gamiStats['points']; ?>
                        </div>
                    </div>
                    <div class="progress-card">
                        <h4>🏅 Thành tích</h4>
                        <div class="big-number" style="color:#dc3545;">
                            <?php echo $gamiStats['achievements_count']; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <h3>📋 Bảng xếp hạng (Top 5)</h3>
                <?php if (empty($leaderboard)): ?>
                    <p class="text-center" style="color:#666;">Chưa có dữ liệu.</p>
                <?php else: ?>
                    <ol>
                        <?php foreach ($leaderboard as $row): ?>
                            <li style="margin: .5rem 0; display:flex; justify-content:space-between;">
                                <span><?php echo htmlspecialchars($row['full_name']); ?></span>
                                <strong><?php echo (int)$row['total_points']; ?> pts</strong>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>

        <!-- Khóa học gần đây -->
        <div class="card mt-3">
            <h3><?php echo $user['role'] == 'teacher' ? 'Khóa học của tôi' : 'Khóa học gần đây'; ?></h3>
            <div class="grid grid-2">
                <?php
                if ($user['role'] == 'teacher') {
                    $query = "SELECT * FROM courses WHERE teacher_id = :teacher_id ORDER BY created_at DESC LIMIT 4";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':teacher_id', $user['id']);
                } else {
                    $query = "SELECT c.*, e.progress FROM courses c 
                             JOIN enrollments e ON c.id = e.course_id 
                             WHERE e.student_id = :student_id 
                             ORDER BY e.enrolled_at DESC LIMIT 4";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':student_id', $user['id']);
                }
                $stmt->execute();
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($courses as $course):
                ?>
                    <div class="course-card">
                        <div class="course-thumbnail">📖</div>
                        <div class="course-content">
                            <h4 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h4>
                            <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                            
                            <?php if ($user['role'] == 'student' && isset($course['progress'])): ?>
                                <div class="mini-progress">
                                    <div class="mini-progress-bar">
                                        <div class="mini-progress-fill" style="width: <?php echo $course['progress']; ?>%"></div>
                                    </div>
                                    <span class="mini-progress-text"><?php echo $course['progress']; ?>% hoàn thành</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="course-meta">
                                <span><?php echo date('d/m/Y', strtotime($course['created_at'])); ?></span>
                                <a href="pages/course_detail.php?id=<?php echo $course['id']; ?>" class="btn" style="padding: 0.5rem 1rem;">Xem chi tiết</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($courses)): ?>
                    <p class="text-center" style="grid-column: 1 / -1; color: #666;">
                        <?php echo $user['role'] == 'teacher' ? 'Chưa có khóa học nào. Tạo khóa học đầu tiên!' : 'Chưa đăng ký khóa học nào. Khám phá ngay!'; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <style>
            .mini-progress {
                margin: 0.5rem 0;
            }
            
            .mini-progress-bar {
                width: 100%;
                height: 8px;
                background: #f0f0f0;
                border-radius: 4px;
                overflow: hidden;
                margin-bottom: 0.25rem;
            }
            
            .mini-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
                border-radius: 4px;
                transition: width 0.5s ease;
            }
            
            .mini-progress-text {
                font-size: 0.8rem;
                color: #667eea;
                font-weight: 600;
            }
        </style>
    </main>
    <?php $ROOT = ''; include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>