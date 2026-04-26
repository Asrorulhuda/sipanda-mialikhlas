-- --------------------------------------------------------
-- SIPANDA Database Migration Script
-- Version: 2.0 (A-la-carte Pricing & Dynamic Packages)
-- --------------------------------------------------------


ALTER TABLE tbl_features ADD COLUMN harga_satuan INT DEFAULT 0;
CREATE TABLE IF NOT EXISTS tbl_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    price_text VARCHAR(100),
    period VARCHAR(50),
    description TEXT,
    features_json TEXT,
    modules_json TEXT,
    color_hex VARCHAR(20),
    icon_class VARCHAR(50),
    is_recommended TINYINT DEFAULT 0,
    sort_order INT DEFAULT 0
);

INSERT INTO tbl_packages (id, name, price_text, period, description, features_json, modules_json, color_hex, icon_class, is_recommended, sort_order) VALUES
(1, 'Basic', 'Rp 500.000', '/ tahun', 'Sistem dasar yang cocok untuk sekolah skala kecil.', '["Akses Admin & Guru","Support Chat","Server Shared","Update Berkala"]', '[]', '#3b82f6', 'fa-paper-plane', 0, 1),
(2, 'Pro', 'Rp 1.500.000', '/ tahun', 'Fitur lengkap untuk sekolah yang sedang berkembang pesat.', '["Akses Semua Role (Siswa/Ortu)","Prioritas Support","Server Semi-Dedicated","Guru AI Assistant","CBT E-Learning"]', '[]', '#10b981', 'fa-rocket', 1, 2),
(3, 'Enterprise', 'Hubungi Kami', '', 'Solusi khusus dengan server mandiri untuk Yayasan/Grup Sekolah.', '["Custom Branding (White-label)","Dedicated Engineer","Server Dedicated 32GB","Custom Fitur Sesuai Request","SLA 99.9%"]', '[]', '#8b5cf6', 'fa-building', 0, 3)
ON DUPLICATE KEY UPDATE id=id;

-- Tabel untuk Sidik Jari / Biometric Login (WebAuthn)
CREATE TABLE IF NOT EXISTS tbl_webauthn (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    user_role VARCHAR(20) NOT NULL,
    credential_id TEXT NOT NULL,
    public_key TEXT NOT NULL,
    username VARCHAR(100) NOT NULL,
    device_name VARCHAR(100) DEFAULT 'Unknown Device',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id, user_role),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
