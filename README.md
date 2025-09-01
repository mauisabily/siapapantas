# 🎮 Sistem Kuiz Interaktif

Sistem kuiz real-time yang cantik dan interaktif dengan ranking, timer, dan multi-player support.

## ✨ Ciri-ciri Utama

### 🎯 **Gameplay Features**
- **Clickable Options**: Klik terus pada jawapan tanpa perlu tick
- **Real-time Timer**: Timer yang berjalan semasa menjawab
- **Instant Results**: Keputusan segera dengan animasi cantik
- **Top 3 Ranking**: Paparan 3 pemain terpantas untuk setiap soalan
- **Multi-player Support**: Beberapa pemain boleh bermain serentak

### 🏆 **Ranking System**
- **Fastest Players**: Ranking berdasarkan bilangan jawapan terpantas
- **Accuracy Ranking**: Ranking berdasarkan peratusan ketepatan
- **Real-time Statistics**: Statistik keseluruhan yang dikemas kini
- **Recent Activity**: Aktiviti terkini semua pemain

### 🎨 **Design & UI**
- **Modern Gradient Design**: Background dan button yang cantik
- **Glass Morphism**: Card dengan backdrop blur effect
- **Smooth Animations**: Pulse, shake, dan transition effects
- **Responsive Layout**: Sesuai untuk desktop dan mobile
- **Bootstrap 5**: Framework CSS yang modern

## 🚀 Cara Setup

### 1. **Database Setup**

```bash
# Import database structure
mysql -u your_username -p your_database < database_setup.sql
```

### 2. **Database Configuration**

Edit `db.php` dengan maklumat database anda:

```php
$servername = "your_server";
$username = "your_username";
$password = "your_password";
$dbname = "your_database";
```

### 3. **Start Server**

```bash
# PHP Built-in Server (untuk development)
php -S localhost:8000

# Atau gunakan Apache/Nginx untuk production
```

### 4. **Access System**

Buka browser dan pergi ke:
- **Main Game**: `http://localhost:8000`
- **Winners Page**: `http://localhost:8000/winners.php`

## 📁 Struktur Projek

```
games/
├── 📄 index.php              # Landing page
├── 📄 login.php              # Player registration
├── 📄 question.php           # Main quiz interface
├── 📄 winners.php            # Winners & statistics
├── 📄 logout.php             # Logout handler
├── 📄 db.php                 # Database connection
├── 📄 database_setup.sql     # Database structure
├── 📄 styles.css             # Additional styles
├── 📁 admin/                 # Admin panel (optional)
│   ├── dashboard.php
│   ├── players.php
│   └── settings.php
└── 📁 config/                # Configuration files
    └── database.php
```

## 🗄️ Database Structure

### **Tables**

1. **`players`** - Maklumat pemain
   - `id`, `nickname`, `full_name`, `phone_number`, `created_at`

2. **`questions`** - Soalan kuiz
   - `id`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`

3. **`responses`** - Jawapan pemain
   - `id`, `player_id`, `nickname`, `question_id`, `chosen_option`, `response_time`, `is_correct`, `created_at`

4. **`winners`** - Rekod pemenang
   - `id`, `player_id`, `nickname`, `fastest_answers`, `total_correct_answers`, `average_response_time`

5. **`game_sessions`** - Sesi permainan
   - `id`, `player_id`, `start_time`, `end_time`, `total_questions`, `correct_answers`

## 🎮 Cara Bermain

### **Untuk Pemain**

1. **Daftar**: Masukkan nickname, nama penuh (optional), dan nombor telefon (optional)
2. **Menjawab**: Klik pada pilihan jawapan A, B, C, atau D
3. **Lihat Keputusan**: Sistem akan tunjuk betul/salah dan ranking
4. **Teruskan**: Klik "Soalan Seterusnya" untuk meneruskan
5. **Selesai**: Selepas 10 soalan, lihat papan pemenang

### **Keyboard Shortcuts**
- **1, 2, 3, 4**: Pilih jawapan A, B, C, D
- **Enter**: Submit jawapan (jika ada yang dipilih)

## 🏆 Sistem Ranking

### **Top Fastest Players**
- Berdasarkan bilangan jawapan terpantas
- Hanya jawapan betul yang dikira
- Paparan dengan medal 🥇🥈🥉

### **Top Accuracy Players**
- Berdasarkan peratusan jawapan betul
- Minimum 5 soalan untuk layak
- Menunjukkan nisbah betul/salah

## 🔧 Features Teknikal

### **Security**
- SQL Injection protection dengan prepared statements
- Input sanitization dengan htmlspecialchars
- Unique constraints untuk nickname dan phone number
- Session management yang selamat

### **Performance**
- Optimized database queries
- Efficient indexing
- Minimal JavaScript untuk speed
- Responsive design untuk semua device

### **Multi-player Support**
- Concurrent players tanpa conflict
- Unique player identification
- Real-time ranking updates
- Separate game sessions

## 🎨 Customization

### **Mengubah Soalan**

Tambah soalan baru dalam database:

```sql
INSERT INTO questions (question, option_a, option_b, option_c, option_d, correct_option) 
VALUES ('Soalan anda?', 'Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D', 'a');
```

### **Mengubah Design**

Edit CSS dalam setiap file PHP atau tambah dalam `styles.css`:

```css
/* Custom gradient */
body {
    background: linear-gradient(135deg, #your-color1, #your-color2);
}
```

### **Mengubah Timer**

Edit JavaScript dalam `question.php`:

```javascript
// Ubah delay sebelum submit (default: 500ms)
setTimeout(() => {
    form.submit();
}, 1000); // 1 second delay
```

## 🐛 Troubleshooting

### **Database Connection Error**
- Semak maklumat dalam `db.php`
- Pastikan MySQL server berjalan
- Verify database permissions

### **Duplicate Entry Error**
- Pastikan nickname dan phone number unik
- Clear browser cache dan cookies
- Restart PHP session

### **Questions Not Loading**
- Semak ada soalan dalam database
- Verify database structure
- Check file permissions

## 📱 Browser Support

- ✅ Chrome 80+
- ✅ Firefox 75+
- ✅ Safari 13+
- ✅ Edge 80+
- ✅ Mobile browsers

## 🔄 Updates & Maintenance

### **Adding New Features**
1. Update database structure jika perlu
2. Modify PHP files
3. Test thoroughly
4. Update documentation

### **Backup Database**
```bash
mysqldump -u username -p database_name > backup.sql
```

### **Monitor Performance**
- Check MySQL slow query log
- Monitor PHP error logs
- Review server resources

## 📞 Support

Jika ada masalah atau soalan:
1. Semak troubleshooting section
2. Review error logs
3. Test dengan browser lain
4. Verify database connection

---

**Dibuat dengan ❤️ menggunakan PHP, MySQL, Bootstrap 5, dan JavaScript**

*Sistem Kuiz Interaktif - Menjadikan pembelajaran lebih menyeronokkan!* 🎉
