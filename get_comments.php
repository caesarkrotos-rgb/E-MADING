<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Ensure comments table exists with correct structure
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_artikel_id (artikel_id),
    INDEX idx_user_id (user_id)
)");

$artikel_id = (int)$_GET['artikel_id'];

// Get comments for article (show more comments)
$comments = mysqli_query($conn, "SELECT c.*, u.nama FROM comments c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.artikel_id = $artikel_id 
    ORDER BY c.created_at DESC LIMIT 50");

if (!$comments) {
    echo json_encode([
        'comments' => [],
        'total' => 0,
        'unique_commenters' => 0,
        'error' => mysqli_error($conn)
    ]);
    exit;
}

// Get total comment count
$count_query = "SELECT COUNT(*) as total FROM comments WHERE artikel_id = $artikel_id";
$count_result = mysqli_query($conn, $count_query);
$total_count = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;

// Get unique commenter count
$unique_query = "SELECT COUNT(DISTINCT user_id) as unique_count FROM comments WHERE artikel_id = $artikel_id";
$unique_result = mysqli_query($conn, $unique_query);
$unique_commenters = $unique_result ? mysqli_fetch_assoc($unique_result)['unique_count'] : 0;

// Debug: Log the queries and results
error_log("Article ID: $artikel_id, Total: $total_count, Unique: $unique_commenters");

$comments_list = [];
while ($comment = mysqli_fetch_assoc($comments)) {
    $can_delete = false;
    if (isset($_SESSION['user_id'])) {
        // Admin can delete any comment, users can delete their own
        $can_delete = ($_SESSION['role'] == 'admin') || ($_SESSION['user_id'] == $comment['user_id']);
    }
    
    $comments_list[] = [
        'id' => $comment['id'],
        'user_id' => $comment['user_id'],
        'nama' => $comment['nama'] ?: 'User Tidak Dikenal',
        'comment' => $comment['comment'],
        'created_at' => date('d M Y H:i', strtotime($comment['created_at'])),
        'can_delete' => $can_delete
    ];
}

echo json_encode([
    'comments' => $comments_list,
    'total' => $total_count,
    'unique_commenters' => $unique_commenters
]);
?>