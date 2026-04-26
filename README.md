# 🏫 SIPANDA - Sistem Informasi Pokok Pendidikan
**Dikembangkan oleh:** ASR Production  
**Versi:** 2.0.1 (Bisa diupdate otomatis via GitHub)

---

## 📖 Tentang Aplikasi
**SIPANDA** (Sistem Informasi Pokok Pendidikan) adalah platform manajemen sekolah digital modern yang dirancang untuk mengotomatisasi seluruh operasional sekolah. Berbeda dengan sistem informasi akademik biasa, SIPANDA mengintegrasikan teknologi **RFID Smart Card System**, **WhatsApp Gateway**, dan **PWA (Progressive Web App)** untuk menciptakan ekosistem sekolah pintar (Smart School) dengan tampilan visual yang sangat premium.

---

## 🚀 Fitur & Modul Utama

### 1. 💳 Sistem Kartu Pelajar Cerdas (RFID Terintegrasi)
Satu kartu untuk semua keperluan di sekolah. Sistem membedakan secara otomatis antara kartu Siswa dan Guru.
- **Absensi Tap-In / Tap-Out:** Siswa & Guru melakukan tap kartu, sistem membedakan status (Tepat Waktu/Terlambat) berdasarkan kelas/hari libur.
- **Batasan Jam Pulang:** Mencegah siswa absen pulang sebelum waktunya.

### 2. 🏪 E-Kantin (Cashless Society)
Sistem dompet digital untuk pelajar dan guru.
- Cek limit saldo harian dan total saldo tersisa langsung dari monitor.
- Auto-potong saldo dengan hanya sekali tap kartu di kantin.
- Mencegah siswa jajan melebihi *Limit Jajan Harian* yang diset oleh orang tua.

### 3. 👥 Buku Tamu Digital (Kiosk Mode)
Sistem buku tamu cerdas layaknya di gedung perkantoran.
- Layar Kiosk otomatis merespon saat RFID KTP/Kartu Tamu ditempel.
- Auto Check-In & Check-Out.
- Notifikasi WhatsApp "Terima kasih telah berkunjung" dikirim otomatis setelah check-out.

### 4. 🏥 Modul Inovatif (UKS & Perpustakaan)
- **Kunjungan Perpus Pintar:** Tap kartu untuk absen masuk perpustakaan, terhubung ke layar monitor petugas (Librarian Dashboard).
- **UKS Mandiri:** Siswa yang sakit cukup nge-tap kartu, sistem UKS akan terbuka dan *WhatsApp Gateway* otomatis mengirim pesan panggilan ke petugas medis/admin.

### 5. 💰 Modul Keuangan & Payroll
- **Cek Tagihan Publik:** Orang tua dapat mengecek status tunggakan SPP/bulanan langsung dari halaman depan tanpa perlu login.
- **Manajemen Pembayaran:** Sistem cart (keranjang) untuk pembayaran SPP sekaligus (Bulk Payment) dan cetak kuitansi.
- **Teacher Payroll:** Penggajian Guru otomatis berdasar masa kerja (TMT) dan rekap absensi harian yang diambil dari log Tap RFID.

### 6. 🤖 Akademik & AI RPP Generator
- Data Kelas, Jadwal, Wali Kelas terpusat.
- Pembuatan RPP Cerdas dengan AI yang mendukung Kurikulum Berbasis Cinta (KBC) dan Model Pembelajaran Deep Learning (PEDATTI), bisa langsung diprint.
- Sistem Penilaian berbasis JSON yang dinamis untuk rapor mobile-friendly.

### 7. 📲 Notifikasi WhatsApp & PWA Otomatis
Dukungan penuh `api/wa_helper.php` secara auto-pilot:
- Pesan WA saat Siswa datang ke sekolah.
- Pesan WA saat Siswa pulang dari sekolah.
- Pesan Notifikasi Tagihan & Keuangan.
- PWA Notification (Push Notif) untuk Info Sekolah.

---

## 🔒 Sistem Keamanan Lisensi (Pro-License)
Aplikasi SIPANDA dilindungi oleh Core License System tingkat industri buatan ASR Production.
- Otomatis melakukan *Ping* Validasi ke Server API Pusat (Cek Domain & Expiry).
- Proteksi Anti-Manipulasi (Terenkripsi Base64 + GzipDeflate).
- Auto-Lock jika mencoba dipindahkan ke Hosting tak dikenal.
- Jika tagihan klien belum dibayar, sistem dapat dimatikan jarak jauh via Lisensi Server Administrator.

---

## 💻 Tech Stack
- **Backend:** PHP 8, PDO & Prepared Statements
- **Database:** MariaDB / MySQL
- **Frontend:** HTML5, Premium CSS (Inter Fonts, Glassmorphism, Animations), SweetAlert2, Confetti JS
- **API & Integrations:** cURL, WhatsApp Gateway Endpoint, Web Push Notifications (PWA)

---
*Mencetak generasi cerdas, berakhlak mulia, dan siap menghadapi tantangan masa depan melalui sistem pendidikan terpadu.*
*(c) Hak Cipta ASR Production - Dilarang merekayasa ulang tanpa izin.*
