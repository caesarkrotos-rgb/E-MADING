<?php
session_start();
include 'config.php';

$article_id = $_GET['id'] ?? 0;

// Get article data
$result = mysqli_query($conn, "SELECT a.*, u.nama FROM artikel a LEFT JOIN users u ON a.author_id = u.id WHERE a.id = $article_id");
$article = mysqli_fetch_assoc($result);

if (!$article) {
    echo "<script>alert('Artikel tidak ditemukan'); window.close();</script>";
    exit;
}

function getImagePath($filename) {
    if (empty($filename)) return null;
    $paths = [
        'uploads/' . $filename,
        'uploads/gallery/' . $filename,
        'uploads/profiles/' . $filename,
        'uploads/documents/' . $filename,
        $filename // Direct path if already includes directory
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    return 'uploads/' . $filename; // Default fallback
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['judul']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; line-height: 1.6; }
        
        .container { max-width: 800px; margin: 20px auto; background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; }
        
        .article-header { padding: 30px; border-bottom: 1px solid #eee; }
        .article-title { font-size: 2rem; font-weight: 700; color: #333; margin-bottom: 15px; }
        .article-meta { display: flex; gap: 20px; color: #666; font-size: 0.9rem; margin-bottom: 20px; }
        .status-badge { padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-published { background: #d4edda; color: #155724; }
        .status-draft { background: #fff3cd; color: #856404; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .article-image { width: 100%; max-height: 400px; object-fit: contain; background: #f8f9fa; }
        
        .article-content { padding: 30px; }
        .article-text { font-size: 1.1rem; color: #444; white-space: pre-wrap; }
        
        .close-btn { position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 50px; cursor: pointer; font-size: 1rem; z-index: 1000; }
        .close-btn:hover { background: #c82333; transform: scale(1.1); }
    </style>
</head>
<body>
    <button class="close-btn" onclick="closeWindow()">
        <i class="fas fa-times"></i>
    </button>
    
    <script>
    function closeWindow() {
        if (window.opener) {
            window.close();
        } else {
            window.history.back();
        }
    }
    </script>

    <div class="container">
        <?php if (!empty($article['foto'])): ?>
            <?php $image_path = getImagePath($article['foto']); ?>
            <?php if ($image_path): ?>
                <img src="<?php echo $image_path; ?>" class="article-image">
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="article-header">
            <h1 class="article-title"><?php echo htmlspecialchars($article['judul']); ?></h1>
            
            <div class="article-meta">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($article['nama'] ?? 'Unknown'); ?></span>
                <span><i class="fas fa-calendar"></i> <?php echo date('d M Y H:i', strtotime($article['created_at'])); ?></span>
                <?php if (!empty($article['kategori'])): ?>
                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($article['kategori']); ?></span>
                <?php endif; ?>
            </div>
            
            <div>
                <span class="status-badge status-<?php echo $article['status']; ?>">
                    Status: <?php echo ucfirst($article['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="article-content">
            <div class="article-text"><?php echo htmlspecialchars($article['konten']); ?></div>
        </div>
    </div>
</body>
</html>