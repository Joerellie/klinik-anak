<?php
/**
 * Database Configuration & Helper for Klinik Anak Happy
 * Supports both Local MySQL (XAMPP) and Cloud PostgreSQL (Supabase) / Cloud MySQL (TiDB / Aiven)
 */

$db_driver = getenv('DB_DRIVER') ?: 'mysql'; // 'mysql' or 'pgsql'

// Check if Cloud environment variables exist (e.g. from Render, Vercel, or Supabase)
$db_url = getenv('DATABASE_URL');

try {
    if ($db_url) {
        // Parse DATABASE_URL (e.g., postgresql://postgres:password@db.xxx.supabase.co:5432/postgres)
        $parsed = parse_url($db_url);
        $scheme = $parsed['scheme'] ?? 'mysql';
        $host   = $parsed['host'] ?? 'localhost';
        $port   = $parsed['port'] ?? ($scheme === 'postgres' || $scheme === 'postgresql' ? 5432 : 3306);
        $user   = $parsed['user'] ?? 'root';
        $pass   = $parsed['pass'] ?? '';
        $dbname = ltrim($parsed['path'] ?? 'klinik_anak_happy', '/');

        if ($scheme === 'postgres' || $scheme === 'postgresql') {
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
    } elseif ($db_driver === 'pgsql' || getenv('SUPABASE_HOST')) {
        // Direct Supabase PostgreSQL settings
        $host = getenv('SUPABASE_HOST') ?: 'aws-0-ap-southeast-1.pooler.supabase.com';
        $port = getenv('SUPABASE_PORT') ?: '5432';
        $dbname = getenv('SUPABASE_DBNAME') ?: 'postgres';
        $user = getenv('SUPABASE_USER') ?: 'postgres.xxxx';
        $pass = getenv('SUPABASE_PASS') ?: '';

        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        // Default Local XAMPP MySQL
        $db_host = getenv('DB_HOST') ?: 'localhost';
        $db_user = getenv('DB_USER') ?: 'root';
        $db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $db_name = getenv('DB_NAME') ?: 'klinik_anak_happy';

        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        require_once __DIR__ . '/../install_db.php';
        header("Refresh:0");
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
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
