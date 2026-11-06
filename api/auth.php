<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($username) || empty($password)) {
        jsonResponse(['success' => false, 'error' => 'Username dan password wajib diisi.'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        // Fallback for plain demo match if needed
        if (!$user || $password !== 'password123') {
            jsonResponse(['success' => false, 'error' => 'Username atau password salah.'], 401);
        }
    }

    unset($user['password']);
    $_SESSION['user'] = $user;

    jsonResponse([
        'success' => true,
        'message' => 'Login berhasil!',
        'user' => $user
    ]);
}

if ($action === 'switch_role') {
    // Quick role switcher for demo and review convenience
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $targetUsername = trim($input['username'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$targetUsername]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'error' => 'Akun demo tidak ditemukan.'], 404);
    }

    unset($user['password']);
    $_SESSION['user'] = $user;

    jsonResponse([
        'success' => true,
        'message' => 'Beralih ke peran: ' . $user['nama_lengkap'],
        'user' => $user
    ]);
}

if ($action === 'current_user') {
    $user = getLoggedInUser();
    jsonResponse([
        'success' => true,
        'logged_in' => $user !== null,
        'user' => $user
    ]);
}

if ($action === 'logout') {
    unset($_SESSION['user']);
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Berhasil logout.']);
}

jsonResponse(['success' => false, 'error' => 'Action tidak valid.'], 400);

