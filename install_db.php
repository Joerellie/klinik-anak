<?php
/**
 * Database Initialization Script for Klinik Anak Happy
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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

    echo "All tables initialized successfully.\n";

    // Seed default users if empty
    $chk = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($chk == 0) {
        $hashed = password_hash('password123', PASSWORD_BCRYPT);
        $insertUsers = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, spesialisasi) VALUES (?, ?, ?, ?, ?)");
        
        // Staf sesuai dokumen proposal:
        // 1 resepsionis, 2 asisten dokter, 1 dokter utama
        $insertUsers->execute(['resepsionis', $hashed, 'Kristoforus Vieri, S.Kom', 'resepsionis', 'Front Office & Kasir']);
        $insertUsers->execute(['asisten1', $hashed, 'Juan Darren, A.Md.Kep', 'asisten', 'Asisten Medis 1 (Poli Anak & Tumbuh Kembang)']);
        $insertUsers->execute(['asisten2', $hashed, 'Siti Nurhaliza, A.Md.Kep', 'asisten', 'Asisten Medis 2 (Poli Gigi & Vaksinasi)']);
        $insertUsers->execute(['dokter', $hashed, 'dr. Aldrich Joerellie, Sp.A, B.Med', 'dokter', 'Dokter Spesialis Anak & Bedah Minor']);
        $insertUsers->execute(['admin', $hashed, 'Administrator Klinik', 'admin', 'Super Admin Sistem']);

        echo "Default users seeded (password for all: password123).\n";
    }

    // Seed medicines and vaccines if empty
    $chkMed = $pdo->query("SELECT COUNT(*) FROM medicines_stock")->fetchColumn();
    if ($chkMed == 0) {
        $stmtMed = $pdo->prepare("INSERT INTO medicines_stock (kode, nama_item, kategori, stok, min_stok, satuan, harga_satuan, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $meds = [
            // Obat-obatan anak
            ['OBT-001', 'Paracetamol Drop 100mg/ml (15ml)', 'obat', 45, 10, 'Botol', 28000, 'Pereda demam & nyeri bayi/balita'],
            ['OBT-002', 'Paracetamol Sirup 120mg/5ml (60ml)', 'obat', 60, 15, 'Botol', 22000, 'Pereda demam anak rasa strawberry'],
            ['OBT-003', 'Amoxicillin Sirup Kering 125mg/5ml', 'obat', 35, 10, 'Botol', 35000, 'Antibiotik infeksi bakteri'],
            ['OBT-004', 'Cetirizine Drop 10mg/ml (10ml)', 'obat', 28, 8, 'Botol', 42000, 'Antihistamin alergi & gatal'],
            ['OBT-005', 'Zinc Sirup 20mg/5ml (60ml)', 'obat', 50, 10, 'Botol', 30000, 'Suplemen terapi diare balita'],
            ['OBT-006', 'Oralit Serbuk Sachet 200ml', 'obat', 120, 30, 'Sachet', 4500, 'Cairan rehidrasi elektrolit diare'],
            ['OBT-007', 'Ibuprofen Sirup 100mg/5ml (60ml)', 'obat', 40, 10, 'Botol', 32000, 'Antiinflamasi dan antipiretik anak'],
            ['OBT-008', 'Multivitamin Anak Curcuma Plus (100ml)', 'obat', 55, 12, 'Botol', 26000, 'Penambah nafsu makan & daya tahan'],
            // Vaksinasi anak
            ['VKN-001', 'Vaksin Hepatitis B Rekombinan (Uniject)', 'vaksin', 25, 8, 'Dosis/Pcs', 95000, 'Imunisasi hepatitis B bayi baru lahir'],
            ['VKN-002', 'Vaksin BCG Freeze-Dried + Pelarut', 'vaksin', 20, 5, 'Ampul', 110000, 'Pencegahan TBC anak'],
            ['VKN-003', 'Vaksin Polio Tetes (bOPV 10 dosis)', 'vaksin', 30, 8, 'Vial', 75000, 'Imunisasi polio tetes oral'],
            ['VKN-004', 'Vaksin DTP-HB-Hib (Pentabio)', 'vaksin', 22, 6, 'Vial', 185000, 'Difteri, Tetanus, Pertusis, Hep B, Hib'],
            ['VKN-005', 'Vaksin Campak-Rubella (MR)', 'vaksin', 18, 5, 'Vial', 160000, 'Vaksin pencegahan campak & rubella'],
            ['VKN-006', 'Vaksin PCV 13 (Prevenar / Pneumokokus)', 'vaksin', 14, 4, 'Syringe', 650000, 'Pencegahan radang paru & selaput otak'],
            ['VKN-007', 'Vaksin Rotavirus Oral', 'vaksin', 16, 4, 'Tube', 340000, 'Pencegahan diare berat rotavirus'],
            ['VKN-008', 'Vaksin Influenza Anak Trivalent', 'vaksin', 15, 5, 'Pre-filled', 220000, 'Vaksin influenza tahunan anak'],
            // Alat medis & tindakan
            ['ALT-001', 'Masker Nebulizer Pediatric Size', 'alat_medis', 50, 15, 'Pcs', 35000, 'Masker uap inhalasi anak'],
            ['ALT-002', 'Spuit Disposable 1 cc / Tuberculin', 'alat_medis', 200, 50, 'Pcs', 3000, 'Alat suntik vaksinasi presisi'],
            ['ALT-003', 'Bahan Tambal Gigi Glass Ionomer (GIC)', 'alat_medis', 12, 3, 'Set', 175000, 'Penambalan gigi susu anak'],
            ['ALT-004', 'Anestesi Topikal Spray Gigi Rasa Buah', 'alat_medis', 8, 2, 'Botol', 140000, 'Mati rasa lokal cabut gigi susu']
        ];
        foreach ($meds as $m) {
            $stmtMed->execute($m);
        }
        echo "Medicines, vaccines and medical supplies seeded successfully.\n";
    }

    // Seed dummy patients (<15 yo) and initial live queues
    $chkPat = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    if ($chkPat == 0) {
        $stmtPat = $pdo->prepare("INSERT INTO patients (kode_rm, nama_anak, nik, tanggal_lahir, jenis_kelamin, nama_ortu, no_hp, alamat, golongan_darah, riwayat_alergi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $patients = [
            ['RM-2026-001', 'Kenzo Pratama', '3374012005190001', '2021-05-14', 'L', 'Budi Pratama', '081234567890', 'Jl. Cempaka Indah No. 12, Semarang', 'O', 'Tidak ada riwayat alergi'],
            ['RM-2026-002', 'Aisyah Putri Azzahra', '3374016508220002', '2022-08-25', 'P', 'Rina Wulandari', '081398765432', 'Jl. Melati Selatan No. 45, Semarang', 'A', 'Alergi Dingin'],
            ['RM-2026-003', 'Gibran Rakabuming Satria', '3374011202200003', '2020-02-12', 'L', 'Bambang Satria', '085711223344', 'Perum Bukit Asri Blok C-3, Semarang', 'B', 'Alergi Seafood'],
            ['RM-2026-004', 'Naura Kayla Shabira', '3374014901240004', '2024-01-09', 'P', 'dr. Fitriani Dewi', '087855443322', 'Jl. Pandanaran Barat No. 8, Semarang', 'AB', 'Tidak ada'],
            ['RM-2026-005', 'Rafi Ahmad Fauzan', '3374011507180005', '2018-07-15', 'L', 'Ahmad Ridwan', '081900112233', 'Jl. Majapahit Raya No. 102, Semarang', 'O', 'Alergi Penisilin']
        ];
        foreach ($patients as $p) {
            $stmtPat->execute($p);
        }
        echo "Sample patients seeded successfully.\n";

        // Seed initial live queue for today's realistic demo
        // Queues:
        // 1. Kenzo: Sedang di Ruang Dokter (Poli Umum)
        // 2. Aisyah: Sedang di Ruang Asisten (Pemeriksaan awal Poli Gigi)
        // 3. Gibran: Menunggu Dokter (Poli Vaksin)
        // 4. Naura: Menunggu Asisten (Poli Umum)
        // 5. Rafi: Selesai / Kasir (Poli Gigi)
        $pdo->exec("INSERT INTO queues (id, nomor_antrean, id_pasien, poli_tujuan, keluhan_awal, status, estimasi_menit, waktu_daftar, waktu_panggil_asisten, waktu_selesai_asisten, waktu_panggil_dokter) VALUES
            (1, 'U-001', 1, 'umum', 'Demam tinggi 2 hari, batuk berdahak dan pilek', 'ruang_dokter', 10, NOW() - INTERVAL 45 MINUTE, NOW() - INTERVAL 35 MINUTE, NOW() - INTERVAL 20 MINUTE, NOW() - INTERVAL 5 MINUTE),
            (2, 'G-001', 2, 'gigi', 'Gigi geraham bawah berlubang dan mengeluh sakit saat mengunyah', 'ruang_asisten', 12, NOW() - INTERVAL 30 MINUTE, NOW() - INTERVAL 4 MINUTE, NULL, NULL),
            (3, 'V-001', 3, 'vaksin', 'Jadwal suntik Vaksin Influenza Tahunan dan PCV booster', 'menunggu_dokter', 15, NOW() - INTERVAL 25 MINUTE, NOW() - INTERVAL 20 MINUTE, NOW() - INTERVAL 10 MINUTE, NULL),
            (4, 'U-002', 4, 'umum', 'Cek tumbuh kembang bayi 1 tahun dan bintik merah di punggung', 'menunggu_asisten', 18, NOW() - INTERVAL 15 MINUTE, NULL, NULL, NULL),
            (5, 'T-001', 5, 'tindakan', 'Luka lecet di lutut kanan akibat jatuh dari sepeda dan perlu dibersihkan', 'kasir', 5, NOW() - INTERVAL 60 MINUTE, NOW() - INTERVAL 50 MINUTE, NOW() - INTERVAL 40 MINUTE, NOW() - INTERVAL 15 MINUTE)");

        // Vital signs for queues 1, 3, 5
        $pdo->exec("INSERT INTO vital_signs (id_antrean, id_pasien, id_asisten, berat_badan, tinggi_badan, suhu_tubuh, lingkar_kepala, status_gizi, catatan_asisten) VALUES
            (1, 1, 2, 16.5, 102.0, 38.6, 49.5, 'Normal', 'Anak rewel, suhu cukup tinggi, nafas sedikit berbunyi grek-grek.'),
            (3, 3, 2, 21.0, 115.0, 36.6, 51.0, 'Normal / Sehat', 'Kondisi anak aktif dan bugar, siap menerima suntikan vaksin.'),
            (5, 5, 3, 26.2, 126.0, 36.7, 52.0, 'Normal', 'Luka abrasi lutut kanan, tidak ada perdarahan aktif.')");

        // Medical record for queue 1 & 5
        $pdo->exec("INSERT INTO medical_records (id_antrean, id_pasien, id_dokter, anamnesis, pemeriksaan_fisik, diagnosis, catatan_gigi, tindakan_medis, resep_obat, edukasi_ortu, jadwal_kontrol, biaya_layanan) VALUES
            (5, 5, 4, 'Anak terjatuh saat bersepeda, mengeluh perih di lutut kanan.', 'Terdapat vulnus excoriatum (luka lecet) di patella dextra 4x3 cm, kotor pasir, tidak ada fraktur.', 'Vulnus Excoriatum Regio Patella Dextra', NULL, 'Pembersihan luka dengan NaCl 0.9%, asepsis povidone iodine, dan dressing steril kassa sofratulle.', 'Paracetamol sirup 120mg/5ml (3x sehari 1 sendok takar bila nyeri), Salep Gentamicin 0.1% oles 2x sehari', 'Jaga luka tetap kering selama 3 hari, jangan digaruk bila mulai mengering.', NULL, 65000)");

        // Transaction for queue 5
        $pdo->exec("INSERT INTO transactions (no_nota, id_antrean, id_pasien, id_kasir, biaya_konsultasi, biaya_tindakan, biaya_obat_vaksin, biaya_admin, total_biaya, metode_pembayaran, status_bayar, catatan) VALUES
            ('NOTA-20261106-0001', 5, 5, 1, 75000, 65000, 35000, 10000, 185000, 'qris', 'belum_bayar', 'Pembersihan luka + obat sirup & salep')");

        // Seed 2 vaccination histories
        $pdo->exec("INSERT INTO vaccination_records (id_pasien, nama_vaksin, dosis_ke, nomor_batch, tanggal_vaksin, jadwal_vaksin_berikutnya, catatan_reaksi) VALUES
            (1, 'Vaksin Hepatitis B Rekombinan', 1, 'HB-2021-992', '2021-05-14', '2021-06-14', 'Tidak ada KIPI / reaksi negatif'),
            (1, 'Vaksin BCG + Polio 1', 1, 'BCG-8821', '2021-06-20', '2021-08-20', 'Muncul indurasi normal di lengan kanan atas'),
            (3, 'Vaksin DTP-HB-Hib (Pentabio)', 3, 'PENTA-091A', '2020-08-15', '2021-08-15', 'Demam ringan pasca suntik, reda dengan paracetamol')");

        echo "Realistic initial live queues and medical data seeded successfully.\n";
    }

    echo "\n=== Database Setup Complete! ===\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}

