<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'guest';

// Define role-specific colors and menus
$role_config = [
    'admin' => [
        'color' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'icon' => 'fas fa-user-shield',
        'title' => 'Admin Panel',
        'menu' => [
            ['dashboard.php', 'fas fa-tachometer-alt', 'Dashboard'],
            ['kelola_user.php', 'fas fa-users', 'Kelola User'],
            ['kelola_komentar.php', 'fas fa-comments', 'Kelola Komentar'],
            ['moderasi.php', 'fas fa-check-circle', 'Moderasi'],
            ['laporan.php', 'fas fa-chart-line', 'Laporan'],
            ['profil.php', 'fas fa-user', 'Profil']
        ]
    ],
    'guru' => [
        'color' => 'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
        'icon' => 'fas fa-chalkboard-teacher',
        'title' => 'Panel Guru',
        'menu' => [
            ['dashboard.php', 'fas fa-tachometer-alt', 'Dashboard'],
            ['tulis_artikel.php', 'fas fa-plus', 'Tulis Artikel'],
            ['artikel.php', 'fas fa-newspaper', 'Semua Artikel'],
            ['profil.php', 'fas fa-user', 'Profil']
        ]
    ],
    'siswa' => [
        'color' => 'linear-gradient(135deg, #007bff 0%, #6610f2 100%)',
        'icon' => 'fas fa-user-graduate',
        'title' => 'Portal Siswa',
        'menu' => [
            ['dashboard.php', 'fas fa-tachometer-alt', 'Dashboard'],
    
            ['artikel.php', 'fas fa-newspaper', 'Semua Artikel'],
            ['tulis_artikel.php', 'fas fa-edit', 'Tulis Artikel'],
            ['profil.php', 'fas fa-user', 'Profil']
        ]
    ]
];

$config = $role_config[$role] ?? $role_config['siswa'];
?>

<div class="sidebar">
    <div class="sidebar-header">
        <i class="<?php echo $config['icon']; ?>" style="font-size: 2rem;"></i>
        <h3><?php echo $config['title']; ?></h3>
    </div>
    
    <ul class="nav-menu">
        <?php foreach ($config['menu'] as $menu_item): ?>
        <li class="nav-item">
            <a href="<?php echo $menu_item[0]; ?>" class="nav-link <?php echo (basename($menu_item[0]) == $current_page) ? 'active' : ''; ?>">
                <i class="<?php echo $menu_item[1]; ?>"></i>
                <?php echo $menu_item[2]; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    background: <?php echo $config['color']; ?>;
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

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .main-content {
        margin-left: 0;
    }
}
</style>