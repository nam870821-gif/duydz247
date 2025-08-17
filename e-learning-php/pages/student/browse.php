<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';
require_once '../../includes/gamification.php';

$auth = new Auth();
$auth->requireRole('student');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();
$gamification = new Gamification();

$message = '';

// Xử lý đăng ký khóa học
if (isset($_POST['enroll'])) {
    $course_id = $_POST['course_id'];
    
    // Kiểm tra đã đăng ký chưa
    $check_query = "SELECT id FROM enrollments WHERE student_id = :student_id AND course_id = :course_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':student_id', $user['id']);
    $check_stmt->bindParam(':course_id', $course_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        $enroll_query = "INSERT INTO enrollments (student_id, course_id) VALUES (:student_id, :course_id)";
        $enroll_stmt = $db->prepare($enroll_query);
        $enroll_stmt->bindParam(':student_id', $user['id']);
        $enroll_stmt->bindParam(':course_id', $course_id);
        
        if ($enroll_stmt->execute()) {
            $message = 'Đăng ký khóa học thành công!';
            // Gamification: award points and possible achievement
            $gamification->recordEnrollment($user['id'], (int)$course_id);
        } else {
            $message = 'Có lỗi xảy ra khi đăng ký!';
        }
    } else {
        $message = 'Bạn đã đăng ký khóa học này rồi!';
    }
}

// Tìm kiếm khóa học
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = "WHERE c.status = 'active'";
$params = [];

if (!empty($search)) {
    $where_clause .= " AND (c.title LIKE :search OR c.description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$query = "SELECT c.*, u.full_name as teacher_name, 
          COUNT(e.student_id) as student_count,
          CASE WHEN e2.student_id IS NOT NULL THEN 1 ELSE 0 END as is_enrolled
          FROM courses c 
          JOIN users u ON c.teacher_id = u.id 
          LEFT JOIN enrollments e ON c.id = e.course_id 
          LEFT JOIN enrollments e2 ON c.id = e2.course_id AND e2.student_id = :student_id
          $where_clause
          GROUP BY c.id 
          ORDER BY c.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $user['id']);
foreach ($params as $key => $value) {
    $stmt->bindParam($key, $value);
}
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm Khóa Học - E-Learning Platform</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🎓 E-Learning</div>
            <ul class="nav-menu">
                <li><a href="../../dashboard.php">Dashboard</a></li>
                <li><a href="courses.php">Khóa học của tôi</a></li>
                <li><a href="assignments.php">Bài tập</a></li>
                <li><a href="../messages.php">Tin nhắn</a></li>
                <li><a href="../../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="dashboard-header">
            <h1>🔍 Tìm Kiếm Khóa Học</h1>
            <p>Khám phá các khóa học thú vị và mở rộng kiến thức</p>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Form tìm kiếm -->
        <div class="card">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                <input type="text" name="search" class="form-control" 
                       placeholder="Tìm kiếm khóa học..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       style="flex: 1;">
                <button type="submit" class="btn">🔍 Tìm kiếm</button>
                <?php if ($search): ?>
                    <a href="browse.php" class="btn btn-secondary">Xóa bộ lọc</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Kết quả tìm kiếm -->
        <div class="card">
            <h3>
                <?php if ($search): ?>
                    Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search); ?>" (<?php echo count($courses); ?> khóa học)
                <?php else: ?>
                    Tất cả khóa học (<?php echo count($courses); ?> khóa học)
                <?php endif; ?>
            </h3>
        </div>

        <?php if (empty($courses)): ?>
            <div class="card text-center" style="padding: 3rem; color: #666;">
                <h3>📚 Không tìm thấy khóa học nào</h3>
                <p>Thử tìm kiếm với từ khóa khác hoặc xem tất cả khóa học</p>
                <?php if ($search): ?>
                    <a href="browse.php" class="btn">Xem tất cả khóa học</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <div class="course-thumbnail">
                            <?php echo $course['is_enrolled'] ? '✅' : '📖'; ?>
                        </div>
                        <div class="course-content">
                            <h4 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h4>
                            <p class="course-description">
                                <?php echo htmlspecialchars(substr($course['description'], 0, 120)) . '...'; ?>
                            </p>
                            
                            <div class="course-meta mb-2">
                                <span>👨‍🏫 <?php echo htmlspecialchars($course['teacher_name']); ?></span>
                                <span>👥 <?php echo $course['student_count']; ?> học sinh</span>
                            </div>
                            
                            <div class="course-meta">
                                <span><?php echo date('d/m/Y', strtotime($course['created_at'])); ?></span>
                                
                                <?php if ($course['is_enrolled']): ?>
                                    <a href="../course_detail.php?id=<?php echo $course['id']; ?>" 
                                       class="btn btn-success" style="padding: 0.5rem 1rem;">
                                        📚 Học tiếp
                                    </a>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" name="enroll" class="btn" style="padding: 0.5rem 1rem;">
                                            ➕ Đăng ký
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Gợi ý -->
        <div class="card">
            <h3>💡 Gợi ý học tập</h3>
            <div class="grid grid-3">
                <div>
                    <h4>🎯 Chọn khóa học phù hợp</h4>
                    <ul>
                        <li>Đọc kỹ mô tả khóa học</li>
                        <li>Xem số lượng học sinh tham gia</li>
                        <li>Kiểm tra thông tin giáo viên</li>
                    </ul>
                </div>
                <div>
                    <h4>📚 Học hiệu quả</h4>
                    <ul>
                        <li>Đặt mục tiêu học tập rõ ràng</li>
                        <li>Tham gia thảo luận với giáo viên</li>
                        <li>Hoàn thành bài tập đúng hạn</li>
                    </ul>
                </div>
                <div>
                    <h4>🤝 Tương tác</h4>
                    <ul>
                        <li>Đặt câu hỏi khi không hiểu</li>
                        <li>Chia sẻ kinh nghiệm với bạn học</li>
                        <li>Phản hồi về chất lượng khóa học</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</body>
</html>