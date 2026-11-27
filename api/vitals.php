<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Auto-ensure tensi_darah column & vital_signs_logs table exist
try {
    if ($db_driver === 'mysql') {
        $cols = $pdo->query("SHOW COLUMNS FROM vital_signs LIKE 'tensi_darah'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE vital_signs ADD COLUMN tensi_darah VARCHAR(30) DEFAULT NULL AFTER id_asisten");
        }
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
    } elseif ($db_driver === 'pgsql') {
        $pdo->exec("ALTER TABLE vital_signs ADD COLUMN IF NOT EXISTS tensi_darah VARCHAR(30) DEFAULT NULL");
        $pdo->exec("CREATE TABLE IF NOT EXISTS vital_signs_logs (
            id SERIAL PRIMARY KEY,
            id_antrean INT NOT NULL,
            id_pasien INT NOT NULL,
            id_asisten INT DEFAULT NULL,
            nama_asisten VARCHAR(100) DEFAULT NULL,
            tensi_darah VARCHAR(30) DEFAULT NULL,
            berat_badan NUMERIC(5,2) NOT NULL,
            tinggi_badan NUMERIC(5,2) NOT NULL,
            suhu_tubuh NUMERIC(4,1) NOT NULL,
            lingkar_kepala NUMERIC(4,1) DEFAULT NULL,
            status_gizi VARCHAR(50) DEFAULT 'Normal',
            catatan_asisten TEXT DEFAULT NULL,
            perubahan_ringkas TEXT DEFAULT NULL,
            created_at TIMESTAMPTZ DEFAULT NOW()
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS vital_signs_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_antrean INTEGER,
            id_pasien INTEGER,
            id_asisten INTEGER,
            nama_asisten TEXT,
            tensi_darah TEXT,
            berat_badan REAL,
            tinggi_badan REAL,
            suhu_tubuh REAL,
            lingkar_kepala REAL,
            status_gizi TEXT,
            catatan_asisten TEXT,
            perubahan_ringkas TEXT,
            created_at TEXT
        )");
    }
} catch (Exception $e) {
    // Ignore if already exists
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Save vital signs from Asisten Dokter (Input Tensi, Suhu, Berat, Tinggi, Lingkar Kepala & Log Perubahan Jam demi Jam)
if ($action === 'save') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $id_antrean = intval($input['id_antrean'] ?? 0);
    $tensi_darah = trim($input['tensi_darah'] ?? '');
    $berat_badan = floatval($input['berat_badan'] ?? 0);
    $tinggi_badan = floatval($input['tinggi_badan'] ?? 0);
    $suhu_tubuh = floatval($input['suhu_tubuh'] ?? 0);
    $lingkar_kepala = !empty($input['lingkar_kepala']) ? floatval($input['lingkar_kepala']) : null;
    $catatan_asisten = trim($input['catatan_asisten'] ?? '');
    $vaksin_disiapkan = trim($input['vaksin_disiapkan'] ?? '');

    if ($id_antrean <= 0 || $berat_badan <= 0 || $tinggi_badan <= 0 || $suhu_tubuh <= 0) {
        jsonResponse(['success' => false, 'error' => 'Berat badan, tinggi badan, dan suhu tubuh wajib diisi dengan benar!'], 400);
    }

    if (empty($tensi_darah)) {
        $tensi_darah = '100/65 mmHg';
    } elseif (stripos($tensi_darah, 'mmhg') === false) {
        $tensi_darah .= ' mmHg';
    }

    if (!empty($vaksin_disiapkan)) {
        $prefixNote = "[Vaksin Disiapkan Asisten: $vaksin_disiapkan] ";
        if (strpos($catatan_asisten, $vaksin_disiapkan) === false) {
            $catatan_asisten = trim($prefixNote . $catatan_asisten);
        }
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
    $id_asisten = $user['id'] ?? 2;
    $nama_asisten = $user['nama_lengkap'] ?? 'Juan Darren, A.Md.Kep';

    // Calculate BMI and Nutritional Status (Status Gizi Anak)
    $heightInM = $tinggi_badan / 100.0;
    $bmi = $berat_badan / ($heightInM * $heightInM);
    
    if ($bmi < 13.5) {
        $status_gizi = 'Gizi Kurang (Underweight)';
    } elseif ($bmi <= 18.0) {
        $status_gizi = 'Gizi Baik (Normal)';
    } elseif ($bmi <= 21.0) {
        $status_gizi = 'Berisiko Gizi Lebih (Overweight)';
    } else {
        $status_gizi = 'Gizi Lebih (Obesitas)';
    }

    // Check if record already exists for this queue
    $chkStmt = $pdo->prepare("SELECT * FROM vital_signs WHERE id_antrean = ?");
    $chkStmt->execute([$id_antrean]);
    $existing = $chkStmt->fetch();

    // Ensure baseline log exists in vital_signs_logs if $existing was created before logging table existed
    if ($existing) {
        $cntLogStmt = $pdo->prepare("SELECT COUNT(*) FROM vital_signs_logs WHERE id_antrean = ?");
        $cntLogStmt->execute([$id_antrean]);
        if ($cntLogStmt->fetchColumn() == 0) {
            $baseTime = $existing['created_at'] ?: date('Y-m-d H:i:s', time() - 3600);
            $insBase = $pdo->prepare("INSERT INTO vital_signs_logs (id_antrean, id_pasien, id_asisten, nama_asisten, tensi_darah, berat_badan, tinggi_badan, suhu_tubuh, lingkar_kepala, status_gizi, catatan_asisten, perubahan_ringkas, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insBase->execute([
                $id_antrean,
                $id_pasien,
                $existing['id_asisten'] ?: $id_asisten,
                $nama_asisten,
                $existing['tensi_darah'] ?: '100/65 mmHg',
                $existing['berat_badan'],
                $existing['tinggi_badan'],
                $existing['suhu_tubuh'],
                $existing['lingkar_kepala'],
                $existing['status_gizi'] ?: 'Normal',
                $existing['catatan_asisten'],
                'Pengukuran Awal (Baseline Pemeriksaan Asisten)',
                $baseTime
            ]);
        }
    }

    // Compute what changed compared to previous reading ($existing)
    $changes = [];
    if ($existing) {
        $oldSuhu = floatval($existing['suhu_tubuh']);
        if (abs($suhu_tubuh - $oldSuhu) >= 0.05) {
            $diffSuhu = $suhu_tubuh - $oldSuhu;
            $arah = $diffSuhu > 0 ? 'Naik +' . number_format($diffSuhu, 1) . '°C' : 'Turun ' . number_format($diffSuhu, 1) . '°C';
            $changes[] = "Suhu: {$oldSuhu}°C → {$suhu_tubuh}°C ($arah)";
        }

        $oldBb = floatval($existing['berat_badan']);
        if (abs($berat_badan - $oldBb) >= 0.05) {
            $diffBb = $berat_badan - $oldBb;
            $arahBb = $diffBb > 0 ? '+' . number_format($diffBb, 2) . ' kg' : number_format($diffBb, 2) . ' kg';
            $changes[] = "BB: {$oldBb} kg → {$berat_badan} kg ($arahBb)";
        }

        $oldTb = floatval($existing['tinggi_badan']);
        if (abs($tinggi_badan - $oldTb) >= 0.05) {
            $diffTb = $tinggi_badan - $oldTb;
            $arahTb = $diffTb > 0 ? '+' . number_format($diffTb, 1) . ' cm' : number_format($diffTb, 1) . ' cm';
            $changes[] = "TB: {$oldTb} cm → {$tinggi_badan} cm ($arahTb)";
        }

        $oldTensi = trim($existing['tensi_darah'] ?: '100/65 mmHg');
        if (strcasecmp($oldTensi, $tensi_darah) !== 0) {
            $changes[] = "Tensi: {$oldTensi} → {$tensi_darah}";
        }

        $oldLk = $existing['lingkar_kepala'] !== null ? floatval($existing['lingkar_kepala']) : null;
        if ($lingkar_kepala !== null && ($oldLk === null || abs($lingkar_kepala - $oldLk) >= 0.05)) {
            $changes[] = "Lingkar Kepala: " . ($oldLk !== null ? "{$oldLk} cm" : "-") . " → {$lingkar_kepala} cm";
        }

        $perubahan_ringkas = !empty($changes)
            ? implode(' • ', $changes)
            : 'Update Observasi / Catatan Asisten (Nilai tanda vital tetap stabil)';
    } else {
        $perubahan_ringkas = 'Pengukuran Awal (Pemeriksaan Pertama Asisten)';
    }

    // Update or Insert current vital_signs
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE vital_signs SET tensi_darah = ?, berat_badan = ?, tinggi_badan = ?, suhu_tubuh = ?, lingkar_kepala = ?, status_gizi = ?, catatan_asisten = ?, id_asisten = ? WHERE id = ?");
        $stmt->execute([$tensi_darah, $berat_badan, $tinggi_badan, $suhu_tubuh, $lingkar_kepala, $status_gizi, $catatan_asisten, $id_asisten, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO vital_signs (id_antrean, id_pasien, id_asisten, tensi_darah, berat_badan, tinggi_badan, suhu_tubuh, lingkar_kepala, status_gizi, catatan_asisten) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_antrean, $id_pasien, $id_asisten, $tensi_darah, $berat_badan, $tinggi_badan, $suhu_tubuh, $lingkar_kepala, $status_gizi, $catatan_asisten]);
    }

    // Always append a new timestamped snapshot into vital_signs_logs
    $now = date('Y-m-d H:i:s');
    $logInsert = $pdo->prepare("INSERT INTO vital_signs_logs (id_antrean, id_pasien, id_asisten, nama_asisten, tensi_darah, berat_badan, tinggi_badan, suhu_tubuh, lingkar_kepala, status_gizi, catatan_asisten, perubahan_ringkas, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $logInsert->execute([
        $id_antrean,
        $id_pasien,
        $id_asisten,
        $nama_asisten,
        $tensi_darah,
        $berat_badan,
        $tinggi_badan,
        $suhu_tubuh,
        $lingkar_kepala,
        $status_gizi,
        $catatan_asisten,
        $perubahan_ringkas,
        $now
    ]);

    // Advance queue status to 'menunggu_dokter' if currently in assistant phase
    if (in_array($queue['status'], ['menunggu_asisten', 'ruang_asisten', 'menunggu_dokter'])) {
        $upQ = $pdo->prepare("UPDATE queues SET status = 'menunggu_dokter', waktu_selesai_asisten = ? WHERE id = ?");
        $upQ->execute([$now, $id_antrean]);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Tanda vital disimpan & dicatat ke Log History! (' . $perubahan_ringkas . ')',
        'status_gizi' => $status_gizi,
        'tensi_darah' => $tensi_darah,
        'perubahan_ringkas' => $perubahan_ringkas,
        'bmi' => round($bmi, 1)
    ]);
}

// Get vital signs & full change history logs for a queue
if ($action === 'get') {
    $id_antrean = intval($_GET['id_antrean'] ?? 0);
    $stmt = $pdo->prepare("SELECT vs.*, u.nama_lengkap AS nama_asisten
                           FROM vital_signs vs
                           LEFT JOIN users u ON vs.id_asisten = u.id
                           WHERE vs.id_antrean = ?");
    $stmt->execute([$id_antrean]);
    $data = $stmt->fetch();

    // Fetch chronological logs for this queue
    $logs = [];
    try {
        $lStmt = $pdo->prepare("SELECT * FROM vital_signs_logs WHERE id_antrean = ? ORDER BY id DESC");
        $lStmt->execute([$id_antrean]);
        $logs = $lStmt->fetchAll();
    } catch (Exception $e) {}

    jsonResponse([
        'success' => true,
        'data' => $data ?: null,
        'logs' => $logs
    ]);
}

jsonResponse(['success' => false, 'error' => 'Action tidak valid.'], 400);
