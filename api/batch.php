<?php
require_once __DIR__ . '/_bootstrap.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $sql = "SELECT b.*, o.nama AS nama_obat, o.satuan, o.jenis
                FROM data_batch b
                JOIN data_obat o ON o.id_obat = b.id_obat
                ORDER BY b.exp_date ASC";
        $res  = $db->query($sql);
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        break;

    case 'POST':
        $b    = json_input();
        $stmt = $db->prepare('INSERT INTO data_batch (batch,id_obat,exp_date) VALUES (?,?,?)');
        $stmt->bind_param('sis', $b['batch'], $b['id_obat'], $b['exp_date']);
        $stmt->execute();
        echo json_encode(['id_batch' => $db->insert_id, 'message' => 'Berhasil ditambahkan']);
        $stmt->close();
        break;
}

$db->close();