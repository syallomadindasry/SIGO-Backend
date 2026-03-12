<?php
require_once __DIR__ . '/_bootstrap.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $q    = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '%';
        $stmt = $db->prepare("SELECT * FROM data_obat WHERE nama LIKE ? OR jenis LIKE ? ORDER BY nama");
        $stmt->bind_param('ss', $q, $q);
        $stmt->execute();
        $res  = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        $stmt->close();
        break;

    case 'POST':
        $b    = json_input();
        $stmt = $db->prepare('INSERT INTO data_obat (nama,satuan,jenis) VALUES (?,?,?)');
        $stmt->bind_param('sss', $b['nama'], $b['satuan'], $b['jenis']);
        $stmt->execute();
        echo json_encode(['id_obat' => $db->insert_id, 'message' => 'Berhasil ditambahkan']);
        $stmt->close();
        break;

    case 'PUT':
        $b    = json_input();
        $id   = intval($b['id_obat']);
        $stmt = $db->prepare('UPDATE data_obat SET nama=?,satuan=?,jenis=? WHERE id_obat=?');
        $stmt->bind_param('sssi', $b['nama'], $b['satuan'], $b['jenis'], $id);
        $stmt->execute();
        echo json_encode(['message' => 'Berhasil diupdate']);
        $stmt->close();
        break;

    case 'DELETE':
        $id   = intval($_GET['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM data_obat WHERE id_obat=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['message' => 'Berhasil dihapus']);
        $stmt->close();
        break;
}

$db->close();