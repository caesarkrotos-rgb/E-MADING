<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['guru', 'siswa'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Auto-create shares table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    share_type ENUM('facebook', 'twitter', 'whatsapp', 'copy') DEFAULT 'copy',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$artikel_id = (int)$_POST['artikel_id'];
$user_id = (int)$_SESSION['user_id'];
$share_type = mysqli_real_escape_string($conn, $_POST['share_type']);

// Insert share record
$insert = mysqli_query($conn, "INSERT INTO shares (user_id, artikel_id, share_type) VALUES ($user_id, $artikel_id, '$share_type')");

if ($insert) {
    // Get total shares for this article
    $total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM shares WHERE artikel_id = $artikel_id");
    $total = mysqli_fetch_assoc($total_result)['total'];
    
    echo json_encode([
        'success' => true,
        'share_count' => $total
    ]);
} else {
    echo json_encode(['error' => 'Failed to record share']);
}
?>