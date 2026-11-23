<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Mask child name for privacy on public queue display (e.g., "Kenzo Pratama" -> "K***o P. (5 thn)")
function maskName($fullName, $ageText = '') {
    $parts = explode(' ', trim($fullName));
    $first = $parts[0] ?? '';
    if (strlen($first) > 2) {
        $masked = substr($first, 0, 1) . '***' . substr($first, -1);
    } else {
        $masked = substr($first, 0, 1) . '***';
    }
    if (isset($parts[1])) {
        $masked .= ' ' . substr($parts[1], 0, 1) . '.';
    }
    return $ageText ? "$masked ($ageText)" : $masked;
}

// 1. Live Queue Data for Public TV & Mobile Queue View
if ($action === 'live') {
    $today = date('Y-m-d');
    
    // Fetch all queues registered today or currently active
    $stmt = $pdo->prepare("SELECT q.*, p.nama_anak, p.tanggal_lahir, p.jenis_kelamin, p.kode_rm
                          FROM queues q
                          JOIN patients p ON q.id_pasien = p.id
                          WHERE DATE(q.waktu_daftar) = ?
                          ORDER BY q.id ASC");
    $stmt->execute([$today]);
    $allQueues = $stmt->fetchAll();

    // Organize by Poli
    $polis = ['umum', 'gigi', 'vaksin', 'tindakan'];
    $summary = [];

    foreach ($polis as $poli) {
        $poliQueues = array_values(array_filter($allQueues, function($q) use ($poli) {
            return $q['poli_tujuan'] === $poli;
        }));

        $currentDoctor = null;
        $currentAssistant = null;
        $waitingList = [];
        $completedCount = 0;

        foreach ($poliQueues as $q) {
            $age = calculateAge($q['tanggal_lahir']);
            $item = [
                'id' => $q['id'],
                'nomor_antrean' => $q['nomor_antrean'],
                'nama_anak_masked' => maskName($q['nama_anak'], $age['text']),
                'nama_anak_full' => $q['nama_anak'],
                'usia' => $age['text'],
                'status' => $q['status'],
                'poli_tujuan' => $q['poli_tujuan'],
                'keluhan_awal' => $q['keluhan_awal'],
                'estimasi_menit' => $q['estimasi_menit'],
                'waktu_daftar' => date('H:i', strtotime($q['waktu_daftar'])),
            ];

            if ($q['status'] === 'ruang_dokter') {
                $currentDoctor = $item;
            } elseif ($q['status'] === 'ruang_asisten') {
                $currentAssistant = $item;
            } elseif (in_array($q['status'], ['menunggu_asisten', 'menunggu_dokter'])) {
                $waitingList[] = $item;
            } elseif (in_array($q['status'], ['kasir', 'selesai'])) {
                $completedCount++;
            }
        }

        $summary[$poli] = [
            'total' => count($poliQueues),
            'completed' => $completedCount,
            'current_doctor' => $currentDoctor,
            'current_assistant' => $currentAssistant,
            'waiting_list' => $waitingList,
            'waiting_count' => count($waitingList)
        ];
    }

    // Latest called queue for speech announcement trigger
    $latestCallStmt = $pdo->prepare("SELECT q.nomor_antrean, q.poli_tujuan, q.status, p.nama_anak
                                     FROM queues q
                                     JOIN patients p ON q.id_pasien = p.id
                                     WHERE DATE(q.waktu_daftar) = ?
                                     AND q.status IN ('ruang_asisten', 'ruang_dokter')
                                     ORDER BY GREATEST(COALESCE(q.waktu_panggil_dokter, '1970-01-01'), COALESCE(q.waktu_panggil_asisten, '1970-01-01')) DESC
                                     LIMIT 1");
    $latestCallStmt->execute([$today]);
    $latestCall = $latestCallStmt->fetch();

    jsonResponse([
        'success' => true,
        'server_time' => date('H:i:s'),
        'server_date' => date('d M Y'),
        'is_open' => (date('H') >= 8 && date('H') < 17), // 08:00 - 17:00
        'summary' => $summary,
        'latest_call' => $latestCall ?: null
    ]);
}

// 2. Ticket Tracking for Parents / Users
if ($action === 'track_ticket') {
    $code = trim($_GET['code'] ?? '');
    if (empty($code)) {
        jsonResponse(['success' => false, 'error' => 'Nomor antrean atau kode RM wajib diisi.'], 400);
    }

    $stmt = $pdo->prepare("SELECT q.*, p.nama_anak, p.tanggal_lahir, p.kode_rm
                          FROM queues q
                          JOIN patients p ON q.id_pasien = p.id
                          WHERE (q.nomor_antrean = ? OR p.kode_rm = ?)
                          AND DATE(q.waktu_daftar) = CURDATE()
                          ORDER BY q.id DESC LIMIT 1");
    $stmt->execute([$code, $code]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        jsonResponse(['success' => false, 'error' => 'Nomor antrean tidak ditemukan untuk hari ini.'], 404);
    }

    $age = calculateAge($ticket['tanggal_lahir']);
    $ticket['usia_formatted'] = $age['text'];

    // Calculate queue position in front of this ticket
    $posStmt = $pdo->prepare("SELECT COUNT(*) FROM queues
                             WHERE poli_tujuan = ?
                             AND DATE(waktu_daftar) = CURDATE()
                             AND status IN ('menunggu_asisten', 'ruang_asisten', 'menunggu_dokter')
                             AND id < ?");
    $posStmt->execute([$ticket['poli_tujuan'], $ticket['id']]);
    $positionAhead = $posStmt->fetchColumn();

    $ticket['people_ahead'] = $positionAhead;
    $ticket['estimated_minutes_remaining'] = ($positionAhead + 1) * 12;

    jsonResponse(['success' => true, 'ticket' => $ticket]);
}

// 3. Queue List for Admin / Staff Portals
if ($action === 'list') {
    $statusFilter = $_GET['status'] ?? '';
    $poliFilter = $_GET['poli'] ?? '';
    
    $query = "SELECT q.*, p.nama_anak, p.kode_rm, p.tanggal_lahir, p.jenis_kelamin, p.nama_ortu, p.no_hp,
                     vs.berat_badan, vs.tinggi_badan, vs.suhu_tubuh, vs.status_gizi,
                     tx.status_bayar, tx.total_biaya, tx.no_nota
              FROM queues q
              JOIN patients p ON q.id_pasien = p.id
              LEFT JOIN vital_signs vs ON q.id = vs.id_antrean
              LEFT JOIN transactions tx ON q.id = tx.id_antrean
              WHERE DATE(q.waktu_daftar) = CURDATE()";
    
    $params = [];
    if (!empty($statusFilter)) {
        $query .= " AND q.status = ?";
        $params[] = $statusFilter;
    }
    if (!empty($poliFilter)) {
        $query .= " AND q.poli_tujuan = ?";
        $params[] = $poliFilter;
    }

    $query .= " ORDER BY q.id ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $age = calculateAge($r['tanggal_lahir']);
        $r['usia_formatted'] = $age['text'];
    }

    jsonResponse(['success' => true, 'data' => $rows]);
}

// 4. Create New Queue Entry (Resepsionis / Online Registration)
if ($action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $id_pasien = intval($input['id_pasien'] ?? 0);
    $poli_tujuan = trim($input['poli_tujuan'] ?? 'umum');
    $keluhan_awal = trim($input['keluhan_awal'] ?? '');

    if ($id_pasien <= 0) {
        jsonResponse(['success' => false, 'error' => 'Pasien belum dipilih.'], 400);
    }

    if (!in_array($poli_tujuan, ['umum', 'gigi', 'vaksin', 'tindakan'])) {
        $poli_tujuan = 'umum';
    }

    // Prefix for queue number: U = Umum, G = Gigi, V = Vaksin, T = Tindakan
    $prefixMap = ['umum' => 'U', 'gigi' => 'G', 'vaksin' => 'V', 'tindakan' => 'T'];
    $prefix = $prefixMap[$poli_tujuan];

    // Generate queue number today
    $stmtNum = $pdo->prepare("SELECT COUNT(*) FROM queues WHERE poli_tujuan = ? AND DATE(waktu_daftar) = CURDATE()");
    $stmtNum->execute([$poli_tujuan]);
    $nextSeq = $stmtNum->fetchColumn() + 1;
    $nomor_antrean = sprintf("%s-%03d", $prefix, $nextSeq);

    $insert = $pdo->prepare("INSERT INTO queues (nomor_antrean, id_pasien, poli_tujuan, keluhan_awal, status, estimasi_menit)
                             VALUES (?, ?, ?, ?, 'menunggu_asisten', 15)");
    $insert->execute([$nomor_antrean, $id_pasien, $poli_tujuan, $keluhan_awal]);
    $newQueueId = $pdo->lastInsertId();

    // Fetch full patient data for printable ticket
    $patStmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $patStmt->execute([$id_pasien]);
    $patient = $patStmt->fetch();
    $age = calculateAge($patient['tanggal_lahir']);

    jsonResponse([
        'success' => true,
        'message' => 'Nomor antrean berhasil diterbitkan!',
        'id' => $newQueueId,
        'nomor_antrean' => $nomor_antrean,
        'poli_tujuan' => $poli_tujuan,
        'patient' => [
            'nama_anak' => $patient['nama_anak'],
            'kode_rm' => $patient['kode_rm'],
            'usia_formatted' => $age['text'],
            'nama_ortu' => $patient['nama_ortu']
        ]
    ]);
}

// 5. Update Queue Status (Transitions: Panggil Asisten -> Ke Dokter -> Selesai -> Kasir)
if ($action === 'update_status') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $queueId = intval($input['id'] ?? 0);
    $newStatus = trim($input['status'] ?? '');

    $allowedStatuses = ['menunggu_asisten', 'ruang_asisten', 'menunggu_dokter', 'ruang_dokter', 'kasir', 'selesai', 'batal'];
    if (!in_array($newStatus, $allowedStatuses)) {
        jsonResponse(['success' => false, 'error' => 'Status tidak valid.'], 400);
    }

    $timeField = '';
    if ($newStatus === 'ruang_asisten') {
        $timeField = ", waktu_panggil_asisten = NOW()";
    } elseif ($newStatus === 'menunggu_dokter') {
        $timeField = ", waktu_selesai_asisten = NOW()";
    } elseif ($newStatus === 'ruang_dokter') {
        $timeField = ", waktu_panggil_dokter = NOW()";
    } elseif ($newStatus === 'kasir') {
        $timeField = ", waktu_selesai_dokter = NOW()";
    } elseif ($newStatus === 'selesai') {
        $timeField = ", waktu_selesai_kasir = NOW()";
    }

    $stmt = $pdo->prepare("UPDATE queues SET status = ? $timeField WHERE id = ?");
    $stmt->execute([$newStatus, $queueId]);

    jsonResponse(['success' => true, 'message' => "Status antrean diperbarui ke $newStatus"]);
}

jsonResponse(['success' => false, 'error' => 'Action tidak valid.'], 400);

