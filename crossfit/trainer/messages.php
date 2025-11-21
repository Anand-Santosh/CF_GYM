<?php
require_once '../includes/auth.php';

if ($_SESSION['role'] !== 'trainer') {
    header("Location: ../index.php");
    exit();
}

// Get trainer info
$stmt = $conn->prepare("SELECT * FROM trainers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$trainer = $stmt->fetch();

// Get assigned members list with correct user_id
$assignedMembers = $conn->query("
    SELECT DISTINCT m.member_id, m.full_name, m.user_id, u.created_at
    FROM members m 
    JOIN users u ON m.user_id = u.user_id
    JOIN bookings b ON m.member_id = b.member_id 
    WHERE b.trainer_id = {$trainer['trainer_id']}
    ORDER BY m.full_name
")->fetchAll();

// Since there's no read_status column, set unread messages to 0
$unreadMessages = 0;

// Handle sending a message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message_content = $_POST['message_content'];
    
    try {
        // Use 'content' column instead of 'message_content'
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $message_content]);
        
        $messageSuccess = "Message sent successfully!";
        
        // Refresh page to show new message (stay on same page)
        header("Location: messages.php");
        exit();
    } catch(PDOException $e) {
        $messageError = "Failed to send message: " . $e->getMessage();
    }
}

// Get all messages using the correct column names - ORDER BY DESC for newest first
try {
    $allMessages = $conn->query("
        SELECT m.*, mem.full_name as sender_name 
        FROM messages m 
        LEFT JOIN members mem ON m.sender_id = mem.user_id 
        WHERE m.receiver_id = {$_SESSION['user_id']} 
        OR m.sender_id = {$_SESSION['user_id']}
        ORDER BY m.sent_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $allMessages = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Messages - CrossFit Revolution</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #FF5A1F;
            --primary-dark: #E04A14;
            --dark: #121212;
            --darker: #0A0A0A;
            --light: #F8F9FA;
            --text-dark: #E0E0E0;
            --text-light: #FFFFFF;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--darker);
            color: var(--text-light);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-top: 0;
        }

        .messages-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.3);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        .card {
            background-color: var(--dark);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .message-list {
            max-height: 500px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            display: flex;
            flex-direction: column;
        }

        .message-item {
            padding: 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: background-color 0.3s ease;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-item:hover {
            background-color: rgba(255,255,255,0.05);
        }

        .message-item.sent {
            border-left: 4px solid var(--info);
            background-color: rgba(23, 162, 184, 0.05);
        }

        .message-item.received {
            border-left: 4px solid var(--primary);
            background-color: rgba(255, 90, 31, 0.05);
        }

        .message-sender {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message-item.sent .message-sender {
            color: var(--info);
        }

        .message-content {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .message-time {
            font-size: 0.8rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .message-form {
            background-color: var(--darker);
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 14px 16px;
            background-color: var(--dark);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 90, 31, 0.2);
            background-color: rgba(255,255,255,0.08);
        }

        /* Style for dropdown options */
        .form-select option {
            background-color: var(--dark);
            color: var(--text-light);
            padding: 10px;
        }

        /* Style for the dropdown arrow */
        .form-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23FF5A1F' viewBox='0 0 16 16'%3E%3Cpath d='M8 12L2 6h12L8 12z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
            padding-right: 45px;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .alert {
            padding: 1.2rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            border-color: rgba(40, 167, 69, 0.2);
            color: #d4edda;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            border-color: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
        }

        /* Custom scrollbar */
        .message-list::-webkit-scrollbar {
            width: 6px;
        }

        .message-list::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
            border-radius: 3px;
        }

        .message-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .message-list::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Empty state styling */
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="messages-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1><i class="bi bi-chat-dots-fill"></i> Messages</h1>
                <p style="color: var(--text-dark);">Communicate with your clients</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Messages List -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem; color: var(--primary);">
                <i class="bi bi-inbox"></i> All Messages
            </h2>
            
            <?php if(isset($messageSuccess)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> <?= $messageSuccess ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($messageError)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle-fill"></i> <?= $messageError ?>
                </div>
            <?php endif; ?>
            
            <div class="message-list">
                <?php if(count($allMessages) > 0): ?>
                    <?php foreach($allMessages as $message): ?>
                        <div class="message-item <?= $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received' ?>">
                            <div class="message-sender">
                                <i class="bi bi-person"></i> 
                                <?= $message['sender_id'] == $_SESSION['user_id'] ? 'You' : htmlspecialchars($message['sender_name']) ?>
                                <span style="font-size: 0.8rem; color: var(--text-dark); margin-left: auto;">
                                    <?= $message['sender_id'] == $_SESSION['user_id'] ? '(Sent)' : '(Received)' ?>
                                </span>
                            </div>
                            <div class="message-content"><?= htmlspecialchars($message['content']) ?></div>
                            <div class="message-time">
                                <i class="bi bi-clock"></i> <?= date('M j, Y g:i A', strtotime($message['sent_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-chat-square-text"></i>
                        <h3>No messages yet</h3>
                        <p>Start a conversation with your clients</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Send Message Form -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem; color: var(--primary);">
                <i class="bi bi-send"></i> Send New Message
            </h2>
            
            <form method="POST" class="message-form">
                <div class="form-group">
                    <label for="receiver_id" class="form-label">
                        <i class="bi bi-person"></i> Select Client
                    </label>
                    <select class="form-select" id="receiver_id" name="receiver_id" required>
                        <option value="">Choose a client...</option>
                        <?php foreach($assignedMembers as $member): ?>
                            <option value="<?= $member['user_id'] ?>"><?= htmlspecialchars($member['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message_content" class="form-label">
                        <i class="bi bi-chat-text"></i> Your Message
                    </label>
                    <textarea class="form-control" id="message_content" name="message_content" rows="4" placeholder="Type your message here..." required></textarea>
                </div>
                
                <button type="submit" name="send_message" class="btn">
                    <i class="bi bi-send-fill"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <script>
        // Auto-focus on message textarea
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('message_content');
            if (textarea) {
                textarea.focus();
            }
            
            // Auto-scroll to top of message list (newest messages are at top)
            const messageList = document.querySelector('.message-list');
            if (messageList) {
                messageList.scrollTop = 0;
            }
        });

        // Scroll to top when page loads
        window.addEventListener('load', function() {
            window.scrollTo(0, 0);
        });
    </script>
</body>
</html>