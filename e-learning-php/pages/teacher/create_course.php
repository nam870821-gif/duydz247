<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('teacher');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    if (!empty($title) && !empty($description)) {
        try {
            $query = "INSERT INTO courses (title, description, teacher_id) VALUES (:title, :description, :teacher_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':teacher_id', $user['id']);
            
            if ($stmt->execute()) {
                $message = 'Khóa học đã được tạo thành công!';
                $success = true;
            } else {
                $message = 'Có lỗi xảy ra khi tạo khóa học!';
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
    <title>Tạo Khóa Học - E-Learning Platform</title>
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
            <h1>Tạo Khóa Học Mới</h1>
            <p>Tạo khóa học để chia sẻ kiến thức với học sinh</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
                <?php if ($success): ?>
                    <a href="courses.php" style="margin-left: 1rem;">Xem danh sách khóa học</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label for="title">Tên khóa học:</label>
                    <input type="text" id="title" name="title" class="form-control" required 
                           placeholder="Nhập tên khóa học...">
                </div>

                <div class="form-group">
                    <label for="description">Mô tả khóa học:</label>
                    <textarea id="description" name="description" class="form-control" rows="6" required 
                              placeholder="Mô tả chi tiết về khóa học, mục tiêu học tập, đối tượng học viên..."></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn">Tạo Khóa Học</button>
                    <a href="courses.php" class="btn btn-secondary" style="margin-left: 1rem;">Hủy</a>
                </div>
            </form>
        </div>

        <!-- Hướng dẫn -->
        <div class="card">
            <h3>💡 Hướng dẫn tạo khóa học</h3>
            <div class="grid grid-2">
                <div>
                    <h4>📝 Tên khóa học</h4>
                    <ul>
                        <li>Nên ngắn gọn, dễ hiểu</li>
                        <li>Thể hiện rõ chủ đề</li>
                        <li>Tránh dùng ký tự đặc biệt</li>
                    </ul>
                </div>
                <div>
                    <h4>📖 Mô tả khóa học</h4>
                    <ul>
                        <li>Mô tả mục tiêu học tập</li>
                        <li>Nội dung sẽ được học</li>
                        <li>Đối tượng học viên phù hợp</li>
                        <li>Yêu cầu kiến thức trước (nếu có)</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</body>
</html>