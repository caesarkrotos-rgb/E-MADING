<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';

// Get all comments with article and user info
$comments = mysqli_query($conn, "SELECT c.*, u.nama as user_nama, a.judul as artikel_judul 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    JOIN artikel a ON c.artikel_id = a.id 
    ORDER BY c.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Komentar - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }

        .header { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .comments-section { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .comment-item { border: 1px solid #e9ecef; border-radius: 10px; padding: 20px; margin-bottom: 15px; transition: all 0.3s ease; }
        .comment-item:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .comment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .comment-meta { font-size: 0.9rem; color: #666; }
        .comment-text { margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .delete-btn { background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; transition: all 0.3s ease; }
        .delete-btn:hover { background: #c82333; transform: translateY(-2px); }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Kelola Komentar</h1>
            <p>Moderasi dan kelola semua komentar di sistem</p>
        </div>
        
        <div class="comments-section">
            <h3 style="margin-bottom: 20px; color: #333;">
                <i class="fas fa-comments"></i> Semua Komentar
            </h3>
            
            <?php if ($comments && mysqli_num_rows($comments) > 0): ?>
                <?php while($comment = mysqli_fetch_assoc($comments)): ?>
                <div class="comment-item" id="comment-<?php echo $comment['id']; ?>">
                    <div class="comment-header">
                        <div>
                            <strong><?php echo htmlspecialchars($comment['user_nama']); ?></strong>
                            <span class="comment-meta">
                                pada artikel "<strong><?php echo htmlspecialchars($comment['artikel_judul']); ?></strong>"
                            </span>
                        </div>
                        <div class="comment-meta">
                            <?php echo date('d M Y H:i', strtotime($comment['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="comment-text">
                        <?php echo htmlspecialchars($comment['comment']); ?>
                    </div>
                    
                    <div style="text-align: right;">
                        <button onclick="deleteComment(<?php echo $comment['id']; ?>)" class="delete-btn">
                            <i class="fas fa-trash"></i> Hapus Komentar
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>Belum ada komentar yang tersedia.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function deleteComment(commentId) {
            if (confirm('Apakah Anda yakin ingin menghapus komentar ini?')) {
                fetch('../delete_comment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'comment_id=' + commentId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('comment-' + commentId).remove();
                        alert('Komentar berhasil dihapus!');
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus komentar.');
                });
            }
        }
    </script>
</body>
</html>