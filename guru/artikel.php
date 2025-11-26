<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';

// Function to get image path
function getImagePath($filename) {
    if (!$filename) return null;
    $paths = ['../uploads/' . $filename, '../uploads/gallery/' . $filename, '../uploads/profiles/' . $filename];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
}

// Handle search
$search = $_GET['search'] ?? '';
$search_condition = '';
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $search_condition = "AND (a.judul LIKE '%$search%' OR a.konten LIKE '%$search%')";
}

// Get all articles
$artikel = mysqli_query($conn, "SELECT a.*, u.nama, u.role FROM artikel a 
    JOIN users u ON a.author_id = u.id 
    WHERE a.status = 'published' $search_condition
    ORDER BY a.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Artikel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px 0;
            z-index: 1000;
        }
        
        .sidebar-header {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h3 {
            margin-top: 10px;
            font-size: 1.2rem;
        }
        
        .nav-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .nav-item {
            margin: 5px 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            border-right: 3px solid white;
        }
        
        .nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        .header { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .articles-section { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .article-card { border: 1px solid #e9ecef; border-radius: 15px; margin-bottom: 20px; overflow: hidden; transition: all 0.3s ease; background: white; }
        .article-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .search-form { max-width: 400px; margin-bottom: 20px; display: flex; gap: 10px; }
        .search-form input { flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 25px; }
        .search-form button { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 25px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-chalkboard-teacher" style="font-size: 2rem;"></i>
            <h3>Panel Guru</h3>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="tulis_artikel.php" class="nav-link">
                    <i class="fas fa-plus"></i>
                    Tulis Artikel
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <i class="fas fa-newspaper"></i>
                    Semua Artikel
                </a>
            </li>
            
            </li>
            <li class="nav-item">
                <a href="profil.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    Profil
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content" style="margin-left: 250px; padding: 20px;">
        <div class="header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Semua Artikel</h1>
                <p>Jelajahi semua artikel yang telah dipublikasi</p>
            </div>
            <a href="../logout.php" style="background: #dc3545; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <div class="articles-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Cari artikel..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
                <?php if (!empty($search)): ?>
                    <a href="artikel.php" style="padding: 10px 15px; background: #6c757d; color: white; border-radius: 25px; text-decoration: none;">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </form>
            
            <h3 style="margin-bottom: 20px; color: #333;">
                <i class="fas fa-newspaper"></i>
                <?php if (!empty($search)): ?>
                    Hasil Pencarian: "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    Semua Artikel Terpublikasi
                <?php endif; ?>
            </h3>
            
            <?php if ($artikel && mysqli_num_rows($artikel) > 0): ?>
                <div class="articles-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php while($art = mysqli_fetch_assoc($artikel)): 
                        $image_path = getImagePath($art['foto']);
                        $like_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM likes WHERE artikel_id = {$art['id']}"))['count'];
                        $comment_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM comments WHERE artikel_id = {$art['id']}"))['count'];
                    ?>
                    <div class="article-card" style="background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; transition: all 0.3s ease;">
                        <?php if ($image_path): ?>
                            <div style="position: relative; overflow: hidden; background: #f8f9fa;">
                                <img src="<?php echo htmlspecialchars($image_path); ?>" style="width: 100%; height: 280px; object-fit: contain;">
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                                    <?php if (!empty($art['kategori'])): ?>
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($art['kategori']); ?>
                                    <?php else: ?>
                                        <i class="fas fa-newspaper"></i> Artikel
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div style="padding: 25px;">
                            <h3 style="font-size: 1.4rem; font-weight: 700; color: #333; margin-bottom: 15px; line-height: 1.3;">
                                <?php echo htmlspecialchars($art['judul']); ?>
                            </h3>
                            
                            <div style="display: flex; align-items: center; gap: 15px; font-size: 0.85rem; color: #666; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;">
                                <span style="display: flex; align-items: center; gap: 5px;">
                                    <i class="fas fa-user" style="color: #28a745;"></i> 
                                    <?php echo htmlspecialchars($art['nama']); ?>
                                </span>
                                <span style="display: flex; align-items: center; gap: 5px;">
                                    <i class="fas fa-calendar" style="color: #28a745;"></i> 
                                    <?php echo date('d M Y', strtotime($art['created_at'])); ?>
                                </span>
                            </div>
                            
                            <p style="color: #555; line-height: 1.6; margin-bottom: 20px; height: 60px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                <?php echo htmlspecialchars(substr($art['konten'], 0, 120)) . '...'; ?>
                            </p>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <div style="display: flex; gap: 8px;">
                                    <button onclick="toggleLike(<?php echo $art['id']; ?>)" style="padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; font-weight: 500;">
                                        <i class="fas fa-heart"></i> <?php echo $like_count; ?>
                                    </button>
                                    <button onclick="toggleComment(<?php echo $art['id']; ?>)" style="padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; font-weight: 500;">
                                        <i class="fas fa-comment"></i> <?php echo $comment_count; ?>
                                    </button>
                                </div>
                                <a href="../artikel_pengunjung.php?id=<?php echo $art['id']; ?>" 
                                   style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 12px 20px; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 0.9rem;">
                                    <i class="fas fa-eye"></i> Baca
                                </a>
                            </div>
                            
                            <div style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 15px; border-left: 4px solid #28a745;">
                                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                                    <input type="text" id="comment-input-<?php echo $art['id']; ?>" placeholder="Tulis komentar..." style="flex: 1; padding: 12px 18px; border: 2px solid #e9ecef; border-radius: 25px; font-size: 14px; outline: none;">
                                    <button onclick="addComment(<?php echo $art['id']; ?>)" style="background: #28a745; color: white; border: none; padding: 12px 25px; border-radius: 25px; cursor: pointer; font-weight: 600;">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                                <div id="comments-list-<?php echo $art['id']; ?>"></div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-newspaper" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>Tidak ada artikel yang ditemukan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function toggleLike(artikelId) {
            const btn = event.target.closest('button');
            const countEl = btn.querySelector('i').nextSibling;
            
            btn.style.opacity = '0.6';
            
            fetch('../like_simple.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'artikel_id=' + artikelId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    countEl.textContent = ' ' + data.total;
                    if (data.liked) {
                        btn.style.background = '#e74c3c';
                        btn.style.color = 'white';
                    } else {
                        btn.style.background = 'white';
                        btn.style.color = '#666';
                    }
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .finally(() => {
                btn.style.opacity = '1';
            });
        }
        
        function toggleComment(articleId) {
            const form = document.getElementById('comment-form-' + articleId);
            const isVisible = form.style.display === 'block';
            
            if (isVisible) {
                form.style.display = 'none';
            } else {
                form.style.display = 'block';
                loadComments(articleId);
            }
        }
        
        function deleteComment(commentId, articleId) {
            if (confirm('Yakin ingin menghapus komentar ini?')) {
                fetch('../delete_comment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'comment_id=' + commentId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadComments(articleId);
                    } else {
                        alert('Gagal menghapus komentar: ' + data.message);
                    }
                });
            }
        }
        
        function loadComments(articleId) {
            fetch('../get_comments.php?artikel_id=' + articleId)
            .then(response => response.json())
            .then(data => {
                const commentsList = document.getElementById('comments-list-' + articleId);
                const comments = data.comments || data; // Support both old and new format
                const total = data.total || comments.length;
                
                // Add header with comment count
                let headerHtml = `<div style="background: #e8f5e8; padding: 8px 12px; margin-bottom: 8px; border-radius: 6px; border-left: 3px solid #28a745;"><strong style="color: #28a745; font-size: 0.9rem;"><i class="fas fa-comments"></i> ${total} Komentar</strong></div>`;
                
                if (comments.length === 0) {
                    commentsList.innerHTML = headerHtml + '<div style="text-align: center; color: #999; padding: 10px;">Belum ada komentar</div>';
                } else {
                    commentsList.innerHTML = headerHtml;
                    comments.forEach(comment => {
                        const deleteBtn = comment.can_delete ? `<button onclick="deleteComment(${comment.id}, ${articleId})" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; cursor: pointer; float: right;"><i class="fas fa-trash"></i></button>` : '';
                        const commentHtml = `<div style="background: white; padding: 10px; margin: 5px 0; border-radius: 8px; border-left: 3px solid #28a745; position: relative;">${deleteBtn}<strong style="color: #28a745;">${comment.nama}:</strong><br><span style="color: #555;">${comment.comment}</span><br><small style="color: #999;">${comment.created_at}</small></div>`;
                        commentsList.insertAdjacentHTML('beforeend', commentHtml);
                    });
                }
            });
        }
        
        function addComment(artikelId) {
            const input = document.getElementById('comment-input-' + artikelId);
            const comment = input.value.trim();
            if (!comment) {
                alert('Komentar tidak boleh kosong!');
                return;
            }
            
            fetch('../comment_simple.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'artikel_id=' + artikelId + '&comment=' + encodeURIComponent(comment)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadComments(artikelId);
                    
                    // Update comment count in button
                    const commentBtn = document.querySelector(`button[onclick="toggleComment(${artikelId})"]`);
                    if (commentBtn) {
                        const countMatch = commentBtn.innerHTML.match(/(\d+)/); 
                        if (countMatch) {
                            const newCount = parseInt(countMatch[1]) + 1;
                            commentBtn.innerHTML = commentBtn.innerHTML.replace(/(\d+)/, newCount);
                        }
                    }
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
        
        // Update all comment buttons on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateAllCommentButtons();
        });
        
        function updateAllCommentButtons() {
            const commentButtons = document.querySelectorAll('[id^="comment-count-"]');
            commentButtons.forEach(button => {
                const articleId = button.id.replace('comment-count-', '');
                fetch('../get_comments.php?artikel_id=' + articleId)
                .then(response => response.json())
                .then(data => {
                    const total = data.total || 0;
                    const uniqueCommenters = data.unique_commenters || 0;
                    
                    if (total > 0 && uniqueCommenters > 0) {
                        button.innerHTML = `${total} <small style="opacity: 0.7;">(${uniqueCommenters} orang)</small>`;
                    } else {
                        button.textContent = total;
                    }
                })
                .catch(error => console.log('Error updating button:', error));
            });
        }
    </script>
</body>
</html>