<?php
session_start();
include '../config.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa') {
  header("Location: ../index.php");
  exit;
}

// Create tables
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guru', 'siswa') DEFAULT 'siswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artikel_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (artikel_id, user_id)
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artikel_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Debug session info
echo "<!-- Debug: User ID = " . $_SESSION['user_id'] . ", Role = " . $_SESSION['role'] . " -->";

// Get articles
$artikel_query = "SELECT a.*, u.nama, u.role,
    (SELECT COUNT(*) FROM likes l WHERE l.artikel_id = a.id) as like_count,
    (SELECT COUNT(*) FROM likes l WHERE l.artikel_id = a.id AND l.user_id = {$_SESSION['user_id']}) as user_liked,
    (SELECT COUNT(*) FROM comments c WHERE c.artikel_id = a.id) as comment_count
    FROM artikel a 
    JOIN users u ON a.author_id = u.id 
    WHERE a.status = 'published'
    ORDER BY a.created_at DESC";
$artikel = mysqli_query($conn, $artikel_query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Artikel - Portal Siswa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .main-content { margin-left: 250px; padding: 20px; min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
        }
        .articles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 20px; margin-top: 20px; }
        .article-card { background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; transition: all 0.3s ease; }
        .article-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .article-title { color: #333; margin-bottom: 15px; font-size: 1.4rem; font-weight: 700; line-height: 1.3; }
        .article-meta { color: #666; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; display: flex; gap: 15px; flex-wrap: wrap; font-size: 0.85rem; }
        .article-content { color: #555; line-height: 1.6; margin-bottom: 20px; height: 60px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; }
        .article-image { width: 100%; height: 280px; object-fit: cover; }
        .like-btn.liked { background: #e74c3c !important; color: white !important; border-color: #e74c3c !important; }
        .comment-item { background: white; padding: 12px; margin: 8px 0; border-radius: 8px; border-left: 3px solid #007bff; }
        .comment-author { font-weight: 600; color: #007bff; margin-bottom: 5px; }
        .comment-text { color: #555; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 2% auto; padding: 0; border-radius: 15px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 20px 30px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 30px; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <h1><i class="fas fa-newspaper"></i> Artikel Sekolah</h1>

            <div class="articles-grid">
            <?php while ($art = mysqli_fetch_assoc($artikel)): ?>
            <div class="article-card">
                <?php if (!empty($art['foto'])): ?>
                    <div style="position: relative; overflow: hidden; background: #f8f9fa;">
                        <img src="../uploads/<?php echo htmlspecialchars($art['foto']); ?>" class="article-image" alt="Article image">
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
                    <h3 class="article-title"><?php echo htmlspecialchars($art['judul']); ?></h3>
                    
                    <div class="article-meta">
                        <span><i class="fas fa-user" style="color: #007bff;"></i> <?php echo htmlspecialchars($art['nama']); ?> (<?php echo ucfirst($art['role']); ?>)</span>
                        <span><i class="fas fa-calendar" style="color: #28a745;"></i> <?php echo date('d M Y', strtotime($art['created_at'])); ?></span>
                    </div>
                    
                    <div class="article-content">
                        <?php echo htmlspecialchars(substr($art['konten'], 0, 120)) . '...'; ?>
                    </div>
            
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div style="display: flex; gap: 8px;">
                            <button onclick="toggleLike(<?php echo $art['id']; ?>)" class="like-btn <?php echo $art['user_liked'] ? 'liked' : ''; ?>" id="like-btn-<?php echo $art['id']; ?>" style="padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; font-weight: 500;">
                                <i class="fas fa-heart"></i> <span id="like-count-<?php echo $art['id']; ?>"><?php echo $art['like_count']; ?></span>
                            </button>
                            <button onclick="toggleComment(<?php echo $art['id']; ?>)" style="padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; font-weight: 500;">
                                <i class="fas fa-comment"></i> <span id="comment-count-<?php echo $art['id']; ?>"><?php echo $art['comment_count']; ?></span>
                            </button>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button onclick="openModal(<?php echo $art['id']; ?>)" style="background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); color: white; padding: 12px 20px; border-radius: 25px; border: none; font-weight: 600; font-size: 0.9rem; cursor: pointer;">
                                <i class="fas fa-eye"></i> Baca
                            </button>
                        </div>
                    </div>
                    
                    <div id="comment-<?php echo $art['id']; ?>" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 15px; border-left: 4px solid #007bff; display: none;">
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
        </div>
    </div>
    
    <!-- MODAL UNTUK BACA SELENGKAPNYA -->
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
    function openModal(articleId) {
        // Get article data from the page
        const articleCard = document.querySelector(`[data-article-id="${articleId}"]`) || 
                           document.querySelector(`button[onclick="openModal(${articleId})"]`).closest('.article-card');
        
        if (!articleCard) {
            console.error('Article card not found');
            return;
        }
        
        const title = articleCard.querySelector('.article-title').textContent;
        const meta = articleCard.querySelector('.article-meta').innerHTML;
        const image = articleCard.querySelector('.article-image');
        
        // Set modal content
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalMeta').innerHTML = meta;
        
        if (image) {
            document.getElementById('modalImage').innerHTML = `<img src="${image.src}" style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 10px; margin-bottom: 20px;">`;
        } else {
            document.getElementById('modalImage').innerHTML = '';
        }
        
        // Fetch full article content
        fetch('../artikel_pengunjung.php?id=' + articleId)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const content = doc.querySelector('.article-text');
            if (content) {
                document.getElementById('modalContent').innerHTML = `<div style="font-size: 1.1rem; color: #444; line-height: 1.6; white-space: pre-wrap;">${content.textContent}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading article:', error);
            document.getElementById('modalContent').innerHTML = '<p>Error loading article content.</p>';
        });
        
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
    
    function toggleComment(id) {
        var box = document.getElementById('comment-' + id);
        if (box.style.display === 'none' || box.style.display === '') {
            box.style.display = 'block';
            loadComments(id);
        } else {
            box.style.display = 'none';
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
            const comments = data.comments || [];
            const total = data.total || 0;
            
            // Clear existing comments and add fresh ones
            commentsList.innerHTML = '';
            
            if (comments.length === 0) {
                commentsList.innerHTML = '<div style="text-align: center; color: #999; padding: 15px;">Belum ada komentar</div>';
            } else {
                comments.forEach(comment => {
                    const deleteBtn = comment.can_delete ? `<button onclick="deleteComment(${comment.id}, ${articleId})" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; cursor: pointer; float: right;"><i class="fas fa-trash"></i></button>` : '';
                    const commentHtml = `<div class="comment-item" style="position: relative;">${deleteBtn}<div class="comment-author">${comment.nama}</div><div class="comment-text">${comment.comment}</div><div style="font-size: 0.8rem; color: #999; margin-top: 5px;">${comment.created_at}</div></div>`;
                    commentsList.insertAdjacentHTML('beforeend', commentHtml);
                });
            }
            
            // Update comment count in button
            const countEl = document.getElementById('comment-count-' + articleId);
            if (countEl) countEl.textContent = total;
        })
        .catch(error => console.log('Error loading comments:', error));
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
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
    </script>
</body>
</html>