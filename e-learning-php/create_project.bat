@echo off
echo ========================================
echo 🎓 E-Learning Platform Setup Script
echo ========================================
echo.

REM Tạo thư mục dự án
mkdir e-learning-php 2>nul
cd e-learning-php

REM Tạo cấu trúc thư mục
mkdir assets\css 2>nul
mkdir assets\js 2>nul
mkdir assets\images 2>nul
mkdir includes 2>nul
mkdir pages\teacher 2>nul
mkdir pages\student 2>nul
mkdir uploads 2>nul
mkdir database 2>nul

echo ✅ Đã tạo cấu trúc thư mục
echo.
echo 📁 Cấu trúc dự án:
echo   📂 assets/css/        - File CSS
echo   📂 includes/          - File PHP chung
echo   📂 pages/teacher/     - Trang giáo viên
echo   📂 pages/student/     - Trang học sinh
echo   📂 database/          - Database config
echo   📂 uploads/           - File upload
echo.
echo 🚀 Tiếp theo:
echo   1. Chạy file create_files.bat để tạo tất cả files
echo   2. Cấu hình database trong database/config.php
echo   3. Import database/schema.sql vào MySQL
echo   4. Chạy PHP server: php -S localhost:8000
echo.
echo ✨ Hoàn thành tạo cấu trúc thư mục!
pause