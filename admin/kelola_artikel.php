<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../config.php';

// Create tables if not exist
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    artikel_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    artikel_id INT,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    artikel_id INT,
    share_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle actions
if ($_POST['action'] ?? '' == 'update_status') {
    $artikel_id = $_POST['artikel_id'];
    $status = $_POST['status'];
    mysqli_query($conn, "UPDATE artikel SET status = '$status' WHERE id = $artikel_id");
    header('Location: kelola_artikel.php?success=status_updated');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'delete_artikel') {
    $artikel_id = (int)$_POST['artikel_id'];
    
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
    exit;
}

// Get articles with search
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$where_conditions = [];

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "(a.judul LIKE '%$search%' OR a.konten LIKE '%$search%')";
}

if (!empty($status_filter)) {
    $where_conditions[] = "a.status = '$status_filter'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$articles = mysqli_query($conn, "SELECT a.*, u.nama FROM artikel a LEFT JOIN users u ON a.author_id = u.id $where_clause ORDER BY a.created_at DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Artikel - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 15px 0; box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px; margin: 0 auto; display: flex;
            justify-content: space-between; align-items: center; padding: 0 20px;
        }
        
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links { display: flex; gap: 20px; }
        .nav-links a { color: white; text-decoration: none; padding: 10px 15px; border-radius: 5px; }
        .nav-links a:hover { background: rgba(255,255,255,0.2); }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .page-header {
            background: white; padding: 30px; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;
        }
        
        .filters {
            background: white; padding: 20px; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;
            display: flex; gap: 15px; align-items: end; flex-wrap: wrap;
        }
        
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select {
            padding: 10px; border: 1px solid #ddd; border-radius: 5px;
        }
        
        .btn {
            padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;
            font-weight: 600; text-decoration: none; display: inline-block;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; color: white; }
        
        .articles-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .article-card {
            background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden; transition: all 0.3s ease;
        }
        
        .article-image {
            width: 100%; height: 200px; object-fit: cover; background: #f8f9fa;
            display: flex; align-items: center; justify-content: center;
        }
        
        .article-content { padding: 20px; }
        .article-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 10px; }
        .article-meta { font-size: 0.9rem; color: #666; margin-bottom: 15px; }
        .article-excerpt { color: #555; margin-bottom: 15px; }
        
        .status-badge {
            padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;
        }
        .status-published { background: #d4edda; color: #155724; }
        .status-draft { background: #fff3cd; color: #856404; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .article-actions {
            display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;
        }
        
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1000;
        }
        
        .modal-content {
            background: white; margin: 50px auto; padding: 30px; border-radius: 15px;
            max-width: 500px; position: relative;
        }
        
        .close { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo"><i class="fas fa-cogs"></i> Kelola Artikel</div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-newspaper"></i> Kelola Artikel</h1>
            <p>Mengelola semua artikel dan postingan dalam sistem</p>
            
            <?php if (isset($_GET['success'])): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 8px; margin: 15px 0;">
                    <?php if ($_GET['success'] == 'status_updated'): ?>
                        <i class="fas fa-check"></i> Status artikel berhasil diperbarui!
                    <?php elseif ($_GET['success'] == 'deleted'): ?>
                        <i class="fas fa-trash"></i> Artikel berhasil dihapus!
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 10px 15px; border-radius: 8px; margin: 15px 0;">
                    <?php if ($_GET['error'] == 'delete_failed'): ?>
                        <i class="fas fa-exclamation-triangle"></i> Gagal menghapus artikel!
                    <?php elseif ($_GET['error'] == 'invalid_id'): ?>
                        <i class="fas fa-exclamation-triangle"></i> ID artikel tidak valid!
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="filters">
            <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                <div class="form-group">
                    <label>Cari Artikel:</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Judul atau konten...">
                </div>
                <div class="form-group">
                    <label>Filter Status:</label>
                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="published" <?php echo ($_GET['status'] ?? '') == 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo ($_GET['status'] ?? '') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="rejected" <?php echo ($_GET['status'] ?? '') == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="kelola_artikel.php" class="btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-refresh"></i> Reset
                </a>
            </form>
        </div>

        <div class="articles-grid">
            <?php while($article = mysqli_fetch_assoc($articles)): ?>
            <div class="article-card">
                <?php if (!empty($article['foto'])): ?>
                    <?php 
                    $image_path = null;
                    $paths = ['../uploads/' . $article['foto'], '../uploads/gallery/' . $article['foto']];
                    foreach ($paths as $path) {
                        if (file_exists($path)) {
                            $image_path = $path;
                            break;
                        }
                    }
                    ?>
                    <?php if ($image_path): ?>
                        <img src="<?php echo $image_path; ?>" class="article-image">
                    <?php else: ?>
                        <div class="article-image">
                            <i class="fas fa-image" style="font-size: 3rem; color: #ccc;"></i>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="article-image">
                        <i class="fas fa-newspaper" style="font-size: 3rem; color: #ccc;"></i>
                    </div>
                <?php endif; ?>
                
                <div class="article-content">
                    <h3 class="article-title"><?php echo htmlspecialchars($article['judul']); ?></h3>
                    
                    <div class="article-meta">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($article['nama'] ?? 'Unknown'); ?> |
                        <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($article['created_at'])); ?>
                        <?php if (!empty($article['kategori'])): ?>
                            | <i class="fas fa-tag"></i> <?php echo htmlspecialchars($article['kategori']); ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="article-excerpt">
                        <?php echo htmlspecialchars(substr($article['konten'], 0, 150)) . '...'; ?>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <span class="status-badge status-<?php echo $article['status']; ?>">
                            <?php echo ucfirst($article['status']); ?>
                        </span>
                    </div>
                    
                    <div class="article-actions">
                        <button onclick="changeStatus(<?php echo $article['id']; ?>, '<?php echo $article['status']; ?>')" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Ubah Status
                        </button>
                        <button onclick="viewArticle(<?php echo $article['id']; ?>)" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Lihat
                        </button>
                        <button onclick="deleteArticle(<?php echo $article['id']; ?>, '<?php echo addslashes(htmlspecialchars($article['judul'])); ?>')" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    </div>

    <!-- Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><i class="fas fa-edit"></i> Ubah Status Artikel</h2>
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="artikel_id" id="statusArtikelId">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Status Baru:</label>
                    <select name="status" id="statusSelect" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: #6c757d; color: white; margin-right: 10px;">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function changeStatus(id, currentStatus) {
            document.getElementById('statusArtikelId').value = id;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        
        function viewArticle(id) {
            window.open('../artikel_pengunjung.php?id=' + id, '_blank');
        }
        
        function deleteArticle(id, title) {
            if (confirm('Yakin ingin menghapus artikel "' + title + '"?\n\nArtikel yang dihapus tidak dapat dikembalikan!')) {
                window.location.href = 'delete_artikel.php?id=' + id;
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>