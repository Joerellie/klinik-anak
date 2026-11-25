<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Get invoice details by id_antrean or transaction id
if ($action === 'get_invoice') {
    $id_antrean = intval($_GET['id_antrean'] ?? 0);
    $id_tx = intval($_GET['id_tx'] ?? 0);

    $sqlBase = "SELECT tx.*, q.nomor_antrean, q.poli_tujuan, q.keluhan_awal,
                       p.nama_anak, p.kode_rm, p.tanggal_lahir, p.nama_ortu, p.no_hp,
                       mr.diagnosis, mr.resep_obat, mr.tindakan_medis, mr.catatan_gigi, mr.edukasi_ortu, mr.jadwal_kontrol,
                       vs.tensi_darah, vs.berat_badan, vs.tinggi_badan, vs.suhu_tubuh, vs.status_gizi,
                       vr.nama_vaksin, vr.dosis_ke, vr.nomor_batch,
                       u.nama_lengkap AS nama_kasir,
                       ud.nama_lengkap AS nama_dokter
                FROM transactions tx
                JOIN queues q ON tx.id_antrean = q.id
                JOIN patients p ON tx.id_pasien = p.id
                LEFT JOIN medical_records mr ON q.id = mr.id_antrean
                LEFT JOIN vital_signs vs ON q.id = vs.id_antrean
                LEFT JOIN vaccination_records vr ON q.id = vr.id_antrean
                LEFT JOIN users u ON tx.id_kasir = u.id
                LEFT JOIN users ud ON mr.id_dokter = ud.id";

    if ($id_antrean > 0) {
        $stmt = $pdo->prepare("$sqlBase WHERE tx.id_antrean = ? LIMIT 1");
        $stmt->execute([$id_antrean]);
    } else {
        $stmt = $pdo->prepare("$sqlBase WHERE tx.id = ? LIMIT 1");
        $stmt->execute([$id_tx]);
    }

    $invoice = $stmt->fetch();
    if (!$invoice) {
        jsonResponse(['success' => false, 'error' => 'Data tagihan kasir tidak ditemukan.'], 404);
    }

    $age = calculateAge($invoice['tanggal_lahir']);
    $invoice['usia_formatted'] = $age['text'];

    // Format monetary values
    $invoice['biaya_konsultasi_fmt'] = 'Rp ' . number_format($invoice['biaya_konsultasi'], 0, ',', '.');
    $invoice['biaya_tindakan_fmt'] = 'Rp ' . number_format($invoice['biaya_tindakan'], 0, ',', '.');
    $invoice['biaya_obat_vaksin_fmt'] = 'Rp ' . number_format($invoice['biaya_obat_vaksin'], 0, ',', '.');
    $invoice['biaya_admin_fmt'] = 'Rp ' . number_format($invoice['biaya_admin'], 0, ',', '.');
    $invoice['total_biaya_fmt'] = 'Rp ' . number_format($invoice['total_biaya'], 0, ',', '.');

    jsonResponse(['success' => true, 'invoice' => $invoice]);
}

// Process payment (and optionally adjust costs at cashier desk)
if ($action === 'pay') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id_antrean = intval($input['id_antrean'] ?? 0);
    $metode = trim($input['metode_pembayaran'] ?? 'tunai');
    $catatan = trim($input['catatan'] ?? '');

    if ($id_antrean <= 0) {
        jsonResponse(['success' => false, 'error' => 'Antrean tidak valid.'], 400);
    }

    $user = getLoggedInUser();
    $id_kasir = $user['id'] ?? 1;
    $now = date('Y-m-d H:i:s');

    // Optional cost overrides from cashier modal
    $chk = $pdo->prepare("SELECT * FROM transactions WHERE id_antrean = ?");
    $chk->execute([$id_antrean]);
    $tx = $chk->fetch();
    if (!$tx) {
        jsonResponse(['success' => false, 'error' => 'Nota transaksi tidak ditemukan.'], 404);
    }

    $biaya_konsultasi = isset($input['biaya_konsultasi']) ? intval($input['biaya_konsultasi']) : intval($tx['biaya_konsultasi']);
    $biaya_tindakan = isset($input['biaya_tindakan']) ? intval($input['biaya_tindakan']) : intval($tx['biaya_tindakan']);
    $biaya_obat_vaksin = isset($input['biaya_obat_vaksin']) ? intval($input['biaya_obat_vaksin']) : intval($tx['biaya_obat_vaksin']);
    $biaya_admin = isset($input['biaya_admin']) ? intval($input['biaya_admin']) : intval($tx['biaya_admin']);
    $total_biaya = $biaya_konsultasi + $biaya_tindakan + $biaya_obat_vaksin + $biaya_admin;

    $finalCatatan = !empty($catatan) ? $catatan : $tx['catatan'];

    // Update transaction
    $upTx = $pdo->prepare("UPDATE transactions SET biaya_konsultasi = ?, biaya_tindakan = ?, biaya_obat_vaksin = ?, biaya_admin = ?, total_biaya = ?, status_bayar = 'lunas', metode_pembayaran = ?, waktu_bayar = ?, id_kasir = ?, catatan = ? WHERE id_antrean = ?");
    $upTx->execute([$biaya_konsultasi, $biaya_tindakan, $biaya_obat_vaksin, $biaya_admin, $total_biaya, $metode, $now, $id_kasir, $finalCatatan, $id_antrean]);

    // Update queue status to 'selesai'
    $upQ = $pdo->prepare("UPDATE queues SET status = 'selesai', waktu_selesai_kasir = ? WHERE id = ?");
    $upQ->execute([$now, $id_antrean]);

    jsonResponse([
        'success' => true,
        'message' => 'Pembayaran berhasil dikonfirmasi! Pelayanan pasien selesai.',
        'total_biaya' => $total_biaya
    ]);
}

// Daily Financial & Operational Report
if ($action === 'daily_report') {
    $date = $_GET['date'] ?? date('Y-m-d');

    // Revenue totals
    $sumStmt = $pdo->prepare("SELECT 
        COUNT(CASE WHEN status_bayar = 'lunas' THEN 1 END) as total_lunas_transaksi,
        COALESCE(SUM(CASE WHEN status_bayar = 'lunas' THEN total_biaya ELSE 0 END), 0) as total_omzet,
        COALESCE(SUM(CASE WHEN status_bayar = 'lunas' THEN biaya_konsultasi ELSE 0 END), 0) as omzet_konsultasi,
        COALESCE(SUM(CASE WHEN status_bayar = 'lunas' THEN biaya_tindakan ELSE 0 END), 0) as omzet_tindakan,
        COALESCE(SUM(CASE WHEN status_bayar = 'lunas' THEN biaya_obat_vaksin ELSE 0 END), 0) as omzet_obat,
        COALESCE(SUM(CASE WHEN status_bayar = 'lunas' THEN biaya_admin ELSE 0 END), 0) as omzet_admin
        FROM transactions
        WHERE DATE(created_at) = ?");
    $sumStmt->execute([$date]);
    $summary = $sumStmt->fetch();

    // Patient count per poli today
    $poliStmt = $pdo->prepare("SELECT poli_tujuan, COUNT(*) as jumlah
                              FROM queues
                              WHERE DATE(waktu_daftar) = ?
                              GROUP BY poli_tujuan");
    $poliStmt->execute([$date]);
    $perPoli = $poliStmt->fetchAll();

    // Transaction list
    $listStmt = $pdo->prepare("SELECT tx.*, q.nomor_antrean, q.poli_tujuan, p.nama_anak, p.kode_rm
                               FROM transactions tx
                               JOIN queues q ON tx.id_antrean = q.id
                               JOIN patients p ON tx.id_pasien = p.id
                               WHERE DATE(tx.created_at) = ?
                               ORDER BY tx.id DESC");
    $listStmt->execute([$date]);
    $transactions = $listStmt->fetchAll();

    foreach ($transactions as &$tx) {
        $tx['total_biaya_fmt'] = 'Rp ' . number_format($tx['total_biaya'], 0, ',', '.');
    }

    jsonResponse([
        'success' => true,
        'date' => $date,
        'summary' => [
            'total_omzet_fmt' => 'Rp ' . number_format($summary['total_omzet'], 0, ',', '.'),
            'total_omzet_raw' => intval($summary['total_omzet']),
            'total_lunas' => intval($summary['total_lunas_transaksi']),
            'omzet_konsultasi_fmt' => 'Rp ' . number_format($summary['omzet_konsultasi'], 0, ',', '.'),
            'omzet_tindakan_fmt' => 'Rp ' . number_format($summary['omzet_tindakan'], 0, ',', '.'),
            'omzet_obat_fmt' => 'Rp ' . number_format($summary['omzet_obat'], 0, ',', '.'),
            'omzet_admin_fmt' => 'Rp ' . number_format($summary['omzet_admin'], 0, ',', '.')
        ],
        'per_poli' => $perPoli,
        'transactions' => $transactions
    ]);
}

jsonResponse(['success' => false, 'error' => 'Action tidak valid.'], 400);
