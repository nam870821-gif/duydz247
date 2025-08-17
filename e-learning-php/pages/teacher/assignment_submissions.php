<?php
require_once '../../includes/auth.php';
require_once '../../database/config.php';

$auth = new Auth();
$auth->requireRole('teacher');

$user = $auth->getUser();
$database = new Database();
$db = $database->getConnection();

$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
if ($assignment_id <= 0) {
    header('Location: assignments.php');
    exit();
}

// Kiểm tra assignment thuộc khóa học của giáo viên
$check = $db->prepare("SELECT a.id, a.title, c.title AS course_title
                       FROM assignments a JOIN courses c ON a.course_id = c.id
                       WHERE a.id = :aid AND c.teacher_id = :tid");
$check->bindParam(':aid', $assignment_id);
$check->bindParam(':tid', $user['id']);
$check->execute();
$assignment = $check->fetch(PDO::FETCH_ASSOC);
if (!$assignment) {
    header('Location: assignments.php');
    exit();
}

$message = '';

// Chấm điểm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade'])) {
    $submission_id = (int)$_POST['submission_id'];
    $score = isset($_POST['score']) ? (int)$_POST['score'] : null;
    $feedback = trim($_POST['feedback'] ?? '');
    $upd = $db->prepare("UPDATE submissions SET score = :score, feedback = :feedback, graded_at = NOW()
                         WHERE id = :sid AND assignment_id = :aid");
    $upd->bindParam(':score', $score);
    $upd->bindParam(':feedback', $feedback);
    $upd->bindParam(':sid', $submission_id);
    $upd->bindParam(':aid', $assignment_id);
    if ($upd->execute()) {
        $message = 'Đã lưu điểm.';
    } else {
        $message = 'Không thể lưu điểm.';
    }
}

// Lấy submissions
$subs = $db->prepare("SELECT s.*, u.full_name FROM submissions s JOIN users u ON s.student_id = u.id WHERE s.assignment_id = :aid ORDER BY s.submitted_at DESC");
$subs->bindParam(':aid', $assignment_id);
$subs->execute();
$submissions = $subs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài nộp - <?php echo htmlspecialchars($assignment['title']); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php $ROOT = '../..'; include __DIR__ . '/../../includes/header.php'; ?>
    <main class="container">
        <div class="dashboard-header">
            <h1>📄 Bài nộp: <?php echo htmlspecialchars($assignment['title']); ?></h1>
            <p>Khóa học: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (empty($submissions)): ?>
            <div class="card text-center" style="padding:2rem; color:#666;">Chưa có bài nộp.</div>
        <?php else: ?>
            <?php foreach ($submissions as $s): ?>
                <div class="card" style="margin-bottom:1rem;">
                    <h3><?php echo htmlspecialchars($s['full_name']); ?> <span style="color:#666; font-weight:normal;">(<?php echo date('d/m/Y H:i', strtotime($s['submitted_at'])); ?>)</span></h3>
                    <?php if (!empty($s['content'])): ?>
                        <div style="white-space: pre-wrap; margin:.5rem 0;"><?php echo htmlspecialchars($s['content']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($s['file_path'])): ?>
                        <div><a href="<?php echo htmlspecialchars($s['file_path']); ?>" target="_blank">📎 Tệp đính kèm</a></div>
                    <?php endif; ?>

                    <form method="POST" style="margin-top:1rem;">
                        <input type="hidden" name="submission_id" value="<?php echo $s['id']; ?>">
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Điểm</label>
                                <input type="number" name="score" class="form-control" value="<?php echo htmlspecialchars($s['score']); ?>" min="0" max="1000">
                            </div>
                            <div class="form-group">
                                <label>Nhận xét</label>
                                <input type="text" name="feedback" class="form-control" value="<?php echo htmlspecialchars($s['feedback']); ?>" placeholder="Nhận xét ngắn...">
                            </div>
                        </div>
                        <button type="submit" name="grade" class="btn">💾 Lưu</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    <?php $ROOT = '../..'; include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>