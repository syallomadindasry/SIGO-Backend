<?php
require_once __DIR__ . '/_bootstrap.php';


$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sumber = intval($_GET['sumber'] ?? 0);
    $id     = intval($_GET['id']     ?? 0);

    if ($id) {
        $res  = $db->query("SELECT md.*, b.batch, b.exp_date, o.nama AS nama_obat, o.satuan
                            FROM mutasi_detail md
                            JOIN data_batch b ON b.id_batch = md.id_batch
                            JOIN data_obat  o ON o.id_obat  = b.id_obat
                            WHERE md.id_mutasi = $id");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        exit();
    }

    $where = $sumber ? "WHERE m.sumber = $sumber" : '';
    $res   = $db->query("SELECT m.*,
                         g1.nama_gudang AS nama_sumber,
                         g2.nama_gudang AS nama_tujuan,
                         u.nama AS nama_admin,
                         (SELECT COUNT(*) FROM mutasi_detail md WHERE md.id_mutasi = m.id) AS total_item
                         FROM mutasi m
                         JOIN gudang g1 ON g1.id_gudang = m.sumber
                         JOIN gudang g2 ON g2.id_gudang = m.tujuan
                         JOIN user   u  ON u.id_admin   = m.id_admin
                         $where ORDER BY m.created_at DESC");
    $rows  = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
    exit();
}

if ($method === 'POST') {
    $b    = json_decode(file_get_contents('php://input'), true);
    $type = $b['type'] ?? 'master';

    if ($type === 'master') {
        $stmt = $db->prepare(
            'INSERT INTO mutasi (no_mutasi,tanggal,sumber,tujuan,penyerah,penerima,catatan,id_admin)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('ssiiissi',
            $b['no_mutasi'], $b['tanggal'],
            $b['sumber'],    $b['tujuan'],
            $b['penyerah'],  $b['penerima'],
            $b['catatan'],   $b['id_admin']
        );
        $stmt->execute();
        echo json_encode(['id' => $db->insert_id, 'message' => 'Distribusi berhasil dibuat']);
        $stmt->close();
    }

    if ($type === 'detail') {
        $id_mutasi = intval($b['id_mutasi']);
        $id_batch  = intval($b['id_batch']);
        $sumber    = intval($b['sumber']);
        $tujuan    = intval($b['tujuan']);
        $jumlah    = intval($b['jumlah']);

        // Cek stok sumber
        $res  = $db->query("SELECT stok FROM stok_batch WHERE id_gudang=$sumber AND id_batch=$id_batch");
        $stok = $res->fetch_assoc()['stok'] ?? 0;
        if ($stok < $jumlah) {
            http_response_code(400);
            echo json_encode(['error' => 'Stok tidak mencukupi. Stok tersedia: ' . $stok]);
            exit();
        }

        // Simpan detail
        $stmt = $db->prepare('INSERT INTO mutasi_detail (id_mutasi,id_batch,jumlah) VALUES (?,?,?)');
        $stmt->bind_param('iii', $id_mutasi, $id_batch, $jumlah);
        $stmt->execute();
        $stmt->close();

        // Kurangi stok sumber
        $db->query("UPDATE stok_batch SET stok = stok - $jumlah WHERE id_gudang=$sumber AND id_batch=$id_batch");

        // Tambah stok tujuan (upsert)
        $db->query("INSERT INTO stok_batch (id_gudang,id_batch,stok) VALUES ($tujuan,$id_batch,$jumlah)
                    ON DUPLICATE KEY UPDATE stok = stok + $jumlah");

        echo json_encode(['message' => 'Detail berhasil ditambahkan, stok diperbarui']);
    }
}

$db->close();