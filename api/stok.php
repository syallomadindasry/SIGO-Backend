<?php
require_once __DIR__ . '/_bootstrap.php';


$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id_gudang = intval($_GET['id_gudang'] ?? 0);
    $where     = $id_gudang ? "WHERE s.id_gudang = $id_gudang" : '';

    $sql = "SELECT s.id_gudang, s.id_batch, s.stok,
                   b.batch, b.exp_date,
                   o.id_obat, o.nama AS nama_obat, o.satuan, o.jenis,
                   g.nama_gudang
            FROM stok_batch s
            JOIN data_batch b ON b.id_batch  = s.id_batch
            JOIN data_obat  o ON o.id_obat   = b.id_obat
            JOIN gudang     g ON g.id_gudang = s.id_gudang
            $where
            ORDER BY o.nama, b.exp_date";

    $res  = $db->query($sql);
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
}

$db->close();