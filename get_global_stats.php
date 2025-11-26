<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Get role parameter
$role = $_GET['role'] ?? null;
$user_id = $_GET['user_id'] ?? null;

// Get statistics based on role
if ($role && in_array($role, ['guru', 'siswa'])) {
    // Role-specific statistics
    $total_articles = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel a JOIN users u ON a.author_id = u.id WHERE u.role = '$role'"))['total'] ?? 0;
    $total_published = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel a JOIN users u ON a.author_id = u.id WHERE a.status='published' AND u.role = '$role'"))['total'] ?? 0;
    $total_draft = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel a JOIN users u ON a.author_id = u.id WHERE a.status='draft' AND u.role = '$role'"))['total'] ?? 0;
} elseif ($user_id) {
    // User-specific statistics
    $total_articles = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel WHERE author_id = $user_id"))['total'] ?? 0;
    $total_published = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel WHERE status='published' AND author_id = $user_id"))['total'] ?? 0;
    $total_draft = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel WHERE status='draft' AND author_id = $user_id"))['total'] ?? 0;
} else {
    // Global statistics (for admin)
    $total_articles = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel"))['total'] ?? 0;
    $total_published = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel WHERE status='published'"))['total'] ?? 0;
    $total_draft = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel WHERE status='draft'"))['total'] ?? 0;
}

// Global statistics (always global)
$total_likes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM likes"))['total'] ?? 0;
$total_comments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM comments"))['total'] ?? 0;
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'] ?? 0;

echo json_encode([
    'success' => true,
    'likes' => $total_likes,
    'comments' => $total_comments,
    'articles' => $total_articles,
    'published' => $total_published,
    'draft' => $total_draft,
    'users' => $total_users
]);
?>