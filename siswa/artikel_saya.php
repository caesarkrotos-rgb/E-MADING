<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';

// Handle delete
if (isset($_POST['delete_artikel'])) {
    $artikel_id = (int)$_POST['artikel_id'];
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM likes WHERE artikel_id = $artikel_id");
        mysqli_query($conn, "DELETE FROM comments WHERE artikel_id = $artikel_id");
        mysqli_query($conn, "DELETE FROM artikel WHERE id = $artikel_id AND author_id = {$_SESSION['user_id']}");
        mysqli_commit($conn);
        header('Location: artikel_saya.php?success=deleted');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
    }
}

$my_articles = mysqli_query($conn, "SELECT * FROM artikel WHERE author_id = {$_SESSION['user_id']} ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artikel Saya - Siswa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .article-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-view {
            background: #007bff;
            color: white;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .add-article-btn {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 30px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .btn-read {
            background: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="dashboard.php" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <a href="../logout.php" style="background: #dc3545; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <div class="header">
            <h1><i class="fas fa-newspaper"></i> Artikel Saya</h1>
            <p>Kelola semua artikel yang telah Anda tulis</p>
        </div>
        
        <a href="tulis_artikel.php" class="add-article-btn">
            <i class="fas fa-plus"></i> Tulis Artikel Baru
        </a>
        
        <?php if ($my_articles && mysqli_num_rows($my_articles) > 0): ?>
            <div class="articles-grid">
                <?php while($article = mysqli_fetch_assoc($my_articles)): ?>
                <div class="article-card">
                    <div style="padding: 25px;">
                        <h3 style="font-size: 1.4rem; font-weight: 700; color: #333; margin-bottom: 15px; line-height: 1.3;">
                            <?php echo htmlspecialchars($article['judul']); ?>
                        </h3>
                        
                        <div style="display: flex; align-items: center; gap: 15px; font-size: 0.85rem; color: #666; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;">
                            <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($article['created_at'])); ?></span>
                            <span class="status-badge status-<?php echo $article['status']; ?>">
                                <?php 
                                if ($article['status'] == 'published') {
                                    echo '<i class="fas fa-check-circle"></i> Dipublikasi';
                                } elseif ($article['status'] == 'rejected') {
                                    echo '<i class="fas fa-times-circle"></i> Ditolak';
                                } else {
                                    echo '<i class="fas fa-clock"></i> Menunggu Review';
                                }
                                ?>
                            </span>
                        </div>
                        
                        <p style="color: #555; line-height: 1.6; margin-bottom: 20px; height: 60px; overflow: hidden;">
                            <?php echo htmlspecialchars(substr($article['konten'], 0, 120)) . '...'; ?>
                        </p>
                        
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button onclick="openModal(<?php echo $article['id']; ?>, '<?php echo addslashes($article['judul']); ?>', '<?php echo addslashes($article['konten']); ?>', '<?php echo !empty($article['foto']) ? addslashes('../uploads/' . $article['foto']) : ''; ?>', '<?php echo $_SESSION['nama']; ?>', '<?php echo date('d M Y', strtotime($article['created_at'])); ?>')" class="btn btn-read">
                                <i class="fas fa-book-open"></i> Baca Selengkapnya
                            </button>
                            <?php if($article['status'] == 'published'): ?>
                                <a href="../artikel_pengunjung.php?id=<?php echo $article['id']; ?>" target="_blank" class="btn btn-view">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                            <?php endif; ?>
                            <a href="../edit_artikel.php?id=<?php echo $article['id']; ?>" class="btn btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus artikel ini?')">
                                <input type="hidden" name="artikel_id" value="<?php echo $article['id']; ?>">
                                <button type="submit" name="delete_artikel" class="btn btn-delete">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-newspaper"></i>
                <h3>Belum Ada Artikel</h3>
                <p>Anda belum membuat artikel apapun. <a href="tulis_artikel.php">Tulis artikel pertama Anda!</a></p>
            </div>
        <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal untuk Baca Selengkapnya -->
    <div id="articleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"></h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="modalImage"></div>
                <div id="modalMeta"></div>
                <div id="modalContent"></div>
            </div>
        </div>
    </div>
    
    <script>
    function openModal(articleId, title, content, image, author, date) {
        document.getElementById('modalTitle').textContent = title;
        
        const metaHtml = `
            <div style="display: flex; gap: 20px; margin-bottom: 25px; font-size: 0.9rem; color: #666; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                <span><i class="fas fa-user" style="color: #007bff;"></i> ${author}</span>
                <span><i class="fas fa-calendar" style="color: #28a745;"></i> ${date}</span>
            </div>
        `;
        document.getElementById('modalMeta').innerHTML = metaHtml;
        
        if (image) {
            document.getElementById('modalImage').innerHTML = `<img src="${image}" style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 10px; margin-bottom: 20px;">`;
        } else {
            document.getElementById('modalImage').innerHTML = '';
        }
        
        document.getElementById('modalContent').innerHTML = `<div style="font-size: 1.1rem; color: #444; line-height: 1.6; white-space: pre-wrap;">${content}</div>`;
        
        document.getElementById('articleModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('articleModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('articleModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
    </script>
</body>
</html>