<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}
include '../config.php';

// Auto-create tables if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guru', 'siswa') DEFAULT 'siswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Add email column if it doesn't exist
$check_email_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'email'");
if (!$check_email_column || mysqli_num_rows($check_email_column) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE AFTER username");
}

// Insert default admin if not exists
$check_admin = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
if ($check_admin && mysqli_fetch_assoc($check_admin)['count'] == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (nama, username, email, password, role) VALUES 
        ('Administrator', 'admin', 'admin@sekolah.com', '$admin_password', 'admin')");
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS artikel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    konten TEXT NOT NULL,
    foto VARCHAR(255),
    kategori VARCHAR(100),
    author_id INT NOT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, artikel_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE
)");

// Handle add user
if ($_POST['action'] ?? '' == 'add_user') {
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    if (!empty($nama) && !empty($email) && !empty($_POST['password']) && !empty($role)) {
        $username = explode('@', $email)[0];
        $username = mysqli_real_escape_string($conn, $username);
        
        $check_query = "SELECT id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (!$check_result || mysqli_num_rows($check_result) == 0) {
            $insert_query = "INSERT INTO users (nama, username, email, password, role) VALUES ('$nama', '$username', '$email', '$password', '$role')";
            if (mysqli_query($conn, $insert_query)) {
                header('Location: dashboard.php?user_success=added');
                exit;
            } else {
                $user_error = "Gagal menambahkan user: " . mysqli_error($conn);
            }
        } else {
            $user_error = "Email sudah digunakan!";
        }
    } else {
        $user_error = "Semua field harus diisi!";
    }
}

if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    if ($user_id != $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $user_id AND role IN ('guru', 'siswa')");
        header('Location: dashboard.php?user_success=deleted');
        exit;
    }
}

// Ensure likes and comments tables exist
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



// Get statistics with error handling
$total_users = 0;
$total_artikel = 0;
$total_guru = 0;
$total_siswa = 0;
$total_published = 0;
$total_draft = 0;
$total_likes = 0;
$total_comments = 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
if ($result) $total_users = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel");
if ($result) $total_artikel = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='guru'");
if ($result) $total_guru = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='siswa'");
if ($result) $total_siswa = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel WHERE status='published'");
if ($result) $total_published = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel WHERE status='draft'");
if ($result) $total_draft = mysqli_fetch_assoc($result)['total'];

$total_rejected = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM artikel WHERE status='rejected'");
if ($result) $total_rejected = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM likes");
if ($result) $total_likes = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM comments");
if ($result) $total_comments = mysqli_fetch_assoc($result)['total'];

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


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        
        .admin-card { color: #e74c3c; }
        .guru-card { color: #3498db; }
        .siswa-card { color: #2ecc71; }
        .artikel-card { color: #f39c12; }
        .published-card { color: #28a745; }
        .draft-card { color: #ffc107; }
        .likes-card { color: #dc3545; }
        .comments-card { color: #6f42c1; }
        
        .quick-actions {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .recent-activity {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .quick-actions h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            border-color: #667eea;
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .action-btn i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Dashboard Admin</h1>
                <p>Selamat datang, <?php echo $_SESSION['nama']; ?>!</p>
                
                <?php if (isset($_GET['success'])): ?>
                    <div style="background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 8px; margin: 10px 0; border: 1px solid #c3e6cb;">
                        <?php if ($_GET['success'] == 'published'): ?>
                            <i class="fas fa-check-circle"></i> Artikel berhasil dipublikasi!
                        <?php elseif ($_GET['success'] == 'status_changed'): ?>
                            <i class="fas fa-edit"></i> Status artikel berhasil diubah!
                        <?php elseif ($_GET['success'] == 'deleted'): ?>
                            <i class="fas fa-trash"></i> Artikel berhasil dihapus!
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['user_success'])): ?>
                    <div style="background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 8px; margin: 10px 0; border: 1px solid #c3e6cb;">
                        <?php if ($_GET['user_success'] == 'added'): ?>
                            <i class="fas fa-check-circle"></i> User berhasil ditambahkan!
                        <?php elseif ($_GET['user_success'] == 'deleted'): ?>
                            <i class="fas fa-trash"></i> User berhasil dihapus!
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($user_error)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 10px 15px; border-radius: 8px; margin: 10px 0; border: 1px solid #f5c6cb;">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $user_error; ?>
                    </div>
                <?php endif; ?>

            </div>
            <div class="user-info">
                <a href="kelola_user.php" style="background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 8px; margin-right: 10px; text-decoration: none; font-size: 0.9rem;">
                    <i class="fas fa-users"></i> Kelola User
                </a>
                <span><i class="fas fa-user"></i> <?php echo $_SESSION['nama']; ?></span>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        

        
        <div class="stats-grid">
            <div class="stat-card admin-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            
            <div class="stat-card guru-card">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-number"><?php echo $total_guru; ?></div>
                <div class="stat-label">Total Guru</div>
            </div>
            
            <div class="stat-card siswa-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-number"><?php echo $total_siswa; ?></div>
                <div class="stat-label">Total Siswa</div>
            </div>
            
            <div class="stat-card artikel-card">
                <div class="stat-icon">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="stat-number"><?php echo $total_artikel; ?></div>
                <div class="stat-label">Total Artikel</div>
            </div>
            
            <div class="stat-card published-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $total_published; ?></div>
                <div class="stat-label">Dipublikasi</div>
            </div>
            
            <div class="stat-card draft-card">
                <div class="stat-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="stat-number"><?php echo $total_draft; ?></div>
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
        
        

        
        <div class="recent-activity">
            <h3><i class="fas fa-newspaper"></i> Artikel Terbaru</h3>
            
            <?php 
            // Get latest articles without search filter for recent activity
            $recent_articles = mysqli_query($conn, "SELECT a.*, u.nama, u.role,
                (SELECT COUNT(*) FROM likes WHERE artikel_id = a.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE artikel_id = a.id) as comment_count
                FROM artikel a 
                JOIN users u ON a.author_id = u.id 
                WHERE a.status = 'published'
                ORDER BY a.created_at DESC LIMIT 5");
            
            if ($recent_articles && mysqli_num_rows($recent_articles) > 0): 
                while($art = mysqli_fetch_assoc($recent_articles)): 
                    $image_path = getImagePath($art['foto']);
            ?>
                <div style="background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; transition: all 0.3s ease; border: 1px solid #e9ecef; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; padding: 20px;">
                        <?php 
                        $foto_path = null;
                        if (!empty($art['foto'])) {
                            $foto_path = '../uploads/' . $art['foto'];
                            if (!file_exists($foto_path)) {
                                $foto_path = '../uploads/gallery/' . $art['foto'];
                                if (!file_exists($foto_path)) {
                                    $foto_path = '../uploads/documents/' . $art['foto'];
                                    if (!file_exists($foto_path)) {
                                        $foto_path = null;
                                    }
                                }
                            }
                        }
                        ?>
                        <?php if ($foto_path): ?>
                            <img src="<?php echo htmlspecialchars($foto_path); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 10px; margin-right: 20px; flex-shrink: 0;">
                        <?php else: ?>
                            <div style="width: 80px; height: 80px; background: #f8f9fa; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 20px; flex-shrink: 0;">
                                <i class="fas fa-newspaper" style="font-size: 2rem; color: #ccc;"></i>
                            </div>
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <h4 style="font-size: 1.2rem; font-weight: 600; color: #333; margin-bottom: 8px;"><?php echo htmlspecialchars($art['judul']); ?></h4>
                            <div style="font-size: 0.9rem; color: #666; margin-bottom: 8px;">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($art['nama']); ?> (<?php echo ucfirst($art['role']); ?>) | 
                                <i class="fas fa-calendar"></i> <?php echo date('d M Y H:i', strtotime($art['created_at'])); ?>
                                <?php if (!empty($art['kategori'])): ?>
                                    | <i class="fas fa-tag"></i> <?php echo htmlspecialchars($art['kategori']); ?>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                <button onclick="openArticle(<?php echo $art['id']; ?>)" style="background: #667eea; color: white; padding: 6px 12px; border-radius: 15px; border: none; cursor: pointer; font-size: 0.8rem;">
                                    <i class="fas fa-book-open"></i> Baca
                                </button>
                                <button onclick="editArticle(<?php echo $art['id']; ?>)" style="background: #28a745; color: white; padding: 6px 12px; border-radius: 15px; border: none; cursor: pointer; font-size: 0.8rem;">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button onclick="deleteArticle(<?php echo $art['id']; ?>, '<?php echo addslashes(htmlspecialchars($art['judul'])); ?>')" style="background: #dc3545; color: white; padding: 6px 12px; border-radius: 15px; border: none; cursor: pointer; font-size: 0.8rem;">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="activity-item">
                    <div class="activity-content">
                        <div class="activity-title">Belum ada artikel terbaru</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    
            </div>
        </div>
    </div>
    

    
    <!-- Add User Modal -->
    <div id="addUserModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; margin: 50px auto; padding: 30px; border-radius: 15px; max-width: 500px; position: relative;">
            <span onclick="closeAddUserModal()" style="position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer;">&times;</span>
            <h2><i class="fas fa-user-plus"></i> Tambah User Baru</h2>
            
            <form method="POST" action="dashboard.php" style="margin-top: 20px;">
                <input type="hidden" name="action" value="add_user">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nama Lengkap *</label>
                    <input type="text" name="nama" required minlength="3" maxlength="255" 
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;"
                           placeholder="Masukkan nama lengkap" 
                           onblur="this.style.borderColor = this.value ? '#28a745' : '#dc3545'">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email *</label>
                    <input type="email" name="email" required 
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;"
                           placeholder="contoh@sekolah.com"
                           onblur="this.style.borderColor = this.value && this.checkValidity() ? '#28a745' : '#dc3545'">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Password *</label>
                    <input type="password" name="password" required minlength="6" maxlength="50"
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;"
                           placeholder="Minimal 6 karakter"
                           onblur="this.style.borderColor = this.value.length >= 6 ? '#28a745' : '#dc3545'">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Role *</label>
                    <select name="role" required 
                            style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px;"
                            onchange="this.style.borderColor = this.value ? '#28a745' : '#dc3545'">
                        <option value="">-- Pilih Role --</option>
                        <option value="guru">👨‍🏫 Guru</option>
                        <option value="siswa">👨‍🎓 Siswa</option>
                    </select>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" onclick="closeAddUserModal()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin-right: 10px; cursor: pointer;">
                        Batal
                    </button>
                    <button type="submit" id="submitBtn" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-save"></i> Simpan User
                    </button>
                </div>
                
                <div style="margin-top: 10px; font-size: 0.9rem; color: #666;">
                    * Field wajib diisi
                </div>
            </form>
        </div>
    </div>
    
    <!-- Article Modal -->
    <div id="articleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; overflow-y: auto;">
        <div style="background: white; margin: 20px auto; padding: 0; border-radius: 15px; max-width: 800px; position: relative; max-height: 90vh; overflow-y: auto;">
            <div style="position: sticky; top: 0; background: white; padding: 20px; border-bottom: 1px solid #eee; border-radius: 15px 15px 0 0; z-index: 10;">
                <span onclick="closeArticleModal()" style="position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer; color: #999;">&times;</span>
                <h2 id="modalTitle" style="margin: 0; padding-right: 40px; color: #333;"></h2>
                <div id="modalMeta" style="color: #666; font-size: 0.9rem; margin-top: 8px;"></div>
            </div>
            
            <div style="padding: 20px;">
                <div id="modalImage" style="margin-bottom: 20px; text-align: center;"></div>
                <div id="modalContent" style="line-height: 1.6; color: #333;"></div>
                

            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            refreshDashboardStats();
        });
        
        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
            setTimeout(() => {
                document.querySelector('input[name="nama"]').focus();
            }, 100);
        }
        
        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
            // Reset form and border colors
            const form = document.querySelector('#addUserModal form');
            form.reset();
            form.querySelectorAll('input, select').forEach(input => {
                input.style.borderColor = '#e9ecef';
            });
        }
        
        function deleteUser(id, name) {
            if (confirm(`Yakin ingin menghapus user "${name}"?\n\nUser akan dihapus permanen dari sistem.`)) {
                window.location.href = `?delete_user=${id}`;
            }
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('#addUserModal form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const nama = form.querySelector('input[name="nama"]').value.trim();
                    const email = form.querySelector('input[name="email"]').value.trim();
                    const password = form.querySelector('input[name="password"]').value;
                    const role = form.querySelector('select[name="role"]').value;
                    
                    if (!nama || nama.length < 3) {
                        alert('Nama harus diisi minimal 3 karakter!');
                        e.preventDefault();
                        return;
                    }
                    
                    if (!email || !email.includes('@')) {
                        alert('Email harus valid!');
                        e.preventDefault();
                        return;
                    }
                    
                    if (!password || password.length < 6) {
                        alert('Password harus minimal 6 karakter!');
                        e.preventDefault();
                        return;
                    }
                    
                    if (!role) {
                        alert('Role harus dipilih!');
                        e.preventDefault();
                        return;
                    }
                    
                    // Show loading state
                    const submitBtn = document.getElementById('submitBtn');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                    submitBtn.disabled = true;
                });
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addUserModal');
            if (event.target == modal) {
                closeAddUserModal();
            }
        }
        

        
        function refreshDashboardStats() {
            fetch('../get_global_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update likes
                    const likesElement = document.querySelector('.likes-card .stat-number');
                    if (likesElement) likesElement.textContent = data.likes;
                    
                    // Update comments
                    const commentsElement = document.querySelector('.comments-card .stat-number');
                    if (commentsElement) commentsElement.textContent = data.comments;
                }
            })
            .catch(error => console.log('Stats refresh error:', error));
        }
        

        

        

        
        let currentArticleId = null;
        
        function openArticle(id) {
            if (!id) return;
            
            fetch('../get_article_data.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                console.log('Article data received:', data);
                if (data && data.success) {
                    showArticlePopup(data.id, data.title || '', data.content || '', data.image || '', data.author || '', data.date || '', data.like_count || 0, data.comment_count || 0);
                } else {
                    alert('Artikel tidak ditemukan atau terjadi kesalahan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal memuat artikel');
            });
        }
        
        function showArticlePopup(id, title, content, image, author, date, likeCount, commentCount) {
            if (!id) return;
            
            currentArticleId = id;
            
            document.getElementById('modalTitle').textContent = title || 'Artikel';
            document.getElementById('modalMeta').innerHTML = `<i class="fas fa-user"></i> ${author || 'Unknown'} | <i class="fas fa-calendar"></i> ${date || 'Unknown'}`;
            document.getElementById('modalContent').innerHTML = content ? content.replace(/\n/g, '<br>') : 'Konten tidak tersedia';
            document.getElementById('modalLikeCount').textContent = likeCount || 0;
            document.getElementById('modalCommentCount').textContent = commentCount || 0;
            
            const imageContainer = document.getElementById('modalImage');
            if (image && image.trim()) {
                const imagePaths = [
                    '../uploads/' + image,
                    '../uploads/gallery/' + image,
                    '../uploads/profiles/' + image,
                    '../uploads/documents/' + image,
                    '../' + image
                ];
                
                let imgElement = document.createElement('img');
                imgElement.style.cssText = 'max-width: 100%; height: auto; border-radius: 10px; object-fit: contain; display: block; margin: 0 auto;';
                
                let currentIndex = 0;
                
                function tryNextImage() {
                    if (currentIndex >= imagePaths.length) {
                        imageContainer.innerHTML = '';
                        return;
                    }
                    
                    imgElement.onload = function() {
                        imageContainer.innerHTML = '';
                        imageContainer.appendChild(imgElement);
                    };
                    
                    imgElement.onerror = function() {
                        currentIndex++;
                        tryNextImage();
                    };
                    
                    imgElement.src = imagePaths[currentIndex];
                }
                
                tryNextImage();
            } else {
                imageContainer.innerHTML = '';
            }
            
            document.getElementById('articleModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeArticleModal() {
            document.getElementById('articleModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            currentArticleId = null;
        }
        

        

        
        function editArticle(id) {
            window.location.href = '../edit_artikel.php?id=' + id;
        }
        
        function deleteArticle(id, title) {
            if (confirm('Yakin ingin menghapus artikel "' + title + '"?\n\nArtikel yang dihapus tidak dapat dikembalikan!')) {
                fetch('delete_artikel.php?id=' + id)
                .then(response => {
                    if (response.ok) {
                        location.reload();
                    } else {
                        alert('Gagal menghapus artikel!');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus artikel!');
                });
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('addUserModal');
            const articleModal = document.getElementById('articleModal');
            if (event.target == modal) {
                closeAddUserModal();
            } else if (event.target == articleModal) {
                closeArticleModal();
            }
        }
        

    </script>
</body>
</html>