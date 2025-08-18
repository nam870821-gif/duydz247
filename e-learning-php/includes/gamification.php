<?php
require_once __DIR__ . '/../database/config.php';

class Gamification {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function addEvent($userId, $type, $points, $courseId = null, $assignmentId = null) {
        try {
            $query = "INSERT INTO gamification_events (user_id, type, points, course_id, assignment_id)
                      VALUES (:user_id, :type, :points, :course_id, :assignment_id)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            $stmt->bindParam(':points', $points, PDO::PARAM_INT);
            $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
            $stmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getTotalPoints($userId) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(points), 0) FROM gamification_events WHERE user_id = :uid");
        $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getLevelFromPoints($points) {
        return max(1, (int)floor($points / 100) + 1);
    }

    public function getUserAchievements($userId) {
        $query = "SELECT a.code, a.title, a.description, ua.earned_at
                  FROM user_achievements ua
                  JOIN achievements a ON ua.achievement_id = a.id
                  WHERE ua.user_id = :uid
                  ORDER BY ua.earned_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function awardAchievementByCode($userId, $code) {
        // Get achievement
        $achStmt = $this->db->prepare("SELECT id, points_reward FROM achievements WHERE code = :code");
        $achStmt->bindParam(':code', $code, PDO::PARAM_STR);
        $achStmt->execute();
        $achievement = $achStmt->fetch(PDO::FETCH_ASSOC);
        if (!$achievement) {
            return false;
        }

        // Check already earned
        $check = $this->db->prepare("SELECT 1 FROM user_achievements WHERE user_id = :uid AND achievement_id = :aid");
        $check->bindParam(':uid', $userId, PDO::PARAM_INT);
        $check->bindParam(':aid', $achievement['id'], PDO::PARAM_INT);
        $check->execute();
        if ($check->fetchColumn()) {
            return false;
        }

        // Award
        $ins = $this->db->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (:uid, :aid)");
        $ins->bindParam(':uid', $userId, PDO::PARAM_INT);
        $ins->bindParam(':aid', $achievement['id'], PDO::PARAM_INT);
        $ins->execute();

        // Reward points
        $points = (int)$achievement['points_reward'];
        if ($points > 0) {
            $this->addEvent($userId, 'achievement_' . $code, $points);
        }
        return true;
    }

    public function hasCompletedCourse($userId, $courseId) {
        $q = $this->db->prepare("SELECT 1 FROM gamification_events WHERE user_id = :uid AND type = 'complete_course' AND course_id = :cid LIMIT 1");
        $q->bindParam(':uid', $userId, PDO::PARAM_INT);
        $q->bindParam(':cid', $courseId, PDO::PARAM_INT);
        $q->execute();
        return (bool)$q->fetchColumn();
    }

    public function recordCourseCompletionIfNew($userId, $courseId) {
        if (!$this->hasCompletedCourse($userId, $courseId)) {
            $this->addEvent($userId, 'complete_course', 50, $courseId, null);
            // If this is the first completion, award achievement
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM gamification_events WHERE user_id = :uid AND type = 'complete_course'");
            $countStmt->bindParam(':uid', $userId, PDO::PARAM_INT);
            $countStmt->execute();
            $completed = (int)$countStmt->fetchColumn();
            if ($completed === 1) {
                $this->awardAchievementByCode($userId, 'course_finisher');
            }
        }
    }

    public function recordEnrollment($userId, $courseId) {
        $this->addEvent($userId, 'enroll_course', 10, $courseId, null);
        // First enrollment achievement?
        $enCount = $this->db->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = :sid");
        $enCount->bindParam(':sid', $userId, PDO::PARAM_INT);
        $enCount->execute();
        if ((int)$enCount->fetchColumn() === 1) {
            $this->awardAchievementByCode($userId, 'first_enrollment');
        }
    }

    public function recordSubmission($userId, $assignmentId, $isNew) {
        $points = $isNew ? 10 : 2;
        $this->addEvent($userId, 'submit_assignment', $points, null, $assignmentId);
        if ($isNew) {
            $subCount = $this->db->prepare("SELECT COUNT(*) FROM submissions WHERE student_id = :sid");
            $subCount->bindParam(':sid', $userId, PDO::PARAM_INT);
            $subCount->execute();
            if ((int)$subCount->fetchColumn() === 1) {
                $this->awardAchievementByCode($userId, 'first_submission');
            }
        }
    }

    public function getLeaderboard($limit = 10) {
        $query = "SELECT u.id, u.full_name, COALESCE(SUM(e.points),0) AS total_points
                  FROM users u
                  LEFT JOIN gamification_events e ON u.id = e.user_id
                  WHERE u.role = 'student'
                  GROUP BY u.id, u.full_name
                  ORDER BY total_points DESC
                  LIMIT :lim";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserStats($userId) {
        $points = $this->getTotalPoints($userId);
        $level = $this->getLevelFromPoints($points);
        $achievements = $this->getUserAchievements($userId);
        return [
            'points' => $points,
            'level' => $level,
            'achievements_count' => count($achievements)
        ];
    }
}