<?php
echo "🎓 E-Learning Platform Auto Setup\n";
echo "==================================\n\n";

// Tạo cấu trúc thư mục
$folders = [
    'assets/css',
    'assets/js', 
    'assets/images',
    'includes',
    'pages/teacher',
    'pages/student',
    'uploads',
    'database'
];

foreach ($folders as $folder) {
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
        echo "✅ Tạo thư mục: $folder\n";
    }
}

// Tạo file .htaccess
file_put_contents('.htaccess', "RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^(.*)$ index.php [QSA,L]");

// Tạo file uploads/.gitkeep
file_put_contents('uploads/.gitkeep', '');

echo "\n🎉 Setup hoàn thành!\n";
echo "📁 Cấu trúc thư mục đã được tạo\n";
echo "🚀 Bây giờ bạn có thể copy code vào từng file\n\n";

echo "📋 Danh sách files cần tạo:\n";
echo "- index.php (trang đăng nhập)\n";
echo "- dashboard.php (trang chính)\n";
echo "- logout.php (đăng xuất)\n";
echo "- database/config.php (cấu hình DB)\n";
echo "- database/schema.sql (database schema)\n";
echo "- includes/auth.php (authentication)\n";
echo "- includes/chatbot.php (AI chatbot)\n";
echo "- assets/css/style.css (CSS chính)\n";
echo "- pages/messages.php (tin nhắn)\n";
echo "- pages/chatbot.php (AI chat interface)\n";
echo "- pages/forum.php (forum chính)\n";
echo "- pages/forum_create_topic.php (tạo chủ đề)\n";
echo "- pages/teacher/* (trang giáo viên)\n";
echo "- pages/student/* (trang học sinh)\n";
?>