<?php
// =====================================================
// 3) API LIST (TABLE UTAMA)
// File: SIGOO/SIGO/backend/api/stok_batch.php
// =====================================================
require_once __DIR__ . '/_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

function has_column(mysqli $db, string $table, string $column): bool {
  $t = $db->real_escape_string($table);
  $c = $db->real_escape_string($column);
  $sql = "SELECT 1
          FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '$t'
            AND COLUMN_NAME = '$c'
          LIMIT 1";
  $res = $db->query($sql);
  return $res && $res->num_rows > 0;
}

$db = getDB();

$id_gudang = intval($_GET['id_gudang'] ?? 0);
$where = $id_gudang ? "WHERE s.id_gudang = $id_gudang" : "";

$hasKode = has_column($db, 'data_obat', 'kode_obat');
$hasHarga = has_column($db, 'data_obat', 'harga');
$hasMin = has_column($db, 'data_obat', 'min_stok');
$hasLow = has_column($db, 'data_obat', 'low_stok');

$kodeExpr  = $hasKode  ? "IFNULL(o.kode_obat, CONCAT('OBT-', LPAD(o.id_obat,3,'0')))" : "CONCAT('OBT-', LPAD(o.id_obat,3,'0'))";
$hargaExpr = $hasHarga ? "IFNULL(o.harga, 0)" : "0";
$minExpr   = $hasMin   ? "IFNULL(o.min_stok, 100)" : "100";
$lowExpr   = $hasLow   ? "IFNULL(o.low_stok, 200)" : "200";

$sql = "SELECT
          s.id_gudang, s.id_batch, s.stok,
          b.batch, b.exp_date,
          o.id_obat,
          o.nama AS nama_obat,
          o.satuan,
          o.jenis,
          $kodeExpr  AS kode_obat,
          $hargaExpr AS harga,
          $minExpr   AS min_stok,
          $lowExpr   AS low_stok,
          g.nama_gudang
        FROM stok_batch s
        JOIN data_batch b ON b.id_batch = s.id_batch
        JOIN data_obat  o ON o.id_obat  = b.id_obat
        JOIN gudang     g ON g.id_gudang = s.id_gudang
        $where
        ORDER BY o.nama, b.exp_date";

$res = $db->query($sql);
if (!$res) {
  http_response_code(500);
  echo json_encode(['error' => 'SQL error', 'message' => $db->error]);
  $db->close();
  exit;
}

$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;

echo json_encode($rows);
$db->close();