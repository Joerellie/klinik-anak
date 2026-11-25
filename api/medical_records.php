<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Save Medical Record from Dokter (Pemeriksaan Umum, Odontogram Gigi, Vaksinasi & Resep Kasir)
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
    $id_stok_vaksin = intval($input['id_stok_vaksin'] ?? 0);
    $nomor_batch = trim($input['nomor_batch'] ?? '');
    $dosis_ke = intval($input['dosis_ke'] ?? 1);
    $jadwal_vaksin_berikutnya = !empty($input['jadwal_vaksin_berikutnya']) ? $input['jadwal_vaksin_berikutnya'] : null;
    $catatan_reaksi = trim($input['catatan_reaksi'] ?? 'Diberikan tanpa kontraindikasi / tanpa KIPI');

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
    $id_dokter = $user['id'] ?? 4;

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

    // If vaccine administered, insert/update vaccination_records & automatically deduct stock
    $stockDeductedMsg = '';
    if (!empty($nama_vaksin)) {
        $today = date('Y-m-d');
        $chkVac = $pdo->prepare("SELECT id FROM vaccination_records WHERE id_antrean = ?");
        $chkVac->execute([$id_antrean]);
        $existingVac = $chkVac->fetch();

        if ($existingVac) {
            $vacUp = $pdo->prepare("UPDATE vaccination_records SET nama_vaksin = ?, dosis_ke = ?, nomor_batch = ?, jadwal_vaksin_berikutnya = ?, catatan_reaksi = ?, id_dokter = ? WHERE id = ?");
            $vacUp->execute([$nama_vaksin, $dosis_ke, $nomor_batch, $jadwal_vaksin_berikutnya, $catatan_reaksi, $id_dokter, $existingVac['id']]);
        } else {
            $vacInsert = $pdo->prepare("INSERT INTO vaccination_records (id_pasien, id_antrean, id_dokter, nama_vaksin, dosis_ke, nomor_batch, tanggal_vaksin, jadwal_vaksin_berikutnya, catatan_reaksi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $vacInsert->execute([$id_pasien, $id_antrean, $id_dokter, $nama_vaksin, $dosis_ke, $nomor_batch, $today, $jadwal_vaksin_berikutnya, $catatan_reaksi]);

            // Deduct 1 from medicines_stock for this vaccine
            if ($id_stok_vaksin > 0) {
                $stStmt = $pdo->prepare("SELECT * FROM medicines_stock WHERE id = ?");
                $stStmt->execute([$id_stok_vaksin]);
                $vItem = $stStmt->fetch();
            } else {
                $stStmt = $pdo->prepare("SELECT * FROM medicines_stock WHERE kategori = 'vaksin' AND (nama_item = ? OR nama_item LIKE ?) LIMIT 1");
                $stStmt->execute([$nama_vaksin, '%' . $nama_vaksin . '%']);
                $vItem = $stStmt->fetch();
            }

            if (!empty($vItem) && $vItem['stok'] > 0) {
                $newStok = max(0, intval($vItem['stok']) - 1);
                $upStok = $pdo->prepare("UPDATE medicines_stock SET stok = ? WHERE id = ?");
                $upStok->execute([$newStok, $vItem['id']]);
                $stockDeductedMsg = " Stok {$vItem['nama_item']} otomatis berkurang 1 (Sisa: {$newStok} {$vItem['satuan']}).";
            }
        }
    }

    // Create or update transaction / billing invoice for Kasir
    $adminFee = 10000;
    $totalBill = $biaya_layanan + $biaya_tindakan + $biaya_obat + $adminFee;

    $chkTx = $pdo->prepare("SELECT id, status_bayar FROM transactions WHERE id_antrean = ?");
    $chkTx->execute([$id_antrean]);
    $existTx = $chkTx->fetch();

    $catatanTx = "Poli " . ucfirst($queue['poli_tujuan']) . " - " . $diagnosis;
    if (!empty($nama_vaksin)) {
        $catatanTx .= " (Vaksin: $nama_vaksin)";
    }

    if ($existTx) {
        $txUp = $pdo->prepare("UPDATE transactions SET biaya_konsultasi = ?, biaya_tindakan = ?, biaya_obat_vaksin = ?, total_biaya = ?, catatan = ? WHERE id = ?");
        $txUp->execute([$biaya_layanan, $biaya_tindakan, $biaya_obat, $totalBill, $catatanTx, $existTx['id']]);
    } else {
        $noNota = 'NOTA-' . date('Ymd') . '-' . sprintf("%04d", $id_antrean);
        $txIns = $pdo->prepare("INSERT INTO transactions (no_nota, id_antrean, id_pasien, biaya_konsultasi, biaya_tindakan, biaya_obat_vaksin, biaya_admin, total_biaya, status_bayar, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'belum_bayar', ?)");
        $txIns->execute([$noNota, $id_antrean, $id_pasien, $biaya_layanan, $biaya_tindakan, $biaya_obat, $adminFee, $totalBill, $catatanTx]);
    }

    // Advance queue status to 'kasir' if not already paid
    if (!$existTx || $existTx['status_bayar'] !== 'lunas') {
        $now = date('Y-m-d H:i:s');
        $upQ = $pdo->prepare("UPDATE queues SET status = 'kasir', waktu_selesai_dokter = ? WHERE id = ?");
        $upQ->execute([$now, $id_antrean]);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Rekam medis selesai disimpan & diteruskan ke Kasir!' . $stockDeductedMsg,
        'total_tagihan' => $totalBill
    ]);
}

// Get medical record, vaccine & billing details for a specific queue
if ($action === 'get_by_queue') {
    $id_antrean = intval($_GET['id_antrean'] ?? 0);
    $stmt = $pdo->prepare("SELECT mr.*, u.nama_lengkap AS nama_dokter
                           FROM medical_records mr
                           LEFT JOIN users u ON mr.id_dokter = u.id
                           WHERE mr.id_antrean = ?");
    $stmt->execute([$id_antrean]);
    $mr = $stmt->fetch();

    $vacStmt = $pdo->prepare("SELECT * FROM vaccination_records WHERE id_antrean = ? ORDER BY id DESC LIMIT 1");
    $vacStmt->execute([$id_antrean]);
    $vac = $vacStmt->fetch();

    $txStmt = $pdo->prepare("SELECT * FROM transactions WHERE id_antrean = ? LIMIT 1");
    $txStmt->execute([$id_antrean]);
    $tx = $txStmt->fetch();

    jsonResponse([
        'success' => true,
        'data' => $mr ?: null,
        'vaccine' => $vac ?: null,
        'transaction' => $tx ?: null
    ]);
}

jsonResponse(['success' => false, 'error' => 'Action tidak valid.'], 400);
