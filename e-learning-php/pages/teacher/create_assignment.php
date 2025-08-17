<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('teacher');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

// Lấy danh sách khóa học của giáo viên
$query = "SELECT id, title FROM courses WHERE teacher_id = :teacher_id ORDER BY title";
$stmt = $db->prepare($query);
$stmt->bindParam(':teacher_id', $user['id']);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $max_score = $_POST['max_score'];
    
    if (!empty($course_id) && !empty($title) && !empty($description)) {
        try {
            $query = "INSERT INTO assignments (course_id, title, description, due_date, max_score) 
                     VALUES (:course_id, :title, :description, :due_date, :max_score)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':due_date', $due_date);
            $stmt->bindParam(':max_score', $max_score);
            
            if ($stmt->execute()) {
                $message = 'Bài tập đã được tạo thành công!';
                $success = true;
            } else {
                $message = 'Có lỗi xảy ra khi tạo bài tập!';
            }
        } catch(PDOException $e) {
            $message = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    } else {
        $message = 'Vui lòng điền đầy đủ thông tin!';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Bài Tập - E-Learning Platform</title>
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
            <h1>📝 Tạo Bài Tập Mới</h1>
            <p>Tạo bài tập để kiểm tra kiến thức học sinh</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
                <?php if ($success): ?>
                    <a href="assignments.php" style="margin-left: 1rem;">Xem danh sách bài tập</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($courses)): ?>
            <div class="card text-center">
                <h3>⚠️ Chưa có khóa học</h3>
                <p>Bạn cần tạo khóa học trước khi tạo bài tập</p>
                <a href="create_course.php" class="btn">Tạo khóa học ngay</a>
            </div>
        <?php else: ?>
            <div class="card">
                <form method="POST">
                    <div class="form-group">
                        <label for="course_id">Chọn khóa học:</label>
                        <select id="course_id" name="course_id" class="form-control" required>
                            <option value="">-- Chọn khóa học --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="title">Tiêu đề bài tập:</label>
                        <input type="text" id="title" name="title" class="form-control" required 
                               placeholder="Nhập tiêu đề bài tập...">
                    </div>

                    <div class="form-group">
                        <label for="description">Nội dung bài tập:</label>
                        <textarea id="description" name="description" class="form-control" rows="8" required 
                                  placeholder="Mô tả chi tiết bài tập, yêu cầu, hướng dẫn làm bài..."></textarea>
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="due_date">Hạn nộp:</label>
                            <input type="datetime-local" id="due_date" name="due_date" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="max_score">Điểm tối đa:</label>
                            <input type="number" id="max_score" name="max_score" class="form-control" 
                                   value="100" min="1" max="1000">
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn">📝 Tạo Bài Tập</button>
                        <a href="assignments.php" class="btn btn-secondary" style="margin-left: 1rem;">Hủy</a>
                    </div>
                </form>
            </div>

            <!-- Hướng dẫn -->
            <div class="card">
                <h3>💡 Hướng dẫn tạo bài tập hiệu quả</h3>
                <div class="grid grid-2">
                    <div>
                        <h4>📋 Nội dung bài tập</h4>
                        <ul>
                            <li>Đề bài rõ ràng, dễ hiểu</li>
                            <li>Yêu cầu cụ thể, chi tiết</li>
                            <li>Hướng dẫn làm bài (nếu cần)</li>
                            <li>Tiêu chí chấm điểm</li>
                        </ul>
                    </div>
                    <div>
                        <h4>⏰ Thời gian và điểm số</h4>
                        <ul>
                            <li>Đặt hạn nộp hợp lý</li>
                            <li>Thang điểm phù hợp</li>
                            <li>Thông báo trước cho học sinh</li>
                            <li>Nhắc nhở gần hạn nộp</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>