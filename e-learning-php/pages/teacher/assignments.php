<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('teacher');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

// Xóa bài tập (chỉ nếu thuộc khóa học của giáo viên)
if (isset($_GET['delete'])) {
    $assignment_id = intval($_GET['delete']);
    $delete_query = "DELETE a FROM assignments a 
                     JOIN courses c ON a.course_id = c.id 
                     WHERE a.id = :aid AND c.teacher_id = :tid";
    $stmt = $db->prepare($delete_query);
    $stmt->bindParam(':aid', $assignment_id);
    $stmt->bindParam(':tid', $user['id']);
    $stmt->execute();
    header('Location: assignments.php');
    exit();
}

$filter_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Lấy danh sách khóa học của giáo viên (để filter)
$courses_stmt = $db->prepare("SELECT id, title FROM courses WHERE teacher_id = :tid ORDER BY title");
$courses_stmt->bindParam(':tid', $user['id']);
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách bài tập
$assign_query = "SELECT a.*, c.title AS course_title,
                 (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS submissions_count
                 FROM assignments a
                 JOIN courses c ON a.course_id = c.id
                 WHERE c.teacher_id = :tid";

if ($filter_course_id > 0) {
    $assign_query .= " AND c.id = :cid";
}
$assign_query .= " ORDER BY a.created_at DESC";

$assign_stmt = $db->prepare($assign_query);
$assign_stmt->bindParam(':tid', $user['id']);
if ($filter_course_id > 0) {
    $assign_stmt->bindParam(':cid', $filter_course_id);
}
$assign_stmt->execute();
$assignments = $assign_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Bài Tập - E-Learning Platform</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🎓 E-Learning</div>
            <ul class="nav-menu">
                <li><a href="../../dashboard.php">Dashboard</a></li>
                <li><a href="courses.php">Khóa học</a></li>
                <li><a href="assignments.php" style="opacity: .8;">Bài tập</a></li>
                <li><a href="../messages.php">Tin nhắn</a></li>
                <li><a href="../../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="dashboard-header">
            <h1>📋 Quản Lý Bài Tập</h1>
            <p>Xem và quản lý bài tập thuộc các khóa học của bạn</p>
        </div>

        <div class="card">
            <div style="display:flex; justify-content: space-between; align-items:center;">
                <h3>Danh sách bài tập (<?php echo count($assignments); ?>)</h3>
                <a href="create_assignment.php" class="btn">➕ Tạo bài tập</a>
            </div>

            <form method="GET" style="margin-top:1rem; display:flex; gap:1rem; align-items:center;">
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
            <div class="card text-center" style="padding:2rem; color:#666;">
                <p>Chưa có bài tập nào.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Khóa học</th>
                                <th>Tiêu đề</th>
                                <th>Hạn nộp</th>
                                <th>Điểm tối đa</th>
                                <th>Nộp bài</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($a['course_title']); ?></td>
                                    <td><?php echo htmlspecialchars($a['title']); ?></td>
                                    <td><?php echo $a['due_date'] ? date('d/m/Y H:i', strtotime($a['due_date'])) : '-'; ?></td>
                                    <td><?php echo (int)$a['max_score']; ?></td>
                                    <td><?php echo (int)$a['submissions_count']; ?></td>
                                    <td>
                                        <a href="?delete=<?php echo $a['id']; ?>" class="btn btn-danger" style="padding:.5rem;"
                                           onclick="return confirm('Xóa bài tập này?')">🗑️ Xóa</a>
                                    </td>
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