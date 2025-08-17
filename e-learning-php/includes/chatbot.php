<?php
require_once '../database/config.php';

class Chatbot {
    private $db;
    private $user_id;
    private $session_id;
    
    public function __construct($user_id) {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user_id = $user_id;
        $this->session_id = $this->generateSessionId();
    }
    
    private function generateSessionId() {
        return session_id() . '_' . time();
    }
    
    public function processMessage($user_message) {
        // Làm sạch và chuẩn hóa tin nhắn
        $clean_message = $this->cleanMessage($user_message);
        
        // Tìm câu trả lời phù hợp
        $bot_response = $this->findBestResponse($clean_message);
        
        // Lưu cuộc hội thoại
        $this->saveConversation($user_message, $bot_response);
        
        return $bot_response;
    }
    
    private function cleanMessage($message) {
        // Loại bỏ ký tự đặc biệt, chuyển về chữ thường
        $message = strtolower(trim($message));
        $message = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $message);
        $message = preg_replace('/\s+/', ' ', $message);
        return $message;
    }
    
    private function findBestResponse($user_message) {
        try {
            // Lấy tất cả câu hỏi từ knowledge base
            $query = "SELECT question, answer, keywords FROM chatbot_knowledge ORDER BY id";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $knowledge_base = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $best_match = null;
            $highest_score = 0;
            
            foreach ($knowledge_base as $item) {
                $score = $this->calculateSimilarity($user_message, $item);
                if ($score > $highest_score && $score > 0.3) { // Ngưỡng tối thiểu
                    $highest_score = $score;
                    $best_match = $item;
                }
            }
            
            if ($best_match) {
                return $best_match['answer'];
            }
            
            // Nếu không tìm thấy câu trả lời phù hợp
            return $this->getDefaultResponse();
            
        } catch (Exception $e) {
            return "Xin lỗi, tôi đang gặp sự cố kỹ thuật. Vui lòng thử lại sau hoặc liên hệ với admin.";
        }
    }
    
    private function calculateSimilarity($user_message, $knowledge_item) {
        $score = 0;
        
        // So sánh với câu hỏi
        $question_score = $this->textSimilarity($user_message, strtolower($knowledge_item['question']));
        $score += $question_score * 0.7;
        
        // So sánh với keywords
        if (!empty($knowledge_item['keywords'])) {
            $keywords = explode(',', strtolower($knowledge_item['keywords']));
            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);
                if (strpos($user_message, $keyword) !== false) {
                    $score += 0.3;
                }
            }
        }
        
        return min($score, 1.0); // Giới hạn tối đa là 1.0
    }
    
    private function textSimilarity($text1, $text2) {
        // Thuật toán đơn giản để tính độ tương đồng
        $words1 = explode(' ', $text1);
        $words2 = explode(' ', $text2);
        
        $common_words = array_intersect($words1, $words2);
        $total_words = array_unique(array_merge($words1, $words2));
        
        if (count($total_words) == 0) return 0;
        
        return count($common_words) / count($total_words);
    }
    
    private function getDefaultResponse() {
        $default_responses = [
            "Xin lỗi, tôi chưa hiểu câu hỏi của bạn. Bạn có thể hỏi về:\n• Cách đăng ký khóa học\n• Liên hệ với giáo viên\n• Theo dõi tiến độ học tập\n• Sử dụng các tính năng của platform",
            "Tôi chưa có thông tin về vấn đề này. Bạn có thể:\n• Liên hệ với giáo viên qua tin nhắn\n• Hỏi trong forum của khóa học\n• Liên hệ admin để được hỗ trợ",
            "Câu hỏi của bạn khá thú vị! Tuy nhiên tôi chưa có đủ thông tin để trả lời. Hãy thử hỏi:\n• 'Làm thế nào để đăng ký khóa học?'\n• 'Platform này có những tính năng gì?'\n• 'Làm sao để liên hệ với giáo viên?'"
        ];
        
        return $default_responses[array_rand($default_responses)];
    }
    
    private function saveConversation($user_message, $bot_response) {
        try {
            $query = "INSERT INTO chatbot_conversations (user_id, session_id, user_message, bot_response) 
                     VALUES (:user_id, :session_id, :user_message, :bot_response)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':session_id', $this->session_id);
            $stmt->bindParam(':user_message', $user_message);
            $stmt->bindParam(':bot_response', $bot_response);
            $stmt->execute();
        } catch (Exception $e) {
            // Log error nhưng không làm gián đoạn chat
            error_log("Chatbot save conversation error: " . $e->getMessage());
        }
    }
    
    public function getChatHistory($limit = 10) {
        try {
            $query = "SELECT user_message, bot_response, created_at 
                     FROM chatbot_conversations 
                     WHERE user_id = :user_id 
                     ORDER BY created_at DESC 
                     LIMIT :limit";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function addKnowledge($question, $answer, $category = 'general', $keywords = '') {
        try {
            $query = "INSERT INTO chatbot_knowledge (question, answer, category, keywords) 
                     VALUES (:question, :answer, :category, :keywords)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':question', $question);
            $stmt->bindParam(':answer', $answer);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':keywords', $keywords);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getQuickReplies() {
        return [
            "Làm thế nào để đăng ký khóa học?",
            "Platform này có những tính năng gì?",
            "Làm sao để liên hệ với giáo viên?",
            "Làm thế nào để theo dõi tiến độ học tập?",
            "Forum thảo luận hoạt động như thế nào?"
        ];
    }
}
?>