<?php
session_start();
include '../includes/db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Handle poll creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_poll'])) {
    $question = $_POST['question'];
    
    // Insert new poll into the database
    $stmt = $pdo->prepare("INSERT INTO polls (question) VALUES (?)");
    $stmt->execute([$question]);

    // Get the ID of the newly created poll
    $poll_id = $pdo->lastInsertId();

    // Insert options for the poll
    if (isset($_POST['options']) && !empty($_POST['options'])) {
        foreach ($_POST['options'] as $option) {
            if (!empty($option)) { // Only insert non-empty options
                $stmt = $pdo->prepare("INSERT INTO options (poll_id, option_text) VALUES (?, ?)");
                $stmt->execute([$poll_id, $option]);
            }
        }
    }
}

// Handle poll deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $poll_id = $_GET['id'];
    
    // Delete poll from the database
    $stmt = $pdo->prepare("DELETE FROM polls WHERE id = ?");
    $stmt->execute([$poll_id]);
}

// Fetch existing polls with options
$stmt = $pdo->query("SELECT * FROM polls");
$polls = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles.css">
    <title>Admin Dashboard</title>
</head>
<body>
    <div class="dashboard-container">
        <h2>Admin Dashboard</h2>
        
        <!-- Create New Poll Form -->
        <h3>Create New Poll</h3>
        <form method="POST" action="">
            <input type="text" name="question" placeholder="Poll Question" required>
            <h4>Options:</h4>
            <input type="text" name="options[]" placeholder="Option 1" required>
            <input type="text" name="options[]" placeholder="Option 2" required>
            <input type="text" name="options[]" placeholder="Option 3">
            <input type="text" name="options[]" placeholder="Option 4">
            <button type="submit" name="create_poll">Create Poll</button>
        </form>

        <!-- Display Existing Polls -->
        <h3>Existing Polls</h3>
        <?php if (empty($polls)): ?>
            <p>No polls available.</p>
        <?php else: ?>
            <?php foreach ($polls as $poll): ?>
                <div class="poll">
                    <h4><?= htmlspecialchars($poll['question']); ?></h4>
                    <h5>Options:</h5>
                    <?php
                    // Fetch options for this poll
                    $options_stmt = $pdo->prepare("SELECT * FROM options WHERE poll_id = ?");
                    $options_stmt->execute([$poll['id']]);
                    $options = $options_stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <ul>
                        <?php foreach ($options as $option): ?>
                            <li><?= htmlspecialchars($option['option_text']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="update_poll.php?id=<?= $poll['id']; ?>">Edit</a> | 
                    <a href="?action=delete&id=<?= $poll['id']; ?>" onclick="return confirm('Are you sure you want to delete this poll?');">Delete</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
