<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nickname']) && !empty($_POST['nickname'])) {
        $_SESSION['nickname'] = htmlspecialchars($_POST['nickname']);
        $_SESSION['current_question'] = 1; // Start with the first question
        header("Location: question.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <form method="POST" action="login.php">
        <label for="nickname">Enter your nickname:</label>
        <input type="text" id="nickname" name="nickname" required>
        <button type="submit">Start Game</button>
    </form>
</body>
</html>
