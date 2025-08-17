<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('student');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

$message = '';

// Nộp/ cập nhật bài làm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $assignment_id = intval($_POST['assignment_id']);
    $content = trim($_POST['content'] ?? '');

    if ($assignment_id > 0) {
        // Kiểm tra đã có submission chưa
        $check = $db->prepare("SELECT id FROM submissions WHERE assignment_id = :aid AND student_id = :sid");
        $check->bindParam(':aid', $assignment_id);
        $check->bindParam(':sid', $user['id']);
        $check->execute();
        $existing_id = $check->fetchColumn();

        if ($existing_id) {
            $upd = $db->prepare("UPDATE submissions SET content = :content, submitted_at = NOW() WHERE id = :id");
            $upd->bindParam(':content', $content);
            $upd->bindParam(':id', $existing_id);
            $upd->execute();
            $message = 'Đã cập nhật bài nộp.';
        } else {
            $ins = $db->prepare("INSERT INTO submissions (assignment_id, student_id, content) VALUES (:aid, :sid, :content)");
            $ins->bindParam(':aid', $assignment_id);
            $ins->bindParam(':sid', $user['id']);
            $ins->bindParam(':content', $content);
            $ins->execute();
            $message = 'Đã nộp bài thành công!';
        }
    }
}

$filter_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Lấy danh sách bài tập thuộc các khóa học mà học sinh đã đăng ký
$query = "SELECT a.*, c.title AS course_title,
                 (SELECT s.score FROM submissions s WHERE s.assignment_id = a.id AND s.student_id = :sid LIMIT 1) AS my_score,
                 (SELECT s.id FROM submissions s WHERE s.assignment_id = a.id AND s.student_id = :sid LIMIT 1) AS my_submission_id
          FROM assignments a
          JOIN courses c ON a.course_id = c.id
          JOIN enrollments e ON a.course_id = e.course_id AND e.student_id = :sid
          WHERE 1=1";

if ($filter_course_id > 0) {
    $query .= " AND a.course_id = :cid";
}
$query .= " ORDER BY a.due_date IS NULL, a.due_date ASC, a.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':sid', $user['id']);
if ($filter_course_id > 0) {
    $stmt->bindParam(':cid', $filter_course_id);
}
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách khóa học để filter
$courses_stmt = $db->prepare("SELECT c.id, c.title FROM courses c JOIN enrollments e ON c.id = e.course_id WHERE e.student_id = :sid ORDER BY c.title");
$courses_stmt->bindParam(':sid', $user['id']);
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài Tập - E-Learning Platform</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🎓 E-Learning</div>
            <ul class="nav-menu">
                <li><a href="../../dashboard.php">Dashboard</a></li>
                <li><a href="courses.php">Khóa học của tôi</a></li>
                <li><a href="assignments.php" style="opacity:.8;">Bài tập</a></li>
                <li><a href="../messages.php">Tin nhắn</a></li>
                <li><a href="../../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="dashboard-header">
            <h1>📝 Bài Tập</h1>
            <p>Xem danh sách bài tập và nộp bài</p>
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

        <?php if (empty($assignments)): ?>
            <div class="card text-center" style="color:#666; padding:2rem;">Chưa có bài tập nào.</div>
        <?php else: ?>
            <?php foreach ($assignments as $a): ?>
                <div class="card" style="margin-bottom:1rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h3><?php echo htmlspecialchars($a['title']); ?></h3>
                        <span style="color:#666;">
                            <?php echo htmlspecialchars($a['course_title']); ?>
                            <?php if (!empty($a['due_date'])): ?>
                                • Hạn: <?php echo date('d/m/Y H:i', strtotime($a['due_date'])); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div style="margin-top:.5rem;">
                        <form method="POST">
                            <input type="hidden" name="assignment_id" value="<?php echo $a['id']; ?>">
                            <div class="form-group">
                                <label>Bài làm của bạn</label>
                                <textarea name="content" class="form-control" rows="5" placeholder="Nhập câu trả lời..."></textarea>
                            </div>
                            <div style="display:flex; gap:1rem; align-items:center;">
                                <button type="submit" name="submit_assignment" class="btn">📤 Nộp bài</button>
                                <?php if ($a['my_score'] !== null): ?>
                                    <span style="color:#28a745; font-weight:bold;">Điểm: <?php echo (int)$a['my_score']; ?></span>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>