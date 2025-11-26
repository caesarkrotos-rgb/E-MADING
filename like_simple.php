<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Create likes table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, artikel_id)
)");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if (!isset($_POST['artikel_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing artikel_id']);
    exit;
}

$artikel_id = (int)$_POST['artikel_id'];
$user_id = (int)$_SESSION['user_id'];

// Check if already liked
$check = mysqli_query($conn, "SELECT id FROM likes WHERE user_id = $user_id AND artikel_id = $artikel_id");

if (!$check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_num_rows($check) > 0) {
    // Unlike
    $delete = mysqli_query($conn, "DELETE FROM likes WHERE user_id = $user_id AND artikel_id = $artikel_id");
    if (!$delete) {
        echo json_encode(['success' => false, 'error' => 'Delete failed: ' . mysqli_error($conn)]);
        exit;
    }
    $liked = false;
} else {
    // Like
    $insert = mysqli_query($conn, "INSERT INTO likes (user_id, artikel_id) VALUES ($user_id, $artikel_id)");
    if (!$insert) {
        echo json_encode(['success' => false, 'error' => 'Insert failed: ' . mysqli_error($conn)]);
        exit;
    }
    $liked = true;
}

// Get total
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM likes WHERE artikel_id = $artikel_id");
if (!$total_result) {
    echo json_encode(['success' => false, 'error' => 'Count failed: ' . mysqli_error($conn)]);
    exit;
}
$total = mysqli_fetch_assoc($total_result)['total'];

echo json_encode([
    'success' => true,
    'liked' => $liked,
    'total' => (int)$total
]);
?>