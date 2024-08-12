<?php
session_start();
include 'db.php';

// Fetch top 10 fastest players based on the number of correct fastest answers
$sql = "SELECT nickname, fastest_answers FROM winners ORDER BY fastest_answers DESC LIMIT 10";
$result = $conn->query($sql);

if (!$result) {
    die("Error fetching top 10 fastest players: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Top 10 Fastest Players</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Top 10 Fastest Players</h1>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Nickname</th>
                <th>Fastest Correct Answers</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rank = 1;
            while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo htmlspecialchars($row['nickname']); ?></td>
                    <td><?php echo htmlspecialchars($row['fastest_answers']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Link to start a new game or logout -->
    <a href="login.php">Start New Game</a> | <a href="logout.php">Logout</a>
</body>
</html>
