<?php
require_once __DIR__ . '/_bootstrap.php';


$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET list atau GET detail
if ($method === 'GET') {
    $id_gudang = intval($_GET['id_gudang'] ?? 0);
    $id        = intval($_GET['id']        ?? 0);

    // GET detail satu nota
    if ($id) {
        $res  = $db->query("SELECT pd.*, b.batch, b.exp_date, o.nama AS nama_obat, o.satuan
                            FROM pembelian_detail pd
                            JOIN data_batch b ON b.id_batch = pd.id_batch
                            JOIN data_obat  o ON o.id_obat  = b.id_obat
                            WHERE pd.id_pembelian = $id");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        exit();
    }

    // GET list nota
    $where = $id_gudang ? "WHERE p.id_gudang = $id_gudang" : '';
    $res   = $db->query("SELECT p.*, u.nama AS nama_admin,
                         (SELECT COUNT(*) FROM pembelian_detail pd WHERE pd.id_pembelian = p.id) AS total_item,
                         (SELECT COALESCE(SUM(pd.jumlah*pd.harga),0) FROM pembelian_detail pd WHERE pd.id_pembelian = p.id) AS total_harga
                         FROM pembelian p
                         JOIN user u ON u.id_admin = p.id_admin
                         $where ORDER BY p.created_at DESC");
    $rows  = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
    exit();
}

// POST simpan nota baru
if ($method === 'POST') {
    $b    = json_decode(file_get_contents('php://input'), true);
    $type = $b['type'] ?? 'master';

    if ($type === 'master') {
        $stmt = $db->prepare(
            'INSERT INTO pembelian (no_nota,tanggal,supplier,alamat,kota,telepon,metode_bayar,diskon,catatan,id_admin,id_gudang)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('sssssssdsii',
            $b['no_nota'], $b['tanggal'], $b['supplier'],
            $b['alamat'],  $b['kota'],    $b['telepon'],
            $b['metode_bayar'], $b['diskon'], $b['catatan'],
            $b['id_admin'], $b['id_gudang']
        );
        $stmt->execute();
        $new_id = $db->insert_id;
        $stmt->close();
        echo json_encode(['id' => $new_id, 'message' => 'Nota berhasil dibuat']);
    }

    if ($type === 'detail') {
        $id_pembelian = intval($b['id_pembelian']);
        $id_batch     = intval($b['id_batch']);
        $id_gudang    = intval($b['id_gudang']);
        $jumlah       = intval($b['jumlah']);
        $harga        = floatval($b['harga']);

        // Simpan detail
        $stmt = $db->prepare('INSERT INTO pembelian_detail (id_pembelian,id_batch,jumlah,harga) VALUES (?,?,?,?)');
        $stmt->bind_param('iiid', $id_pembelian, $id_batch, $jumlah, $harga);
        $stmt->execute();
        $stmt->close();

        // Update stok (upsert)
        $stmt = $db->prepare(
            'INSERT INTO stok_batch (id_gudang,id_batch,stok) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE stok = stok + VALUES(stok)'
        );
        $stmt->bind_param('iii', $id_gudang, $id_batch, $jumlah);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['message' => 'Detail berhasil ditambahkan, stok diperbarui']);
    }
}

$db->close();