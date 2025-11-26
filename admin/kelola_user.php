<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../config.php';

// Create users table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    username VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guru', 'siswa') DEFAULT 'siswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert default admin if not exists
$check_admin = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
if ($check_admin && mysqli_fetch_assoc($check_admin)['count'] == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (nama, username, email, password, role) VALUES 
        ('Administrator', 'admin', 'admin@sekolah.com', '$admin_password', 'admin'),
        ('Budi Santoso', 'budi', 'budi@sekolah.com', '$admin_password', 'guru'),
        ('Ahmad Rizki', 'ahmad', 'ahmad@sekolah.com', '$admin_password', 'siswa')");
}

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
                header('Location: kelola_user.php?success=added');
                exit;
            } else {
                $error = "Gagal menambahkan user: " . mysqli_error($conn);
            }
        } else {
            $error = "Email sudah digunakan!";
        }
    } else {
        $error = "Semua field harus diisi!";
    }
}

// Handle edit user
if ($_POST['action'] ?? '' == 'edit_user') {
    $user_id = (int)$_POST['user_id'];
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    if (!empty($nama) && !empty($email) && !empty($role)) {
        $check_query = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if (!$check_result || mysqli_num_rows($check_result) == 0) {
            $update_query = "UPDATE users SET nama = '$nama', email = '$email', role = '$role' WHERE id = $user_id";
            if (mysqli_query($conn, $update_query)) {
                header('Location: kelola_user.php?success=updated');
                exit;
            } else {
                $error = "Gagal mengupdate user: " . mysqli_error($conn);
            }
        } else {
            $error = "Email sudah digunakan!";
        }
    } else {
        $error = "Semua field harus diisi!";
    }
}

// Handle delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    if ($user_id != $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
        header('Location: kelola_user.php?success=deleted');
        exit;
    }
}

// Get users
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
if (!$users) {
    $users = [];
    $error = "Error loading users: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .page-header {
            background: white; padding: 30px; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;
        }
        
        .btn {
            padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer;
            font-weight: 600; text-decoration: none; display: inline-block;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        
        .users-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .user-card {
            background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px; transition: all 0.3s ease;
        }
        
        .role-badge {
            padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;
        }
        .role-admin { background: #f8d7da; color: #721c24; }
        .role-guru { background: #d1ecf1; color: #0c5460; }
        .role-siswa { background: #d4edda; color: #155724; }
        
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1000;
        }
        
        .modal-content {
            background: white; margin: 50px auto; padding: 30px; border-radius: 15px;
            max-width: 500px; position: relative;
        }
        
        .close { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1><i class="fas fa-users"></i> Kelola User</h1>
                    <p>Mengelola semua pengguna dalam sistem</p>
                </div>
                <button onclick="openAddModal()" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah User
                </button>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px 15px; border-radius: 8px; margin: 15px 0;">
                    <?php if ($_GET['success'] == 'added'): ?>
                        <i class="fas fa-check"></i> User berhasil ditambahkan!
                    <?php elseif ($_GET['success'] == 'updated'): ?>
                        <i class="fas fa-edit"></i> User berhasil diupdate!
                    <?php elseif ($_GET['success'] == 'deleted'): ?>
                        <i class="fas fa-trash"></i> User berhasil dihapus!
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 10px 15px; border-radius: 8px; margin: 15px 0;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="users-grid">
            <?php if (is_array($users)): ?>
                <div style="text-align: center; padding: 50px; color: #666;">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 20px;"></i>
                    <h3>Belum ada user</h3>
                    <p>Klik tombol "Tambah User" untuk menambah user baru</p>
                </div>
            <?php else: ?>
            <?php while($user = mysqli_fetch_assoc($users)): ?>
            <div class="user-card">
                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                    <div style="width: 50px; height: 50px; background: #667eea; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                        <i class="fas fa-user" style="color: white; font-size: 1.5rem;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($user['nama']); ?></h3>
                        <div style="font-size: 0.9rem; color: #666;"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <span class="role-badge role-<?php echo $user['role']; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
                
                <div style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">
                    <i class="fas fa-calendar"></i> Bergabung: <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                </div>
                
                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                <div style="display: flex; gap: 10px;">
                    <button onclick="editUser(<?php echo $user['id']; ?>, '<?php echo addslashes(htmlspecialchars($user['nama'])); ?>', '<?php echo addslashes(htmlspecialchars($user['email'])); ?>', '<?php echo $user['role']; ?>')" class="btn btn-primary" style="font-size: 0.8rem; padding: 8px 12px;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes(htmlspecialchars($user['nama'])); ?>')" class="btn btn-danger" style="font-size: 0.8rem; padding: 8px 12px;">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
                <?php else: ?>
                <div style="color: #666; font-style: italic; font-size: 0.9rem;">
                    <i class="fas fa-shield-alt"></i> Akun Anda
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2><i class="fas fa-user-plus"></i> Tambah User Baru</h2>
            
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="admin">👨‍💼 Admin</option>
                        <option value="guru">👨‍🏫 Guru</option>
                        <option value="siswa">👨‍🎓 Siswa</option>
                    </select>
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" onclick="closeAddModal()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin-right: 10px; cursor: pointer;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2><i class="fas fa-user-edit"></i> Edit User</h2>
            
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama" id="editNama" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="editEmail" required>
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" id="editRole" required>
                        <option value="admin">👨💼 Admin</option>
                        <option value="guru">👨🏫 Guru</option>
                        <option value="siswa">👨🎓 Siswa</option>
                    </select>
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" onclick="closeEditModal()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin-right: 10px; cursor: pointer;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function editUser(id, nama, email, role) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editNama').value = nama;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRole').value = role;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function deleteUser(id, name) {
            if (confirm('Yakin ingin menghapus user "' + name + '"?\n\nUser akan dihapus permanen dari sistem.')) {
                window.location.href = '?delete=' + id;
            }
        }
        
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target == addModal) {
                closeAddModal();
            } else if (event.target == editModal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>