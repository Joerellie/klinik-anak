<?php
/**
 * Database Initialization Script for Klinik Anak Happy
 * Updates schema for Super Admin (Aldrich Joerellie - 040207) and Doctor Verification Flow
 */

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS klinik_anak_happy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database `klinik_anak_happy` created or verified successfully.\n";

    $pdo->exec("USE klinik_anak_happy");

    // Table 1: users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        role ENUM('resepsionis', 'asisten', 'dokter', 'admin') NOT NULL,
        spesialisasi VARCHAR(100) DEFAULT NULL,
        no_str VARCHAR(50) DEFAULT NULL,
        no_hp VARCHAR(25) DEFAULT NULL,
        email VARCHAR(100) DEFAULT NULL,
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
        kode_akses VARCHAR(50) DEFAULT NULL,
        verified_at DATETIME DEFAULT NULL,
        verified_by VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Ensure columns exist on already created tables
    $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('no_str', $cols)) $pdo->exec("ALTER TABLE users ADD COLUMN no_str VARCHAR(50) DEFAULT NULL AFTER spesialisasi");
    if (!in_array('no_hp', $cols)) $pdo->exec("ALTER TABLE users ADD COLUMN no_hp VARCHAR(25) DEFAULT NULL AFTER no_str");
    if (!in_array('email', $cols)) $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER no_hp");
    if (!in_array('status', $cols)) $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved' AFTER email");
    if (!in_array('kode_akses', $cols)) $pdo->exec("ALTER TABLE users ADD COLUMN kode_akses VARCHAR(50) DEFAULT NULL AFTER status");
    if (!in_array('verified_at', $cols)) $pdo->exec("ALTER TABLE users ADD COLUMN verified_at DATETIME DEFAULT NULL AFTER kode_akses");
    if (!in_array('verified_by', $cols)) $pdo->exec("ALTER TABLE users ADD COLUMN verified_by VARCHAR(100) DEFAULT NULL AFTER verified_at");

    // Table 2: patients
    $pdo->exec("CREATE TABLE IF NOT EXISTS patients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kode_rm VARCHAR(25) UNIQUE NOT NULL,
        nama_anak VARCHAR(100) NOT NULL,
        nik VARCHAR(20) DEFAULT NULL,
        tanggal_lahir DATE NOT NULL,
        jenis_kelamin ENUM('L', 'P') NOT NULL,
        nama_ortu VARCHAR(100) NOT NULL,
        no_hp VARCHAR(25) NOT NULL,
        alamat TEXT DEFAULT NULL,
        golongan_darah VARCHAR(5) DEFAULT NULL,
        riwayat_alergi TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table 3: queues (antrean harian)
    $pdo->exec("CREATE TABLE IF NOT EXISTS queues (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nomor_antrean VARCHAR(15) NOT NULL,
        id_pasien INT NOT NULL,
        poli_tujuan ENUM('umum', 'gigi', 'vaksin', 'tindakan') NOT NULL,
        keluhan_awal TEXT DEFAULT NULL,
        status ENUM('menunggu_asisten', 'ruang_asisten', 'menunggu_dokter', 'ruang_dokter', 'kasir', 'selesai', 'batal') NOT NULL DEFAULT 'menunggu_asisten',
        estimasi_menit INT DEFAULT 15,
        waktu_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        waktu_panggil_asisten DATETIME DEFAULT NULL,
        waktu_selesai_asisten DATETIME DEFAULT NULL,
        waktu_panggil_dokter DATETIME DEFAULT NULL,
        waktu_selesai_dokter DATETIME DEFAULT NULL,
        waktu_selesai_kasir DATETIME DEFAULT NULL,
        INDEX(waktu_daftar),
        INDEX(status),
        CONSTRAINT fk_queue_patient FOREIGN KEY (id_pasien) REFERENCES patients(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table 4: vital_signs (pemeriksaan awal asisten dokter)
    $pdo->exec("CREATE TABLE IF NOT EXISTS vital_signs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_antrean INT NOT NULL,
        id_pasien INT NOT NULL,
        id_asisten INT DEFAULT NULL,
        tensi_darah VARCHAR(30) DEFAULT NULL,
        berat_badan DECIMAL(5,2) NOT NULL,
        tinggi_badan DECIMAL(5,2) NOT NULL,
        suhu_tubuh DECIMAL(4,1) NOT NULL,
        lingkar_kepala DECIMAL(4,1) DEFAULT NULL,
        status_gizi VARCHAR(50) DEFAULT 'Normal',
        catatan_asisten TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_vital_queue FOREIGN KEY (id_antrean) REFERENCES queues(id) ON DELETE CASCADE,
        CONSTRAINT fk_vital_patient FOREIGN KEY (id_pasien) REFERENCES patients(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $vCols = $pdo->query("SHOW COLUMNS FROM vital_signs")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('tensi_darah', $vCols)) {
        $pdo->exec("ALTER TABLE vital_signs ADD COLUMN tensi_darah VARCHAR(30) DEFAULT NULL AFTER id_asisten");
    }
    $pdo->exec("UPDATE vital_signs SET tensi_darah = '100/65 mmHg' WHERE tensi_darah IS NULL OR tensi_darah = ''");

    // Table 4B: vital_signs_logs (log riwayat perubahan suhu, tensi, BB, TB jam demi jam)
    $pdo->exec("CREATE TABLE IF NOT EXISTS vital_signs_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_antrean INT NOT NULL,
        id_pasien INT NOT NULL,
        id_asisten INT DEFAULT NULL,
        nama_asisten VARCHAR(100) DEFAULT NULL,
        tensi_darah VARCHAR(30) DEFAULT NULL,
        berat_badan DECIMAL(5,2) NOT NULL,
        tinggi_badan DECIMAL(5,2) NOT NULL,
        suhu_tubuh DECIMAL(4,1) NOT NULL,
        lingkar_kepala DECIMAL(4,1) DEFAULT NULL,
        status_gizi VARCHAR(50) DEFAULT 'Normal',
        catatan_asisten TEXT DEFAULT NULL,
        perubahan_ringkas TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(id_antrean),
        INDEX(id_pasien)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table 5: medical_records (rekam medis dokter)
    $pdo->exec("CREATE TABLE IF NOT EXISTS medical_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_antrean INT NOT NULL,
        id_pasien INT NOT NULL,
        id_dokter INT DEFAULT NULL,
        anamnesis TEXT DEFAULT NULL,
        pemeriksaan_fisik TEXT DEFAULT NULL,
        diagnosis VARCHAR(255) NOT NULL,
        catatan_gigi TEXT DEFAULT NULL,
        tindakan_medis TEXT DEFAULT NULL,
        resep_obat TEXT DEFAULT NULL,
        edukasi_ortu TEXT DEFAULT NULL,
        jadwal_kontrol DATE DEFAULT NULL,
        biaya_layanan INT DEFAULT 75000,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_mr_queue FOREIGN KEY (id_antrean) REFERENCES queues(id) ON DELETE CASCADE,
        CONSTRAINT fk_mr_patient FOREIGN KEY (id_pasien) REFERENCES patients(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table 6: vaccination_records (riwayat vaksinasi)
    $pdo->exec("CREATE TABLE IF NOT EXISTS vaccination_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_pasien INT NOT NULL,
        id_antrean INT DEFAULT NULL,
        id_dokter INT DEFAULT NULL,
        nama_vaksin VARCHAR(100) NOT NULL,
        dosis_ke INT DEFAULT 1,
        nomor_batch VARCHAR(50) DEFAULT NULL,
        tanggal_vaksin DATE NOT NULL,
        jadwal_vaksin_berikutnya DATE DEFAULT NULL,
        catatan_reaksi TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_vac_patient FOREIGN KEY (id_pasien) REFERENCES patients(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table 7: medicines_stock (stok obat, vaksin & alkes)
    $pdo->exec("CREATE TABLE IF NOT EXISTS medicines_stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kode VARCHAR(30) UNIQUE NOT NULL,
        nama_item VARCHAR(100) NOT NULL,
        kategori ENUM('obat', 'vaksin', 'alat_medis') NOT NULL,
        stok INT NOT NULL DEFAULT 0,
        min_stok INT NOT NULL DEFAULT 10,
        satuan VARCHAR(30) NOT NULL,
        harga_satuan INT NOT NULL DEFAULT 0,
        deskripsi VARCHAR(255) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Table 8: transactions (kasir / pembayaran)
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        no_nota VARCHAR(35) UNIQUE NOT NULL,
        id_antrean INT NOT NULL,
        id_pasien INT NOT NULL,
        id_kasir INT DEFAULT NULL,
        biaya_konsultasi INT NOT NULL DEFAULT 75000,
        biaya_tindakan INT NOT NULL DEFAULT 0,
        biaya_obat_vaksin INT NOT NULL DEFAULT 0,
        biaya_admin INT NOT NULL DEFAULT 10000,
        total_biaya INT NOT NULL DEFAULT 85000,
        metode_pembayaran ENUM('tunai', 'qris', 'debit', 'transfer') DEFAULT 'tunai',
        status_bayar ENUM('belum_bayar', 'lunas') NOT NULL DEFAULT 'belum_bayar',
        catatan VARCHAR(255) DEFAULT NULL,
        waktu_bayar DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_tx_queue FOREIGN KEY (id_antrean) REFERENCES queues(id) ON DELETE CASCADE,
        CONSTRAINT fk_tx_patient FOREIGN KEY (id_pasien) REFERENCES patients(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "All tables verified/updated successfully.\n";

    // Set Super Admin Aldrich Joerellie with Kode Masuk 040207
    $adminHash = password_hash('040207', PASSWORD_BCRYPT);
    $staffHash = password_hash('password123', PASSWORD_BCRYPT);
    $docHash = password_hash('DOK-04020', PASSWORD_BCRYPT);

    $upsertUser = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, spesialisasi, status, kode_akses)
        VALUES (?, ?, ?, ?, ?, 'approved', ?)
        ON DUPLICATE KEY UPDATE password = VALUES(password), nama_lengkap = VALUES(nama_lengkap), role = VALUES(role), spesialisasi = VALUES(spesialisasi), status = 'approved', kode_akses = VALUES(kode_akses)");

    // Super Admin: Aldrich Joerellie (kode: 040207)
    $upsertUser->execute(['admin', $adminHash, 'Aldrich Joerellie', 'admin', 'Super Admin & Owner Klinik', '040207']);
    $upsertUser->execute(['aldrich', $adminHash, 'Aldrich Joerellie', 'admin', 'Super Admin & Owner Klinik', '040207']);

    // Staff & Demo Doctor
    $upsertUser->execute(['resepsionis', $staffHash, 'Kristoforus Vieri, S.Kom', 'resepsionis', 'Front Office & Kasir', NULL]);
    $upsertUser->execute(['asisten1', $staffHash, 'Juan Darren, A.Md.Kep', 'asisten', 'Asisten Medis 1 (Poli Anak)', NULL]);
    $upsertUser->execute(['asisten2', $staffHash, 'Siti Nurhaliza, A.Md.Kep', 'asisten', 'Asisten Medis 2 (Poli Gigi & Vaksin)', NULL]);
    $upsertUser->execute(['dokter', $docHash, 'dr. Aldrich Joerellie, Sp.A, B.Med', 'dokter', 'Dokter Spesialis Anak & Bedah Minor', 'DOK-04020']);

    echo "Users updated! Super Admin Aldrich Joerellie set with code 040207.\n";

    echo "\n=== Database Migration & Setup Complete! ===\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
