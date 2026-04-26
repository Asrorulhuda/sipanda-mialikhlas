# 🏫 SIPANDA — Rangkuman Deskripsi Fitur

**Sistem Informasi Pokok Pendidikan** · Versi `2.0.5.2` · Dikembangkan oleh **ASR Production**

> [!NOTE]
> Dokumen ini merangkum seluruh fitur dan modul yang tersedia di aplikasi SIPANDA berdasarkan analisis kode sumber secara menyeluruh.

---

## 🔐 Sistem Multi-Role (7 Role Pengguna)

SIPANDA mendukung **7 peran pengguna** yang masing-masing memiliki dashboard dan hak akses berbeda:

| # | Role | Dashboard | Deskripsi Singkat |
|---|------|-----------|-------------------|
| 1 | **Admin** | `index-admin.php` | Akses penuh ke seluruh modul sistem |
| 2 | **Guru** | `index-guru.php` | Akademik, journal, RPP, quiz, absensi, e-kantin guru |
| 3 | **Siswa** | `index-siswa.php` | Tagihan, absensi, tugas, quiz, tabungan, e-kantin |
| 4 | **Bendahara** | `index-bendahara.php` | Keuangan, kas, tabungan, laporan, payroll |
| 5 | **Kepala Sekolah** | `index-kepsek.php` | Monitoring & supervisi seluruh data sekolah |
| 6 | **Kasir** | `index-kasir.php` | POS kantin, top-up saldo, kelola produk |
| 7 | **Petugas Perpus** | `index-petugas.php` | Sirkulasi buku, kunjungan, koleksi perpustakaan |

> [!TIP]
> Guru dengan **tugas tambahan** (Waka Kurikulum, Waka Kesiswaan, Waka Sarpras, Waka Humas, Waka Keagamaan, Waka UKS) otomatis mendapat menu admin tambahan sesuai jabatannya.

---

## 📦 Modul-Modul Utama

### 1. 📊 Master Data
Pengelolaan data dasar sekolah yang menjadi fondasi seluruh modul.

| Fitur | Deskripsi |
|-------|-----------|
| **Tahun Ajaran** | Kelola periode akademik aktif (tahun & semester) |
| **Kelas** | Data ruang kelas beserta wali kelas |
| **Siswa** | CRUD data siswa lengkap (NISN, foto, ortu, kontak, status aktif/lulus/mutasi) |
| **Guru** | Data guru (NIP, TMT, tugas tambahan, status aktif) |
| **Guru BK** | Penugasan guru Bimbingan Konseling |
| **Jam Pelajaran** | Setting jam masuk, istirahat, dan pulang per slot |
| **Kenaikan Kelas** | Proses naik kelas massal per tahun ajaran |
| **Kelulusan** | Proses kelulusan siswa massal |
| **SKL** | Surat Keterangan Lulus digital dengan verifikasi QR publik |
| **Setting Absen** | Konfigurasi batas jam masuk, toleransi terlambat, hari libur |

---

### 2. 💳 Absensi RFID & QR Code
Sistem presensi cerdas terintegrasi kartu RFID dan QR Code.

- **Tap-In / Tap-Out** — Siswa & Guru cukup menempelkan kartu RFID, sistem otomatis membedakan status (Tepat Waktu / Terlambat)
- **Batasan Jam Pulang** — Mencegah absen pulang sebelum waktunya
- **QR Code Scanner** — Alternatif absensi via kamera HP (tersedia di homepage publik)
- **Input Manual** — Fallback jika RFID/QR bermasalah
- **Live Presensi** — Monitor real-time kehadiran siswa di layar terpisah (`home-livepresensi.php`)
- **Absensi Guru** — Tracking terpisah untuk kehadiran guru harian
- **Absensi Eskul** — Presensi khusus kegiatan ekstrakurikuler
- **Izin Guru** — Guru bisa mengajukan izin/sakit langsung dari dashboard, dengan upload bukti surat
- **Izin Siswa** — Siswa bisa lapor sakit langsung ke UKS dari dashboard
- **Notifikasi WhatsApp** — Otomatis kirim WA ke orang tua saat siswa datang & pulang

---

### 3. 💰 Keuangan (SPP & Pembayaran)
Modul keuangan lengkap untuk manajemen pembayaran sekolah.

| Fitur | Deskripsi |
|-------|-----------|
| **Pos Bayar** | Kategori induk pembayaran |
| **Jenis Bayar** | Detail jenis (SPP Bulanan, Uang Bangunan, dll.) dengan tipe Bulanan/Bebas |
| **Tarif** | Pengaturan tarif per kelas dan jenis bayar |
| **Pembayaran** | Sistem keranjang (cart) untuk bayar sekaligus (Bulk Payment) |
| **Hutang Piutang** | Tracking tunggakan siswa |
| **Cetak Kwitansi** | Print bukti pembayaran resmi |
| **Cek Tagihan Publik** | Halaman publik tanpa login untuk orang tua cek tunggakan anak |

---

### 4. 🏦 Kas Sekolah
Pencatatan arus kas masuk dan keluar sekolah.

- **Realisasi** — Dashboard ringkasan kas keseluruhan
- **Jenis Masuk / Keluar** — Kategori sumber penerimaan dan pengeluaran
- **Penerimaan Kas** — Input pemasukan non-SPP
- **Pengeluaran Kas** — Pencatatan belanja operasional
- **Grafik Keuangan** — Chart bulanan pemasukan vs pengeluaran (Chart.js)

---

### 5. 🐷 Tabungan Siswa
Sistem simpanan siswa layaknya bank mini sekolah.

- **Nasabah** — Daftar siswa sebagai penabung
- **Transaksi** — Setor dan tarik tabungan
- **Laporan Transaksi** — Riwayat detail per nasabah
- **Laporan Saldo Akhir** — Rekap posisi saldo seluruh nasabah

---

### 6. 📚 Akademik & E-Learning
Modul akademik digital yang mendukung kegiatan belajar mengajar.

| Fitur | Deskripsi |
|-------|-----------|
| **Mata Pelajaran** | Data mapel dengan kode dan kategori |
| **Jadwal Pelajaran** | Pemetaan guru–mapel–kelas–jam per hari |
| **Journal Mengajar** | Catatan harian guru tentang materi yang diajarkan, bisa dicetak |
| **Bahan & Tugas** | Upload materi/file tugas per kelas, bisa diakses siswa |
| **Quiz / Ujian Online** | Buat soal, atur durasi, koreksi otomatis, cegah duplikasi jawaban |
| **Daftar Nilai (Rapor)** | Sistem penilaian berbasis JSON dinamis, mobile-friendly, bisa dicetak |

---

### 7. 🤖 AI-Powered Features
Fitur berbasis kecerdasan buatan yang terintegrasi langsung ke dalam sistem.

#### AI RPP Generator
- Pembuatan **Rencana Pelaksanaan Pembelajaran (RPP)** secara otomatis menggunakan AI
- Mendukung **Kurikulum Berbasis Cinta (KBC)** dan **Model Pembelajaran Deep Learning (PEDATTI)**
- Konfigurasi Model Pembelajaran & Praktik Pedagogis secara manual
- Output RPP bisa langsung di-print dalam format resmi

#### AI Generator Soal
- Generate bank soal otomatis menggunakan AI berdasarkan topik dan tingkat kesulitan
- Soal bisa langsung dipakai untuk paket ujian/quiz

#### Guru AI Chat (Chatbot)
- Widget chat AI interaktif yang tersedia di dashboard guru & siswa
- Mendukung multi-turn conversation per sesi pengguna
- Filter konten ketat (anti konten negatif & 18+)
- Batasan limit chat untuk pengunjung publik
- Tema warna disesuaikan agar tidak bentrok dengan UI utama

---

### 8. 🏪 E-Kantin (Cashless Canteen)
Sistem kantin digital tanpa uang tunai berbasis saldo kartu.

| Fitur | Deskripsi |
|-------|-----------|
| **Kasir POS** | Point of Sale untuk transaksi kantin, auto-potong saldo via tap kartu |
| **Produk & Kategori** | Kelola menu makanan/minuman beserta stok & harga |
| **Top-up Manual** | Kasir mengisi saldo siswa secara manual |
| **Top-up Online** | Siswa request top-up, admin/kasir konfirmasi |
| **Konfirmasi Top-up** | Admin memverifikasi permintaan top-up masuk |
| **Limit Jajan Harian** | Batas belanja harian yang diset orang tua |
| **Titip Jualan (Guru)** | Guru bisa menitipkan produk jualan di kantin |
| **Dompet Penjual** | Dashboard saldo & riwayat penjualan untuk guru penjual |
| **Penarikan Guru** | Guru menarik hasil penjualan dari dompet kantin |

---

### 9. 📖 Perpustakaan Digital
Sistem perpustakaan lengkap dengan integrasi RFID.

- **Kategori Buku** — Klasifikasi koleksi (fiksi, non-fiksi, referensi, dll.)
- **Koleksi Buku** — CRUD data buku fisik dan e-book
- **Sirkulasi (Pinjam/Kembali)** — Peminjaman via tap kartu RFID, tracking jatuh tempo
- **Kunjungan Perpus** — Absensi masuk perpustakaan via tap kartu
- **Alert Jatuh Tempo** — Peringatan otomatis untuk buku yang belum dikembalikan
- **Dashboard Petugas** — Ringkasan kunjungan hari ini, pinjaman aktif, stok buku

---

### 10. 🏥 Unit Kesehatan Sekolah (UKS)
Modul kesehatan digital terintegrasi RFID dan WhatsApp.

- **Dashboard UKS** — Pusat data kunjungan kesehatan siswa
- **Stok Obat** — Inventaris obat-obatan UKS
- **Lapor Sakit** — Siswa/Guru bisa lapor keluhan dari dashboard masing-masing
- **Tap Kartu UKS** — Siswa sakit cukup tap kartu, sistem otomatis buka form UKS
- **Notifikasi WA** — Panggilan otomatis ke petugas medis/admin via WhatsApp Gateway
- **Alert Frekuensi Sakit** — Peringatan jika siswa terlalu sering sakit

---

### 11. 👥 Kesiswaan
Modul pengelolaan non-akademik siswa.

| Fitur | Deskripsi |
|-------|-----------|
| **Prestasi** | Pencatatan prestasi akademik & non-akademik, ditampilkan di homepage |
| **Bimbingan Konseling (BK)** | Catatan pelanggaran dan konseling, cetak Surat Peringatan (SP) |
| **Ekstrakurikuler** | Data eskul, anggota, jadwal, dan absensi eskul |

---

### 12. 🕌 Bina Agama
Modul kegiatan keagamaan dan pembinaan karakter.

- **Jadwal Kegiatan** — Input kegiatan ibadah/sosial dengan foto & pelaksana
- **Rekap Dana Infaq** — Pencatatan infaq harian/mingguan per kelas
- **Sertifikasi** — Data sertifikasi tahfidz/keagamaan siswa, cetak sertifikat

---

### 13. 🤝 Humas (Hubungan Masyarakat)
Modul manajemen tamu dan kemitraan.

| Fitur | Deskripsi |
|-------|-----------|
| **Data MOU / Kemitraan** | Kelola mitra strategis, logo, website, cetak MOU |
| **Buku Tamu Digital** | Registrasi tamu dengan RFID KTP/Kartu Tamu |
| **Kiosk Mode** | Layar self-service untuk tamu check-in/check-out |
| **Notifikasi WA Tamu** | Kirim "Terima kasih telah berkunjung" otomatis setelah check-out |
| **Cetak Kartu Tamu** | Print identitas tamu sementara |

---

### 14. 🏗️ Sarana & Prasarana (Sarpras)
Inventarisasi aset dan fasilitas sekolah.

- **Data Barang** — CRUD inventaris barang/aset sekolah
- **Pemeliharaan** — Jadwal dan riwayat perawatan aset
- **Cetak Laporan** — Print inventaris dan pemeliharaan

---

### 15. 💵 Payroll Guru
Sistem penggajian otomatis untuk tenaga pendidik.

| Fitur | Deskripsi |
|-------|-----------|
| **Setting Payroll** | Konfigurasi komponen gaji (pokok, tunjangan, potongan) |
| **Config Gaji Guru** | Atur nominal per guru berdasarkan masa kerja (TMT) |
| **Generate Gaji** | Proses hitung gaji otomatis berdasarkan absensi RFID |
| **Riwayat Gaji** | Arsip slip gaji bulanan |
| **Cetak Slip Gaji** | Print slip gaji per guru |
| **Slip Gaji Saya** | Guru bisa lihat riwayat gaji sendiri dari dashboard |

---

### 16. 📢 Notifikasi WhatsApp & PWA
Sistem notifikasi multi-channel otomatis.

- **WA Absensi Datang** — Pesan ke orang tua saat siswa tap masuk
- **WA Absensi Pulang** — Pesan ke orang tua saat siswa tap pulang
- **WA Tagihan** — Notifikasi tagihan & pembayaran
- **WA Top-up** — Konfirmasi top-up saldo kantin
- **WA Tamu** — Notifikasi check-out buku tamu
- **WA UKS** — Panggilan petugas medis
- **PWA Push Notification** — Notifikasi browser untuk info sekolah
- **PWA Installable** — Aplikasi bisa di-install ke homescreen HP

---

### 17. 🌐 Homepage / Website Sekolah
Halaman depan publik yang berfungsi sebagai website resmi sekolah.

- **Hero Section** — Banner dinamis dengan floating badge (akreditasi, program unggulan)
- **Statistik** — Counter animasi (siswa, guru, kelas, eskul)
- **Profil & Keunggulan** — Section keunggulan sekolah yang bisa dikelola via CMS
- **Hall of Fame** — Showcase prestasi siswa terbaru
- **Kegiatan Keagamaan** — Feed kegiatan dari modul Bina Agama
- **Berita & Informasi** — Display info / pengumuman resmi sekolah
- **Galeri Foto** — Grid galeri momen sekolah
- **Mitra & Kerjasama** — Logo partner strategis
- **Layanan Digital** — Akses publik ke Presensi QR, Verifikasi SKL, E-Kantin, Buku Tamu, Cek Tagihan
- **CMS Menu** — Menu navigasi yang bisa dikustomisasi admin

---

### 18. 📈 Laporan & Cetak
Modul pelaporan komprehensif dengan fitur cetak/print.

| Laporan | Deskripsi |
|---------|-----------|
| Data Siswa | Export & cetak daftar siswa per filter |
| Per Kelas | Rekap pembayaran per kelas |
| Per Bulan | Rekap pembayaran bulanan |
| Per Pos | Rekap berdasarkan pos bayar |
| Tagihan | Daftar tunggakan siswa |
| Rekap Bayar | Summary total pembayaran |
| Harian | Laporan transaksi harian |
| Rekap Absensi Siswa | Rekap kehadiran siswa per periode |
| Rekap Absensi Guru | Rekap kehadiran guru per periode |
| Rekap Pengeluaran | Summary pengeluaran kas |
| Kondisi Keuangan | Neraca pemasukan vs pengeluaran |
| Cetak Barcode | Print barcode/QR kartu pelajar |
| Cetak Kartu Pelajar | Cetak ID card siswa |
| Cetak Kwitansi | Bukti pembayaran |
| Cetak SKL | Surat Keterangan Lulus |
| Cetak SP | Surat Peringatan BK |
| Cetak Slip Gaji | Slip payroll guru |

---

## ⚙️ Fitur Sistem & Infrastruktur

### 🔒 Sistem Lisensi (Pro-License)
- Validasi otomatis ke server API pusat (cek domain & masa berlaku)
- Proteksi anti-manipulasi (enkripsi Base64 + GzipDeflate)
- Auto-lock jika dipindah ke hosting tak dikenal
- Kill-switch remote: sistem bisa dimatikan jarak jauh oleh admin lisensi
- Halaman aktivasi (`aktivasi.php`) dengan persetujuan syarat & ketentuan

### 🔄 Auto-Update via GitHub
- Cek versi terbaru dari repository privat GitHub
- Download & extract update otomatis
- Proteksi file konfigurasi lokal (tidak tertimpa saat update)
- Database migration otomatis jika ada perubahan skema
- Changelog ditampilkan di halaman update admin

### 🛡️ Keamanan
- Autentikasi berbasis session dengan role-based access control
- PDO Prepared Statements untuk semua query (anti SQL Injection)
- Authorized Devices — pembatasan perangkat yang boleh akses RFID
- Password hashing
- File `.htaccess` untuk proteksi direktori sensitif

### 🎨 Pengaturan Umum
- **Pengaturan Sekolah** — Nama, NPSN, alamat, telepon, email, logo, kepala sekolah
- **Dual Logo** — Logo kiri (yayasan) dan logo kanan (sekolah)
- **Hero Section CMS** — Edit judul, deskripsi, badge, floating text
- **Galeri** — Upload banner dan foto galeri
- **Kelola Keunggulan** — Edit card keunggulan di homepage
- **Display Info** — Buat pengumuman/berita dengan gambar
- **Pengguna** — Manajemen akun login multi-role
- **Backup Database** — Export database langsung dari panel admin

---

## 💻 Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP 8, PDO & Prepared Statements |
| Database | MariaDB / MySQL |
| Frontend | HTML5, TailwindCSS, Premium CSS (Glassmorphism, Animations) |
| Typography | Plus Jakarta Sans, Playfair Display, Inter |
| UI Library | SweetAlert2, Font Awesome, Chart.js |
| API | cURL, WhatsApp Gateway, Web Push (PWA) |
| Hardware | RFID Reader (ESP32/Arduino), Kartu RFID Mifare |
| AI | Google Gemini API (RPP, Soal, Chat) |
| Update | GitHub API (Auto-updater) |
| Lisensi | Custom License Server (ASR Production) |

---

> *"Mencetak generasi cerdas, berakhlak mulia, dan siap menghadapi tantangan masa depan melalui sistem pendidikan terpadu."*
> — © ASR Production
