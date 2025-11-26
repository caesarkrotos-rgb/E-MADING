<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Create comments table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if (!isset($_POST['artikel_id']) || !isset($_POST['comment'])) {
    echo json_encode(['success' => false, 'error' => 'Missing fields']);
    exit;
}

$artikel_id = (int)$_POST['artikel_id'];
$user_id = (int)$_SESSION['user_id'];
$comment = mysqli_real_escape_string($conn, trim($_POST['comment']));

if (empty($comment)) {
    echo json_encode(['success' => false, 'error' => 'Comment empty']);
    exit;
}

// Insert comment
$insert = mysqli_query($conn, "INSERT INTO comments (user_id, artikel_id, comment) VALUES ($user_id, $artikel_id, '$comment')");

if (!$insert) {
    echo json_encode(['success' => false, 'error' => 'Insert failed: ' . mysqli_error($conn)]);
    exit;
}

// Get user name
$user_result = mysqli_query($conn, "SELECT nama FROM users WHERE id = $user_id");
if (!$user_result) {
    echo json_encode(['success' => false, 'error' => 'User query failed: ' . mysqli_error($conn)]);
    exit;
}

$user_data = mysqli_fetch_assoc($user_result);
if (!$user_data) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'nama' => $user_data['nama'],
    'comment' => $comment,
    'created_at' => date('d M Y H:i')
]);
?>