# Portal Artikel Sekolah - Sistem Lengkap dengan Like, Comment & Share

## 🚀 Fitur Utama

### 👥 Multi-Role System
- **Admin**: Moderasi artikel, manajemen user
- **Guru**: Tulis artikel, moderasi komentar
- **Siswa**: Tulis artikel (perlu approval), interaksi

### 💝 Sistem Interaksi Lengkap
- **Like System**: Toggle like/unlike dengan counter real-time
- **Comment System**: Komentar dengan AJAX, tampil di modal
- **Share System**: 4 platform (Facebook, Twitter, WhatsApp, Copy Link)

### 📊 Database Auto-Setup
- Auto-create semua tabel yang diperlukan
- Foreign key constraints untuk data integrity
- Sample data untuk testing

## 🗄️ Struktur Database

### Tabel Utama
```sql
- users (id, nama, email, password, role)
- artikel (id, judul, konten, foto, kategori, author_id, status)
- likes (id, user_id, artikel_id, created_at)
- comments (id, user_id, artikel_id, comment, created_at)
- shares (id, user_id, artikel_id, share_type, created_at)
- notifications (id, user_id, title, message, type, is_read)
```

## 📁 File Structure

### Core Files
- `config.php` - Database configuration
- `database_setup.sql` - Complete database setup
- `index.php` - Homepage dengan artikel preview
- `public.php` - Public article listing dengan share
- `login.php` - Authentication system

### Handler Files
- `like_handler.php` - AJAX like/unlike handler
- `comment_handler.php` - AJAX comment handler  
- `share_handler.php` - Share tracking handler
- `get_article_data.php` - Article stats API
- `get_comments.php` - Comments API

### Dashboard Files
- `admin/` - Admin panel dengan moderasi
- `guru/` - Guru dashboard dengan artikel management
- `siswa/` - Siswa dashboard dengan interaksi

## 🛠️ Setup Instructions

### 1. Database Setup
```sql
-- Import database_setup.sql atau jalankan:
CREATE DATABASE portal_sekolah;
-- File akan auto-create tables saat pertama diakses
```

### 2. Configuration
```php
// config.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "portal_sekolah";
```

### 3. Default Login
```
Admin: admin@sekolah.com / password
Guru: budi@sekolah.com / password  
Siswa: ahmad@sekolah.com / password
```

## 🎯 Fitur Terintegrasi

### Like System
- ✅ Real-time toggle like/unlike
- ✅ Counter update otomatis
- ✅ Visual feedback (warna berubah)
- ✅ Permission check (guru/siswa only)

### Comment System  
- ✅ AJAX form submission
- ✅ Real-time comment display
- ✅ Counter update otomatis
- ✅ Modal popup untuk detail

### Share System
- ✅ Facebook share dengan Open Graph
- ✅ Twitter share dengan custom text
- ✅ WhatsApp share dengan formatted message
- ✅ Copy to clipboard functionality
- ✅ Share counter tracking

### Dashboard Integration
- ✅ Grid layout artikel dengan preview
- ✅ Popup modal untuk baca artikel
- ✅ Search & filter functionality
- ✅ Notification system
- ✅ Image handling dengan multiple directories

## 🔧 Technical Features

### Auto-Create Tables
Semua handler file akan otomatis membuat tabel jika belum ada:
```php
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS likes (...)");
```

### Error Handling
- Database connection fallback
- Graceful degradation jika DB tidak tersedia
- Input validation dan sanitization

### Security
- Prepared statements untuk SQL injection prevention
- Role-based access control
- CSRF protection dengan session validation

### Performance
- Efficient queries dengan subqueries untuk counting
- AJAX untuk interaksi tanpa page reload
- Optimized image handling

## 📱 Responsive Design
- Mobile-first approach
- Grid layout yang adaptif
- Touch-friendly buttons
- Optimized untuk semua device

## 🎨 UI/UX Features
- Modern gradient design
- Smooth animations dan transitions
- Interactive hover effects
- Loading states untuk AJAX
- Toast notifications

## 🚀 Deployment Ready
- Production-ready code structure
- Environment configuration
- Error logging capability
- SEO-friendly URLs

---

**Sistem Portal Artikel Sekolah** - Complete social media features untuk lingkungan pendidikan! 🎓✨