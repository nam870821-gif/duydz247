<?php
require_once 'includes/auth.php';

$auth = new Auth();

// Nếu đã đăng nhập thì redirect
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        if ($auth->login($username, $password)) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
        }
    }
    
    if (isset($_POST['register'])) {
        $username = $_POST['reg_username'];
        $email = $_POST['reg_email'];
        $password = $_POST['reg_password'];
        $full_name = $_POST['reg_full_name'];
        $role = $_POST['reg_role'];
        
        if (strlen($password) < 6) {
            $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
        } else {
            if ($auth->register($username, $email, $password, $full_name, $role)) {
                $success = 'Đăng ký thành công! Vui lòng đăng nhập.';
            } else {
                $error = 'Tên đăng nhập hoặc email đã tồn tại!';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Learning Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>🎓 E-Learning Platform</h2>
                <p>Nền tảng học tập trực tuyến</p>
            </div>

            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('login')">Đăng Nhập</button>
                <button class="tab-btn" onclick="showTab('register')">Đăng Ký</button>
            </div>

            <!-- Form Đăng Nhập -->
            <div id="login-tab" class="tab-content active">
                <form method="POST">
                    <div class="form-group">
                        <label>Tên đăng nhập hoặc Email:</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu:</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" name="login" class="btn" style="width: 100%;">Đăng Nhập</button>
                </form>
            </div>

            <!-- Form Đăng Ký -->
            <div id="register-tab" class="tab-content">
                <form method="POST">
                    <div class="form-group">
                        <label>Họ và tên:</label>
                        <input type="text" name="reg_full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tên đăng nhập:</label>
                        <input type="text" name="reg_username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="reg_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu:</label>
                        <input type="password" name="reg_password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Vai trò:</label>
                        <select name="reg_role" class="form-control" required>
                            <option value="student">Học sinh</option>
                            <option value="teacher">Giáo viên</option>
                        </select>
                    </div>
                    <button type="submit" name="register" class="btn" style="width: 100%;">Đăng Ký</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Ẩn tất cả tab content
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Xóa active class từ tất cả tab buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Hiện tab được chọn
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>

    <style>
        .tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 1px solid #ddd;
        }

        .tab-btn {
            flex: 1;
            padding: 1rem;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .tab-btn.active {
            background: #667eea;
            color: white;
            border-radius: 8px 8px 0 0;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</body>
</html>