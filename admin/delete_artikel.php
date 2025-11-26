<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../config.php';

if (isset($_GET['id'])) {
    $artikel_id = (int)$_GET['id'];
    
    if ($artikel_id > 0) {
        // Delete related data first (ignore errors if tables don't exist)
        @mysqli_query($conn, "DELETE FROM likes WHERE artikel_id = $artikel_id");
        @mysqli_query($conn, "DELETE FROM comments WHERE artikel_id = $artikel_id");
        @mysqli_query($conn, "DELETE FROM shares WHERE artikel_id = $artikel_id");
        
        // Delete the article
        $result = mysqli_query($conn, "DELETE FROM artikel WHERE id = $artikel_id");
        
        if ($result && mysqli_affected_rows($conn) > 0) {
            header('Location: kelola_artikel.php?success=deleted');
        } else {
            header('Location: kelola_artikel.php?error=delete_failed');
        }
    } else {
        header('Location: kelola_artikel.php?error=invalid_id');
    }
} else {
    header('Location: kelola_artikel.php');
}
exit();
?>