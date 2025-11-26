<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../config.php';

// Get filters
$filter_date = $_GET['date'] ?? '';
$status_filter = $_GET['status'] ?? '';
$author_filter = $_GET['author'] ?? '';

// Build WHERE conditions
$where_conditions = [];
if (!empty($filter_date)) {
    $where_conditions[] = "DATE(a.created_at) = '$filter_date'";
}
if (!empty($status_filter)) {
    $where_conditions[] = "a.status = '$status_filter'";
}
if (!empty($author_filter)) {
    $where_conditions[] = "a.author_id = '$author_filter'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get statistics
$total_articles = 0;
$total_published = 0;
$total_draft = 0;
$total_users = 0;

// If no filters, show all articles
if (empty($where_conditions)) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM artikel");
    if ($result) {
        $total_articles = mysqli_fetch_assoc($result)['count'];
    }
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM artikel WHERE status='published'");
    if ($result) {
        $total_published = mysqli_fetch_assoc($result)['count'];
    }
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM artikel WHERE status='draft'");
    if ($result) {
        $total_draft = mysqli_fetch_assoc($result)['count'];
    }
} else {
    // With filters
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM artikel a $where_clause");
    if ($result) {
        $total_articles = mysqli_fetch_assoc($result)['count'];
    }
    
    $published_where = str_replace($where_clause, $where_clause . " AND a.status='published'", $where_clause);
    if ($where_clause) {
        $published_where = $where_clause . " AND a.status='published'";
    } else {
        $published_where = "WHERE a.status='published'";
    }
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM artikel a $published_where");
    if ($result) {
        $total_published = mysqli_fetch_assoc($result)['count'];
    }
    
    $draft_where = str_replace($where_clause, $where_clause . " AND a.status='draft'", $where_clause);
    if ($where_clause) {
        $draft_where = $where_clause . " AND a.status='draft'";
    } else {
        $draft_where = "WHERE a.status='draft'";
    }
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM artikel a $draft_where");
    if ($result) {
        $total_draft = mysqli_fetch_assoc($result)['count'];
    }
}

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
if ($result) {
    $total_users = mysqli_fetch_assoc($result)['count'];
}

// Get authors
$authors = [];
$result = mysqli_query($conn, "SELECT id, nama FROM users WHERE role IN ('guru', 'siswa') ORDER BY nama");
if ($result) {
    while($author = mysqli_fetch_assoc($result)) {
        $authors[] = $author;
    }
}

// Get articles
$articles = mysqli_query($conn, "SELECT a.*, u.nama FROM artikel a LEFT JOIN users u ON a.author_id = u.id $where_clause ORDER BY a.created_at DESC LIMIT 20");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .page-header {
            background: white; padding: 30px; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;
        }
        
        .filters {
            background: white; padding: 20px; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;
        }
        
        .filter-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px; margin-bottom: 15px;
        }
        
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select {
            padding: 10px; border: 1px solid #ddd; border-radius: 8px;
        }
        
        .btn {
            padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer;
            font-weight: 600; text-decoration: none; display: inline-block;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        
        .stat-card {
            background: white; padding: 25px; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;
        }
        
        .stat-number { font-size: 2.5rem; font-weight: bold; margin-bottom: 10px; }
        .stat-label { color: #666; font-size: 0.9rem; }
        
        .table-container {
            background: white; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden;
        }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        
        .status-badge {
            padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;
        }
        .status-published { background: #d4edda; color: #155724; }
        .status-draft { background: #fff3cd; color: #856404; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .switch-small {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
        }
        
        .switch-small input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider-small {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 20px;
        }
        
        .slider-small:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider-small {
            background-color: #28a745;
        }
        
        input:not(:checked) + .slider-small {
            background-color: #ffc107;
        }
        
        input:checked + .slider-small:before {
            transform: translateX(20px);
        }
        
        .article-hidden {
            opacity: 0.3;
            background-color: #f8f9fa !important;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Laporan Sistem</h1>
            <p>Statistik dan data portal artikel sekolah</p>
        </div>

       

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" style="color: #667eea;"><?php echo $total_articles; ?></div>
                <div class="stat-label">Total Artikel</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #28a745;"><?php echo $total_published; ?></div>
                <div class="stat-label">Published</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ffc107;"><?php echo $total_draft; ?></div>
                <div class="stat-label">Draft</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #17a2b8;"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>

        <div style="background: white; padding: 20px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-download"></i> Export Laporan</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div style="border: 1px solid #e9ecef; border-radius: 10px; padding: 20px;">
                    <h4 style="margin-bottom: 15px; color: #667eea;"><i class="fas fa-calendar"></i> Laporan Per Bulan</h4>
                    <form method="GET" action="export_report.php" style="display: flex; flex-direction: column; gap: 15px;">
                        <input type="hidden" name="type" value="monthly">
                        <div>
                            <label style="font-weight: 600; margin-bottom: 5px; display: block;">Pilih Bulan:</label>
                            <input type="month" name="month" value="<?php echo date('Y-m'); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="format" value="pdf" style="flex: 1; background: #dc3545; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;" onclick="this.form.target='_blank';">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button type="submit" name="format" value="excel" style="flex: 1; background: #28a745; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                        </div>
                    </form>
                </div>
                
                <div style="border: 1px solid #e9ecef; border-radius: 10px; padding: 20px;">
                    <h4 style="margin-bottom: 15px; color: #28a745;"><i class="fas fa-tags"></i> Laporan Per Kategori</h4>
                    <form method="GET" action="export_report.php" style="display: flex; flex-direction: column; gap: 15px;">
                        <input type="hidden" name="type" value="category">
                        <div>
                            <label style="font-weight: 600; margin-bottom: 5px; display: block;">Pilih Kategori:</label>
                            <select name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php 
                                $categories = mysqli_query($conn, "SELECT DISTINCT kategori FROM artikel WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
                                while($cat = mysqli_fetch_assoc($categories)): 
                                ?>
                                    <option value="<?php echo htmlspecialchars($cat['kategori']); ?>"><?php echo htmlspecialchars($cat['kategori']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="format" value="pdf" style="flex: 1; background: #dc3545; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;" onclick="this.form.target='_blank';">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button type="submit" name="format" value="excel" style="flex: 1; background: #28a745; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <div style="padding: 20px; border-bottom: 1px solid #eee;">
                <h3><i class="fas fa-list"></i> Artikel</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($articles && mysqli_num_rows($articles) > 0): ?>
                        <?php while($article = mysqli_fetch_assoc($articles)): ?>
                        <tr id="article-<?php echo $article['id']; ?>">
                            <td><?php echo htmlspecialchars($article['judul']); ?></td>
                            <td><?php echo htmlspecialchars($article['nama'] ?? 'Unknown'); ?></td>
                            <td>
                                <span style="background: #e7f3ff; color: #0066cc; padding: 4px 8px; border-radius: 10px; font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($article['kategori'] ?? 'Umum'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $article['status']; ?>">
                                    <?php echo ucfirst($article['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($article['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">Tidak ada artikel ditemukan</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    

</body>
</html>