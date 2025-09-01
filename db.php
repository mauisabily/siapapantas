<?php
$servername = "localhost";
$username = "games";
$password = "P55w0rd";
$dbname = "games";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to create randomized question sequence for a player
function createRandomizedQuestionSequence($conn, $player_id) {
    // Check if sequence already exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM player_question_sequence WHERE player_id = ?");
    $check_stmt->bind_param("i", $player_id);
    $check_stmt->execute();
    $count = 0;
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();
    
    if ($count > 0) {
        return; // Sequence already exists
    }
    
    // Get all question IDs
    $questions_result = $conn->query("SELECT id FROM questions ORDER BY id");
    $question_ids = [];
    while ($row = $questions_result->fetch_assoc()) {
        $question_ids[] = $row['id'];
    }
    
    // Shuffle the question IDs
    shuffle($question_ids);
    
    // Insert randomized sequence
    $insert_stmt = $conn->prepare("INSERT INTO player_question_sequence (player_id, question_id, sequence_order) VALUES (?, ?, ?)");
    foreach ($question_ids as $order => $question_id) {
        $sequence_order = $order + 1; // Start from 1
        $insert_stmt->bind_param("iii", $player_id, $question_id, $sequence_order);
        $insert_stmt->execute();
    }
    $insert_stmt->close();
}

// Function to get question ID by sequence order for a player
function getQuestionBySequence($conn, $player_id, $sequence_order) {
    $stmt = $conn->prepare("SELECT question_id FROM player_question_sequence WHERE player_id = ? AND sequence_order = ?");
    $stmt->bind_param("ii", $player_id, $sequence_order);
    $stmt->execute();
    $question_id = null;
    $stmt->bind_result($question_id);
    $stmt->fetch();
    $stmt->close();
    return $question_id;
}

// Function to get total questions count for a player
function getTotalQuestionsForPlayer($conn, $player_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM player_question_sequence WHERE player_id = ?");
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    $count = 0;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}
?>
