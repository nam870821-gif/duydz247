<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('student');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

// Cập nhật progress nếu có action
if (isset($_POST['update_progress'])) {
    $course_id = $_POST['course_id'];
    $progress = min(100, max(0, intval($_POST['progress']))); // Đảm bảo 0-100
    
    $update_query = "UPDATE enrollments SET progress = :progress 
                     WHERE student_id = :student_id AND course_id = :course_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':progress', $progress);
    $update_stmt->bindParam(':student_id', $user['id']);
    $update_stmt->bindParam(':course_id', $course_id);
    $update_stmt->execute();
}

// Lấy danh sách khóa học đã đăng ký với thông tin progress
$query = "SELECT c.*, u.full_name as teacher_name, e.enrolled_at, e.progress,
          COUNT(DISTINCT l.id) as total_lessons,
          COUNT(DISTINCT a.id) as total_assignments,
          COUNT(DISTINCT s.id) as completed_assignments
          FROM courses c 
          JOIN enrollments e ON c.id = e.course_id 
          JOIN users u ON c.teacher_id = u.id
          LEFT JOIN lessons l ON c.id = l.course_id
          LEFT JOIN assignments a ON c.id = a.course_id
          LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = e.student_id
          WHERE e.student_id = :student_id 
          GROUP BY c.id, e.id
          ORDER BY e.enrolled_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $user['id']);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khóa Học Của Tôi - E-Learning Platform</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🎓 E-Learning</div>
            <ul class="nav-menu">
                <li><a href="../../dashboard.php">Dashboard</a></li>
                <li><a href="courses.php" style="opacity: 0.8;">Khóa học của tôi</a></li>
                <li><a href="assignments.php">Bài tập</a></li>
                <li><a href="../messages.php">Tin nhắn</a></li>
                <li><a href="../../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="dashboard-header">
            <h1>📚 Khóa Học Của Tôi</h1>
            <p>Theo dõi tiến độ học tập và hoàn thành khóa học</p>
        </div>

        <?php if (empty($courses)): ?>
            <div class="card text-center" style="padding: 3rem; color: #666;">
                <h3>📖 Chưa đăng ký khóa học nào</h3>
                <p>Hãy khám phá và đăng ký khóa học để bắt đầu hành trình học tập!</p>
                <a href="browse.php" class="btn">🔍 Tìm khóa học</a>
            </div>
        <?php else: ?>
            <div class="card">
                <h3>📊 Tổng quan tiến độ</h3>
                <div class="grid grid-3">
                    <?php 
                    $total_courses = count($courses);
                    $completed_courses = count(array_filter($courses, function($c) { return $c['progress'] >= 100; }));
                    $avg_progress = $total_courses > 0 ? array_sum(array_column($courses, 'progress')) / $total_courses : 0;
                    ?>
                    <div class="progress-card">
                        <h4>📚 Tổng khóa học</h4>
                        <div class="big-number"><?php echo $total_courses; ?></div>
                    </div>
                    <div class="progress-card">
                        <h4>✅ Đã hoàn thành</h4>
                        <div class="big-number" style="color: #28a745;"><?php echo $completed_courses; ?></div>
                    </div>
                    <div class="progress-card">
                        <h4>📈 Tiến độ trung bình</h4>
                        <div class="big-number" style="color: #667eea;"><?php echo round($avg_progress); ?>%</div>
                    </div>
                </div>
            </div>

            <div class="courses-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="course-progress-card">
                        <div class="course-header">
                            <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                            <div class="course-teacher">👨‍🏫 <?php echo htmlspecialchars($course['teacher_name']); ?></div>
                        </div>

                        <div class="course-description">
                            <?php echo htmlspecialchars(substr($course['description'], 0, 150)) . '...'; ?>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress-section">
                            <div class="progress-header">
                                <span>📊 Tiến độ học tập</span>
                                <span class="progress-percentage"><?php echo $course['progress']; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $course['progress']; ?>%"></div>
                            </div>
                            <div class="progress-labels">
                                <span>Bắt đầu</span>
                                <span>Hoàn thành</span>
                            </div>
                        </div>

                        <!-- Course Stats -->
                        <div class="course-stats">
                            <div class="stat-item">
                                <span class="stat-icon">📖</span>
                                <span><?php echo $course['total_lessons']; ?> bài học</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-icon">📝</span>
                                <span><?php echo $course['completed_assignments']; ?>/<?php echo $course['total_assignments']; ?> bài tập</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-icon">📅</span>
                                <span>Đăng ký: <?php echo date('d/m/Y', strtotime($course['enrolled_at'])); ?></span>
                            </div>
                        </div>

                        <!-- Progress Update -->
                        <div class="progress-update">
                            <form method="POST" style="display: flex; align-items: center; gap: 1rem;">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <label for="progress_<?php echo $course['id']; ?>">Cập nhật tiến độ:</label>
                                <input type="range" 
                                       id="progress_<?php echo $course['id']; ?>" 
                                       name="progress" 
                                       min="0" max="100" 
                                       value="<?php echo $course['progress']; ?>"
                                       class="progress-slider"
                                       oninput="updateProgressValue(<?php echo $course['id']; ?>, this.value)">
                                <span id="value_<?php echo $course['id']; ?>" class="progress-value"><?php echo $course['progress']; ?>%</span>
                                <button type="submit" name="update_progress" class="btn-small">💾</button>
                            </form>
                        </div>

                        <!-- Action Buttons -->
                        <div class="course-actions">
                            <a href="../course_detail.php?id=<?php echo $course['id']; ?>" class="btn">📖 Học tiếp</a>
                            <a href="assignments.php?course_id=<?php echo $course['id']; ?>" class="btn btn-secondary">📝 Bài tập</a>
                        </div>

                        <!-- Progress Status -->
                        <div class="progress-status">
                            <?php if ($course['progress'] >= 100): ?>
                                <span class="status-completed">🎉 Đã hoàn thành</span>
                            <?php elseif ($course['progress'] >= 50): ?>
                                <span class="status-good">🚀 Đang tiến bộ tốt</span>
                            <?php elseif ($course['progress'] > 0): ?>
                                <span class="status-started">📚 Đã bắt đầu</span>
                            <?php else: ?>
                                <span class="status-not-started">⏳ Chưa bắt đầu</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Motivation Section -->
        <div class="card">
            <h3>🎯 Mục tiêu học tập</h3>
            <div class="motivation-section">
                <div class="grid grid-2">
                    <div>
                        <h4>💪 Động lực học tập</h4>
                        <ul>
                            <li>Đặt mục tiêu hoàn thành mỗi tuần</li>
                            <li>Học ít nhất 30 phút mỗi ngày</li>
                            <li>Hoàn thành bài tập đúng hạn</li>
                            <li>Tương tác với giáo viên thường xuyên</li>
                        </ul>
                    </div>
                    <div>
                        <h4>🏆 Thành tựu của bạn</h4>
                        <div class="achievements">
                            <?php if ($completed_courses > 0): ?>
                                <div class="achievement">🎓 Hoàn thành <?php echo $completed_courses; ?> khóa học</div>
                            <?php endif; ?>
                            <?php if ($avg_progress >= 80): ?>
                                <div class="achievement">⭐ Học sinh xuất sắc</div>
                            <?php elseif ($avg_progress >= 50): ?>
                                <div class="achievement">📈 Tiến bộ ổn định</div>
                            <?php endif; ?>
                            <?php if ($total_courses >= 3): ?>
                                <div class="achievement">📚 Người học tích cực</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <style>
        .courses-grid {
            display: grid;
            gap: 2rem;
            margin: 2rem 0;
        }

        .course-progress-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .course-progress-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .course-header {
            margin-bottom: 1rem;
        }

        .course-title {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .course-teacher {
            color: #666;
            font-size: 0.9rem;
        }

        .course-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .progress-section {
            margin: 1.5rem 0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .progress-percentage {
            font-weight: bold;
            color: #667eea;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            transition: width 0.5s ease;
            position: relative;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .progress-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #888;
            margin-top: 0.25rem;
        }

        .course-stats {
            display: flex;
            gap: 1.5rem;
            margin: 1.5rem 0;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        .stat-icon {
            font-size: 1.1rem;
        }

        .progress-update {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .progress-slider {
            flex: 1;
            height: 6px;
            border-radius: 3px;
            background: #ddd;
            outline: none;
            -webkit-appearance: none;
        }

        .progress-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #667eea;
            cursor: pointer;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }

        .progress-value {
            font-weight: bold;
            color: #667eea;
            min-width: 40px;
            text-align: center;
        }

        .btn-small {
            padding: 0.5rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .course-actions {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .course-actions .btn {
            flex: 1;
            text-align: center;
            padding: 0.75rem;
        }

        .progress-status {
            text-align: center;
            margin-top: 1rem;
        }

        .status-completed { color: #28a745; font-weight: bold; }
        .status-good { color: #17a2b8; font-weight: bold; }
        .status-started { color: #ffc107; font-weight: bold; }
        .status-not-started { color: #6c757d; }

        .progress-card {
            text-align: center;
            padding: 1rem;
        }

        .big-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .motivation-section {
            margin-top: 1rem;
        }

        .achievements {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .achievement {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .course-stats {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .course-actions {
                flex-direction: column;
            }
            
            .progress-update form {
                flex-direction: column;
                gap: 0.5rem;
                align-items: stretch;
            }
        }
    </style>

    <script>
        function updateProgressValue(courseId, value) {
            document.getElementById('value_' + courseId).textContent = value + '%';
        }

        // Animate progress bars on page load
        window.addEventListener('load', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        });
    </script>
</body>
</html>