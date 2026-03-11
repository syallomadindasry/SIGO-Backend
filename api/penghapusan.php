<?php
require_once __DIR__ . '/_bootstrap.php';


$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id_gudang = intval($_GET['id_gudang'] ?? 0);
    $id        = intval($_GET['id']        ?? 0);

    if ($id) {
        $res  = $db->query("SELECT pd.*, b.batch, b.exp_date, o.nama AS nama_obat, o.satuan
                            FROM penghapusan_detail pd
                            JOIN data_batch b ON b.id_batch = pd.id_batch
                            JOIN data_obat  o ON o.id_obat  = b.id_obat
                            WHERE pd.id_hapus = $id");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        exit();
    }

    $where = $id_gudang ? "WHERE p.id_gudang = $id_gudang" : '';
    $res   = $db->query("SELECT p.*, u.nama AS nama_admin,
                         (SELECT COUNT(*) FROM penghapusan_detail pd WHERE pd.id_hapus = p.id) AS total_item
                         FROM penghapusan p
                         JOIN user u ON u.id_admin = p.id_admin
                         $where ORDER BY p.created_at DESC");
    $rows  = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
    exit();
}

if ($method === 'POST') {
    $b    = json_decode(file_get_contents('php://input'), true);
    $type = $b['type'] ?? 'master';

    if ($type === 'master') {
        $stmt = $db->prepare('INSERT INTO penghapusan (no_hapus,tanggal,alasan,id_admin,id_gudang) VALUES (?,?,?,?,?)');
        $stmt->bind_param('sssii', $b['no_hapus'], $b['tanggal'], $b['alasan'], $b['id_admin'], $b['id_gudang']);
        $stmt->execute();
        echo json_encode(['id' => $db->insert_id, 'message' => 'Berhasil dibuat']);
        $stmt->close();
    }

    if ($type === 'detail') {
        $id_hapus  = intval($b['id_hapus']);
        $id_batch  = intval($b['id_batch']);
        $id_gudang = intval($b['id_gudang']);
        $jumlah    = intval($b['jumlah']);

        $res  = $db->query("SELECT stok FROM stok_batch WHERE id_gudang=$id_gudang AND id_batch=$id_batch");
        $stok = $res->fetch_assoc()['stok'] ?? 0;
        if ($stok < $jumlah) {
            http_response_code(400);
            echo json_encode(['error' => 'Stok tidak mencukupi. Stok tersedia: ' . $stok]);
            exit();
        }

        $stmt = $db->prepare('INSERT INTO penghapusan_detail (id_hapus,id_batch,jumlah) VALUES (?,?,?)');
        $stmt->bind_param('iii', $id_hapus, $id_batch, $jumlah);
        $stmt->execute();
        $stmt->close();

        $db->query("UPDATE stok_batch SET stok = stok - $jumlah WHERE id_gudang=$id_gudang AND id_batch=$id_batch");

        echo json_encode(['message' => 'Detail ditambahkan, stok diperbarui']);
    }
}

$db->close();