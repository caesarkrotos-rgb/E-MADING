<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    z-index: 1000;
    overflow-y: auto;
}

.sidebar-header {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h3 {
    margin: 10px 0 0 0;
    font-size: 1.2rem;
}

.nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
}

.nav-link:hover {
    background: rgba(255,255,255,0.1);
    padding-left: 30px;
}

.nav-link.active {
    background: rgba(255,255,255,0.2);
    border-right: 3px solid white;
}

.nav-link i {
    margin-right: 10px;
    width: 20px;
}
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-user-graduate" style="font-size: 2rem;"></i>
        <h3>Portal Siswa</h3>
    </div>
    
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="artikel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'artikel.php' ? 'active' : ''; ?>">
                <i class="fas fa-newspaper"></i>
                Semua Artikel
            </a>
        </li>
        <li class="nav-item">
            <a href="tulis_artikel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tulis_artikel.php' ? 'active' : ''; ?>">
                <i class="fas fa-edit"></i>
                Tulis Artikel
            </a>
        </li>
        <li class="nav-item">
            <a href="profil.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                Profil
            </a>
        </li>
    </ul>
</div>