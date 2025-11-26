<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../config.php';

// Cari semua user dengan role admin atau nama Administrator
$admin_query = "SELECT id FROM users WHERE role = 'admin' OR nama LIKE '%Administrator%'";
$admin_result = mysqli_query($conn, $admin_query);

$admin_ids = [];
while ($admin = mysqli_fetch_assoc($admin_result)) {
    $admin_ids[] = $admin['id'];
}

if (!empty($admin_ids)) {
    $admin_ids_str = implode(',', $admin_ids);
    
    // Get artikel yang akan dihapus
    $artikel_query = "SELECT id FROM artikel WHERE author_id IN ($admin_ids_str)";
    $artikel_result = mysqli_query($conn, $artikel_query);
    
    $artikel_ids = [];
    while ($artikel = mysqli_fetch_assoc($artikel_result)) {
        $artikel_ids[] = $artikel['id'];
    }
    
    if (!empty($artikel_ids)) {
        $artikel_ids_str = implode(',', $artikel_ids);
        
        // Hapus data terkait
        mysqli_query($conn, "DELETE FROM likes WHERE artikel_id IN ($artikel_ids_str)");
        mysqli_query($conn, "DELETE FROM comments WHERE artikel_id IN ($artikel_ids_str)");
        mysqli_query($conn, "DELETE FROM shares WHERE artikel_id IN ($artikel_ids_str)");
        
        // Hapus artikel
        $delete_result = mysqli_query($conn, "DELETE FROM artikel WHERE author_id IN ($admin_ids_str)");
        
        if ($delete_result) {
            $deleted_count = mysqli_affected_rows($conn);
            echo json_encode(['success' => true, 'message' => "Berhasil menghapus $deleted_count artikel Administrator"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus artikel']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Tidak ada artikel Administrator yang ditemukan']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Tidak ada user Administrator yang ditemukan']);
}
?>