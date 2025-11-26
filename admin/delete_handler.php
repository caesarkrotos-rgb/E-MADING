<?php
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['delete_artikel'])) {
    $artikel_id = (int)$_POST['artikel_id'];
    
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM likes WHERE artikel_id = $artikel_id");
        mysqli_query($conn, "DELETE FROM comments WHERE artikel_id = $artikel_id");
        mysqli_query($conn, "DELETE FROM artikel WHERE id = $artikel_id");
        mysqli_commit($conn);
        header('Location: artikel.php?success=deleted');
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header('Location: artikel.php?error=delete_failed');
    }
}

if (isset($_POST['change_status'])) {
    $artikel_id = (int)$_POST['artikel_id'];
    $status = $_POST['status'];
    
    if (in_array($status, ['published', 'draft'])) {
        mysqli_query($conn, "UPDATE artikel SET status = '$status' WHERE id = $artikel_id");
        header('Location: artikel.php?success=status_changed');
    }
}
?>