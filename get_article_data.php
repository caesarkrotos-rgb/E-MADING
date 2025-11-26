<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

$artikel_id = (int)$_GET['id'];

// Get article data
$article_query = "SELECT a.*, u.nama FROM artikel a LEFT JOIN users u ON a.author_id = u.id WHERE a.id = $artikel_id";
$article_result = mysqli_query($conn, $article_query);

if (!$article_result || mysqli_num_rows($article_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Article not found']);
    exit;
}

$article = mysqli_fetch_assoc($article_result);

// Ensure likes table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, artikel_id)
)");

// Get like count
$like_count = 0;
$user_liked = 0;

$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM likes WHERE artikel_id = $artikel_id");
if ($count_query) {
    $like_count = mysqli_fetch_assoc($count_query)['total'];
}

// Check if current user liked this article
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_like_query = mysqli_query($conn, "SELECT COUNT(*) as liked FROM likes WHERE artikel_id = $artikel_id AND user_id = $user_id");
    if ($user_like_query) {
        $user_liked = mysqli_fetch_assoc($user_like_query)['liked'];
    }
}

// Get comment count
$comment_count = 0;
$comment_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM comments WHERE artikel_id = $artikel_id");
if ($comment_query) {
    $comment_count = mysqli_fetch_assoc($comment_query)['total'];
}

// Get image path
function getImagePath($filename) {
    if (!$filename) return '';
    $paths = ['uploads/' . $filename, 'uploads/gallery/' . $filename, 'uploads/profiles/' . $filename];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    return '';
}

echo json_encode([
    'success' => true,
    'id' => $article['id'],
    'title' => $article['judul'] ?? '',
    'content' => $article['konten'] ?? '',
    'image' => $article['foto'] ?? '',
    'author' => $article['nama'] ?? 'Unknown',
    'date' => date('d M Y', strtotime($article['created_at'])),
    'like_count' => (int)$like_count,
    'comment_count' => (int)$comment_count,
    'user_liked' => (int)$user_liked > 0
]);
?>