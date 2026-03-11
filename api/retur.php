<?php
require_once __DIR__ . '/_bootstrap.php';


$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id_gudang = intval($_GET['id_gudang'] ?? 0);
    $id        = intval($_GET['id']        ?? 0);

    if ($id) {
        $res  = $db->query("SELECT rd.*, b.batch, b.exp_date, o.nama AS nama_obat, o.satuan
                            FROM retur_detail rd
                            JOIN data_batch b ON b.id_batch = rd.id_batch
                            JOIN data_obat  o ON o.id_obat  = b.id_obat
                            WHERE rd.id_retur = $id");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        exit();
    }

    $where = $id_gudang ? "WHERE r.id_gudang = $id_gudang" : '';
    $res   = $db->query("SELECT r.*, u.nama AS nama_admin, g.nama_gudang AS nama_tujuan,
                         (SELECT COUNT(*) FROM retur_detail rd WHERE rd.id_retur = r.id) AS total_item
                         FROM retur r
                         JOIN user   u ON u.id_admin   = r.id_admin
                         JOIN gudang g ON g.id_gudang  = r.tujuan
                         $where ORDER BY r.created_at DESC");
    $rows  = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
    exit();
}

if ($method === 'POST') {
    $b    = json_decode(file_get_contents('php://input'), true);
    $type = $b['type'] ?? 'master';

    if ($type === 'master') {
        $stmt = $db->prepare('INSERT INTO retur (no_retur,tanggal,alasan,id_admin,id_gudang,tujuan) VALUES (?,?,?,?,?,?)');
        $stmt->bind_param('sssiii', $b['no_retur'], $b['tanggal'], $b['alasan'], $b['id_admin'], $b['id_gudang'], $b['tujuan']);
        $stmt->execute();
        echo json_encode(['id' => $db->insert_id, 'message' => 'Retur berhasil dibuat']);
        $stmt->close();
    }

    if ($type === 'detail') {
        $id_retur  = intval($b['id_retur']);
        $id_batch  = intval($b['id_batch']);
        $id_gudang = intval($b['id_gudang']);
        $tujuan    = intval($b['tujuan']);
        $jumlah    = intval($b['jumlah']);

        $res  = $db->query("SELECT stok FROM stok_batch WHERE id_gudang=$id_gudang AND id_batch=$id_batch");
        $stok = $res->fetch_assoc()['stok'] ?? 0;
        if ($stok < $jumlah) {
            http_response_code(400);
            echo json_encode(['error' => 'Stok tidak mencukupi. Stok tersedia: ' . $stok]);
            exit();
        }

        $stmt = $db->prepare('INSERT INTO retur_detail (id_retur,id_batch,jumlah) VALUES (?,?,?)');
        $stmt->bind_param('iii', $id_retur, $id_batch, $jumlah);
        $stmt->execute();
        $stmt->close();

        $db->query("UPDATE stok_batch SET stok = stok - $jumlah WHERE id_gudang=$id_gudang AND id_batch=$id_batch");
        $db->query("INSERT INTO stok_batch (id_gudang,id_batch,stok) VALUES ($tujuan,$id_batch,$jumlah)
                    ON DUPLICATE KEY UPDATE stok = stok + $jumlah");

        echo json_encode(['message' => 'Detail ditambahkan, stok diperbarui']);
    }
}

$db->close();