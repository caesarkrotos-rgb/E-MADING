<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';

$user_id = (int)$_SESSION['user_id'];

// Create tables if not exist with proper structure
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

// Handle actions
if ($_POST['action'] ?? '' == 'like' && isset($_POST['artikel_id'])) {
    $artikel_id = (int)$_POST['artikel_id'];
    $check = mysqli_query($conn, "SELECT id FROM likes WHERE user_id = {$_SESSION['user_id']} AND artikel_id = $artikel_id");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM likes WHERE user_id = {$_SESSION['user_id']} AND artikel_id = $artikel_id");
    } else {
        mysqli_query($conn, "INSERT INTO likes (user_id, artikel_id) VALUES ({$_SESSION['user_id']}, $artikel_id)");
    }
    header("Location: dashboard.php");
    exit;
}

if ($_POST['action'] ?? '' == 'comment' && isset($_POST['artikel_id'], $_POST['comment'])) {
    $artikel_id = (int)$_POST['artikel_id'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    if (!empty(trim($comment))) {
        mysqli_query($conn, "INSERT INTO comments (user_id, artikel_id, comment) VALUES ({$_SESSION['user_id']}, $artikel_id, '$comment')");
    }
    header("Location: dashboard.php");
    exit;
}

// Role-specific statistics for guru
$total_articles = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel a JOIN users u ON a.author_id = u.id WHERE u.role = 'guru'"))['total'];
$published = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel a JOIN users u ON a.author_id = u.id WHERE a.status = 'published' AND u.role = 'guru'"))['total'];
$draft = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel a JOIN users u ON a.author_id = u.id WHERE a.status = 'draft' AND u.role = 'guru'"))['total'];
$total_likes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM likes"))['total'];
$total_comments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM comments"))['total'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];

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

// Debug: Check if teacher articles exist
$debug_query = "SELECT COUNT(*) as total FROM artikel a JOIN users u ON a.author_id = u.id WHERE a.status = 'published' AND u.role = 'guru'";
$debug_result = mysqli_query($conn, $debug_query);
$debug_count = mysqli_fetch_assoc($debug_result)['total'];

// Show only articles by teachers (role = 'guru')
$artikel = mysqli_query($conn, "SELECT a.*, u.nama, u.role,
    (SELECT COUNT(*) FROM likes WHERE artikel_id = a.id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE artikel_id = a.id) as comment_count,
    (SELECT COUNT(*) FROM shares WHERE artikel_id = a.id) as share_count,
    (SELECT COUNT(*) FROM likes WHERE artikel_id = a.id AND user_id = {$_SESSION['user_id']}) as user_liked
    FROM artikel a 
    JOIN users u ON a.author_id = u.id 
    WHERE a.status = 'published' AND u.role = 'guru' $search_condition
    ORDER BY a.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
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
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
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
        
        .total-card { color: #28a745; }
        .published-card { color: #007bff; }
        .draft-card { color: #ffc107; }
        .likes-card { color: #e74c3c; }
        .comments-card { color: #6f42c1; }
        
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
        
        .article-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            display: flex;
            gap: 20px;
        }
        
        .article-card:hover {
            border-color: #28a745;
            box-shadow: 0 5px 15px rgba(40,167,69,0.1);
        }
        
        .article-image {
            width: 150px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .article-content-wrapper {
            flex: 1;
        }
        
        .article-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        
        .article-content {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            color: #666;
        }
        
        .article-actions {
            display: flex;
            gap: 10px;
        }
        
        .like-btn, .comment-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #666;
        }
        
        .like-btn:hover, .comment-btn:hover {
            background: #f8f9fa;
        }
        
        .like-btn.liked {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }
        
        .comment-form {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            min-height: 80px;
        }
        
        .comment-form button {
            margin-top: 10px;
            padding: 8px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .articles-grid .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .articles-grid .article-card button:hover {
            background: #28a745 !important;
            color: white !important;
            border-color: #28a745 !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        }
        
        .articles-grid .article-card .like-btn.liked {
            background: #e74c3c !important;
            color: white !important;
            border-color: #e74c3c !important;
        }
        
        .articles-grid .article-card .like-btn.liked:hover {
            background: #dc3545 !important;
            border-color: #dc3545 !important;
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
            
            .articles-grid {
                grid-template-columns: 1fr !important;
            }
        }
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
                <a href="#" class="nav-link active">
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
                <a href="artikel.php" class="nav-link">
                    <i class="fas fa-newspaper"></i>
                    Semua Artikel
                </a>
            </li>
            
            <li class="nav-item">
                <a href="profil.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    Profil
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Dashboard Guru</h1>
                <p>Selamat datang, <?php echo $_SESSION['nama']; ?>!</p>
                
                <?php if (isset($_GET['success'])): ?>
                    <div style="background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 8px; margin: 10px 0; border: 1px solid #c3e6cb;">
                        <?php if ($_GET['success'] == 'published'): ?>
                            <i class="fas fa-check-circle"></i> Artikel berhasil dipublikasi dan muncul di dashboard!
                        <?php elseif ($_GET['success'] == 'draft'): ?>
                            <i class="fas fa-save"></i> Artikel berhasil disimpan sebagai draft!
                        <?php elseif ($_GET['success'] == 'updated'): ?>
                            <i class="fas fa-edit"></i> Artikel berhasil diperbarui!
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Search Form -->
                <form method="GET" style="max-width: 300px; margin: 10px 0 0; display: flex; gap: 8px;">
                    <input type="text" name="search" placeholder="Cari artikel..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                           style="flex: 1; padding: 8px 15px; border: 1px solid #ddd; border-radius: 20px; font-size: 14px; outline: none;">
                    <button type="submit" 
                            style="padding: 8px 15px; background: #28a745; color: white; border: none; border-radius: 20px; cursor: pointer; font-size: 14px;">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($_GET['search'])): ?>
                        <a href="dashboard.php" style="padding: 8px 15px; background: #6c757d; color: white; border-radius: 20px; text-decoration: none;">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="user-info">

                <span><i class="fas fa-chalkboard-teacher"></i> <?php echo $_SESSION['nama']; ?></span>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        

        
        <div class="stats-grid">
            <div class="stat-card total-card">
                <div class="stat-icon">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="stat-number"><?php echo $total_articles; ?></div>
                <div class="stat-label">Total Artikel</div>
            </div>
            
            <div class="stat-card published-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $published; ?></div>
                <div class="stat-label">Dipublikasi</div>
            </div>
            
            <div class="stat-card draft-card">
                <div class="stat-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="stat-number"><?php echo $draft; ?></div>
                <div class="stat-label">Draft</div>
            </div>
            
        
            
            <div class="stat-card comments-card">
                <div class="stat-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-number"><?php echo $total_comments; ?></div>
                <div class="stat-label">Total Komentar</div>
            </div>
        </div>
        
        <div class="articles-section">
            <h3 class="section-title">
                <i class="fas fa-newspaper"></i>
                <?php if (!empty($search)): ?>
                    Hasil Pencarian: "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    Artikel dari Guru
                <?php endif; ?>
            </h3>
            
            <?php if ($artikel && mysqli_num_rows($artikel) > 0): ?>
                <div class="articles-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php while($art = mysqli_fetch_assoc($artikel)): 
                        $image_path = getImagePath($art['foto']);
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
                                    <i class="fas fa-calendar" style="color: #007bff;"></i> 
                                    <?php echo date('d M Y', strtotime($art['created_at'])); ?>
                                </span>
                            </div>
                            
                            <!-- Content Preview -->
                            <p style="color: #555; line-height: 1.6; margin-bottom: 20px; height: 60px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                <?php echo htmlspecialchars(substr($art['konten'], 0, 120)) . '...'; ?>
                            </p>
                            
                            <!-- Action Buttons -->
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
                                    <button onclick="openArticle(<?php echo $art['id']; ?>)" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 12px 20px; border-radius: 25px; border: none; font-weight: 600; font-size: 0.9rem; cursor: pointer;">
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
                            
                            <!-- Always visible comment section -->
                            <div style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 15px; border-left: 4px solid #28a745;">
                                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                                    <input type="text" id="comment-input-<?php echo $art['id']; ?>" placeholder="Tulis komentar..." style="flex: 1; padding: 12px 18px; border: 2px solid #e9ecef; border-radius: 25px; font-size: 14px; outline: none;">
                                    <button onclick="addComment(<?php echo $art['id']; ?>)" style="background: #28a745; color: white; border: none; padding: 12px 25px; border-radius: 25px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                                <div id="comments-list-<?php echo $art['id']; ?>"></div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="tulis_artikel.php" style="background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; margin-right: 15px; transition: all 0.3s ease;">
                        <i class="fas fa-plus"></i> Tulis Artikel Baru
                    </a>
                    <a href="artikel_saya.php" style="background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; transition: all 0.3s ease;">
                        <i class="fas fa-list"></i> Artikel Saya
                    </a>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-newspaper" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>Belum ada artikel yang tersedia.</p>
                    <?php if ($debug_count > 0): ?>
                        <p style="color: #dc3545; font-size: 0.9rem; margin-top: 10px;">
                            Debug: Ada <?php echo $debug_count; ?> artikel published, tapi query JOIN gagal. 
                            Kemungkinan masalah di tabel users.
                        </p>
                        <p style="margin-top: 10px;">
                            <a href="tulis_artikel.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                                Tulis Artikel Baru
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Article Popup Modal -->
    <div id="articleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; overflow-y: auto;">
        <div style="max-width: 800px; margin: 50px auto; background: white; border-radius: 15px; position: relative; animation: slideIn 0.3s ease;">
            <button onclick="closeArticlePopup()" style="position: absolute; top: 15px; right: 20px; background: rgba(255,255,255,0.9); border: none; font-size: 20px; cursor: pointer; color: #666; z-index: 10; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
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
        function toggleComment(articleId) {
            // Comments are always visible now, just scroll to comment section
            const commentSection = document.getElementById('comments-list-' + articleId);
            if (commentSection) {
                commentSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        
        function loadComments(articleId) {
            fetch('../get_comments.php?artikel_id=' + articleId)
            .then(response => response.json())
            .then(data => {
                const commentsList = document.getElementById('comments-list-' + articleId);
                const comments = data.comments || data;
                const total = data.total || comments.length;
                const uniqueCommenters = data.unique_commenters || 0;
                
                // Update comment button with detailed info
                const commentBtn = document.getElementById('comment-count-' + articleId);
                if (commentBtn) {
                    if (uniqueCommenters > 0) {
                        commentBtn.innerHTML = `${total} <small style="opacity: 0.7;">(${uniqueCommenters} orang)</small>`;
                    } else {
                        commentBtn.textContent = total;
                    }
                }
                
                // Add header with detailed comment info
                let headerHtml = `<div style="background: #e8f5e8; padding: 10px 15px; margin-bottom: 10px; border-radius: 8px; border-left: 4px solid #28a745;"><strong style="color: #28a745;"><i class="fas fa-comments"></i> ${total} Komentar dari ${uniqueCommenters} orang</strong></div>`;
                
                if (comments.length === 0) {
                    commentsList.innerHTML = headerHtml + '<div style="text-align: center; color: #999; padding: 15px;">Belum ada komentar</div>';
                } else {
                    commentsList.innerHTML = headerHtml;
                    comments.forEach(comment => {
                        console.log('Comment:', comment.id, 'Can delete:', comment.can_delete, 'User ID:', comment.user_id);
                        const deleteBtn = comment.can_delete ? `<button onclick="deleteComment(${comment.id}, ${articleId})" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; cursor: pointer; float: right;"><i class="fas fa-trash"></i></button>` : '';
                        const commentHtml = `<div style="background: white; padding: 12px; margin: 8px 0; border-radius: 8px; border-left: 3px solid #28a745; position: relative;">${deleteBtn}<strong style="color: #28a745;">${comment.nama}:</strong><br><span style="color: #555;">${comment.comment}</span><br><small style="color: #999;">${comment.created_at}</small></div>`;
                        commentsList.insertAdjacentHTML('beforeend', commentHtml);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading comments:', error);
                document.getElementById('comments-list-' + articleId).innerHTML = '<div style="color: #dc3545; padding: 15px;">Error loading comments</div>';
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
            .catch(error => {
                alert('Network error: ' + error.message);
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
                        // Clear input first
                        input.value = '';
                        
                        // Reload comments to show all comments including the new one
                        loadComments(artikelId);
                        
                        // Update comment count in button
                        const countEl = document.getElementById('comment-count-' + artikelId);
                        countEl.textContent = parseInt(countEl.textContent) + 1;
                        
                        // Update dashboard statistics
                        updateCommentStats();
                        
                        // Refresh all stats from server
                        setTimeout(() => refreshStatistics(), 500);
                        
                        // Show success notification
                        showCommentSuccessNotification();
                    } else {
                        alert('Error: ' + data.error);
                    }
                } catch(e) {
                    console.log('Response:', text);
                    alert('Response error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error');
            });
        }
        
        function updateCommentStats() {
            // Update the comment statistics in dashboard
            const statsElement = document.querySelector('.comments-card .stat-number');
            if (statsElement) {
                const currentCount = parseInt(statsElement.textContent);
                statsElement.textContent = currentCount + 1;
            }
        }
        
        function showCommentSuccessNotification() {
            const commentsCard = document.querySelector('.comments-card');
            if (commentsCard) {
                // Create success notification
                const notification = document.createElement('div');
                notification.style.cssText = 'position: absolute; top: 10px; right: 10px; background: #28a745; color: white; padding: 8px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; z-index: 10; animation: slideIn 0.3s ease;';
                notification.innerHTML = '<i class="fas fa-check"></i> Komentar berhasil!';
                
                // Add relative positioning to card
                commentsCard.style.position = 'relative';
                commentsCard.appendChild(notification);
                
                // Remove notification after 3 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 3000);
            }
        }
        
        // Load like counts and comments on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load like counts for all articles
            loadAllLikeCounts();
            
            // Update all comment buttons with correct counts
            updateAllCommentButtons();
            
            // Check for open comment forms
            const forms = document.querySelectorAll('[id^="comment-form-"]');
            forms.forEach(form => {
                const articleId = form.id.replace('comment-form-', '');
                if (localStorage.getItem('comment-open-' + articleId)) {
                    form.style.display = 'block';
                    loadComments(articleId);
                }
            });
            
            // Refresh statistics from database
            refreshStatistics();
        });
        
        function loadAllLikeCounts() {
            // Find all like buttons and load their counts
            const likeButtons = document.querySelectorAll('[id^="like-btn-"]');
            likeButtons.forEach(button => {
                const articleId = button.id.replace('like-btn-', '');
                loadLikeCount(articleId);
            });
        }
        
        function loadLikeCount(articleId) {
            fetch('../get_article_data.php?id=' + articleId)
            .then(response => response.json())
            .then(data => {
                if (data.success !== false) {
                    const countEl = document.getElementById('like-count-' + articleId);
                    const btn = document.getElementById('like-btn-' + articleId);
                    
                    if (countEl) countEl.textContent = data.like_count || 0;
                    
                    if (btn && data.user_liked) {
                        btn.classList.add('liked');
                        btn.style.background = '#e74c3c';
                        btn.style.color = 'white';
                        btn.style.borderColor = '#e74c3c';
                    }
                }
            })
            .catch(error => console.log('Like count load error:', error));
        }
        
        function updateAllCommentButtons() {
            // Find all comment buttons and update their counts, also load comments
            const commentButtons = document.querySelectorAll('[id^="comment-count-"]');
            commentButtons.forEach(button => {
                const articleId = button.id.replace('comment-count-', '');
                // Load comments for this article to update the button and display comments
                loadComments(articleId);
            });
        }
        
        function refreshStatistics() {
            fetch('../get_global_stats.php?role=guru')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const articlesElement = document.querySelector('.total-card .stat-number');
                    if (articlesElement) articlesElement.textContent = data.articles;
                    
                    const publishedElement = document.querySelector('.published-card .stat-number');
                    if (publishedElement) publishedElement.textContent = data.published;
                    
                    const draftElement = document.querySelector('.draft-card .stat-number');
                    if (draftElement) draftElement.textContent = data.draft;
                    
                    const commentsElement = document.querySelector('.comments-card .stat-number');
                    if (commentsElement) commentsElement.textContent = data.comments;
                }
            })
            .catch(error => console.log('Stats refresh error:', error));
        }
        
        function showArticlePopup(id, title, content, image, author, date) {
            try {
                const modal = document.getElementById('articleModal');
                const modalContent = document.getElementById('modalContent');
                
                if (!modal || !modalContent) {
                    console.error('Modal elements not found');
                    return;
                }
                
                // Safely handle parameters
                title = title || 'Artikel';
                content = content || 'Konten tidak tersedia';
                author = author || 'Unknown';
                date = date || 'Unknown';
                
                // Handle image path properly
                let imageHtml = '';
                if (image && image.trim()) {
                    let imagePath = image;
                    if (!imagePath.startsWith('http') && !imagePath.startsWith('../')) {
                        imagePath = '../uploads/' + image;
                    }
                    imageHtml = `<img src="${imagePath}" style="width: 100%; max-height: 400px; object-fit: contain; background: #f8f9fa; border-radius: 15px 15px 0 0;" onerror="this.style.display='none'">`;
                }
                
                modalContent.innerHTML = `
                ${imageHtml}
                <div style="padding: 30px;">
                    <h2 style="color: #333; margin-bottom: 15px; line-height: 1.4;">${title}</h2>
                    <div style="display: flex; gap: 20px; margin-bottom: 25px; font-size: 0.9rem; color: #666; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                        <span><i class="fas fa-user" style="color: #28a745;"></i> ${author}</span>
                        <span><i class="fas fa-calendar" style="color: #007bff;"></i> ${date}</span>
                    </div>
                    <div style="color: #555; line-height: 1.8; font-size: 16px; white-space: pre-wrap; margin-bottom: 30px;">${content}</div>
                    
                    <!-- Like, Comment, Share Section -->
                    <div style="border-top: 1px solid #eee; padding-top: 20px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <div style="display: flex; gap: 15px;">
                                <button onclick="toggleLikeModal(${id})" id="modal-like-btn-${id}" style="padding: 10px 20px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-weight: 500;">
                                    <i class="fas fa-heart"></i> <span id="modal-like-count-${id}">0</span>
                                </button>
                                <button onclick="toggleModalComment(${id})" style="padding: 10px 20px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-weight: 500;">
                                    <i class="fas fa-comment"></i> <span id="modal-comment-count-${id}">0</span>
                                </button>
                                <button onclick="shareArticle(${id}, '${title.replace(/'/g, "\\'")}')") style="padding: 10px 20px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-weight: 500;">
                                    <i class="fas fa-share"></i> Share
                                </button>
                            </div>
                        </div>
                        
                        <!-- Comment Form -->
                        <div id="modal-comment-form-${id}" style="display: none; margin-bottom: 20px;">
                            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                                <input type="text" id="modal-comment-input-${id}" placeholder="Tulis komentar..." style="flex: 1; padding: 12px 18px; border: 2px solid #e9ecef; border-radius: 25px; font-size: 14px; outline: none;">
                                <button onclick="addModalComment(${id})" style="background: #28a745; color: white; border: none; padding: 12px 25px; border-radius: 25px; cursor: pointer; font-weight: 600;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <div id="modal-comments-list-${id}" style="max-height: 200px; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 10px; padding: 10px; background: #fafafa;"></div>
                        </div>
                    </div>
                    
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
            
            // Load article data
            loadModalData(id);
            
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } catch (error) {
                console.error('Error in showArticlePopup:', error);
                alert('Error opening article: ' + error.message);
            }
        }
        
        function loadModalData(id) {
            fetch(`../get_article_data.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('modal-like-count-' + id).textContent = data.like_count;
                document.getElementById('modal-comment-count-' + id).textContent = data.comment_count;
                const btn = document.getElementById('modal-like-btn-' + id);
                if (data.user_liked) {
                    btn.style.background = '#e74c3c';
                    btn.style.color = 'white';
                }
            });
        }
        
        function toggleLikeModal(artikelId) {
            fetch('../like_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'artikel_id=' + artikelId
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('modal-like-count-' + artikelId).textContent = data.total;
                const btn = document.getElementById('modal-like-btn-' + artikelId);
                if (data.liked) {
                    btn.style.background = '#e74c3c';
                    btn.style.color = 'white';
                } else {
                    btn.style.background = 'white';
                    btn.style.color = '#666';
                }
                const mainBtn = document.getElementById('like-count-' + artikelId);
                if (mainBtn) mainBtn.textContent = data.total;
            });
        }
        
        function toggleModalComment(articleId) {
            const form = document.getElementById('modal-comment-form-' + articleId);
            form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
            if (form.style.display === 'block') {
                loadModalComments(articleId);
            }
        }
        
        function addModalComment(artikelId) {
            const input = document.getElementById('modal-comment-input-' + artikelId);
            const comment = input.value.trim();
            if (!comment) {
                alert('Komentar tidak boleh kosong!');
                return;
            }
            
            fetch('../comment_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'artikel_id=' + artikelId + '&comment=' + encodeURIComponent(comment)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentHtml = `<div style="background: white; padding: 12px; margin: 8px 0; border-radius: 10px; border-left: 3px solid #28a745;"><strong style="color: #28a745;">${data.nama}:</strong><br><span style="color: #555;">${data.comment}</span><br><small style="color: #999;">${data.created_at}</small></div>`;
                    document.getElementById('modal-comments-list-' + artikelId).insertAdjacentHTML('afterbegin', commentHtml);
                    input.value = '';
                    const countEl = document.getElementById('modal-comment-count-' + artikelId);
                    countEl.textContent = parseInt(countEl.textContent) + 1;
                    const mainCountEl = document.getElementById('comment-count-' + artikelId);
                    if (mainCountEl) mainCountEl.textContent = parseInt(mainCountEl.textContent) + 1;
                } else {
                    alert('Gagal menambahkan komentar: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menambahkan komentar.');
            });
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
                            loadModalComments(articleId);
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
        
        function loadModalComments(articleId) {
            fetch(`../get_comments.php?artikel_id=${articleId}`)
            .then(response => response.json())
            .then(data => {
                const commentsList = document.getElementById('modal-comments-list-' + articleId);
                if (data.length === 0) {
                    commentsList.innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">Belum ada komentar</div>';
                } else {
                    commentsList.innerHTML = '';
                    data.forEach(comment => {
                        const deleteBtn = comment.can_delete ? `<button onclick="deleteComment(${comment.id}, ${articleId})" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; cursor: pointer; float: right;"><i class="fas fa-trash"></i></button>` : '';
                        const commentHtml = `<div style="background: white; padding: 12px; margin: 8px 0; border-radius: 10px; border-left: 3px solid #28a745; position: relative;">${deleteBtn}<strong style="color: #28a745;">${comment.nama}:</strong><br><span style="color: #555;">${comment.comment}</span><br><small style="color: #999;">${comment.created_at}</small></div>`;
                        commentsList.insertAdjacentHTML('beforeend', commentHtml);
                    });
                }
            });
        }
        
        function toggleShareMenu(id) {
            const menu = document.getElementById('share-menu-' + id);
            // Close all other menus
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
        
        function shareArticle(id, title) {
            const url = window.location.origin + '/kesarujikom/artikel_pengunjung.php?id=' + id;
            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: 'Baca artikel menarik ini!',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link artikel berhasil disalin!');
                });
            }
        }
        
        function closeArticlePopup() {
            const modal = document.getElementById('articleModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        // Close modal when clicking outside - setup after DOM loaded
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('articleModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeArticlePopup();
                    }
                });
            }
        });
        
        // Add keyboard support for closing modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeArticlePopup();
            }
        });
        
        // Close share menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="share-menu-"]') && !e.target.closest('button[onclick*="toggleShareMenu"]')) {
                document.querySelectorAll('[id^="share-menu-"]').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
        
        function openArticle(id) {
            if (!id) return;
            
            fetch('../get_article_data.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                console.log('Article data received:', data);
                if (data && data.success) {
                    // Use the correct field names from the API
                    showArticlePopup(data.id, data.title || data.judul, data.content || data.konten, data.image || data.foto, data.author || data.nama, data.date || data.created_at);
                } else {
                    alert('Artikel tidak ditemukan atau terjadi kesalahan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal memuat artikel');
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
    </script>
</body>
</html>