<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Save Medical Record from Dokter
if ($action === 'save') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $id_antrean = intval($input['id_antrean'] ?? 0);
    $anamnesis = trim($input['anamnesis'] ?? '');
    $pemeriksaan_fisik = trim($input['pemeriksaan_fisik'] ?? '');
    $diagnosis = trim($input['diagnosis'] ?? '');
    $catatan_gigi = trim($input['catatan_gigi'] ?? '');
    $tindakan_medis = trim($input['tindakan_medis'] ?? '');
    $resep_obat = trim($input['resep_obat'] ?? '');
    $edukasi_ortu = trim($input['edukasi_ortu'] ?? '');
    $jadwal_kontrol = !empty($input['jadwal_kontrol']) ? $input['jadwal_kontrol'] : null;
    $biaya_layanan = intval($input['biaya_layanan'] ?? 75000);
    $biaya_tindakan = intval($input['biaya_tindakan'] ?? 0);
    $biaya_obat = intval($input['biaya_obat'] ?? 0);

    // Optional Vaccination fields
    $nama_vaksin = trim($input['nama_vaksin'] ?? '');
    $nomor_batch = trim($input['nomor_batch'] ?? '');
    $dosis_ke = intval($input['dosis_ke'] ?? 1);
    $jadwal_vaksin_berikutnya = !empty($input['jadwal_vaksin_berikutnya']) ? $input['jadwal_vaksin_berikutnya'] : null;

    if ($id_antrean <= 0 || empty($diagnosis)) {
        jsonResponse(['success' => false, 'error' => 'Diagnosis dokter wajib diisi!'], 400);
    }

    // Get queue and patient
    $qStmt = $pdo->prepare("SELECT * FROM queues WHERE id = ?");
    $qStmt->execute([$id_antrean]);
    $queue = $qStmt->fetch();

    if (!$queue) {
        jsonResponse(['success' => false, 'error' => 'Antrean tidak ditemukan.'], 404);
    }

    $id_pasien = $queue['id_pasien'];
    $user = getLoggedInUser();
    $id_dokter = $user['id'] ?? 4; // default to doctor Aldrich if not set

    // Insert or update medical record
    $chk = $pdo->prepare("SELECT id FROM medical_records WHERE id_antrean = ?");
    $chk->execute([$id_antrean]);
    $existing = $chk->fetch();

    if ($existing) {
        $mrUpdate = $pdo->prepare("UPDATE medical_records SET anamnesis = ?, pemeriksaan_fisik = ?, diagnosis = ?, catatan_gigi = ?, tindakan_medis = ?, resep_obat = ?, edukasi_ortu = ?, jadwal_kontrol = ?, biaya_layanan = ?, id_dokter = ? WHERE id = ?");
        $mrUpdate->execute([$anamnesis, $pemeriksaan_fisik, $diagnosis, $catatan_gigi, $tindakan_medis, $resep_obat, $edukasi_ortu, $jadwal_kontrol, $biaya_layanan, $id_dokter, $existing['id']]);
    } else {
        $mrInsert = $pdo->prepare("INSERT INTO medical_records (id_antrean, id_pasien, id_dokter, anamnesis, pemeriksaan_fisik, diagnosis, catatan_gigi, tindakan_medis, resep_obat, edukasi_ortu, jadwal_kontrol, biaya_layanan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $mrInsert->execute([$id_antrean, $id_pasien, $id_dokter, $anamnesis, $pemeriksaan_fisik, $diagnosis, $catatan_gigi, $tindakan_medis, $resep_obat, $edukasi_ortu, $jadwal_kontrol, $biaya_layanan]);
    }

    // If vaccine administered, insert into vaccination_records
    if (!empty($nama_vaksin)) {
        $vacInsert = $pdo->prepare("INSERT INTO vaccination_records (id_pasien, id_antrean, id_dokter, nama_vaksin, dosis_ke, nomor_batch, tanggal_vaksin, jadwal_vaksin_berikutnya, catatan_reaksi) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)");
        $vacInsert->execute([$id_pasien, $id_antrean, $id_dokter, $nama_vaksin, $dosis_ke, $nomor_batch, $jadwal_vaksin_berikutnya, 'Diberikan tanpa kontraindikasi']);
    }

    // Create or update transaction / billing invoice for Kasir
    $adminFee = 10000;
    $totalBill = $biaya_layanan + $biaya_tindakan + $biaya_obat + $adminFee;

    $chkTx = $pdo->prepare("SELECT id FROM transactions WHERE id_antrean = ?");
    $chkTx->execute([$id_antrean]);
    $existTx = $chkTx->fetch();

    if ($existTx) {
        $txUp = $pdo->prepare("UPDATE transactions SET biaya_konsultasi = ?, biaya_tindakan = ?, biaya_obat_vaksin = ?, total_biaya = ? WHERE id = ?");
        $txUp->execute([$biaya_layanan, $biaya_tindakan, $biaya_obat, $totalBill, $existTx['id']]);
    } else {
        $noNota = 'NOTA-' . date('Ymd') . '-' . sprintf("%04d", $id_antrean);
        $txIns = $pdo->prepare("INSERT INTO transactions (no_nota, id_antrean, id_pasien, biaya_konsultasi, biaya_tindakan, biaya_obat_vaksin, biaya_admin, total_biaya, status_bayar, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'belum_bayar', ?)");
        $txIns->execute([$noNota, $id_antrean, $id_pasien, $biaya_layanan, $biaya_tindakan, $biaya_obat, $adminFee, $totalBill, "Layanan Poli {$queue['poli_tujuan']} - $diagnosis"]);
    }

    // Advance queue status to 'kasir'
    $upQ = $pdo->prepare("UPDATE queues SET status = 'kasir', waktu_selesai_dokter = NOW() WHERE id = ?");
    $upQ->execute([$id_antrean]);

    jsonResponse([
        'success' => true,
        'message' => 'Pemeriksaan dokter selesai! Data rekam medis dan resep obat telah diteruskan ke Kasir/Resepsionis.',
        'total_tagihan' => $totalBill
    ]);
}

// Get medical record for a specific queue
if ($action === 'get_by_queue') {
    $id_antrean = intval($_GET['id_antrean'] ?? 0);
    $stmt = $pdo->prepare("SELECT mr.*, u.nama_lengkap AS nama_dokter
                           FROM medical_records mr
                           LEFT JOIN users u ON mr.id_dokter = u.id
                           WHERE mr.id_antrean = ?");
    $stmt->execute([$id_antrean]);
    $mr = $stmt->fetch();

    jsonResponse(['success' => true, 'data' => $mr ?: null]);
}

jsonResponse(['success' => false, 'error' => 'Action tidak valid.'], 400);

