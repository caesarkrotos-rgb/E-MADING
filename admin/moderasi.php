<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';



// Ensure artikel table has rejected status
$check_status = mysqli_query($conn, "SHOW COLUMNS FROM artikel LIKE 'status'");
if ($check_status && mysqli_num_rows($check_status) > 0) {
    $status_info = mysqli_fetch_assoc($check_status);
    if (strpos($status_info['Type'], 'rejected') === false) {
        mysqli_query($conn, "ALTER TABLE artikel MODIFY status ENUM('draft', 'published', 'rejected') DEFAULT 'draft'");
    }
}

// Handle publish/reject/delete
if (isset($_POST['action'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $reason = $_POST['reason'] ?? '';
    
    if ($action == 'publish') {
        mysqli_query($conn, "UPDATE artikel SET status = 'published' WHERE id = $id");
        
        $success = "Artikel berhasil dipublikasi!";
    } elseif ($action == 'reject') {
        mysqli_query($conn, "UPDATE artikel SET status = 'rejected' WHERE id = $id");
        
        $success = "Artikel berhasil ditolak!";
    } elseif ($action == 'delete') {
        // Delete related data first
        mysqli_query($conn, "DELETE FROM likes WHERE artikel_id = $id");
        mysqli_query($conn, "DELETE FROM comments WHERE artikel_id = $id");
        mysqli_query($conn, "DELETE FROM shares WHERE artikel_id = $id");
        
        // Delete the article
        mysqli_query($conn, "DELETE FROM artikel WHERE id = $id");
        
        $success = "Artikel berhasil dihapus!";
    }
}

// Get both draft and rejected articles for moderation
$draft_articles = mysqli_query($conn, "SELECT a.*, u.nama FROM artikel a JOIN users u ON a.author_id = u.id WHERE a.status IN ('draft', 'rejected') ORDER BY a.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderasi Artikel - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 0; }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links a { color: white; text-decoration: none; padding: 10px 20px; margin: 0 5px; border-radius: 25px; transition: all 0.3s ease; }
        .nav-links a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .page-header { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px; text-align: center; }
        .articles-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px; }
        .article-card { background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .article-header { padding: 20px; border-bottom: 1px solid #eee; }
        .article-title { font-size: 1.3rem; font-weight: 600; color: #333; margin-bottom: 10px; }
        .article-meta { display: flex; align-items: center; gap: 15px; font-size: 0.9rem; color: #666; }
        .article-content { padding: 20px; }
        .article-excerpt { color: #555; line-height: 1.6; margin-bottom: 20px; }
        .article-actions { display: flex; gap: 10px; padding-top: 15px; border-top: 1px solid #eee; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn:hover { transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="dashboard.php" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <a href="../logout.php" style="background: #dc3545; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <div class="page-header">
            <h1><i class="fas fa-check-circle"></i> Moderasi Artikel</h1>
            <p>Verifikasi dan publikasi artikel dari guru dan siswa</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="articles-grid">
            <?php if (mysqli_num_rows($draft_articles) > 0): ?>
                <?php while ($art = mysqli_fetch_assoc($draft_articles)): ?>
                <div class="article-card">
                    <div class="article-header">
                        <?php if ($art['foto']): ?>
                            <img src="../uploads/<?php echo $art['foto']; ?>" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 15px;">
                        <?php endif; ?>
                        <h3 class="article-title"><?php echo htmlspecialchars($art['judul']); ?></h3>
                        <div class="article-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($art['nama']); ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($art['created_at'])); ?></span>
                            <?php if ($art['kategori']): ?>
                                <span><i class="fas fa-tag"></i> <?php echo $art['kategori']; ?></span>
                            <?php endif; ?>
                            <span style="padding: 4px 8px; border-radius: 10px; font-size: 0.8rem; font-weight: 600; <?php echo $art['status'] == 'rejected' ? 'background: #f8d7da; color: #721c24;' : 'background: #fff3cd; color: #856404;'; ?>">
                                <?php echo $art['status'] == 'rejected' ? 'Ditolak' : 'Menunggu Review'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="article-content">
                        <p class="article-excerpt"><?php echo htmlspecialchars(substr($art['konten'], 0, 200)) . '...'; ?></p>
                        
                        <div class="article-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $art['id']; ?>">
                                <button type="submit" name="action" value="publish" class="btn btn-success">
                                    <i class="fas fa-check"></i> Publikasi
                                </button>
                            </form>
                            <button onclick="showRejectModal(<?php echo $art['id']; ?>)" class="btn btn-danger">
                                <i class="fas fa-times"></i> Tolak
                            </button>
                            <button onclick="viewArticle(<?php echo $art['id']; ?>)" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Lihat
                            </button>
                            <button onclick="deleteArticle(<?php echo $art['id']; ?>, '<?php echo addslashes($art['judul']); ?>')" class="btn" style="background: #dc3545; color: white;">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>Tidak ada artikel draft yang perlu dimoderasi.</p>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="max-width: 500px; margin: 100px auto; background: white; border-radius: 15px; padding: 30px;">
            <h3 style="margin-bottom: 20px; color: #dc3545;"><i class="fas fa-times-circle"></i> Tolak Artikel</h3>
            <form method="POST" id="rejectForm">
                <input type="hidden" name="id" id="rejectArticleId">
                <input type="hidden" name="action" value="reject">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Alasan Penolakan:</label>
                    <textarea name="reason" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; min-height: 100px; resize: vertical;" placeholder="Berikan alasan mengapa artikel ini ditolak..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeRejectModal()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        Batal
                    </button>
                    <button type="submit" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fas fa-times"></i> Tolak Artikel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showRejectModal(articleId) {
            document.getElementById('rejectArticleId').value = articleId;
            document.getElementById('rejectModal').style.display = 'block';
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }
        
        function viewArticle(id) {
            window.open('../artikel_pengunjung.php?id=' + id, '_blank');
        }
        
        function deleteArticle(id, title) {
            if (confirm('Yakin ingin menghapus artikel "' + title + '"?\n\nArtikel yang dihapus tidak dapat dikembalikan!')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    </script>
</body>
</html>