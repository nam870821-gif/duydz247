<?php
require_once '../includes/auth.php';
require_once '../database/config.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

// Lấy danh sách khóa học mà user có thể truy cập forum
if ($user['role'] == 'teacher') {
    // Giáo viên có thể truy cập forum của các khóa học mình tạo
    $query = "SELECT c.*, COUNT(DISTINCT ft.id) as topic_count, COUNT(DISTINCT fr.id) as reply_count,
              ft_latest.created_at as latest_activity
              FROM courses c
              LEFT JOIN forum_topics ft ON c.id = ft.course_id
              LEFT JOIN forum_replies fr ON ft.id = fr.topic_id
              LEFT JOIN forum_topics ft_latest ON c.id = ft_latest.course_id
              WHERE c.teacher_id = :user_id
              GROUP BY c.id
              ORDER BY latest_activity DESC, c.created_at DESC";
} else {
    // Học sinh có thể truy cập forum của các khóa học đã đăng ký
    $query = "SELECT c.*, COUNT(DISTINCT ft.id) as topic_count, COUNT(DISTINCT fr.id) as reply_count,
              ft_latest.created_at as latest_activity, e.enrolled_at
              FROM courses c
              JOIN enrollments e ON c.id = e.course_id
              LEFT JOIN forum_topics ft ON c.id = ft.course_id
              LEFT JOIN forum_replies fr ON ft.id = fr.topic_id
              LEFT JOIN forum_topics ft_latest ON c.id = ft_latest.course_id
              WHERE e.student_id = :user_id
              GROUP BY c.id, e.id
              ORDER BY latest_activity DESC, e.enrolled_at DESC";
}

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user['id']);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy các topic nổi bật (nhiều replies nhất)
$popular_query = "SELECT ft.*, c.title as course_title, u.full_name as author_name,
                  COUNT(fr.id) as reply_count, MAX(COALESCE(fr.created_at, ft.created_at)) as latest_activity
                  FROM forum_topics ft
                  JOIN courses c ON ft.course_id = c.id
                  JOIN users u ON ft.user_id = u.id
                  LEFT JOIN forum_replies fr ON ft.id = fr.topic_id";

if ($user['role'] == 'teacher') {
    $popular_query .= " WHERE c.teacher_id = :user_id";
} else {
    $popular_query .= " JOIN enrollments e ON c.id = e.course_id WHERE e.student_id = :user_id";
}

$popular_query .= " GROUP BY ft.id ORDER BY reply_count DESC, latest_activity DESC LIMIT 5";

$popular_stmt = $db->prepare($popular_query);
$popular_stmt->bindParam(':user_id', $user['id']);
$popular_stmt->execute();
$popular_topics = $popular_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🗣️ Forum Thảo Luận - E-Learning Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🎓 E-Learning</div>
            <ul class="nav-menu">
                <li><a href="../dashboard.php">Dashboard</a></li>
                <?php if ($user['role'] == 'teacher'): ?>
                    <li><a href="teacher/courses.php">Khóa học</a></li>
                    <li><a href="teacher/assignments.php">Bài tập</a></li>
                <?php else: ?>
                    <li><a href="student/courses.php">Khóa học</a></li>
                    <li><a href="student/assignments.php">Bài tập</a></li>
                <?php endif; ?>
                <li><a href="messages.php">Tin nhắn</a></li>
                <li><a href="forum.php" style="opacity: 0.8;">🗣️ Forum</a></li>
                <li><a href="chatbot.php">🤖 AI Bot</a></li>
                <li><a href="../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="dashboard-header">
            <h1>🗣️ Forum Thảo Luận</h1>
            <p>Tham gia thảo luận và trao đổi kiến thức với cộng đồng học tập</p>
        </div>

        <?php if (empty($courses)): ?>
            <div class="card text-center" style="padding: 3rem; color: #666;">
                <h3>📚 Chưa có khóa học nào</h3>
                <p>
                    <?php if ($user['role'] == 'teacher'): ?>
                        Tạo khóa học để bắt đầu xây dựng cộng đồng học tập!
                    <?php else: ?>
                        Đăng ký khóa học để tham gia forum thảo luận!
                    <?php endif; ?>
                </p>
                <?php if ($user['role'] == 'teacher'): ?>
                    <a href="teacher/create_course.php" class="btn">Tạo khóa học</a>
                <?php else: ?>
                    <a href="student/browse.php" class="btn">Tìm khóa học</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Popular Topics -->
            <?php if (!empty($popular_topics)): ?>
                <div class="card">
                    <h3>🔥 Chủ đề nổi bật</h3>
                    <div class="popular-topics">
                        <?php foreach ($popular_topics as $topic): ?>
                            <div class="popular-topic">
                                <div class="topic-info">
                                    <h4>
                                        <a href="forum_topic.php?id=<?php echo $topic['id']; ?>" class="topic-link">
                                            <?php echo htmlspecialchars($topic['title']); ?>
                                        </a>
                                    </h4>
                                    <div class="topic-meta">
                                        <span class="course-tag"><?php echo htmlspecialchars($topic['course_title']); ?></span>
                                        <span>👤 <?php echo htmlspecialchars($topic['author_name']); ?></span>
                                        <span>💬 <?php echo $topic['reply_count']; ?> trả lời</span>
                                        <span>🕒 <?php echo date('d/m/Y H:i', strtotime($topic['latest_activity'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Course Forums -->
            <div class="card">
                <h3>📚 Forum theo khóa học</h3>
                <div class="course-forums">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-forum-card">
                            <div class="course-forum-header">
                                <h4>
                                    <a href="forum_course.php?course_id=<?php echo $course['id']; ?>" class="course-forum-link">
                                        📖 <?php echo htmlspecialchars($course['title']); ?>
                                    </a>
                                </h4>
                                <div class="course-forum-stats">
                                    <span class="stat-item">💬 <?php echo $course['topic_count']; ?> chủ đề</span>
                                    <span class="stat-item">📝 <?php echo $course['reply_count']; ?> bài viết</span>
                                </div>
                            </div>
                            
                            <div class="course-forum-description">
                                <?php echo htmlspecialchars(substr($course['description'], 0, 120)) . '...'; ?>
                            </div>

                            <div class="course-forum-meta">
                                <?php if ($course['latest_activity']): ?>
                                    <span>🕒 Hoạt động gần nhất: <?php echo date('d/m/Y H:i', strtotime($course['latest_activity'])); ?></span>
                                <?php else: ?>
                                    <span>💤 Chưa có hoạt động nào</span>
                                <?php endif; ?>
                                
                                <div class="forum-actions">
                                    <a href="forum_course.php?course_id=<?php echo $course['id']; ?>" class="btn" style="padding: 0.5rem 1rem;">
                                        👀 Xem forum
                                    </a>
                                    <a href="forum_create_topic.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success" style="padding: 0.5rem 1rem;">
                                        ➕ Tạo chủ đề
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Forum Guidelines -->
            <div class="card">
                <h3>📋 Quy tắc Forum</h3>
                <div class="grid grid-2">
                    <div>
                        <h4>✅ Nên làm</h4>
                        <ul>
                            <li>Đặt tiêu đề rõ ràng, mô tả chính xác nội dung</li>
                            <li>Tìm kiếm trước khi tạo chủ đề mới</li>
                            <li>Sử dụng ngôn ngữ lịch sự và tôn trọng</li>
                            <li>Cung cấp thông tin chi tiết khi hỏi</li>
                            <li>Đánh dấu câu trả lời hữu ích</li>
                        </ul>
                    </div>
                    <div>
                        <h4>❌ Không nên làm</h4>
                        <ul>
                            <li>Spam hoặc đăng nội dung không liên quan</li>
                            <li>Sử dụng ngôn ngữ thô tục, xúc phạm</li>
                            <li>Đăng thông tin cá nhân nhạy cảm</li>
                            <li>Copy paste từ nguồn khác không ghi nguồn</li>
                            <li>Tạo nhiều chủ đề trùng lặp</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <style>
        .popular-topics {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .popular-topic {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .topic-link {
            color: #333;
            text-decoration: none;
            font-weight: 600;
        }

        .topic-link:hover {
            color: #667eea;
        }

        .topic-meta {
            margin-top: 0.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: #666;
        }

        .course-tag {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .course-forums {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .course-forum-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .course-forum-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .course-forum-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .course-forum-link {
            color: #333;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .course-forum-link:hover {
            color: #667eea;
        }

        .course-forum-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .stat-item {
            background: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            border: 1px solid #e1e8ed;
        }

        .course-forum-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .course-forum-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #666;
        }

        .forum-actions {
            display: flex;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .course-forum-header,
            .course-forum-meta {
                flex-direction: column;
                gap: 1rem;
            }

            .course-forum-stats {
                justify-content: flex-start;
            }

            .forum-actions {
                width: 100%;
            }

            .forum-actions .btn {
                flex: 1;
                text-align: center;
            }

            .topic-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</body>
</html>