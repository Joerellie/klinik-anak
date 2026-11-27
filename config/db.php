<?php
/**
 * Database Configuration & Helper for Klinik Anak Happy
 * Supports:
 * 1. Cloud PostgreSQL (Supabase) via DATABASE_URL or SUPABASE_* vars
 * 2. Local MySQL (XAMPP)
 * 3. Graceful SQLite / Demo Fallback
 */

// 1. Auto-load .env file if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            $val = trim($val, "'\"");
            putenv("$key=$val");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

$db_url = getenv('DATABASE_URL') ?: getenv('SUPABASE_DB_URL');
$pdo = null;
$db_driver = 'mysql';
$db_active_host = 'localhost';
$is_fallback_mode = false;
$db_connect_error = null;

try {
    if (!empty($db_url)) {
        // Parse Supabase PostgreSQL connection string
        $parsed = parse_url($db_url);
        $scheme = $parsed['scheme'] ?? 'mysql';
        $host   = $parsed['host'] ?? 'localhost';
        $port   = $parsed['port'] ?? ($scheme === 'postgres' || $scheme === 'postgresql' ? 5432 : 3306);
        $user   = isset($parsed['user']) ? urldecode($parsed['user']) : 'root';
        $pass   = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
        $dbname = ltrim($parsed['path'] ?? 'postgres', '/');
        $db_active_host = $host;

        if ($scheme === 'postgres' || $scheme === 'postgresql') {
            $db_driver = 'pgsql';
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
            try {
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 6
                ]);
            } catch (Exception $e) {
                // Retry without sslmode if required fails
                $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 6
                ]);
            }
        } else {
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
    } elseif (!empty(getenv('SUPABASE_HOST'))) {
        // Individual Supabase credentials
        $host = getenv('SUPABASE_HOST');
        $port = getenv('SUPABASE_PORT') ?: 5432;
        $dbname = getenv('SUPABASE_DB') ?: 'postgres';
        $user = getenv('SUPABASE_USER') ?: 'postgres';
        $pass = getenv('SUPABASE_PASSWORD') ?: '';
        $db_driver = 'pgsql';
        $db_active_host = $host;

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 6
            ]);
        } catch (Exception $e) {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 6
            ]);
        }
    } else {
        // Local MySQL (XAMPP)
        $db_host = getenv('DB_HOST') ?: 'localhost';
        $db_port = getenv('DB_PORT') ?: 3306;
        $db_user = getenv('DB_USER') ?: 'root';
        $db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $db_name = getenv('DB_NAME') ?: 'klinik_anak_happy';
        $db_driver = 'mysql';
        $db_active_host = $db_host;

        $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 3
        ]);
    }
} catch (Exception $e) {
    // If Primary database fails, fallback to SQLite demo database
    $db_connect_error = $e->getMessage();
    $is_fallback_mode = true;
    try {
        $sqlite_path = sys_get_temp_dir() . '/klinik_demo.sqlite';
        $is_new = !file_exists($sqlite_path);
        $pdo = new PDO("sqlite:" . $sqlite_path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $db_driver = 'sqlite';
        $db_active_host = 'SQLite Demo File';

        if ($is_new) {
            initSqliteFallback($pdo);
        }
    } catch (Exception $sqe) {
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
 * Return current active database info
 */
function getDbInfo() {
    global $db_driver, $is_fallback_mode, $db_active_host, $db_connect_error;
    $label = 'MySQL Lokal (XAMPP)';
    if ($db_driver === 'pgsql') {
        $label = 'Supabase PostgreSQL (Cloud)';
    } elseif ($db_driver === 'sqlite') {
        $label = 'SQLite Demo Fallback';
    }

    return [
        'driver' => $db_driver,
        'label' => $label,
        'is_supabase' => ($db_driver === 'pgsql'),
        'is_fallback' => $is_fallback_mode,
        'host' => $db_active_host,
        'error' => $db_connect_error
    ];
}

/**
 * Generate unique random access code for doctor
 * Format: DOK-XXXXX (e.g. DOK-48291) or 6 digits
 */
function generateRandomDoctorCode($prefix = 'DOK-') {
    $codeNum = str_pad(mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
    return $prefix . $codeNum;
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
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE,
        password TEXT,
        nama_lengkap TEXT,
        role TEXT,
        spesialisasi TEXT,
        no_str TEXT,
        no_hp TEXT,
        email TEXT,
        status TEXT DEFAULT 'approved',
        kode_akses TEXT,
        verified_at TEXT,
        verified_by TEXT,
        created_at TEXT
    );");
    $pdo->exec("CREATE TABLE IF NOT EXISTS patients (id INTEGER PRIMARY KEY AUTOINCREMENT, kode_rm TEXT UNIQUE, nama_anak TEXT, nik TEXT, tanggal_lahir TEXT, jenis_kelamin TEXT, nama_ortu TEXT, no_hp TEXT, alamat TEXT, golongan_darah TEXT, riwayat_alergi TEXT, created_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS queues (id INTEGER PRIMARY KEY AUTOINCREMENT, nomor_antrean TEXT, id_pasien INTEGER, poli_tujuan TEXT, keluhan_awal TEXT, status TEXT, estimasi_menit INTEGER, waktu_daftar TEXT, waktu_panggil_asisten TEXT, waktu_selesai_asisten TEXT, waktu_panggil_dokter TEXT, waktu_selesai_dokter TEXT, waktu_selesai_kasir TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS vital_signs (id INTEGER PRIMARY KEY AUTOINCREMENT, id_antrean INTEGER, id_pasien INTEGER, id_asisten INTEGER, tensi_darah TEXT, berat_badan REAL, tinggi_badan REAL, suhu_tubuh REAL, lingkar_kepala REAL, status_gizi TEXT, catatan_asisten TEXT, created_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS vital_signs_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, id_antrean INTEGER, id_pasien INTEGER, id_asisten INTEGER, nama_asisten TEXT, tensi_darah TEXT, berat_badan REAL, tinggi_badan REAL, suhu_tubuh REAL, lingkar_kepala REAL, status_gizi TEXT, catatan_asisten TEXT, perubahan_ringkas TEXT, created_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS medical_records (id INTEGER PRIMARY KEY AUTOINCREMENT, id_antrean INTEGER, id_pasien INTEGER, id_dokter INTEGER, anamnesis TEXT, pemeriksaan_fisik TEXT, diagnosis TEXT, catatan_gigi TEXT, tindakan_medis TEXT, resep_obat TEXT, edukasi_ortu TEXT, jadwal_kontrol TEXT, biaya_layanan INTEGER, created_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS vaccination_records (id INTEGER PRIMARY KEY AUTOINCREMENT, id_pasien INTEGER, id_antrean INTEGER, id_dokter INTEGER, nama_vaksin TEXT, dosis_ke INTEGER, nomor_batch TEXT, tanggal_vaksin TEXT, jadwal_vaksin_berikutnya TEXT, catatan_reaksi TEXT, created_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS medicines_stock (id INTEGER PRIMARY KEY AUTOINCREMENT, kode TEXT UNIQUE, nama_item TEXT, kategori TEXT, stok INTEGER, min_stok INTEGER, satuan TEXT, harga_satuan INTEGER, deskripsi TEXT, updated_at TEXT);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (id INTEGER PRIMARY KEY AUTOINCREMENT, no_nota TEXT UNIQUE, id_antrean INTEGER, id_pasien INTEGER, id_kasir INTEGER, biaya_konsultasi INTEGER, biaya_tindakan INTEGER, biaya_obat_vaksin INTEGER, biaya_admin INTEGER, total_biaya INTEGER, metode_pembayaran TEXT, status_bayar TEXT, catatan TEXT, waktu_bayar TEXT, created_at TEXT);");

    // Super Admin: Aldrich Joerellie with Code 040207
    $adminHash = password_hash('040207', PASSWORD_BCRYPT);
    $staffHash = password_hash('password123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT OR IGNORE INTO users (username, password, nama_lengkap, role, spesialisasi, status, kode_akses) VALUES
        ('admin', '$adminHash', 'Aldrich Joerellie', 'admin', 'Super Admin & Owner', 'approved', '040207'),
        ('aldrich', '$adminHash', 'Aldrich Joerellie', 'admin', 'Super Admin & Owner', 'approved', '040207'),
        ('resepsionis', '$staffHash', 'Kristoforus Vieri, S.Kom', 'resepsionis', 'Front Office & Kasir', 'approved', NULL),
        ('asisten1', '$staffHash', 'Juan Darren, A.Md.Kep', 'asisten', 'Asisten Medis 1', 'approved', NULL),
        ('dokter', '$staffHash', 'dr. Aldrich Joerellie, Sp.A', 'dokter', 'Dokter Spesialis Anak', 'approved', 'DOK-04020');");

    // Seed dummy patients
    $pdo->exec("INSERT OR IGNORE INTO patients (id, kode_rm, nama_anak, nik, tanggal_lahir, jenis_kelamin, nama_ortu, no_hp, alamat, golongan_darah, riwayat_alergi) VALUES
        (1, 'RM-2026-0001', 'Kenzo Pratama', '3374012005190001', '2021-05-14', 'L', 'Budi Pratama', '081234567890', 'Semarang', 'O', 'Tidak ada'),
        (2, 'RM-2026-0002', 'Aisyah Putri Azzahra', '3374016508220002', '2022-08-25', 'P', 'Rina Wulandari', '081398765432', 'Semarang', 'A', 'Alergi Dingin');");
}
