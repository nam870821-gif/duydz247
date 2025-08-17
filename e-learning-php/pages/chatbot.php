<?php
require_once '../includes/auth.php';
require_once '../includes/chatbot.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getUser();
$chatbot = new Chatbot($user['id']);

$response = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $user_message = trim($_POST['message']);
    if (!empty($user_message)) {
        $response = $chatbot->processMessage($user_message);
    }
}

// Lấy lịch sử chat
$chat_history = $chatbot->getChatHistory(20);
$quick_replies = $chatbot->getQuickReplies();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 AI Chatbot - E-Learning Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .chatbot-container {
            max-width: 900px;
            margin: 0 auto;
            height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .chat-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .chat-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }

        .chat-window {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #f8f9fa;
            max-height: 400px;
        }

        .message {
            margin-bottom: 1rem;
            animation: fadeIn 0.3s ease;
        }

        .message.user {
            text-align: right;
        }

        .message.bot {
            text-align: left;
        }

        .message-bubble {
            display: inline-block;
            max-width: 70%;
            padding: 1rem 1.5rem;
            border-radius: 20px;
            word-wrap: break-word;
            position: relative;
        }

        .message.user .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message.bot .message-bubble {
            background: white;
            color: #333;
            border: 1px solid #e1e8ed;
            border-bottom-left-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }

        .message-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin: 0 0.5rem;
            vertical-align: bottom;
        }

        .message.user .message-avatar {
            background: #667eea;
            color: white;
        }

        .message.bot .message-avatar {
            background: #28a745;
            color: white;
        }

        .quick-replies {
            padding: 1rem;
            background: white;
            border-top: 1px solid #e1e8ed;
        }

        .quick-replies h4 {
            margin: 0 0 0.5rem 0;
            color: #666;
            font-size: 0.9rem;
        }

        .quick-reply-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .quick-reply-btn {
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border: 1px solid #e1e8ed;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
            color: #666;
        }

        .quick-reply-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .chat-input-container {
            padding: 1rem;
            background: white;
            border-top: 1px solid #e1e8ed;
            border-radius: 0 0 15px 15px;
        }

        .chat-input-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 1rem;
            border: 2px solid #e1e8ed;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input:focus {
            border-color: #667eea;
        }

        .send-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            transition: transform 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-btn:hover {
            transform: scale(1.1);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .typing-indicator {
            display: none;
            text-align: left;
            margin-bottom: 1rem;
        }

        .typing-dots {
            display: inline-flex;
            gap: 3px;
            padding: 1rem 1.5rem;
            background: white;
            border: 1px solid #e1e8ed;
            border-radius: 20px;
            border-bottom-left-radius: 5px;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background: #667eea;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { opacity: 0.3; transform: scale(1); }
            30% { opacity: 1; transform: scale(1.2); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome-message {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .welcome-message .bot-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
        }

        @media (max-width: 768px) {
            .chatbot-container {
                height: calc(100vh - 150px);
                margin: 0 1rem;
            }
            
            .message-bubble {
                max-width: 85%;
            }
            
            .quick-reply-buttons {
                flex-direction: column;
            }
            
            .quick-reply-btn {
                text-align: center;
            }
        }
    </style>
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
                <li><a href="forum.php">Forum</a></li>
                <li><a href="chatbot.php" style="opacity: 0.8;">🤖 AI Bot</a></li>
                <li><a href="../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <main class="container" style="padding: 2rem 1rem;">
        <div class="chatbot-container">
            <div class="chat-header">
                <h2>🤖 AI Chatbot Hỗ Trợ</h2>
                <p>Tôi có thể giúp bạn về các vấn đề liên quan đến e-learning platform</p>
            </div>

            <div class="chat-window">
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($chat_history)): ?>
                        <div class="welcome-message">
                            <span class="bot-icon">🤖</span>
                            <h3>Chào mừng <?php echo htmlspecialchars($user['full_name']); ?>!</h3>
                            <p>Tôi là AI Assistant của E-Learning Platform. Tôi có thể giúp bạn:</p>
                            <ul style="text-align: left; max-width: 300px; margin: 1rem auto;">
                                <li>Hướng dẫn sử dụng platform</li>
                                <li>Trả lời câu hỏi về khóa học</li>
                                <li>Hỗ trợ kỹ thuật cơ bản</li>
                                <li>Giải đáp thắc mắc chung</li>
                            </ul>
                            <p><strong>Hãy bắt đầu cuộc trò chuyện!</strong></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($chat_history as $chat): ?>
                            <div class="message user">
                                <div class="message-avatar">👤</div>
                                <div class="message-bubble">
                                    <?php echo nl2br(htmlspecialchars($chat['user_message'])); ?>
                                    <div class="message-time"><?php echo date('H:i', strtotime($chat['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="message bot">
                                <div class="message-avatar">🤖</div>
                                <div class="message-bubble">
                                    <?php echo nl2br(htmlspecialchars($chat['bot_response'])); ?>
                                    <div class="message-time"><?php echo date('H:i', strtotime($chat['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($response): ?>
                        <div class="message user">
                            <div class="message-avatar">👤</div>
                            <div class="message-bubble">
                                <?php echo nl2br(htmlspecialchars($_POST['message'])); ?>
                                <div class="message-time"><?php echo date('H:i'); ?></div>
                            </div>
                        </div>
                        <div class="message bot">
                            <div class="message-avatar">🤖</div>
                            <div class="message-bubble">
                                <?php echo nl2br(htmlspecialchars($response)); ?>
                                <div class="message-time"><?php echo date('H:i'); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="typing-indicator" id="typingIndicator">
                        <div class="message-avatar">🤖</div>
                        <div class="typing-dots">
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                        </div>
                    </div>
                </div>

                <div class="quick-replies">
                    <h4>💡 Câu hỏi gợi ý:</h4>
                    <div class="quick-reply-buttons">
                        <?php foreach ($quick_replies as $reply): ?>
                            <button class="quick-reply-btn" onclick="sendQuickReply('<?php echo htmlspecialchars($reply); ?>')">
                                <?php echo htmlspecialchars($reply); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="chat-input-container">
                    <form class="chat-input-form" method="POST" id="chatForm">
                        <input type="text" name="message" class="chat-input" 
                               placeholder="Nhập câu hỏi của bạn..." 
                               id="messageInput" required maxlength="500">
                        <button type="submit" class="send-btn" id="sendBtn">
                            📤
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const chatForm = document.getElementById('chatForm');
        const typingIndicator = document.getElementById('typingIndicator');

        // Auto scroll to bottom
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Show typing indicator
        function showTyping() {
            typingIndicator.style.display = 'block';
            scrollToBottom();
        }

        // Hide typing indicator
        function hideTyping() {
            typingIndicator.style.display = 'none';
        }

        // Send quick reply
        function sendQuickReply(message) {
            messageInput.value = message;
            chatForm.submit();
        }

        // Form submit handler
        chatForm.addEventListener('submit', function(e) {
            if (messageInput.value.trim() === '') {
                e.preventDefault();
                return;
            }
            
            showTyping();
            sendBtn.disabled = true;
            sendBtn.innerHTML = '⏳';
        });

        // Auto focus input
        messageInput.focus();

        // Scroll to bottom on load
        setTimeout(scrollToBottom, 100);

        // Enter key handler
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.submit();
            }
        });

        // Character counter
        messageInput.addEventListener('input', function() {
            const remaining = 500 - this.value.length;
            if (remaining < 50) {
                this.style.borderColor = remaining < 10 ? '#dc3545' : '#ffc107';
            } else {
                this.style.borderColor = '#e1e8ed';
            }
        });

        // Auto-resize textarea on mobile
        if (window.innerWidth <= 768) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        }
    </script>
</body>
</html>