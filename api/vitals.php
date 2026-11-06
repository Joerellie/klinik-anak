<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Save vital signs from Asisten Dokter
if ($action === 'save') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $id_antrean = intval($input['id_antrean'] ?? 0);
    $berat_badan = floatval($input['berat_badan'] ?? 0);
    $tinggi_badan = floatval($input['tinggi_badan'] ?? 0);
    $suhu_tubuh = floatval($input['suhu_tubuh'] ?? 0);
    $lingkar_kepala = !empty($input['lingkar_kepala']) ? floatval($input['lingkar_kepala']) : null;
    $catatan_asisten = trim($input['catatan_asisten'] ?? '');

    if ($id_antrean <= 0 || $berat_badan <= 0 || $tinggi_badan <= 0 || $suhu_tubuh <= 0) {
        jsonResponse(['success' => false, 'error' => 'Berat badan, tinggi badan, dan suhu tubuh wajib diisi dengan benar!'], 400);
    }

    // Get queue and patient
    $qStmt = $pdo->prepare("SELECT * FROM queues WHERE id = ?");
    $qStmt->execute([$id_antrean]);
    $queue = $qStmt->fetch();

    if (!$queue) {
        jsonResponse(['success' => false, 'error' => 'Data antrean tidak ditemukan.'], 404);
    }

    $id_pasien = $queue['id_pasien'];
    $user = getLoggedInUser();
    $id_asisten = $user['id'] ?? null;

    // Calculate BMI and Nutritional Status (Status Gizi Anak)
    $heightInM = $tinggi_badan / 100.0;
    $bmi = $berat_badan / ($heightInM * $heightInM);
    
    // Pediatric nutritional estimate
    if ($bmi < 13.5) {
        $status_gizi = 'Gizi Kurang (Underweight)';
    } elseif ($bmi <= 18.0) {
        $status_gizi = 'Gizi Baik (Normal)';
    } elseif ($bmi <= 21.0) {
        $status_gizi = 'Berisiko Gizi Lebih (Overweight)';
    } else {
        $status_gizi = 'Gizi Lebih (Obesitas)';
    }

    // Check if record exists for this queue
    $chkStmt = $pdo->prepare("SELECT id FROM vital_signs WHERE id_antrean = ?");
    $chkStmt->execute([$id_antrean]);
    $existing = $chkStmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE vital_signs SET berat_badan = ?, tinggi_badan = ?, suhu_tubuh = ?, lingkar_kepala = ?, status_gizi = ?, catatan_asisten = ?, id_asisten = ? WHERE id = ?");
        $stmt->execute([$berat_badan, $tinggi_badan, $suhu_tubuh, $lingkar_kepala, $status_gizi, $catatan_asisten, $id_asisten, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO vital_signs (id_antrean, id_pasien, id_asisten, berat_badan, tinggi_badan, suhu_tubuh, lingkar_kepala, status_gizi, catatan_asisten) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_antrean, $id_pasien, $id_asisten, $berat_badan, $tinggi_badan, $suhu_tubuh, $lingkar_kepala, $status_gizi, $catatan_asisten]);
    }

    // Advance queue status to 'menunggu_dokter'
    $upQ = $pdo->prepare("UPDATE queues SET status = 'menunggu_dokter', waktu_selesai_asisten = NOW() WHERE id = ?");
    $upQ->execute([$id_antrean]);

    jsonResponse([
        'success' => true,
        'message' => 'Pemeriksaan awal berhasil disimpan! Pasien siap masuk ke Ruang Dokter.',
        'status_gizi' => $status_gizi,
        'bmi' => round($bmi, 1)
    ]);
}

// Get vital signs for a queue
if ($action === 'get') {
    $id_antrean = intval($_GET['id_antrean'] ?? 0);
    $stmt = $pdo->prepare("SELECT vs.*, u.nama_lengkap AS nama_asisten
                           FROM vital_signs vs
                           LEFT JOIN users u ON vs.id_asisten = u.id
                           WHERE vs.id_antrean = ?");
    $stmt->execute([$id_antrean]);
    $data = $stmt->fetch();

    jsonResponse(['success' => true, 'data' => $data ?: null]);
}

jsonResponse(['success' => false, 'error' => 'Action tidak valid.'], 400);

