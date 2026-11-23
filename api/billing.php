<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Get invoice details by id_antrean or transaction id
if ($action === 'get_invoice') {
    $id_antrean = intval($_GET['id_antrean'] ?? 0);
    $id_tx = intval($_GET['id_tx'] ?? 0);

    if ($id_antrean > 0) {
        $stmt = $pdo->prepare("SELECT tx.*, q.nomor_antrean, q.poli_tujuan,
                                      p.nama_anak, p.kode_rm, p.tanggal_lahir, p.nama_ortu, p.no_hp,
                                      mr.diagnosis, mr.resep_obat, mr.tindakan_medis,
                                      u.nama_lengkap AS nama_kasir
                               FROM transactions tx
                               JOIN queues q ON tx.id_antrean = q.id
                               JOIN patients p ON tx.id_pasien = p.id
                               LEFT JOIN medical_records mr ON q.id = mr.id_antrean
                               LEFT JOIN users u ON tx.id_kasir = u.id
                               WHERE tx.id_antrean = ?");
        $stmt->execute([$id_antrean]);
    } else {
        $stmt = $pdo->prepare("SELECT tx.*, q.nomor_antrean, q.poli_tujuan,
                                      p.nama_anak, p.kode_rm, p.tanggal_lahir, p.nama_ortu, p.no_hp,
                                      mr.diagnosis, mr.resep_obat, mr.tindakan_medis,
                                      u.nama_lengkap AS nama_kasir
                               FROM transactions tx
                               JOIN queues q ON tx.id_antrean = q.id
                               JOIN patients p ON tx.id_pasien = p.id
                               LEFT JOIN medical_records mr ON q.id = mr.id_antrean
                               LEFT JOIN users u ON tx.id_kasir = u.id
                               WHERE tx.id = ?");
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

// Process payment
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

    // Update transaction
    $upTx = $pdo->prepare("UPDATE transactions SET status_bayar = 'lunas', metode_pembayaran = ?, waktu_bayar = NOW(), id_kasir = ?, catatan = COALESCE(NULLIF(?, ''), catatan) WHERE id_antrean = ?");
    $upTx->execute([$metode, $id_kasir, $catatan, $id_antrean]);

    // Update queue status to 'selesai'
    $upQ = $pdo->prepare("UPDATE queues SET status = 'selesai', waktu_selesai_kasir = NOW() WHERE id = ?");
    $upQ->execute([$id_antrean]);

    jsonResponse([
        'success' => true,
        'message' => 'Pembayaran berhasil dikonfirmasi! Pelayanan pasien selesai.',
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

