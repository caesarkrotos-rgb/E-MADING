<?php
// Fungsi untuk mencari foto (database hanya simpan nama file)
function getImagePath($filename) {
    if (!$filename) return null;
    $paths = [
        'uploads/' . $filename, 
        'uploads/gallery/' . $filename, 
        'uploads/profiles/' . $filename,
        'uploads/documents/' . $filename
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
}

// Database connection
$use_database = false;
$artikel_terbaru = [];
$total_artikel = 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_results = [];

// Database config
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'emading_db';

try {
    // Connect to database
    $conn = mysqli_connect($host, $username, $password, $database);
    
    if ($conn && !mysqli_connect_error()) {
        // Auto-create artikel table if not exists
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS artikel (
            id INT AUTO_INCREMENT PRIMARY KEY,
            judul VARCHAR(255) NOT NULL,
            konten TEXT NOT NULL,
            foto VARCHAR(255),
            kategori VARCHAR(100),
            author_id INT NOT NULL,
            status ENUM('draft', 'published', 'rejected') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Auto-create interaction tables
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
        
        // Auto-create users table if not exists
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(255) NOT NULL,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'guru', 'siswa') DEFAULT 'siswa',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert default users if none exist
        $check_users = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
        if ($check_users && mysqli_fetch_assoc($check_users)['count'] == 0) {
            mysqli_query($conn, "INSERT INTO users (nama, username, password, role) VALUES 
                ('Administrator', 'admin', 'admin', 'admin'),
                ('Pak Guru', 'budi', 'password', 'guru'),
                ('Siswa Aktif', 'ahmad', 'password', 'siswa')");
        }
        
        // Insert sample articles if none exist
        $check_articles = mysqli_query($conn, "SELECT COUNT(*) as count FROM artikel");
        if ($check_articles && mysqli_fetch_assoc($check_articles)['count'] == 0) {
            // Get guru user ID
            $guru_result = mysqli_query($conn, "SELECT id FROM users WHERE role = 'guru' LIMIT 1");
            if ($guru_result && mysqli_num_rows($guru_result) > 0) {
                $guru_id = mysqli_fetch_assoc($guru_result)['id'];
                mysqli_query($conn, "INSERT INTO artikel (judul, konten, kategori, author_id, status, foto) VALUES 
                    ('NADIN AMIZAH', 'NYANYI BAGUS... Nadin Amizah adalah penyanyi muda berbakat Indonesia yang memiliki suara merdu dan lagu-lagu yang menyentuh hati. Karya-karyanya selalu berhasil menarik perhatian banyak orang.', 'Prestasi', $guru_id, 'published', '1763344112_foto.jpg'),
                    ('555555', '12345678... Artikel tentang teknologi dan inovasi terbaru yang sedang berkembang pesat di era digital ini. Perkembangan teknologi membawa dampak besar bagi kehidupan sehari-hari.', 'Prestasi', $guru_id, 'published', '1763359372_344463916_907289797215508_8458254653026121598_n.jpg'),
                    ('persib', 'persib bandung... Tim sepak bola kebanggaan Jawa Barat yang memiliki sejarah panjang dan prestasi gemilang. Persib Bandung selalu menjadi kebanggaan masyarakat Bandung dan Jawa Barat.', 'Kegiatan', $guru_id, 'published', '1763339985_pusinggggg.jpeg'),
                    ('smk bn', 'skmmskasmsa... SMK Bakti Nusantara merupakan sekolah menengah kejuruan yang fokus pada pengembangan keterampilan dan keahlian siswa untuk siap memasuki dunia kerja.', 'Opini', $guru_id, 'published', '1763361643_Jepretan Layar 2025-11-17 pukul 11.27.20.png'),
                    ('Selamat Datang Tahun Ajaran Baru 2024/2025', 'Selamat datang para siswa baru di tahun ajaran 2024/2025. Kami sangat senang menyambut kalian semua di sekolah tercinta ini. Mari bersama-sama menciptakan lingkungan belajar yang kondusif dan menyenangkan.', 'Pengumuman', $guru_id, 'published', 'pahlawan.jpeg'),
                    ('Tips Belajar Efektif di Rumah', 'Belajar di rumah memerlukan strategi khusus agar tetap efektif. Gunakan teknik pomodoro dan buat jadwal yang konsisten. Pastikan tempat belajar nyaman dan bebas dari gangguan.', 'Pendidikan', $guru_id, 'published', '1763431477_691bd4358ae7f.png')");
            }
        }
        
        // Handle search query
        if (!empty($search_query)) {
            $search_param = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
            $search_results = mysqli_query($conn, "SELECT a.*, COALESCE(u.nama, 'Unknown') as nama, COALESCE(u.role, 'user') as role,
                0 as like_count, 0 as comment_count, 0 as share_count
                FROM artikel a LEFT JOIN users u ON a.author_id = u.id 
                WHERE (a.judul LIKE '$search_param' OR a.konten LIKE '$search_param' OR a.kategori LIKE '$search_param')
                ORDER BY a.created_at DESC LIMIT 20");
        }
        
        // Get latest uploaded articles (newest uploads)
        $artikel_terbaru = mysqli_query($conn, "SELECT a.*, COALESCE(u.nama, 'Unknown') as nama, COALESCE(u.role, 'user') as role,
            0 as like_count, 0 as comment_count, 0 as share_count
            FROM artikel a LEFT JOIN users u ON a.author_id = u.id 
            WHERE a.status = 'published' AND DATE(a.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY a.created_at DESC LIMIT 12");
        
        // Get popular articles (articles with most interactions)
        $artikel_populer = mysqli_query($conn, "SELECT a.*, COALESCE(u.nama, 'Unknown') as nama, COALESCE(u.role, 'user') as role,
            (SELECT COUNT(*) FROM likes WHERE artikel_id = a.id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE artikel_id = a.id) as comment_count,
            (SELECT COUNT(*) FROM shares WHERE artikel_id = a.id) as share_count,
            ((SELECT COUNT(*) FROM likes WHERE artikel_id = a.id) + 
             (SELECT COUNT(*) FROM comments WHERE artikel_id = a.id) + 
             (SELECT COUNT(*) FROM shares WHERE artikel_id = a.id)) as total_interactions
            FROM artikel a LEFT JOIN users u ON a.author_id = u.id 
            WHERE a.status = 'published'
            HAVING total_interactions > 0
            ORDER BY total_interactions DESC, a.created_at DESC LIMIT 8");
        
        // Get activity gallery (articles with photos, sorted by date)
        $galeri_kegiatan = mysqli_query($conn, "SELECT a.*, COALESCE(u.nama, 'Unknown') as nama, COALESCE(u.role, 'user') as role,
            (SELECT COUNT(*) FROM likes WHERE artikel_id = a.id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE artikel_id = a.id) as comment_count
            FROM artikel a LEFT JOIN users u ON a.author_id = u.id 
            WHERE a.status = 'published' AND (a.foto IS NOT NULL AND a.foto != '') 
            ORDER BY a.created_at DESC LIMIT 16");
        
        // Count total articles for stats
        $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel WHERE status = 'published'");
        if ($count_result) {
            $total_artikel = mysqli_fetch_assoc($count_result)['total'];
        }
        
        if ($artikel_terbaru && mysqli_num_rows($artikel_terbaru) > 0) {
            $use_database = true;
        } else {
            $use_database = true; // Still use database even if no recent articles
        }
    }
} catch (Exception $e) {
    $use_database = false;
}

// If database connection fails, set empty arrays
if (!$use_database) {
    $artikel_terbaru = [];
    $total_artikel = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Sekolah - Beranda</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('uploads/backgrounds/school-building.jpg') center/cover no-repeat fixed;
            background-color: #667eea;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.8) 0%, rgba(118, 75, 162, 0.8) 100%);
            z-index: -1;
        }
        
        .main-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-logo {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .sidebar-subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            display: block;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            border-left-color: white;
            transform: translateX(5px);
        }
        
        .menu-item i {
            width: 20px;
            margin-right: 12px;
        }
        
        .sidebar-stats {
            padding: 20px;
            margin: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .content-area {
            margin-left: 280px;
            flex: 1;
            min-height: 100vh;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .content-area {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: #667eea;
                color: white;
                border: none;
                padding: 12px;
                border-radius: 8px;
                cursor: pointer;
            }
        }
        
        .mobile-menu-btn {
            display: none;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }
        
        .nav-links a:hover {
            opacity: 0.8;
        }
        
        .login-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            background: white;
            color: #667eea;
        }
        
        .hero {
            background: url('uploads/backgrounds/school-building.jpg') center/cover no-repeat;
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.7) 0%, rgba(118, 75, 162, 0.7) 100%);
            z-index: 1;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="1.5" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        
        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }
        
        .hero h1 {
            font-size: 2.8rem;
            margin-bottom: 20px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 1.3rem;
            opacity: 0.95;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }
        
        .stats {
            display: flex;
            justify-content: center;
            gap: 60px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            min-width: 150px;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            display: block;
            margin-bottom: 8px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .stat-label {
            opacity: 0.9;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-header h2 {
            font-size: 2.2rem;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .section-header p {
            color: #666;
            font-size: 1.15rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .section-tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
            flex-wrap: wrap;
            padding: 0 20px;
        }
        
        .tab-btn {
            padding: 14px 28px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 30px;
            color: #666;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        
        .tab-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .tab-btn:hover::before {
            left: 100%;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .tab-btn:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .article-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .article-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            border-color: rgba(102, 126, 234, 0.2);
        }
        
        .article-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-bottom: 1px solid #eee;
            position: relative;
            overflow: hidden;
        }
        
        .article-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        
        .article-card:hover .article-header::before {
            transform: translateX(100%);
        }
        
        .article-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.4s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .article-card:hover .article-image {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .article-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
            line-height: 1.3;
            position: relative;
            z-index: 1;
        }
        
        .article-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
            position: relative;
            z-index: 1;
        }
        
        .article-meta i {
            margin-right: 5px;
        }
        
        .article-content {
            padding: 20px;
            position: relative;
        }
        
        .article-excerpt {
            color: #555;
            line-height: 1.6;
            margin-bottom: 18px;
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .article-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
            margin-top: auto;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
        }
        
        .action-btn {
            padding: 8px 14px;
            border: 2px solid #e9ecef;
            border-radius: 20px;
            background: #f8f9fa;
            color: #666;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .action-btn:hover::before {
            left: 100%;
        }
        
        .action-btn:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .read-more {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
            border: none;
            cursor: pointer;
        }
        
        .read-more::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        
        .read-more:hover::before {
            left: 100%;
        }
        
        .read-more:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #666;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 25px;
            opacity: 0.2;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .empty-state h3 {
            font-size: 1.6rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .empty-state p {
            font-size: 1rem;
            opacity: 0.8;
            max-width: 400px;
            margin: 0 auto;
        }
        
        /* Search input placeholder styling */
        .navbar input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        /* Gallery hover effects */
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .gallery-item:hover img {
            transform: scale(1.05);
        }
        
        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .navbar form {
                display: none;
            }
            
            .hero h1 {
                font-size: 2.2rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .hero {
                padding: 60px 0;
            }
            
            .stats {
                flex-direction: column;
                gap: 20px;
                margin-top: 30px;
            }
            
            .stat-item {
                min-width: auto;
                margin: 0 20px;
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .article-actions {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
              E-MADING BAKNUS
            </div>
            <div class="nav-links">
                <a href="#home" onclick="scrollToSection('home')">Beranda</a>
                <a href="#articles" onclick="scrollToSection('articles')">Artikel</a>
                <a href="#tentang" onclick="scrollToSection('tentang')">Tentang</a>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <!-- Search Form in Navbar -->
                <form method="GET" onsubmit="performSearch(event)" style="display: flex; align-items: center; gap: 5px;">
                    <div style="display: flex; background: rgba(255,255,255,0.2); border-radius: 25px; padding: 2px;">
                        <input type="text" id="searchInput" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Cari artikel..." style="padding: 8px 15px; border: none; border-radius: 25px; background: transparent; color: white; font-size: 14px; outline: none; width: 200px;" placeholder-style="color: rgba(255,255,255,0.7);">
                        <button type="submit" style="background: rgba(255,255,255,0.3); color: white; border: none; padding: 8px 12px; border-radius: 25px; cursor: pointer; margin-left: 2px;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <button type="button" onclick="resetSearch()" style="background: rgba(220,53,69,0.8); color: white; border: none; padding: 8px 12px; border-radius: 25px; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </form>
                
                <a href="login.php" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </a>
            </div>
        </div>
    </nav>

    <section id="home" class="hero">
        <div class="hero-container">
            <h1><i class="fas fa-newspaper"></i> E-MADING SMK BAKTI NUSANTARA 666</h1>
            <p>Baca artikel terbaru dari guru dan dapatkan informasi penting sekolah</p>
            
            <?php if (!empty($search_query)): ?>
            <!-- Search Results Info -->
            <div style="text-align: center; margin: 20px 0;">
                <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem;">Hasil pencarian untuk: "<?php echo htmlspecialchars($search_query); ?>"</p>
            </div>
            <?php endif; ?>
            
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_artikel; ?></span>
                    <span class="stat-label">Artikel Tersedia</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Akses Online</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">Gratis</span>
                    <span class="stat-label">Untuk Semua</span>
                </div>
            </div>
        </div>
    </section>

    <section id="articles" class="container">
        <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            
            <?php if (!empty($search_query)): ?>
            <!-- Search Results Section -->
            <div style="margin-bottom: 40px;">
                <h2 style="color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-search"></i> Hasil Pencarian: "<?php echo htmlspecialchars($search_query); ?>"
                </h2>
                
                <?php if ($search_results && mysqli_num_rows($search_results) > 0): ?>
                    <p style="color: #666; margin-bottom: 25px;">Ditemukan <?php echo mysqli_num_rows($search_results); ?> artikel</p>
                    <div class="articles-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px;">
                        <?php while($art = mysqli_fetch_assoc($search_results)): 
                            $image_path = getImagePath($art['foto']);
                        ?>
                        <div class="article-card" style="background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; transition: all 0.3s ease;">
                            <?php if ($image_path): ?>
                                <div style="position: relative; overflow: hidden; background: #f8f9fa;">
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" style="width: 100%; height: 250px; object-fit: contain; transition: transform 0.3s ease;">
                                    <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($art['kategori'] ?: 'Artikel'); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="position: relative; overflow: hidden; background: #f8f9fa; height: 250px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-newspaper" style="font-size: 4rem; color: #ccc;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div style="padding: 25px;">
                                <h3 style="font-size: 1.4rem; font-weight: 700; color: #333; margin-bottom: 15px; line-height: 1.3;">
                                    <?php echo htmlspecialchars($art['judul']); ?>
                                </h3>
                                
                                <div style="display: flex; align-items: center; gap: 15px; font-size: 0.85rem; color: #666; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;">
                                    <span><i class="fas fa-user" style="color: #28a745;"></i> <?php echo htmlspecialchars($art['nama']); ?></span>
                                    <span><i class="fas fa-calendar" style="color: #007bff;"></i> <?php echo date('d M Y', strtotime($art['created_at'])); ?></span>
                                </div>
                                
                                <p style="color: #555; line-height: 1.6; margin-bottom: 20px; height: 60px; overflow: hidden;">
                                    <?php 
                                    $preview = htmlspecialchars(substr($art['konten'], 0, 120));
                                    echo $preview;
                                    if (strlen($art['konten']) > 120) {
                                        echo '... <span class="read-more-link" style="color: #667eea; font-weight: 600; cursor: pointer;" data-id="' . $art['id'] . '" data-title="' . htmlspecialchars($art['judul']) . '" data-content="' . htmlspecialchars($art['konten']) . '" data-image="' . ($image_path ? htmlspecialchars($image_path) : '') . '" data-author="' . htmlspecialchars($art['nama']) . '" data-date="' . date('d M Y', strtotime($art['created_at'])) . '">Baca Selengkapnya</span>';
                                    }
                                    ?>
                                </p>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; gap: 8px;">
                                        <span style="padding: 8px 12px; background: #f8f9fa; border-radius: 15px; font-size: 0.8rem; color: #666;">
                                            <i class="fas fa-heart"></i> <?php echo $art['like_count']; ?>
                                        </span>
                                        <span style="padding: 8px 12px; background: #f8f9fa; border-radius: 15px; font-size: 0.8rem; color: #666;">
                                            <i class="fas fa-comment"></i> <?php echo $art['comment_count']; ?>
                                        </span>
                                    </div>
                                    <button class="read-article-btn" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 25px; cursor: pointer; font-weight: 600;" data-id="<?php echo $art['id']; ?>" data-title="<?php echo htmlspecialchars($art['judul']); ?>" data-content="<?php echo htmlspecialchars($art['konten']); ?>" data-image="<?php echo $image_path ? htmlspecialchars($image_path) : ''; ?>" data-author="<?php echo htmlspecialchars($art['nama']); ?>" data-date="<?php echo date('d M Y', strtotime($art['created_at'])); ?>">
                                        <i class="fas fa-eye"></i> Baca
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                        <h3>Tidak ada hasil ditemukan</h3>
                        <p>Coba gunakan kata kunci yang berbeda atau lebih umum</p>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin: 30px 0; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a href="index.php" style="background: #6c757d; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: 600;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                    </a>
                    <button onclick="resetSearch()" style="background: #dc3545; color: white; padding: 12px 25px; border: none; border-radius: 25px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-undo"></i> Reset Pencarian
                    </button>
                </div>
            </div>
            <?php else: ?>
            <!-- Tabs Navigation -->
            <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; flex-wrap: wrap;">
                <button onclick="showTab('terbaru')" id="tab-terbaru" class="tab-btn" style="padding: 12px 24px; background: #667eea; color: white; border: 2px solid #667eea; border-radius: 25px; cursor: pointer; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fas fa-clock"></i> Artikel Terbaru
                </button>
                <button onclick="showTab('populer')" id="tab-populer" class="tab-btn" style="padding: 12px 24px; background: #f8f9fa; color: #666; border: 2px solid #e9ecef; border-radius: 25px; cursor: pointer; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fas fa-fire"></i> Terpopuler
                </button>
                <button onclick="showTab('galeri')" id="tab-galeri" class="tab-btn" style="padding: 12px 24px; background: #f8f9fa; color: #666; border: 2px solid #e9ecef; border-radius: 25px; cursor: pointer; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fas fa-images"></i> Galeri Kegiatan
                </button>
            </div>

            <!-- Tab Content: Artikel Terbaru -->
            <div id="content-terbaru" class="tab-content" style="display: block;">
                <h3 style="margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-clock"></i> Artikel Terbaru
                </h3>
                <?php if ($use_database && mysqli_num_rows($artikel_terbaru) > 0): ?>
                    <div class="articles-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; margin-top: 20px;">
                    <?php while($art = mysqli_fetch_assoc($artikel_terbaru)): 
                        $image_path = getImagePath($art['foto']);
                    ?>
                    <div class="article-card" style="background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; transition: all 0.3s ease;">
                        <!-- Article Image -->
                        <!-- Article Image (always show like guru dashboard) -->
                        <?php if ($image_path): ?>
                            <div style="position: relative; overflow: hidden; background: #f8f9fa;">
                                <img src="<?php echo htmlspecialchars($image_path); ?>" style="width: 100%; height: 250px; object-fit: contain; transition: transform 0.3s ease;">
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                                    <?php if (!empty($art['kategori'])): ?>
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($art['kategori']); ?>
                                    <?php else: ?>
                                        <i class="fas fa-newspaper"></i> Artikel
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="position: relative; overflow: hidden; background: #f8f9fa; height: 250px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-newspaper" style="font-size: 4rem; color: #ccc;"></i>
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                                    <?php if (!empty($art['kategori'])): ?>
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($art['kategori']); ?>
                                    <?php else: ?>
                                        <i class="fas fa-newspaper"></i> Artikel
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Article Content -->
                        <div style="padding: 25px;">
                            <!-- Title -->
                            <h3 style="font-size: 1.4rem; font-weight: 700; color: #333; margin-bottom: 15px; line-height: 1.3; min-height: 60px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($art['judul']); ?>
                            </h3>
                            
                            <!-- Meta Info -->
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
                                <?php 
                                $preview = htmlspecialchars(substr($art['konten'], 0, 120));
                                echo $preview;
                                if (strlen($art['konten']) > 120) {
                                    echo '... <span class="read-more-link" style="color: #667eea; font-weight: 600; cursor: pointer;" data-id="' . $art['id'] . '" data-title="' . htmlspecialchars($art['judul']) . '" data-content="' . htmlspecialchars($art['konten']) . '" data-image="' . ($image_path ? htmlspecialchars($image_path) : '') . '" data-author="' . htmlspecialchars($art['nama']) . '" data-date="' . date('d M Y', strtotime($art['created_at'])) . '">Baca Selengkapnya</span>';
                                }
                                ?>
                            </p>
                            
                            <!-- Action Buttons -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <div style="display: flex; gap: 8px;">
                                    <?php 
                                    // Get real counts from database for all visitors
                                    $like_count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM likes WHERE artikel_id = {$art['id']}");
                                    $like_count = $like_count_result ? mysqli_fetch_assoc($like_count_result)['count'] : 0;
                                    
                                    $comment_count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM comments WHERE artikel_id = {$art['id']}");
                                    $comment_count = $comment_count_result ? mysqli_fetch_assoc($comment_count_result)['count'] : 0;
                                    
                                    // Get unique likers count
                                    $unique_likers_result = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as count FROM likes WHERE artikel_id = {$art['id']}");
                                    $unique_likers = $unique_likers_result ? mysqli_fetch_assoc($unique_likers_result)['count'] : 0;
                                    ?>
                                    <button onclick="alert('Login untuk berinteraksi')" style="padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #e74c3c; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; font-weight: 500;" title="<?php echo $unique_likers; ?> orang menyukai artikel ini">
                                        <i class="fas fa-heart"></i> <?php echo $like_count; ?> <small style="opacity: 0.7;">(<?php echo $unique_likers; ?> orang)</small>
                                    </button>
                                    <button onclick="showComments(<?php echo $art['id']; ?>)" style="padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; font-weight: 500;">
                                        <i class="fas fa-comment"></i> <?php echo $comment_count; ?>
                                    </button>
                                    <button onclick="alert('Login untuk berinteraksi')" style="padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 25px; background: white; color: #666; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; font-weight: 500;">
                                        <i class="fas fa-share"></i> 0
                                    </button>
                                </div>
                                <button class="read-article-btn" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 12px 25px; border-radius: 25px; border: none; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; box-shadow: 0 2px 10px rgba(40,167,69,0.3); cursor: pointer;" data-id="<?php echo $art['id']; ?>" data-title="<?php echo htmlspecialchars($art['judul']); ?>" data-content="<?php echo htmlspecialchars($art['konten']); ?>" data-image="<?php echo $image_path ? htmlspecialchars($image_path) : ''; ?>" data-author="<?php echo htmlspecialchars($art['nama']); ?>" data-date="<?php echo date('d M Y', strtotime($art['created_at'])); ?>">
                                    <i class="fas fa-book-open"></i> Baca Selengkapnya
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-newspaper" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                        <p>Belum ada artikel terbaru.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Content: Artikel Terpopuler -->
            <div id="content-populer" class="tab-content" style="display: none;">
                <h3 style="margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-fire"></i> Artikel Terpopuler
                </h3>
                <?php 
                // If no popular articles with interactions, get recent articles as fallback
                if (!$artikel_populer || mysqli_num_rows($artikel_populer) == 0) {
                    $artikel_populer = mysqli_query($conn, "SELECT a.*, COALESCE(u.nama, 'Unknown') as nama, COALESCE(u.role, 'user') as role,
                        (SELECT COUNT(*) FROM likes WHERE artikel_id = a.id) as like_count,
                        (SELECT COUNT(*) FROM comments WHERE artikel_id = a.id) as comment_count,
                        (SELECT COUNT(*) FROM shares WHERE artikel_id = a.id) as share_count,
                        0 as total_interactions
                        FROM artikel a LEFT JOIN users u ON a.author_id = u.id 
                        WHERE a.status = 'published'
                        ORDER BY a.created_at DESC LIMIT 8");
                }
                
                if ($artikel_populer && mysqli_num_rows($artikel_populer) > 0): ?>
                    <div class="articles-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; margin-top: 20px;">
                        <?php while($art = mysqli_fetch_assoc($artikel_populer)): 
                            $image_path = getImagePath($art['foto']);
                        ?>
                        <div class="article-card" style="background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; transition: all 0.3s ease;">
                            <?php if ($image_path): ?>
                                <div style="position: relative; overflow: hidden; background: #f8f9fa;">
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" style="width: 100%; height: 250px; object-fit: contain; transition: transform 0.3s ease;">
                                    <div style="position: absolute; top: 10px; left: 10px; background: #e74c3c; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                                        <i class="fas fa-fire"></i> <?php echo $art['total_interactions']; ?> interaksi
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="position: relative; overflow: hidden; background: #f8f9fa; height: 250px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-fire" style="font-size: 4rem; color: #e74c3c;"></i>
                                    <div style="position: absolute; top: 10px; left: 10px; background: #e74c3c; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                                        <i class="fas fa-fire"></i> <?php echo $art['total_interactions']; ?> interaksi
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div style="padding: 25px;">
                                <h3 style="font-size: 1.4rem; font-weight: 700; color: #333; margin-bottom: 15px; line-height: 1.3;">
                                    <?php echo htmlspecialchars($art['judul']); ?>
                                </h3>
                                <div style="display: flex; align-items: center; gap: 15px; font-size: 0.85rem; color: #666; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;">
                                    <span><i class="fas fa-user" style="color: #28a745;"></i> <?php echo htmlspecialchars($art['nama']); ?></span>
                                    <span><i class="fas fa-calendar" style="color: #007bff;"></i> <?php echo date('d M Y', strtotime($art['created_at'])); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 10px; background: #fff3cd; border-radius: 8px;">
                                    <div style="display: flex; gap: 15px; font-size: 0.9rem;">
                                        <span style="color: #e74c3c;"><i class="fas fa-heart"></i> <?php echo $art['like_count']; ?></span>
                                        <span style="color: #007bff;"><i class="fas fa-comment"></i> <?php echo $art['comment_count']; ?></span>
                                        <span style="color: #28a745;"><i class="fas fa-share"></i> <?php echo $art['share_count']; ?></span>
                                    </div>
                                    <span style="background: #e74c3c; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem; font-weight: 600;">
                                        <i class="fas fa-fire"></i> <?php echo $art['total_interactions']; ?> interaksi
                                    </span>
                                </div>
                                <p style="color: #555; line-height: 1.6; margin-bottom: 20px; height: 60px; overflow: hidden;">
                                    <?php echo htmlspecialchars(substr($art['konten'], 0, 120)) . '...'; ?>
                                </p>
                                <button class="read-article-btn" style="background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 25px; cursor: pointer; font-weight: 600; width: 100%;" data-id="<?php echo $art['id']; ?>" data-title="<?php echo htmlspecialchars($art['judul']); ?>" data-content="<?php echo htmlspecialchars($art['konten']); ?>" data-image="<?php echo $image_path ? htmlspecialchars($image_path) : ''; ?>" data-author="<?php echo htmlspecialchars($art['nama']); ?>" data-date="<?php echo date('d M Y', strtotime($art['created_at'])); ?>">
                                    <i class="fas fa-book-open"></i> Baca Selengkapnya
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-fire" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                        <p>Belum ada artikel populer. Artikel dengan interaksi terbanyak akan muncul di sini.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Content: Galeri Kegiatan -->
            <div id="content-galeri" class="tab-content" style="display: none;">
                <h3 style="margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-images"></i> Galeri Kegiatan
                </h3>
                <?php if ($galeri_kegiatan && mysqli_num_rows($galeri_kegiatan) > 0): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">
                        <?php while($item = mysqli_fetch_assoc($galeri_kegiatan)): 
                            $image_path = getImagePath($item['foto']);
                        ?>
                        <?php if ($image_path): ?>
                            <div class="gallery-item read-article-btn" style="background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; transition: all 0.3s ease; cursor: pointer; position: relative;" data-id="<?php echo $item['id']; ?>" data-title="<?php echo htmlspecialchars($item['judul']); ?>" data-content="<?php echo htmlspecialchars($item['konten']); ?>" data-image="<?php echo htmlspecialchars($image_path); ?>" data-author="<?php echo htmlspecialchars($item['nama']); ?>" data-date="<?php echo date('d M Y', strtotime($item['created_at'])); ?>">
                                <div style="position: relative; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" style="width: 100%; height: 220px; object-fit: cover; transition: transform 0.3s ease;">
                                    <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                                        <i class="fas fa-camera"></i> Foto
                                    </div>
                                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); color: white; padding: 25px 15px 15px;">
                                        <h4 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 8px; line-height: 1.3;">
                                            <?php echo htmlspecialchars(substr($item['judul'], 0, 45)) . (strlen($item['judul']) > 45 ? '...' : ''); ?>
                                        </h4>
                                        <div style="display: flex; align-items: center; gap: 15px; font-size: 0.85rem; opacity: 0.9; margin-bottom: 8px;">
                                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($item['nama']); ?></span>
                                            <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($item['created_at'])); ?></span>
                                        </div>
                                        <div style="display: flex; gap: 10px; font-size: 0.8rem; opacity: 0.8;">
                                            <span><i class="fas fa-heart"></i> <?php echo $item['like_count']; ?></span>
                                            <span><i class="fas fa-comment"></i> <?php echo $item['comment_count']; ?></span>
                                        </div>
                                        <?php if (!empty($item['kategori'])): ?>
                                            <div style="margin-top: 8px;">
                                                <span style="background: rgba(255,255,255,0.2); padding: 3px 8px; border-radius: 10px; font-size: 0.75rem;">
                                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['kategori']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="gallery-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(102, 126, 234, 0.1); opacity: 0; transition: opacity 0.3s ease;"></div>
                            </div>
                        <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                        <p style="color: #666; margin-bottom: 15px;">📸 Foto-foto kegiatan sekolah diurutkan berdasarkan tanggal terbaru</p>
                        <a href="login.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 25px; font-weight: 600;">
                            <i class="fas fa-plus"></i> Login untuk Upload Foto
                        </a>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-images" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                        <h4>Belum ada galeri kegiatan</h4>
                        <p>Foto-foto kegiatan sekolah akan ditampilkan di sini</p>
                        <a href="login.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 25px; font-weight: 600; margin-top: 15px; display: inline-block;">
                            <i class="fas fa-camera"></i> Upload Foto Pertama
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="login.php" style="background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; transition: all 0.3s ease;">
                    <i class="fas fa-sign-in-alt"></i> Login untuk Berinteraksi
                </a>
            </div>
            <?php endif; ?>
        </div>
                </div>
            </div>
            
            <!-- Artikel Page -->
            <div id="page-artikel" class="page-content" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-newspaper"></i> Artikel Terbaru
                    </h1>
                    <p class="page-subtitle">Kumpulan artikel terbaru dari guru dan siswa</p>
                </div>
                
                <div class="container">
                    <?php if ($use_database && mysqli_num_rows($artikel_terbaru) > 0): ?>
                        <?php mysqli_data_seek($artikel_terbaru, 0); ?>
                        <div class="articles-grid">
                        <?php while($art = mysqli_fetch_assoc($artikel_terbaru)): 
                            $image_path = getImagePath($art['foto']);
                        ?>
                        <div class="article-card">
                            <?php if ($image_path): ?>
                                <div style="position: relative; overflow: hidden; background: #f8f9fa;">
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" style="width: 100%; height: 250px; object-fit: contain;">
                                    <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($art['kategori'] ?: 'Artikel'); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div style="padding: 25px;">
                                <h3 style="font-size: 1.4rem; font-weight: 700; color: #333; margin-bottom: 15px;">
                                    <?php echo htmlspecialchars($art['judul']); ?>
                                </h3>
                                
                                <div style="display: flex; gap: 15px; font-size: 0.85rem; color: #666; margin-bottom: 15px;">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($art['nama']); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($art['created_at'])); ?></span>
                                </div>
                                
                                <p style="color: #555; margin-bottom: 20px;">
                                    <?php echo htmlspecialchars(substr($art['konten'], 0, 120)) . '...'; ?>
                                </p>
                                
                                <button onclick="showArticlePopup(<?php echo $art['id']; ?>, '<?php echo addslashes($art['judul']); ?>', '<?php echo addslashes($art['konten']); ?>', '<?php echo $image_path ? addslashes($image_path) : ''; ?>', '<?php echo addslashes($art['nama']); ?>', '<?php echo date('d M Y', strtotime($art['created_at'])); ?>')" class="read-more">
                                    <i class="fas fa-eye"></i> Baca Artikel
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-newspaper"></i>
                            <h3>Belum ada artikel</h3>
                            <p>Artikel terbaru akan muncul di sini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Populer Page -->
            <div id="page-populer" class="page-content" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-fire"></i> Artikel Terpopuler
                    </h1>
                    <p class="page-subtitle">Artikel dengan interaksi terbanyak</p>
                </div>
                
                <div class="container">
                    <div class="empty-state">
                        <i class="fas fa-fire"></i>
                        <h3>Artikel Populer</h3>
                        <p>Login untuk melihat artikel populer berdasarkan like dan komentar</p>
                    </div>
                </div>
            </div>
            
            <!-- Galeri Page -->
            <div id="page-galeri" class="page-content" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-images"></i> Galeri Kegiatan
                    </h1>
                    <p class="page-subtitle">Dokumentasi kegiatan sekolah</p>
                </div>
                
                <div class="container">
                    <div class="empty-state">
                        <i class="fas fa-images"></i>
                        <h3>Galeri Kegiatan</h3>
                        <p>Foto-foto kegiatan sekolah akan ditampilkan di sini</p>
                    </div>
                </div>
            </div>
            
    <section id="tentang" class="container">
        <div style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 50px;">
                <h2 style="font-size: 2.5rem; color: #333; margin-bottom: 15px; font-weight: 700;">
                    <i class="fas fa-info-circle" style="color: #667eea;"></i> Tentang E-Mading SMK BN 666
                </h2>
                <p style="color: #666; font-size: 1.2rem; max-width: 600px; margin: 0 auto; line-height: 1.6;">
                    Platform digital terpadu untuk mendukung kegiatan pembelajaran dan komunikasi di SMK Bakti Nusantara 666
                </p>
            </div>
            
            <div class="articles-grid" style="margin-bottom: 40px;">
                <div class="article-card">
                    <div style="padding: 30px; text-align: center;">
                        <div style="font-size: 4rem; margin-bottom: 20px; color: #667eea;">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <h3 style="font-size: 1.5rem; font-weight: 700; color: #333; margin-bottom: 15px;">E-Mading Digital</h3>
                        <p style="color: #666; line-height: 1.6;">Platform untuk berbagi artikel, berita, dan informasi sekolah secara digital dengan sistem moderasi yang baik</p>
                    </div>
                </div>
                
                <div class="article-card">
                    <div style="padding: 30px; text-align: center;">
                        <div style="font-size: 4rem; margin-bottom: 20px; color: #28a745;">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 style="font-size: 1.5rem; font-weight: 700; color: #333; margin-bottom: 15px;">Multi Role System</h3>
                        <p style="color: #666; line-height: 1.6;">Mendukung tiga peran pengguna: Admin, Guru, dan Siswa dengan hak akses yang berbeda sesuai kebutuhan</p>
                    </div>
                </div>
                
                <div class="article-card">
                    <div style="padding: 30px; text-align: center;">
                        <div style="font-size: 4rem; margin-bottom: 20px; color: #007bff;">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3 style="font-size: 1.5rem; font-weight: 700; color: #333; margin-bottom: 15px;">Sistem Interaksi</h3>
                        <p style="color: #666; line-height: 1.6;">Fitur like, comment, dan share untuk meningkatkan engagement dan diskusi yang konstruktif</p>
                    </div>
                </div>
            </div>
            
    
        </div>
    </section>
        </div>
    </div>

    <!-- Article Popup Modal -->
    <div id="articleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; overflow-y: auto;">
        <div style="max-width: 800px; margin: 50px auto; background: white; border-radius: 15px; position: relative; animation: slideIn 0.3s ease;">
            <button onclick="closeArticlePopup()" style="position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666; z-index: 10;">
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
        function loginPrompt() {
            if (confirm('Anda harus login untuk berinteraksi. Login sekarang?')) {
                window.location.href = 'login.php';
            }
        }
        
        // Tab switching functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.style.background = '#f8f9fa';
                btn.style.color = '#666';
                btn.style.borderColor = '#e9ecef';
            });
            
            // Show selected tab content
            document.getElementById('content-' + tabName).style.display = 'block';
            
            // Add active style to selected tab button
            const activeBtn = document.getElementById('tab-' + tabName);
            activeBtn.style.background = '#667eea';
            activeBtn.style.color = 'white';
            activeBtn.style.borderColor = '#667eea';
        }
        

        
        function showComments(articleId) {
            fetch('get_comments.php?artikel_id=' + articleId)
            .then(response => response.json())
            .then(data => {
                let commentsHtml = '<h3>Komentar</h3>';
                if (data.length === 0) {
                    commentsHtml += '<p>Belum ada komentar</p>';
                } else {
                    data.forEach(comment => {
                        commentsHtml += `<div style="background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 8px;"><strong>${comment.nama}:</strong><br>${comment.comment}<br><small>${comment.created_at}</small></div>`;
                    });
                }
                commentsHtml += '<p style="margin-top: 15px; color: #666;"><a href="login.php">Login</a> untuk menambah komentar</p>';
                
                const modal = document.getElementById('articleModal');
                const modalContent = document.getElementById('modalContent');
                modalContent.innerHTML = `<div style="padding: 30px;">${commentsHtml}<button onclick="closeArticlePopup()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin-top: 15px;">Tutup</button></div>`;
                modal.style.display = 'block';
            });
        }
        
        function showArticlePopup(id, title, content, image, author, date) {
            const modal = document.getElementById('articleModal');
            const modalContent = document.getElementById('modalContent');
            
            const imageHtml = image ? `<img src="${image}" style="width: 100%; max-height: 400px; object-fit: contain; background: #f8f9fa; border-radius: 15px 15px 0 0;">` : '';
            
            modalContent.innerHTML = `
                ${imageHtml}
                <div style="padding: 30px;">
                    <h2 style="color: #333; margin-bottom: 15px; line-height: 1.4;">${title}</h2>
                    <div style="display: flex; gap: 20px; margin-bottom: 25px; font-size: 0.9rem; color: #666; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                        <span><i class="fas fa-user" style="color: #667eea;"></i> ${author}</span>
                        <span><i class="fas fa-calendar" style="color: #007bff;"></i> ${date}</span>
                    </div>
                    <div style="color: #555; line-height: 1.8; font-size: 16px; white-space: pre-wrap; margin-bottom: 30px;">${content}</div>
                    
                    <div style="margin-bottom: 20px;">

                    </div>
                    
                    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #eee;">
                        <p style="color: #666; margin-bottom: 20px;">Untuk berinteraksi dengan artikel (like, comment, share), silakan login terlebih dahulu.</p>
                        <a href="login.php" style="background: #667eea; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; margin-right: 15px;">
                            <i class="fas fa-sign-in-alt"></i> Login Sekarang
                        </a>
                        <button onclick="closeArticlePopup()" style="background: #6c757d; color: white; padding: 12px 25px; border: none; border-radius: 25px; cursor: pointer;">
                            <i class="fas fa-times"></i> Tutup
                        </button>
                    </div>
                </div>
            `;
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeArticlePopup() {
            document.getElementById('articleModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside
        document.getElementById('articleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeArticlePopup();
            }
        });
        
        // Tab switching functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.style.background = '#f8f9fa';
                btn.style.color = '#666';
                btn.style.borderColor = '#e9ecef';
            });
            
            // Show selected tab content
            document.getElementById('content-' + tabName).style.display = 'block';
            
            // Add active style to selected tab button
            const activeBtn = document.getElementById('tab-' + tabName);
            activeBtn.style.background = '#667eea';
            activeBtn.style.color = 'white';
            activeBtn.style.borderColor = '#667eea';
        }
        
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Search functionality
        function performSearch(event) {
            event.preventDefault();
            const searchTerm = document.getElementById('searchInput').value.trim();
            if (searchTerm) {
                window.location.href = 'index.php?search=' + encodeURIComponent(searchTerm);
            }
        }
        
        // Reset search functionality
        function resetSearch() {
            document.getElementById('searchInput').value = '';
            window.location.href = 'index.php';
        }
        
        // Smooth scroll to section
        function scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        // Event listeners for read more links and buttons
        document.addEventListener('click', function(e) {
            // Handle read more links and buttons
            if (e.target.classList.contains('read-more-link') || e.target.classList.contains('read-article-btn') || e.target.closest('.read-article-btn')) {
                const element = e.target.classList.contains('read-article-btn') ? e.target : e.target.closest('.read-article-btn') || e.target;
                const id = element.dataset.id;
                const title = element.dataset.title;
                const content = element.dataset.content;
                const image = element.dataset.image;
                const author = element.dataset.author;
                const date = element.dataset.date;
                
                if (id && title) {
                    showArticlePopup(id, title, content, image, author, date);
                }
            }
            
            // Handle mobile sidebar
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && sidebar && menuBtn && !sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>