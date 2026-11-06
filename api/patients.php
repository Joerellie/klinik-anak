<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Search patients (by RM code, child name, parent name, or phone)
if ($action === 'search') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 1) {
        $stmt = $pdo->query("SELECT * FROM patients ORDER BY id DESC LIMIT 20");
        $patients = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE kode_rm LIKE ? OR nama_anak LIKE ? OR nama_ortu LIKE ? OR no_hp LIKE ? ORDER BY id DESC LIMIT 20");
        $term = "%$q%";
        $stmt->execute([$term, $term, $term, $term]);
        $patients = $stmt->fetchAll();
    }

    foreach ($patients as &$p) {
        $ageInfo = calculateAge($p['tanggal_lahir']);
        $p['usia_formatted'] = $ageInfo['text'];
        $p['usia_tahun'] = $ageInfo['years'];
        $p['usia_bulan'] = $ageInfo['months'];
    }

    jsonResponse(['success' => true, 'data' => $patients]);
}

// Get patient details including full medical and vaccination history
if ($action === 'detail') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$id]);
    $patient = $stmt->fetch();

    if (!$patient) {
        jsonResponse(['success' => false, 'error' => 'Pasien tidak ditemukan.'], 404);
    }

    $ageInfo = calculateAge($patient['tanggal_lahir']);
    $patient['usia_formatted'] = $ageInfo['text'];
    $patient['usia_tahun'] = $ageInfo['years'];

    // Medical records history
    $mrStmt = $pdo->prepare("SELECT mr.*, u.nama_lengkap AS nama_dokter, q.nomor_antrean, q.poli_tujuan
                             FROM medical_records mr
                             LEFT JOIN users u ON mr.id_dokter = u.id
                             LEFT JOIN queues q ON mr.id_antrean = q.id
                             WHERE mr.id_pasien = ?
                             ORDER BY mr.id DESC");
    $mrStmt->execute([$id]);
    $patient['medical_records'] = $mrStmt->fetchAll();

    // Vaccination history
    $vacStmt = $pdo->prepare("SELECT vr.*, u.nama_lengkap AS nama_dokter
                              FROM vaccination_records vr
                              LEFT JOIN users u ON vr.id_dokter = u.id
                              WHERE vr.id_pasien = ?
                              ORDER BY vr.tanggal_vaksin DESC");
    $vacStmt->execute([$id]);
    $patient['vaccination_history'] = $vacStmt->fetchAll();

    // Vitals history
    $vitStmt = $pdo->prepare("SELECT vs.*, q.nomor_antrean
                              FROM vital_signs vs
                              LEFT JOIN queues q ON vs.id_antrean = q.id
                              WHERE vs.id_pasien = ?
                              ORDER BY vs.id DESC");
    $vitStmt->execute([$id]);
    $patient['vitals_history'] = $vitStmt->fetchAll();

    jsonResponse(['success' => true, 'data' => $patient]);
}

// Create new patient (Validation: STRICTLY UNDER 15 YEARS OLD according to Proposal!)
if ($action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $nama_anak = trim($input['nama_anak'] ?? '');
    $nik = trim($input['nik'] ?? '');
    $tanggal_lahir = trim($input['tanggal_lahir'] ?? '');
    $jenis_kelamin = trim($input['jenis_kelamin'] ?? 'L');
    $nama_ortu = trim($input['nama_ortu'] ?? '');
    $no_hp = trim($input['no_hp'] ?? '');
    $alamat = trim($input['alamat'] ?? '');
    $golongan_darah = trim($input['golongan_darah'] ?? '-');
    $riwayat_alergi = trim($input['riwayat_alergi'] ?? 'Tidak ada alergi yang diketahui');

    if (empty($nama_anak) || empty($tanggal_lahir) || empty($nama_ortu) || empty($no_hp)) {
        jsonResponse(['success' => false, 'error' => 'Nama anak, tanggal lahir, nama orang tua, dan nomor WhatsApp/HP wajib diisi!'], 400);
    }

    // Age validation
    $age = calculateAge($tanggal_lahir);
    if (!$age['is_under_15']) {
        jsonResponse([
            'success' => false,
            'error' => "Validasi Gagal: Klinik Anak Happy khusus melayani anak di bawah 15 tahun (Usia saat ini: {$age['years']} tahun {$age['months']} bulan)."
        ], 422);
    }

    // Auto-generate Kode RM: RM-YYYY-XXXX
    $year = date('Y');
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE kode_rm LIKE ?");
    $stmtCount->execute(["RM-$year-%"]);
    $currentCount = $stmtCount->fetchColumn() + 1;
    $kode_rm = sprintf("RM-%s-%04d", $year, $currentCount);

    $insert = $pdo->prepare("INSERT INTO patients (kode_rm, nama_anak, nik, tanggal_lahir, jenis_kelamin, nama_ortu, no_hp, alamat, golongan_darah, riwayat_alergi)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->execute([
        $kode_rm, $nama_anak, $nik, $tanggal_lahir, $jenis_kelamin, $nama_ortu, $no_hp, $alamat, $golongan_darah, $riwayat_alergi
    ]);

    $newId = $pdo->lastInsertId();

    jsonResponse([
        'success' => true,
        'message' => 'Pasien anak berhasil didaftarkan!',
        'id' => $newId,
        'kode_rm' => $kode_rm,
        'usia_formatted' => $age['text']
    ]);
}

jsonResponse(['success' => false, 'error' => 'Action tidak valid.'], 400);

