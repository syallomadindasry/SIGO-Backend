<?php
require_once __DIR__ . '/_bootstrap.php';


$db  = getDB();
$res = $db->query('SELECT * FROM gudang ORDER BY id_gudang');
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
echo json_encode($rows);
$db->close();