<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';

// Function to get image path
function getImagePath($filename) {
    if (!$filename) return null;
    $paths = [
        '../uploads/' . $filename, 
        '../uploads/gallery/' . $filename, 
        '../uploads/profiles/' . $filename,
        '../uploads/documents/' . $filename
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
}

// Auto-create tables
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, artikel_id)
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    share_type ENUM('facebook', 'twitter', 'whatsapp', 'copy') DEFAULT 'copy',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Role-specific statistics for siswa
$total_articles = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel a JOIN users u ON a.author_id = u.id WHERE u.role = 'siswa'"))['total'];
$published = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel a JOIN users u ON a.author_id = u.id WHERE a.status = 'published' AND u.role = 'siswa'"))['total'];
$draft = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel a JOIN users u ON a.author_id = u.id WHERE a.status = 'draft' AND u.role = 'siswa'"))['total'];
$total_likes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM likes"))['total'];
$total_comments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM comments"))['total'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];

// Handle search and get articles
$search = $_GET['search'] ?? '';
$search_condition = '';
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $search_condition = "AND (a.judul LIKE '%$search%' OR a.konten LIKE '%$search%')";
}

// Get articles from students only
$artikel = mysqli_query($conn, "SELECT a.*, u.nama, u.role,
    (SELECT COUNT(*) FROM likes WHERE artikel_id = a.id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE artikel_id = a.id) as comment_count,
    (SELECT COUNT(*) FROM shares WHERE artikel_id = a.id) as share_count,
    (SELECT COUNT(*) FROM likes WHERE artikel_id = a.id AND user_id = {$_SESSION['user_id']}) as user_liked
    FROM artikel a 
    JOIN users u ON a.author_id = u.id 
    WHERE a.status = 'published' AND u.role = 'siswa' $search_condition
    ORDER BY a.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }
        
        .likes-card { color: #e74c3c; }
        .comments-card { color: #3498db; }
        
        .articles-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .section-title {
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .welcome-card h2 {
            margin-bottom: 10px;
        }
        
        .article-card {
            border: 1px solid #e9ecef;
            border-radius: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
        }
        
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .like-btn.liked {
            background: #e74c3c !important;
            color: white !important;
            border-color: #e74c3c !important;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Dashboard Siswa</h1>
                <p>Selamat datang, <?php echo $_SESSION['nama']; ?>!</p>
                
                <?php if (isset($_GET['success'])): ?>
                    <div style="background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 8px; margin: 10px 0; border: 1px solid #c3e6cb;">
                        <?php if ($_GET['success'] == 'draft_saved'): ?>
                            <i class="fas fa-check-circle"></i> Artikel berhasil disimpan sebagai draft! Menunggu verifikasi untuk dipublikasi.
                        <?php elseif ($_GET['success'] == 'updated'): ?>
                            <i class="fas fa-edit"></i> Artikel berhasil diperbarui!
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-graduate"></i> <?php echo $_SESSION['nama']; ?></span>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="welcome-card">
            <h2>🎓 Selamat Datang di Portal Sekolah!</h2>
            <p>Jelajahi artikel terbaru dan berinteraksi dengan konten dari siswa lainnya</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card" style="color: #28a745;">
                <div class="stat-icon">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="stat-number"><?php echo $total_articles; ?></div>
                <div class="stat-label">Total Artikel</div>
            </div>
            
            <div class="stat-card" style="color: #007bff;">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $published; ?></div>
                <div class="stat-label">Dipublikasi</div>
            </div>
            
            <div class="stat-card comments-card">
                <div class="stat-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-number"><?php echo $total_comments; ?></div>
                <div class="stat-label">Total Komentar</div>
            </div>
            
            <div class="stat-card" style="color: #6f42c1;">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        
        <div class="articles-section">
            <h3 class="section-title">
                <i class="fas fa-newspaper"></i>
                Artikel dari Siswa
            </h3>
            
            <?php if ($artikel && mysqli_num_rows($artikel) > 0): ?>
                <div class="articles-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php while($art = mysqli_fetch_assoc($artikel)): 
                        $image_path = getImagePath($art['foto']);
                    ?>
                    <div class="article-card" style="background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; transition: all 0.3s ease;">
                        <?php if ($image_path): ?>
                            <div style="position: relative; overflow: hidden; background: #f8f9fa;">
                                <img src="<?php echo htmlspecialchars($image_path); ?>" style="width: 100%; height: 280px; object-fit: cover;">
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
                                    <i class="fas fa-user" style="color: #007bff;"></i> 
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
                                    <button onclick="toggleLike(<?php echo $art['id']; ?>)" class="like-btn <?php echo $art['user_liked'] ? 'liked' : ''; ?>" id="like-btn-<?php echo $art['id']; ?>" style="padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; font-weight: 500;">
                                        <i class="fas fa-heart"></i> <span id="like-count-<?php echo $art['id']; ?>"><?php echo $art['like_count']; ?></span>
                                    </button>
                                    <button onclick="toggleComment(<?php echo $art['id']; ?>)" style="padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; font-weight: 500;">
                                        <i class="fas fa-comment"></i> <span id="comment-count-<?php echo $art['id']; ?>"><?php echo $art['comment_count']; ?></span>
                                    </button>
                                    <div style="position: relative; display: inline-block;">
                                        <button onclick="toggleShareMenu(<?php echo $art['id']; ?>)" style="padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; font-weight: 500;">
                                            <i class="fas fa-share"></i> <span id="share-count-<?php echo $art['id']; ?>"><?php echo $art['share_count']; ?></span>
                                        </button>
                                        <div id="share-menu-<?php echo $art['id']; ?>" style="display: none; position: absolute; top: 100%; left: 0; background: white; border: 1px solid #ddd; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000; min-width: 150px; margin-top: 5px;">
                                            <button onclick="shareToFacebook(<?php echo $art['id']; ?>, '<?php echo addslashes($art['judul']); ?>')" style="width: 100%; padding: 10px 15px; border: none; background: none; text-align: left; cursor: pointer; color: #1877f2;">
                                                <i class="fab fa-facebook"></i> Facebook
                                            </button>
                                            <button onclick="shareToTwitter(<?php echo $art['id']; ?>, '<?php echo addslashes($art['judul']); ?>')" style="width: 100%; padding: 10px 15px; border: none; background: none; text-align: left; cursor: pointer; color: #1da1f2;">
                                                <i class="fab fa-twitter"></i> Twitter
                                            </button>
                                            <button onclick="shareToWhatsApp(<?php echo $art['id']; ?>, '<?php echo addslashes($art['judul']); ?>')" style="width: 100%; padding: 10px 15px; border: none; background: none; text-align: left; cursor: pointer; color: #25d366;">
                                                <i class="fab fa-whatsapp"></i> WhatsApp
                                            </button>
                                            <button onclick="copyLink(<?php echo $art['id']; ?>)" style="width: 100%; padding: 10px 15px; border: none; background: none; text-align: left; cursor: pointer; color: #666;">
                                                <i class="fas fa-copy"></i> Copy Link
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick="openArticle(<?php echo $art['id']; ?>)" style="background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); color: white; padding: 12px 20px; border-radius: 25px; border: none; font-weight: 600; font-size: 0.9rem; cursor: pointer;">
                                        <i class="fas fa-eye"></i> Baca
                                    </button>
                                    <?php if ($art['author_id'] == $_SESSION['user_id']): ?>
                                        <a href="../edit_artikel.php?id=<?php echo $art['id']; ?>" style="background: #ffc107; color: #212529; padding: 12px 20px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 0.9rem; margin-right: 8px;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteArticle(<?php echo $art['id']; ?>, '<?php echo addslashes($art['judul']); ?>')" style="background: #dc3545; color: white; padding: 12px 20px; border-radius: 25px; border: none; font-weight: 600; font-size: 0.9rem; cursor: pointer;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 15px; border-left: 4px solid #007bff;">
                                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                                    <input type="text" id="comment-input-<?php echo $art['id']; ?>" placeholder="Tulis komentar..." style="flex: 1; padding: 12px 18px; border: 2px solid #e9ecef; border-radius: 25px; font-size: 14px; outline: none;">
                                    <button onclick="addComment(<?php echo $art['id']; ?>)" style="background: #007bff; color: white; border: none; padding: 12px 25px; border-radius: 25px; cursor: pointer; font-weight: 600;">
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
                    <p>Belum ada artikel yang tersedia.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Article Popup Modal -->
    <div id="articleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; overflow-y: auto;">
        <div style="max-width: 800px; margin: 50px auto; background: white; border-radius: 15px; position: relative; animation: slideIn 0.3s ease;">
            <button onclick="closeArticlePopup()" style="position: absolute; top: 15px; right: 20px; background: rgba(255,255,255,0.9); border: none; font-size: 20px; cursor: pointer; color: #666; z-index: 10; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: all 0.3s ease;" onmouseover="this.style.background='#dc3545'; this.style.color='white';" onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.color='#666';">
                <i class="fas fa-times"></i>
            </button>
            <div id="modalContent"></div>
        </div>
    </div>
    
    <style>
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
    
    <script>
        function closeArticlePopup() {
            const modal = document.getElementById('articleModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeArticlePopup();
            }
        });
        
        function toggleComment(articleId) {
            const commentSection = document.getElementById('comments-list-' + articleId);
            if (commentSection) {
                commentSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        
        function deleteComment(commentId, articleId) {
            if (confirm('Yakin ingin menghapus komentar ini?')) {
                fetch('../delete_comment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'comment_id=' + commentId
                })
                .then(response => response.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            loadComments(articleId);
                        } else {
                            alert('Gagal menghapus komentar: ' + data.message);
                        }
                    } catch (e) {
                        console.error('Response:', text);
                        alert('Error parsing response: ' + text);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error: ' + error.message);
                });
            }
        }
        
        function loadComments(articleId) {
            fetch('../get_comments.php?artikel_id=' + articleId)
            .then(response => response.json())
            .then(data => {
                const commentsList = document.getElementById('comments-list-' + articleId);
                const comments = data.comments || [];
                const total = data.total || 0;
                const uniqueCommenters = data.unique_commenters || 0;
                
                const commentBtn = document.getElementById('comment-count-' + articleId);
                if (commentBtn) {
                    if (total > 0 && uniqueCommenters > 0) {
                        commentBtn.innerHTML = `${total} <small style="opacity: 0.7;">(${uniqueCommenters} orang)</small>`;
                    } else {
                        commentBtn.textContent = total;
                    }
                }
                
                let headerHtml = `<div style="background: #e3f2fd; padding: 10px 15px; margin-bottom: 10px; border-radius: 8px; border-left: 4px solid #007bff;"><strong style="color: #007bff;"><i class="fas fa-comments"></i> ${total} Komentar dari ${uniqueCommenters} orang</strong></div>`;
                
                if (!commentsList) {
                    console.error('Comments list element not found for article:', articleId);
                    return;
                }
                
                if (comments.length === 0) {
                    commentsList.innerHTML = headerHtml + '<div style="text-align: center; color: #999; padding: 15px;">Belum ada komentar</div>';
                } else {
                    commentsList.innerHTML = headerHtml;
                    comments.forEach(comment => {
                        const deleteBtn = comment.can_delete ? `<button onclick="deleteComment(${comment.id}, ${articleId})" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; cursor: pointer; float: right;"><i class="fas fa-trash"></i></button>` : '';
                        const commentHtml = `<div style="background: white; padding: 12px; margin: 8px 0; border-radius: 8px; border-left: 3px solid #007bff; position: relative;">${deleteBtn}<strong style="color: #007bff;">${comment.nama}:</strong><br><span style="color: #555;">${comment.comment}</span><br><small style="color: #999;">${comment.created_at}</small></div>`;
                        commentsList.insertAdjacentHTML('beforeend', commentHtml);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading comments:', error);
                const commentsList = document.getElementById('comments-list-' + articleId);
                if (commentsList) {
                    commentsList.innerHTML = '<div style="color: #dc3545; padding: 15px;">Error loading comments: ' + error.message + '</div>';
                }
            });
        }
        
        function toggleLike(artikelId) {
            const btn = document.getElementById('like-btn-' + artikelId);
            const countEl = document.getElementById('like-count-' + artikelId);
            
            btn.style.opacity = '0.6';
            btn.style.pointerEvents = 'none';
            
            fetch('../like_simple.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'artikel_id=' + artikelId
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        countEl.textContent = data.total;
                        if (data.liked) {
                            btn.classList.add('liked');
                            btn.style.background = '#e74c3c';
                            btn.style.color = 'white';
                            btn.style.borderColor = '#e74c3c';
                        } else {
                            btn.classList.remove('liked');
                            btn.style.background = 'white';
                            btn.style.color = '#666';
                            btn.style.borderColor = '#e9ecef';
                        }
                    } else {
                        alert('Error: ' + (data.error || 'Unknown error'));
                    }
                } catch(e) {
                    console.error('JSON parse error:', e);
                }
            })
            .finally(() => {
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
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
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        input.value = '';
                        setTimeout(() => {
                            loadComments(artikelId);
                        }, 300);
                    } else {
                        alert('Error: ' + data.error);
                    }
                } catch(e) {
                    alert('Response error');
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('articleModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeArticlePopup();
                    }
                });
            }
            
            const commentButtons = document.querySelectorAll('[id^="comment-count-"]');
            commentButtons.forEach(button => {
                const articleId = button.id.replace('comment-count-', '');
                loadComments(articleId);
            });
        });
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="share-menu-"]') && !e.target.closest('button[onclick*="toggleShareMenu"]')) {
                document.querySelectorAll('[id^="share-menu-"]').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
        
        function showArticlePopup(id, title, content, image, author, date) {
            try {
                const modal = document.getElementById('articleModal');
                const modalContent = document.getElementById('modalContent');
                
                if (!modal || !modalContent) {
                    console.error('Modal elements not found');
                    return;
                }
                
                title = title || 'Artikel';
                content = content || 'Konten tidak tersedia';
                image = image || '';
                author = author || 'Unknown';
                date = date || 'Unknown';
                
                let imagePath = '';
                if (image) {
                    if (image.startsWith('../')) {
                        imagePath = image;
                    } else {
                        imagePath = '../uploads/' + image;
                    }
                }
                const imageHtml = imagePath ? `<img src="${imagePath}" style="width: 100%; max-height: 400px; object-fit: contain; background: #f8f9fa; border-radius: 15px 15px 0 0;" onerror="this.style.display='none'">` : '';
            
            modalContent.innerHTML = `
                ${imageHtml}
                <div style="padding: 30px;">
                    <h2 style="color: #333; margin-bottom: 15px; line-height: 1.4;">${title}</h2>
                    <div style="display: flex; gap: 20px; margin-bottom: 25px; font-size: 0.9rem; color: #666; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                        <span><i class="fas fa-user" style="color: #007bff;"></i> ${author}</span>
                        <span><i class="fas fa-calendar" style="color: #28a745;"></i> ${date}</span>
                    </div>
                    <div style="color: #555; line-height: 1.8; font-size: 16px; white-space: pre-wrap; margin-bottom: 30px;">${content}</div>
                    
                    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #eee;">
                        <a href="../artikel_pengunjung.php?id=${id}" target="_blank" style="background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; margin-right: 15px;">
                            <i class="fas fa-external-link-alt"></i> Buka di Tab Baru
                        </a>
                        <button onclick="closeArticlePopup()" style="background: #6c757d; color: white; padding: 12px 25px; border: none; border-radius: 25px; cursor: pointer;">
                            <i class="fas fa-times"></i> Tutup
                        </button>
                    </div>
                </div>
            `;
            
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } catch (error) {
                console.error('Error in showArticlePopup:', error);
                alert('Error opening article: ' + error.message);
            }
        }
        
        function openArticle(id) {
            fetch('../get_article_data.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success !== false) {
                    const title = data.title || data.judul || 'Artikel';
                    const content = data.content || data.konten || 'Konten tidak tersedia';
                    const image = data.image || data.foto || '';
                    const author = data.author || data.nama || 'Unknown';
                    const date = data.date || data.created_at || 'Unknown';
                    
                    showArticlePopup(data.id, title, content, image, author, date);
                } else {
                    alert('Artikel tidak ditemukan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading article');
            });
        }
        
        function deleteArticle(id, title) {
            if (confirm(`Yakin ingin menghapus artikel "${title}"?\n\nArtikel akan dihapus permanen.`)) {
                fetch('../delete_article.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'article_id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Artikel berhasil dihapus!');
                        location.reload();
                    } else {
                        alert('Gagal menghapus artikel: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            }
        }
        
        function toggleShareMenu(id) {
            const menu = document.getElementById('share-menu-' + id);
            document.querySelectorAll('[id^="share-menu-"]').forEach(m => {
                if (m.id !== 'share-menu-' + id) m.style.display = 'none';
            });
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }
        
        function shareToFacebook(id, title) {
            const url = window.location.origin + '/kesarujikom/artikel_pengunjung.php?id=' + id;
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
            recordShare(id, 'facebook');
            document.getElementById('share-menu-' + id).style.display = 'none';
        }
        
        function shareToTwitter(id, title) {
            const url = window.location.origin + '/kesarujikom/artikel_pengunjung.php?id=' + id;
            const text = `Baca artikel: ${title}`;
            window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`, '_blank');
            recordShare(id, 'twitter');
            document.getElementById('share-menu-' + id).style.display = 'none';
        }
        
        function shareToWhatsApp(id, title) {
            const url = window.location.origin + '/kesarujikom/artikel_pengunjung.php?id=' + id;
            const text = `Baca artikel menarik: ${title} ${url}`;
            window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
            recordShare(id, 'whatsapp');
            document.getElementById('share-menu-' + id).style.display = 'none';
        }
        
        function copyLink(id) {
            const url = window.location.origin + '/kesarujikom/artikel_pengunjung.php?id=' + id;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link artikel berhasil disalin!');
                recordShare(id, 'copy');
                document.getElementById('share-menu-' + id).style.display = 'none';
            });
        }
        
        function recordShare(id, type) {
            fetch('../share_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `artikel_id=${id}&share_type=${type}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('share-count-' + id).textContent = data.share_count;
                }
            });
        }
    </script>
</body>
</html>