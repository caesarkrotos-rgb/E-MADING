<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['guru', 'siswa', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['article_id'])) {
    $article_id = (int)$_POST['article_id'];
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Check if user owns the article or is admin
    $check_query = "SELECT author_id FROM artikel WHERE id = $article_id";
    $result = mysqli_query($conn, $check_query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $article = mysqli_fetch_assoc($result);
        
        if ($role === 'admin' || $article['author_id'] == $user_id) {
            // Delete related data first
            mysqli_query($conn, "DELETE FROM likes WHERE artikel_id = $article_id");
            mysqli_query($conn, "DELETE FROM comments WHERE artikel_id = $article_id");
            mysqli_query($conn, "DELETE FROM shares WHERE artikel_id = $article_id");
            
            // Delete the article
            $delete_query = "DELETE FROM artikel WHERE id = $article_id";
            if (mysqli_query($conn, $delete_query)) {
                echo json_encode(['success' => true, 'message' => 'Artikel berhasil dihapus']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus artikel']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Artikel tidak ditemukan']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>