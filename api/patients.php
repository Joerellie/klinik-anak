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

// Get comprehensive patient complaints & medical visit history log (for doctor & staff)
if ($action === 'history_log') {
    $id = intval($_GET['id'] ?? 0);
    $kode_rm = trim($_GET['kode_rm'] ?? '');

    if ($id <= 0 && empty($kode_rm)) {
        jsonResponse(['success' => false, 'error' => 'ID pasien atau Kode RM wajib disertakan.'], 400);
    }

    if ($id > 0) {
        $pStmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
        $pStmt->execute([$id]);
    } else {
        $pStmt = $pdo->prepare("SELECT * FROM patients WHERE kode_rm = ?");
        $pStmt->execute([$kode_rm]);
    }
    $patient = $pStmt->fetch();

    if (!$patient) {
        jsonResponse(['success' => false, 'error' => 'Data pasien tidak ditemukan.'], 404);
    }

    $age = calculateAge($patient['tanggal_lahir']);
    $patient['usia_formatted'] = $age['text'];
    $patient['usia_tahun'] = $age['years'];
    $patient['usia_bulan'] = $age['months'];

    // Fetch all queues (visits) for this patient with complaints, vitals, medical records, and billing
    $qStmt = $pdo->prepare("SELECT q.*,
                                   u_dok.nama_lengkap AS nama_dokter,
                                   mr.anamnesis, mr.pemeriksaan_fisik, mr.diagnosis, mr.catatan_gigi, mr.tindakan_medis, mr.resep_obat, mr.edukasi_ortu, mr.jadwal_kontrol, mr.biaya_layanan,
                                   vs.tensi_darah, vs.berat_badan, vs.tinggi_badan, vs.suhu_tubuh, vs.lingkar_kepala, vs.status_gizi, vs.catatan_asisten, vs.created_at AS vital_created_at,
                                   tx.no_nota, tx.total_biaya, tx.status_bayar, tx.metode_pembayaran
                            FROM queues q
                            LEFT JOIN medical_records mr ON q.id = mr.id_antrean
                            LEFT JOIN users u_dok ON mr.id_dokter = u_dok.id
                            LEFT JOIN vital_signs vs ON q.id = vs.id_antrean
                            LEFT JOIN transactions tx ON q.id = tx.id_antrean
                            WHERE q.id_pasien = ?
                            ORDER BY q.waktu_daftar DESC, q.id DESC");
    $qStmt->execute([$patient['id']]);
    $visits = $qStmt->fetchAll();

    // Ensure vital_signs_logs table exists & attach all timestamped vital updates per visit
    $logsByQueue = [];
    try {
        if ($db_driver === 'mysql') {
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
        }

        // Backfill baseline log if a visit has vital_signs but 0 rows in vital_signs_logs
        $chkVs = $pdo->prepare("SELECT vs.*, u.nama_lengkap AS nama_asisten
                                FROM vital_signs vs
                                LEFT JOIN users u ON vs.id_asisten = u.id
                                WHERE vs.id_pasien = ?");
        $chkVs->execute([$patient['id']]);
        $allVs = $chkVs->fetchAll();
        foreach ($allVs as $vsRow) {
            $cntL = $pdo->prepare("SELECT COUNT(*) FROM vital_signs_logs WHERE id_antrean = ?");
            $cntL->execute([$vsRow['id_antrean']]);
            if ($cntL->fetchColumn() == 0) {
                $insL = $pdo->prepare("INSERT INTO vital_signs_logs (id_antrean, id_pasien, id_asisten, nama_asisten, tensi_darah, berat_badan, tinggi_badan, suhu_tubuh, lingkar_kepala, status_gizi, catatan_asisten, perubahan_ringkas, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insL->execute([
                    $vsRow['id_antrean'],
                    $vsRow['id_pasien'],
                    $vsRow['id_asisten'] ?: 2,
                    $vsRow['nama_asisten'] ?: 'Juan Darren, A.Md.Kep',
                    $vsRow['tensi_darah'] ?: '100/65 mmHg',
                    $vsRow['berat_badan'],
                    $vsRow['tinggi_badan'],
                    $vsRow['suhu_tubuh'],
                    $vsRow['lingkar_kepala'],
                    $vsRow['status_gizi'] ?: 'Normal',
                    $vsRow['catatan_asisten'],
                    'Pengukuran Awal (Pemeriksaan Pertama Asisten)',
                    $vsRow['created_at'] ?: date('Y-m-d H:i:s')
                ]);
            }
        }

        $lStmt = $pdo->prepare("SELECT * FROM vital_signs_logs WHERE id_pasien = ? ORDER BY id ASC");
        $lStmt->execute([$patient['id']]);
        $allLogs = $lStmt->fetchAll();
        foreach ($allLogs as $lg) {
            $qid = $lg['id_antrean'];
            if (!isset($logsByQueue[$qid])) {
                $logsByQueue[$qid] = [];
            }
            $logsByQueue[$qid][] = $lg;
        }
    } catch (Exception $e) {}

    foreach ($visits as &$v) {
        $qid = $v['id'];
        $v['vital_logs'] = isset($logsByQueue[$qid]) ? $logsByQueue[$qid] : [];
    }
    unset($v);

    // Fetch all vaccination records
    $vacStmt = $pdo->prepare("SELECT vr.*, u.nama_lengkap AS nama_dokter, q.nomor_antrean
                              FROM vaccination_records vr
                              LEFT JOIN users u ON vr.id_dokter = u.id
                              LEFT JOIN queues q ON vr.id_antrean = q.id
                              WHERE vr.id_pasien = ?
                              ORDER BY vr.tanggal_vaksin DESC");
    $vacStmt->execute([$patient['id']]);
    $vaccinations = $vacStmt->fetchAll();

    jsonResponse([
        'success' => true,
        'patient' => $patient,
        'total_visits' => count($visits),
        'visits' => $visits,
        'vaccinations' => $vaccinations
    ]);
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
