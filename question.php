<?php
// Enable error reporting for debugging
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['nickname'])) {
    header("Location: login.php");
    exit();
}

// Initialize current question if not set
if (!isset($_SESSION['current_question'])) {
    $_SESSION['current_question'] = 1; // Start from the first question
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['answer'])) {
        $nickname = $_SESSION['nickname'];
        $chosen_option = $_POST['option'];
        $response_time = microtime(true) - $_SESSION['start_time']; // Calculate response time

        // Fetch the correct answer for the current question
        $stmt = $conn->prepare("SELECT correct_option FROM questions WHERE id = ?");
        if (!$stmt) {
            die("Error preparing SQL statement: " . $conn->error);
        }

        $stmt->bind_param("i", $_SESSION['current_question']);
        $stmt->execute();
        $stmt->bind_result($correct_option);
        $stmt->fetch();
        $stmt->close();

        // Check if the chosen option is correct
        $is_correct = ($chosen_option === $correct_option);

        // Insert or update response with correctness
        $stmt = $conn->prepare("INSERT INTO responses (nickname, question_id, chosen_option, response_time, is_correct) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE chosen_option = VALUES(chosen_option), response_time = VALUES(response_time), is_correct = VALUES(is_correct)");
        if (!$stmt) {
            die("Error preparing SQL statement: " . $conn->error);
        }

        $stmt->bind_param("sissi", $nickname, $_SESSION['current_question'], $chosen_option, $response_time, $is_correct);
        $stmt->execute();
        $stmt->close();

        // Get the fastest correct response time for the current question
        $stmt = $conn->prepare("
            SELECT nickname, response_time
            FROM responses
            WHERE question_id = ? AND is_correct = TRUE
            ORDER BY response_time ASC
            LIMIT 1
        ");
        if (!$stmt) {
            die("Error preparing SQL statement: " . $conn->error);
        }

        $stmt->bind_param("i", $_SESSION['current_question']);
        $stmt->execute();
        $fastest = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Update fastest answer count for the player
        if ($is_correct && $nickname == $fastest['nickname']) {
            $stmt = $conn->prepare("INSERT INTO winners (nickname, fastest_answers) VALUES (?, 1)
                ON DUPLICATE KEY UPDATE fastest_answers = fastest_answers + 1");
            if (!$stmt) {
                die("Error preparing SQL statement: " . $conn->error);
            }

            $stmt->bind_param("s", $nickname);
            $stmt->execute();
            $stmt->close();
        }

        // Move to the next question or show results
        $stmt = $conn->prepare("SELECT COUNT(*) FROM questions WHERE id > ?");
        if (!$stmt) {
            die("Error preparing SQL statement: " . $conn->error);
        }

        $stmt->bind_param("i", $_SESSION['current_question']);
        $stmt->execute();
        $stmt->bind_result($remaining_questions);
        $stmt->fetch();
        $stmt->close();

        if ($remaining_questions > 0) {
            // Question answered, show results
            $show_results = true;
        } else {
            // If no more questions, redirect to results page
            header("Location: winners.php");
            exit();
        }
    } elseif (isset($_POST['next'])) {
        $_SESSION['current_question']++; // Move to the next question
        $show_results = false; // Hide results when moving to the next question
    }
} else {
    // Initialize results display flag
    $show_results = false;
}

// Fetch the current question
$question_number = $_SESSION['current_question'];
$sql = "SELECT * FROM questions WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error preparing SQL statement: " . $conn->error);
}

$stmt->bind_param("i", $question_number);
$stmt->execute();
$question = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$question) {
    header("Location: winners.php");
    exit();
}

// Set the start time for response measurement
$_SESSION['start_time'] = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Question <?php echo htmlspecialchars($question_number); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Question <?php echo htmlspecialchars($question_number); ?></h1>

    <?php if ($show_results): ?>
        <h2>Results for Question <?php echo htmlspecialchars($question_number); ?></h2>
        <p>Your response time: <?php echo number_format($response_time, 2); ?> seconds</p>
        <p>Fastest correct response time: <?php echo number_format($fastest['response_time'], 2); ?> seconds</p>
        <p>Fastest correct responder: <?php echo htmlspecialchars($fastest['nickname']); ?></p>
        <?php if ($is_correct && $nickname == $fastest['nickname']): ?>
            <p>Congratulations! You were the fastest to answer this question correctly.</p>
        <?php elseif ($is_correct): ?>
            <p>Your answer was correct, but not the fastest. Try to be quicker next time!</p>
        <?php else: ?>
            <p>Incorrect answer. Try again on the next question.</p>
        <?php endif; ?>
        <form method="POST" action="question.php">
            <button type="submit" name="next">Next Question</button>
        </form>
    <?php else: ?>
        <form method="POST" action="question.php">
            <fieldset>
                <legend><?php echo htmlspecialchars($question['question']); ?></legend>
                <input type="radio" id="a" name="option" value="a" required>
                <label for="a"><?php echo htmlspecialchars($question['option_a']); ?></label><br>
                <input type="radio" id="b" name="option" value="b">
                <label for="b"><?php echo htmlspecialchars($question['option_b']); ?></label><br>
                <input type="radio" id="c" name="option" value="c">
                <label for="c"><?php echo htmlspecialchars($question['option_c']); ?></label><br>
                <input type="radio" id="d" name="option" value="d">
                <label for="d"><?php echo htmlspecialchars($question['option_d']); ?></label><br>
                <button type="submit" name="answer">Submit</button>
            </fieldset>
        </form>
    <?php endif; ?>

    <!-- Logout link >
    <a href="logout.php">Logout</a-->
</body>
</html>
