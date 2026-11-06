<?php
/**
 * Database Configuration & Helper for Klinik Anak Happy
 * Supports:
 * 1. Cloud PostgreSQL (Supabase / Render) via DATABASE_URL or SUPABASE_* vars
 * 2. Local MySQL (XAMPP)
 * 3. Graceful SQLite / Demo Fallback (if no database is setup yet, web still works 100%!)
 */

$db_url = getenv('DATABASE_URL');
$pdo = null;
$db_driver = 'mysql';
$is_fallback_mode = false;

try {
    if ($db_url) {
        // Supabase / Cloud connection string
        $parsed = parse_url($db_url);
        $scheme = $parsed['scheme'] ?? 'mysql';
        $host   = $parsed['host'] ?? 'localhost';
        $port   = $parsed['port'] ?? ($scheme === 'postgres' || $scheme === 'postgresql' ? 5432 : 3306);
        $user   = $parsed['user'] ?? 'root';
        $pass   = $parsed['pass'] ?? '';
        $dbname = ltrim($parsed['path'] ?? 'klinik_anak_happy', '/');

        if ($scheme === 'postgres' || $scheme === 'postgresql') {
            $db_driver = 'pgsql';
            $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } else {
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
    } else {
        // Try Local MySQL
        $db_host = getenv('DB_HOST') ?: 'localhost';
        $db_user = getenv('DB_USER') ?: 'root';
        $db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $db_name = getenv('DB_NAME') ?: 'klinik_anak_happy';

        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 2
        ]);
    }
} catch (Exception $e) {
    // If MySQL / Supabase is not configured yet, fallback to SQLite demo database
    // so the deployment NEVER crashes when shown to lecturers or friends!
    $is_fallback_mode = true;
    try {
        $sqlite_path = sys_get_temp_dir() . '/klinik_demo.sqlite';
        $is_new = !file_exists($sqlite_path);
        $pdo = new PDO("sqlite:" . $sqlite_path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $db_driver = 'sqlite';

        if ($is_new) {
            initSqliteFallback($pdo);
        }
    } catch (Exception $sqe) {
        // Graceful error only if SQLite also fails
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Return JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Check if user is logged in
 */
function getLoggedInUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Require authentication
 */
function requireAuth($allowedRoles = []) {
    $user = getLoggedInUser();
    if (!$user) {
        jsonResponse(['success' => false, 'error' => 'Silakan login terlebih dahulu.'], 401);
    }
    if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles) && $user['role'] !== 'admin') {
        jsonResponse(['success' => false, 'error' => 'Akses ditolak: role tidak memiliki izin.'], 403);
    }
    return $user;
}

/**
 * Helper to calculate age from birthdate
 */
function calculateAge($birthDate) {
    $dob = new DateTime($birthDate);
    $now = new DateTime();
    $diff = $now->diff($dob);
    return [
        'years' => $diff->y,
        'months' => $diff->m,
        'days' => $diff->d,
        'text' => ($diff->y > 0 ? "{$diff->y} thn " : "") . "{$diff->m} bln",
        'is_under_15' => ($diff->y < 15)
    ];
}

/**
 * Initialize fallback SQLite database if online server has no cloud DB yet
 */
function initSqliteFallback($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, nama_lengkap TEXT, role TEXT, spesialisasi TEXT, created_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS patients (id INTEGER PRIMARY KEY AUTOINCREMENT, kode_rm TEXT UNIQUE, nama_anak TEXT, nik TEXT, tanggal_lahir TEXT, jenis_kelamin TEXT, nama_ortu TEXT, no_hp TEXT, alamat TEXT, golongan_darah TEXT, riwayat_alergi TEXT, created_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS queues (id INTEGER PRIMARY KEY AUTOINCREMENT, nomor_antrean TEXT, id_pasien INTEGER, poli_tujuan TEXT, keluhan_awal TEXT, status TEXT, estimasi_menit INTEGER, waktu_daftar TEXT, waktu_panggil_asisten TEXT, waktu_selesai_asisten TEXT, waktu_panggil_dokter TEXT, waktu_selesai_dokter TEXT, waktu_selesai_kasir TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS vital_signs (id INTEGER PRIMARY KEY AUTOINCREMENT, id_antrean INTEGER, id_pasien INTEGER, id_asisten INTEGER, berat_badan REAL, tinggi_badan REAL, suhu_tubuh REAL, lingkar_kepala REAL, status_gizi TEXT, catatan_asisten TEXT, created_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS medical_records (id INTEGER PRIMARY KEY AUTOINCREMENT, id_antrean INTEGER, id_pasien INTEGER, id_dokter INTEGER, anamnesis TEXT, pemeriksaan_fisik TEXT, diagnosis TEXT, catatan_gigi TEXT, tindakan_medis TEXT, resep_obat TEXT, edukasi_ortu TEXT, jadwal_kontrol TEXT, biaya_layanan INTEGER, created_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS vaccination_records (id INTEGER PRIMARY KEY AUTOINCREMENT, id_pasien INTEGER, id_antrean INTEGER, id_dokter INTEGER, nama_vaksin TEXT, dosis_ke INTEGER, nomor_batch TEXT, tanggal_vaksin TEXT, jadwal_vaksin_berikutnya TEXT, catatan_reaksi TEXT, created_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS medicines_stock (id INTEGER PRIMARY KEY AUTOINCREMENT, kode TEXT UNIQUE, nama_item TEXT, kategori TEXT, stok INTEGER, min_stok INTEGER, satuan TEXT, harga_satuan INTEGER, deskripsi TEXT, updated_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (id INTEGER PRIMARY KEY AUTOINCREMENT, no_nota TEXT UNIQUE, id_antrean INTEGER, id_pasien INTEGER, id_kasir INTEGER, biaya_konsultasi INTEGER, biaya_tindakan INTEGER, biaya_obat_vaksin INTEGER, biaya_admin INTEGER, total_biaya INTEGER, metode_pembayaran TEXT, status_bayar TEXT, catatan TEXT, waktu_bayar TEXT, created_at TEXT);");

    // Seed dummy users
    $hashed = password_hash('password123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (username, password, nama_lengkap, role, spesialisasi) VALUES
        ('resepsionis', '$hashed', 'Kristoforus Vieri, S.Kom', 'resepsionis', 'Front Office & Kasir'),
        ('asisten1', '$hashed', 'Juan Darren, A.Md.Kep', 'asisten', 'Asisten Medis 1'),
        ('dokter', '$hashed', 'dr. Aldrich Joerellie, Sp.A', 'dokter', 'Dokter Spesialis Anak'),
        ('admin', '$hashed', 'Administrator', 'admin', 'Super Admin');");

    // Seed dummy patients
    $pdo->exec("INSERT INTO patients (kode_rm, nama_anak, nik, tanggal_lahir, jenis_kelamin, nama_ortu, no_hp, alamat, golongan_darah, riwayat_alergi) VALUES
        ('RM-2026-0001', 'Kenzo Pratama', '3374012005190001', '2021-05-14', 'L', 'Budi Pratama', '081234567890', 'Semarang', 'O', 'Tidak ada'),
        ('RM-2026-0002', 'Aisyah Putri Azzahra', '3374016508220002', '2022-08-25', 'P', 'Rina Wulandari', '081398765432', 'Semarang', 'A', 'Alergi Dingin');");

    // Seed demo queue
    $pdo->exec("INSERT INTO queues (id, nomor_antrean, id_pasien, poli_tujuan, keluhan_awal, status, estimasi_menit, waktu_daftar) VALUES
        (1, 'U-001', 1, 'umum', 'Demam tinggi 2 hari dan batuk pilek', 'ruang_dokter', 10, datetime('now')),
        (2, 'G-001', 2, 'gigi', 'Gigi geraham sakit', 'ruang_asisten', 12, datetime('now'));");
}
