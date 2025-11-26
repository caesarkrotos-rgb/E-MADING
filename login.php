<?php
session_start();
include 'config.php';

// Auto-create users table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('admin', 'guru', 'siswa') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Auto-create default users
mysqli_query($conn, "INSERT IGNORE INTO users (username, password, nama, role) VALUES 
    ('admin', 'admin123', 'Administrator', 'admin'),
    ('guru1', 'guru123', 'Budi Santoso', 'guru'),
    ('guru2', 'guru123', 'Siti Nurhaliza', 'guru'),
    ('siswa1', 'siswa123', 'Ahmad Rizki', 'siswa'),
    ('siswa2', 'siswa123', 'Dewi Sartika', 'siswa')");

if ($_POST) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Query user berdasarkan username/email dan role
    $query = "SELECT * FROM users WHERE (username='$username' OR email='$username') AND role='$role'";
    $user = mysqli_query($conn, $query);
    
    if ($user && mysqli_num_rows($user) > 0) {
        $data = mysqli_fetch_assoc($user);
        
        // Check password (support both hashed and plain text for compatibility)
        $password_valid = false;
        if (password_verify($password, $data['password'])) {
            $password_valid = true;
        } elseif ($data['password'] === $password) {
            $password_valid = true;
        }
        
        if ($password_valid) {
            $_SESSION['user_id'] = $data['id'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['role'] = $data['role'];
            $_SESSION['nama'] = $data['nama'];
            
            // Redirect berdasarkan role
            if ($role == 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($role == 'guru') {
                header('Location: guru/dashboard.php');
            } else {
                header('Location: siswa/dashboard.php');
            }
            exit;
        }
    }
    
    $error = "Login gagal! Periksa username/email, password, dan role.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LOGIN E-MADING BAKNUS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .title {
            color: #333;
            margin-bottom: 30px;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .role-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .role-option {
            position: relative;
        }
        
        .role-option input[type="radio"] {
            display: none;
        }
        
        .role-label {
            display: block;
            padding: 15px 10px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .role-option input[type="radio"]:checked + .role-label {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        .role-icon {
            font-size: 1.5rem;
            display: block;
            margin-bottom: 5px;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }
        
        .back-link {
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .role-selector {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🏫</div>
        <h2 class="title">LOGIN E-MADING BAKNUS</h2>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Login Sebagai</label>
                <div class="role-selector">
                    <div class="role-option">
                        <input type="radio" id="admin" name="role" value="admin" required>
                        <label for="admin" class="role-label">
                            <i class="fas fa-user-shield role-icon"></i>
                            Admin
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="guru" name="role" value="guru" required>
                        <label for="guru" class="role-label">
                            <i class="fas fa-chalkboard-teacher role-icon"></i>
                            Guru
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="siswa" name="role" value="siswa" required>
                        <label for="siswa" class="role-label">
                            <i class="fas fa-user-graduate role-icon"></i>
                            Siswa
                        </label>
                    </div>

                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
        </form>
        
        <div class="back-link">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>