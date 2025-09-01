<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['nickname'])) {
    header("Location: login.php");
    exit();
}

// Initialize current question if not set
if (!isset($_SESSION['current_question'])) {
    $_SESSION['current_question'] = 1;
}

$show_results = false;
$response_time = 0;
$is_correct = false;
$top_players = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['answer']) && isset($_POST['option']) && isset($_POST['response_time'])) {
        $nickname = $_SESSION['nickname'];
        $chosen_option = $_POST['option'];
        $question_id = $_SESSION['current_question'];
        $response_time = floatval($_POST['response_time']);
        
        // Get correct answer
        $stmt = $conn->prepare("SELECT correct_option FROM questions WHERE id = ?");
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $stmt->bind_result($correct_option);
        $stmt->fetch();
        $stmt->close();
        
        $is_correct = ($chosen_option === $correct_option);
        
        // Get or create player_id
        $player_stmt = $conn->prepare("SELECT id FROM players WHERE nickname = ?");
        $player_stmt->bind_param("s", $nickname);
        $player_stmt->execute();
        $player_result = $player_stmt->get_result();
        $player_data = $player_result->fetch_assoc();
        
        if ($player_data) {
            $player_id = $player_data['id'];
        } else {
            // Create new player if not exists
            $insert_stmt = $conn->prepare("INSERT INTO players (nickname, full_name, phone_number) VALUES (?, '', '')");
            $insert_stmt->bind_param("s", $nickname);
            $insert_stmt->execute();
            $player_id = $conn->insert_id;
            $insert_stmt->close();
        }
        $player_stmt->close();
        
        // Insert response with proper player_id
        $stmt = $conn->prepare("INSERT INTO responses (player_id, nickname, question_id, chosen_option, response_time, is_correct) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE chosen_option = VALUES(chosen_option), response_time = VALUES(response_time), is_correct = VALUES(is_correct)");
        $stmt->bind_param("isisdi", $player_id, $nickname, $question_id, $chosen_option, $response_time, $is_correct);
        $stmt->execute();
        $stmt->close();
        
        // Get top 3 fastest correct answers for this question
        $stmt = $conn->prepare("SELECT nickname, response_time FROM responses WHERE question_id = ? AND is_correct = 1 ORDER BY response_time ASC LIMIT 3");
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $top_players[] = $row;
        }
        $stmt->close();
        
        $show_results = true;
        
    } elseif (isset($_POST['next'])) {
        $_SESSION['current_question']++;
        $show_results = false;
    }
}

// Get current question
$question_number = $_SESSION['current_question'];
$stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->bind_param("i", $question_number);
$stmt->execute();
$question = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$question) {
    header("Location: winners.php");
    exit();
}

// Get total questions
$total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM questions");
$total_stmt->execute();
$total_questions = $total_stmt->get_result()->fetch_assoc()['total'];
$total_stmt->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soalan <?php echo $question_number; ?> - Sistem Kuiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .option-card {
            margin-bottom: 15px;
            padding: 20px;
            border-radius: 15px;
            background: rgba(248, 249, 250, 0.9);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        .option-card:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .option-card.selected {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }
        .option-card.correct {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            animation: pulse 0.5s;
        }
        .option-card.wrong {
            background: linear-gradient(45deg, #dc3545, #e83e8c);
            color: white;
            animation: shake 0.5s;
        }
        .option-letter {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.2);
            text-align: center;
            line-height: 40px;
            font-weight: bold;
            margin-right: 15px;
        }
        .option-card.selected .option-letter,
        .option-card.correct .option-letter,
        .option-card.wrong .option-letter {
            background: rgba(255, 255, 255, 0.3);
        }
        .timer {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            text-align: center;
            margin: 20px 0;
        }
        .ranking-card {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 10px;
            color: #333;
        }
        .ranking-card.first {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
        }
        .ranking-card.second {
            background: linear-gradient(45deg, #c0c0c0, #e8e8e8);
        }
        .ranking-card.third {
            background: linear-gradient(45deg, #cd7f32, #daa520);
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .progress {
            height: 8px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.3);
        }
        .progress-bar {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Header Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-0">Soalan <?php echo $question_number; ?> daripada <?php echo $total_questions; ?></h5>
                                <small class="text-muted"><i class="bi bi-person-fill"></i> <?php echo htmlspecialchars($_SESSION['nickname']); ?></small>
                            </div>
                            <div class="text-end">
                                <div class="timer" id="timer">00:00</div>
                            </div>
                        </div>
                        
                        <?php
                        $progress = ($question_number / $total_questions) * 100;
                        ?>
                        
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                    </div>
                </div>

                <?php if ($show_results): ?>
                    <!-- Results Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <?php if ($is_correct): ?>
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle-fill fs-1 text-success"></i>
                                        <h4 class="mt-2">Jawapan Betul! 🎉</h4>
                                        <p>Masa respons: <strong><?php echo number_format($response_time, 2); ?> saat</strong></p>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-danger">
                                        <i class="bi bi-x-circle-fill fs-1 text-danger"></i>
                                        <h4 class="mt-2">Jawapan Salah ❌</h4>
                                        <p>Masa respons: <strong><?php echo number_format($response_time, 2); ?> saat</strong></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($top_players)): ?>
                                <h5 class="text-center mb-3"><i class="bi bi-trophy-fill text-warning"></i> Top 3 Terpantas</h5>
                                <div class="row">
                                    <?php foreach ($top_players as $index => $player): ?>
                                        <?php 
                                        $rank_class = ['first', 'second', 'third'][$index] ?? '';
                                        $rank_icon = ['🥇', '🥈', '🥉'][$index] ?? '';
                                        ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="ranking-card <?php echo $rank_class; ?>">
                                                <div class="text-center">
                                                    <div class="fs-4"><?php echo $rank_icon; ?></div>
                                                    <strong><?php echo htmlspecialchars($player['nickname']); ?></strong>
                                                    <div class="small"><?php echo number_format($player['response_time'], 2); ?>s</div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-center mt-4">
                                <form method="POST">
                                    <button type="submit" name="next" class="btn btn-primary btn-lg">
                                        <i class="bi bi-arrow-right-circle-fill me-2"></i>Soalan Seterusnya
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Question Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4 class="text-center mb-4"><?php echo htmlspecialchars($question['question']); ?></h4>
                            
                            <form method="POST" id="questionForm">
                                <input type="hidden" name="option" id="selectedOption">
                                <input type="hidden" name="response_time" id="responseTime">
                                
                                <div class="mb-4">
                                    <div class="option-card" data-option="a">
                                        <span class="option-letter">A</span>
                                        <?php echo htmlspecialchars($question['option_a']); ?>
                                    </div>
                                    <div class="option-card" data-option="b">
                                        <span class="option-letter">B</span>
                                        <?php echo htmlspecialchars($question['option_b']); ?>
                                    </div>
                                    <div class="option-card" data-option="c">
                                        <span class="option-letter">C</span>
                                        <?php echo htmlspecialchars($question['option_c']); ?>
                                    </div>
                                    <div class="option-card" data-option="d">
                                        <span class="option-letter">D</span>
                                        <?php echo htmlspecialchars($question['option_d']); ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body text-center">
                        <a href="winners.php" class="btn btn-outline-light me-2">
                            <i class="bi bi-trophy-fill me-1"></i>Senarai Pemenang
                        </a>
                        <a href="logout.php" class="btn btn-outline-light">
                            <i class="bi bi-box-arrow-right me-1"></i>Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let startTime = Date.now();
        let timerInterval;
        let questionStartTime = Date.now();
        
        function updateTimer() {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            document.getElementById('timer').textContent = 
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        }
        
        <?php if (!$show_results): ?>
        // Start timer for questions
        timerInterval = setInterval(updateTimer, 1000);
        
        // Handle option selection
        document.querySelectorAll('.option-card').forEach(function(card) {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Get the option value
                const option = this.getAttribute('data-option');
                const responseTime = (Date.now() - questionStartTime) / 1000;
                
                // Set hidden form values
                document.getElementById('selectedOption').value = option;
                document.getElementById('responseTime').value = responseTime;
                
                // Stop timer
                clearInterval(timerInterval);
                
                // Show visual feedback
                setTimeout(() => {
                    // Add answer name to form and submit
                    const form = document.getElementById('questionForm');
                    const answerInput = document.createElement('input');
                    answerInput.type = 'hidden';
                    answerInput.name = 'answer';
                    answerInput.value = '1';
                    form.appendChild(answerInput);
                    
                    form.submit();
                }, 500);
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key >= '1' && e.key <= '4') {
                const options = ['a', 'b', 'c', 'd'];
                const optionIndex = parseInt(e.key) - 1;
                if (optionIndex < options.length) {
                    const card = document.querySelector(`[data-option="${options[optionIndex]}"]`);
                    if (card) card.click();
                }
            }
        });
        <?php endif; ?>
        
        // Auto-scroll to top on page load
        window.addEventListener('load', function() {
            window.scrollTo(0, 0);
        });
    </script>
</body>
</html>
