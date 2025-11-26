-- Database setup untuk Portal Artikel Sekolah
-- Jalankan script ini untuk membuat database dan tabel yang diperlukan

CREATE DATABASE IF NOT EXISTS portal_sekolah;
USE portal_sekolah;

-- Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guru', 'siswa') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel artikel
CREATE TABLE IF NOT EXISTS artikel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    konten TEXT NOT NULL,
    foto VARCHAR(255),
    kategori VARCHAR(100),
    author_id INT NOT NULL,
    status ENUM('draft', 'published', 'rejected') DEFAULT 'draft',
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel likes
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, artikel_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE
);

-- Tabel comments
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE
);

-- Tabel shares
CREATE TABLE IF NOT EXISTS shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    share_type ENUM('facebook', 'twitter', 'whatsapp', 'copy') DEFAULT 'copy',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE
);

-- Tabel notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('published', 'rejected', 'general') DEFAULT 'general',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert admin default
INSERT IGNORE INTO users (nama, email, password, role) VALUES 
('Admin', 'admin@sekolah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample data
INSERT IGNORE INTO users (nama, email, password, role) VALUES 
('Budi Santoso', 'budi@sekolah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru'),
('Siti Nurhaliza', 'siti@sekolah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru'),
('Ahmad Rizki', 'ahmad@sekolah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa'),
('Dewi Sartika', 'dewi@sekolah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa');

-- Insert sample articles
INSERT IGNORE INTO artikel (judul, konten, kategori, author_id, status) VALUES 
('Selamat Datang Tahun Ajaran Baru 2024/2025', 'Selamat datang para siswa baru di tahun ajaran 2024/2025. Kami sangat senang menyambut kalian semua di sekolah tercinta ini. Mari bersama-sama menciptakan lingkungan belajar yang kondusif dan menyenangkan.', 'Pengumuman', 2, 'published'),
('Tips Belajar Efektif di Rumah', 'Belajar di rumah memerlukan strategi khusus agar tetap efektif. Berikut beberapa tips yang dapat membantu: 1. Buat jadwal belajar yang konsisten, 2. Siapkan tempat belajar yang nyaman, 3. Gunakan teknik pomodoro, 4. Hindari distraksi dari gadget.', 'Pendidikan', 3, 'published'),
('Kegiatan Ekstrakurikuler Semester Ini', 'Berbagai kegiatan ekstrakurikuler menarik telah disiapkan untuk semester ini. Mulai dari olahraga, seni, hingga teknologi. Jangan lewatkan kesempatan untuk mengembangkan bakat dan minat kalian!', 'Kegiatan', 2, 'published');