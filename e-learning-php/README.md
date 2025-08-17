# 🎓 E-Learning Platform

Nền tảng học tập trực tuyến được xây dựng bằng PHP, HTML, CSS và MySQL, cho phép giáo viên và học sinh tương tác hiệu quả.

## ✨ Tính năng

### 👨‍🏫 Dành cho Giáo viên
- ✅ Tạo và quản lý khóa học
- ✅ Tạo bài tập và quiz
- ✅ Quản lý học sinh
- ✅ Chấm điểm và phản hồi
- ✅ Tin nhắn với học sinh

### 👨‍🎓 Dành cho Học sinh
- ✅ Tìm kiếm và đăng ký khóa học
- ✅ Xem nội dung bài học
- ✅ Làm bài tập và quiz
- ✅ Theo dõi tiến độ học tập
- ✅ Tin nhắn với giáo viên

### 🔧 Tính năng chung
- ✅ Hệ thống đăng nhập/đăng ký
- ✅ Dashboard tùy theo vai trò
- ✅ Giao diện responsive
- ✅ Hệ thống tin nhắn real-time
- ✅ Gamification (điểm, cấp độ, thành tích, leaderboard)

## 🛠️ Công nghệ sử dụng

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: CSS tùy chỉnh với Flexbox/Grid

## 📋 Yêu cầu hệ thống

- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- Web server (Apache/Nginx)
- Trình duyệt hiện đại

## 🚀 Cài đặt

### 1. Clone dự án
```bash
git clone <repository-url>
cd e-learning-php
```

### 2. Cấu hình database
```bash
# Tạo database MySQL
mysql -u root -p
CREATE DATABASE elearning_db;
```

### 3. Import schema
```bash
mysql -u root -p elearning_db < database/schema.sql
```

### 4. Cấu hình kết nối
Chỉnh sửa file `database/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'elearning_db');
```

### 5. Chạy ứng dụng
```bash
# Sử dụng PHP built-in server
php -S localhost:8000

# Hoặc cấu hình với Apache/Nginx
```

## 👤 Tài khoản mặc định

### Admin
- **Username**: admin
- **Password**: password

### Giáo viên
- **Username**: teacher1
- **Password**: password

### Học sinh
- **Username**: student1
- **Password**: password

## 📁 Cấu trúc thư mục

```
e-learning-php/
├── assets/
│   ├── css/          # File CSS
│   ├── js/           # File JavaScript
│   └── images/       # Hình ảnh
├── database/
│   ├── config.php    # Cấu hình database
│   └── schema.sql    # Schema database (bao gồm Gamification)
├── includes/
│   ├── auth.php      # Xử lý authentication
│   └── gamification.php # Gamification helper
├── pages/
│   ├── teacher/      # Trang dành cho giáo viên
│   ├── student/      # Trang dành cho học sinh
│   └── messages.php  # Hệ thống tin nhắn
├── uploads/          # File upload
├── index.php         # Trang đăng nhập
├── dashboard.php     # Trang chính (có leaderboard)
└── README.md
```

## 🎯 Hướng dẫn sử dụng

- Học sinh: Đăng ký khóa học → Làm bài → Cập nhật tiến độ. Hệ thống tự cộng điểm, lên cấp và trao thành tích.
- Leaderboard: Xem top người học có điểm cao nhất ngay trên dashboard.

## 🔒 Bảo mật

- Mật khẩu được mã hóa bằng bcrypt
- SQL injection protection với PDO
- XSS protection với htmlspecialchars
- Session-based authentication

## 📱 Responsive Design

Giao diện tự động thích ứng với:
- 📱 Mobile (320px+)
- 📱 Tablet (768px+)
- 💻 Desktop (1024px+)

## 🐛 Debug và Troubleshooting

### Lỗi kết nối database
```php
// Kiểm tra thông tin kết nối trong database/config.php
// Đảm bảo MySQL đang chạy
// Kiểm tra quyền truy cập database
```

### Lỗi session
```php
// Đảm bảo session_start() được gọi
// Kiểm tra quyền write trong session directory
```

## 🤝 Đóng góp

1. Fork dự án
2. Tạo feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Tạo Pull Request

## 📄 License

Dự án được phát hành dưới [MIT License](LICENSE).

## 🚀 Phát triển tiếp

### Tính năng sắp tới
- [ ] Video call trực tuyến
- [ ] API REST
- [ ] Multi-language support

### Cải thiện hiệu suất
- [ ] Cache system
- [ ] Database optimization
- [ ] CDN integration
- [ ] Image optimization

---

**Được phát triển với ❤️ bằng PHP, HTML, CSS & MySQL**