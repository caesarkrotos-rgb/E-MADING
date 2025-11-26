<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'guru', 'siswa'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$comment_id = (int)$_POST['comment_id'];
$user_id = $_SESSION['user_id'];

// Admin can delete any comment, others can only delete their own
if ($_SESSION['role'] == 'admin') {
    $check = mysqli_query($conn, "SELECT id FROM comments WHERE id = $comment_id");
    $delete_query = "DELETE FROM comments WHERE id = $comment_id";
} else {
    $check = mysqli_query($conn, "SELECT id FROM comments WHERE id = $comment_id AND user_id = $user_id");
    $delete_query = "DELETE FROM comments WHERE id = $comment_id AND user_id = $user_id";
}

if (!$check || mysqli_num_rows($check) == 0) {
    echo json_encode(['success' => false, 'message' => 'Comment not found or unauthorized']);
    exit;
}

// Delete comment
$delete = mysqli_query($conn, $delete_query);

if ($delete) {
    echo json_encode(['success' => true, 'message' => 'Comment deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete comment: ' . mysqli_error($conn)]);
}
?>