<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// List all stock items
if ($action === 'list') {
    $category = $_GET['category'] ?? '';
    if (!empty($category)) {
        $stmt = $pdo->prepare("SELECT * FROM medicines_stock WHERE kategori = ? ORDER BY nama_item ASC");
        $stmt->execute([$category]);
    } else {
        $stmt = $pdo->query("SELECT * FROM medicines_stock ORDER BY kategori ASC, nama_item ASC");
    }
    $items = $stmt->fetchAll();

    foreach ($items as &$it) {
        $it['is_low'] = ($it['stok'] <= $it['min_stok']);
        $it['harga_formatted'] = 'Rp ' . number_format($it['harga_satuan'], 0, ',', '.');
    }

    jsonResponse(['success' => true, 'data' => $items]);
}

// Adjust stock (+ / -)
if ($action === 'adjust') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = intval($input['id'] ?? 0);
    $amount = intval($input['amount'] ?? 0); // can be positive or negative

    if ($id <= 0 || $amount === 0) {
        jsonResponse(['success' => false, 'error' => 'Data penyesuaian stok tidak valid.'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM medicines_stock WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item) {
        jsonResponse(['success' => false, 'error' => 'Item tidak ditemukan.'], 404);
    }

    $newStock = max(0, $item['stok'] + $amount);
    $up = $pdo->prepare("UPDATE medicines_stock SET stok = ? WHERE id = ?");
    $up->execute([$newStock, $id]);

    jsonResponse([
        'success' => true,
        'message' => "Stok {$item['nama_item']} berhasil diperbarui menjadi $newStock {$item['satuan']}.",
        'new_stock' => $newStock,
        'is_low' => ($newStock <= $item['min_stok'])
    ]);
}

// Add new item
if ($action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $kode = trim($input['kode'] ?? '');
    $nama_item = trim($input['nama_item'] ?? '');
    $kategori = trim($input['kategori'] ?? 'obat');
    $stok = intval($input['stok'] ?? 0);
    $min_stok = intval($input['min_stok'] ?? 10);
    $satuan = trim($input['satuan'] ?? 'Botol');
    $harga_satuan = intval($input['harga_satuan'] ?? 0);
    $deskripsi = trim($input['deskripsi'] ?? '');

    if (empty($kode) || empty($nama_item)) {
        jsonResponse(['success' => false, 'error' => 'Kode dan nama item wajib diisi!'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO medicines_stock (kode, nama_item, kategori, stok, min_stok, satuan, harga_satuan, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$kode, $nama_item, $kategori, $stok, $min_stok, $satuan, $harga_satuan, $deskripsi]);

    jsonResponse(['success' => true, 'message' => "Item $nama_item berhasil ditambahkan."]);
}

jsonResponse(['success' => false, 'error' => 'Action tidak valid.'], 400);

