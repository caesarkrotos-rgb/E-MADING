<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../config.php';

$success = $error = '';
$user_id = (int)$_SESSION['user_id'];

// Handle profile update
if ($_POST) {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validasi input
    if (empty($nama) || strlen($nama) > 100) {
        $error = "Nama harus diisi dan maksimal 100 karakter!";
    } elseif (empty($username) || strlen($username) > 50) {
        $error = "Username harus diisi dan maksimal 50 karakter!";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username hanya boleh mengandung huruf, angka, dan underscore!";
    } else {
        // Cek username sudah digunakan atau belum (kecuali username sendiri)
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $username, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $error = "Username sudah digunakan!";
            } else {
                // Update profil dengan prepared statement
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error = "Password minimal 6 karakter!";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = mysqli_prepare($conn, "UPDATE users SET nama = ?, username = ?, password = ? WHERE id = ?");
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "sssi", $nama, $username, $hashed_password, $user_id);
                            $update = mysqli_stmt_execute($stmt);
                            mysqli_stmt_close($stmt);
                        }
                    }
                } else {
                    $stmt = mysqli_prepare($conn, "UPDATE users SET nama = ?, username = ? WHERE id = ?");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "ssi", $nama, $username, $user_id);
                        $update = mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
                
                if (isset($update) && $update) {
                    $_SESSION['nama'] = $nama;
                    $_SESSION['username'] = $username;
                    $success = "Profil berhasil diperbarui!";
                } elseif (empty($error)) {
                    $error = "Gagal memperbarui profil!";
                }
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Terjadi kesalahan sistem!";
        }
    }
}

// Ambil data admin dengan prepared statement
$admin_data = null;
$stmt = mysqli_prepare($conn, "SELECT nama, username, email FROM users WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Ambil statistik dengan prepared statement
$my_stats = ['users' => 0, 'articles' => 0, 'comments' => 0, 'reports' => 0];

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM users");
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $my_stats['users'] = mysqli_fetch_assoc($result)['total'];
    mysqli_stmt_close($stmt);
}

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM artikel");
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $my_stats['articles'] = mysqli_fetch_assoc($result)['total'];
    mysqli_stmt_close($stmt);
}

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM comments");
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $my_stats['comments'] = mysqli_fetch_assoc($result)['total'];
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .profile-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 3rem;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
            font-size: 0.9rem;
        }
        
        .achievement-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .achievement-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .achievement-badges {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-box {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h2><?php echo htmlspecialchars($admin_data['nama'] ?? 'Nama tidak tersedia', ENT_QUOTES, 'UTF-8'); ?></h2>
                <p style="color: #666;">Administrator - <?php echo htmlspecialchars($admin_data['username'] ?? 'Username tidak tersedia', ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo (int)$my_stats['users']; ?></div>
                    <div class="stat-label">Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo (int)$my_stats['articles']; ?></div>
                    <div class="stat-label">Artikel</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo (int)$my_stats['comments']; ?></div>
                    <div class="stat-label">Komentar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo (int)$my_stats['reports']; ?></div>
                    <div class="stat-label">Laporan</div>
                </div>
            </div>
            
            <div class="achievement-section">
                <div class="achievement-title">
                    <i class="fas fa-shield-alt"></i> Status Administrator
                </div>
                <div class="achievement-badges">
                    <span class="badge"><i class="fas fa-crown"></i> Super Admin</span>
                    <span class="badge"><i class="fas fa-users-cog"></i> User Manager</span>
                    <span class="badge"><i class="fas fa-shield-check"></i> Content Moderator</span>
                    <span class="badge"><i class="fas fa-chart-line"></i> System Monitor</span>
                </div>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i> 
                <strong>Info:</strong> Kosongkan field password jika tidak ingin mengubah password.
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" class="form-control" 
                           value="<?php echo htmlspecialchars($admin_data['nama'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                           maxlength="100" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($admin_data['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                           maxlength="50" pattern="[a-zA-Z0-9_]+" 
                           title="Username hanya boleh mengandung huruf, angka, dan underscore" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password Baru (Opsional)</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Kosongkan jika tidak ingin mengubah" minlength="6">
                    <small style="color: #666;">Minimal 6 karakter jika ingin mengubah password</small>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
    </div>
</body>
</html>