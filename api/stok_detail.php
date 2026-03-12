<?php
// =====================================================
// 4) API DETAIL (MODAL)
// File: SIGOO/SIGO/backend/api/stok_detail.php
// sesuai tabel kamu:
// pembelian_detail: id_pembelian, id_batch, jumlah, harga
// mutasi_detail   : id_mutasi, id_batch, jumlah
// pembelian       : id, no_nota, tanggal, id_gudang, ...
// mutasi          : id, no_mutasi, tanggal, sumber, tujuan, ...
// =====================================================
require_once __DIR__ . '/_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

function json_out($data, $code = 200) {
  http_response_code($code);
  echo json_encode($data);
  exit;
}

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

$id_obat   = intval($_GET['id_obat'] ?? 0);
$id_gudang = intval($_GET['id_gudang'] ?? 0);
if (!$id_obat) json_out(['error' => 'id_obat required'], 400);

$hasKode = has_column($db, 'data_obat', 'kode_obat');
$hasHarga = has_column($db, 'data_obat', 'harga');
$hasMin = has_column($db, 'data_obat', 'min_stok');
$hasLow = has_column($db, 'data_obat', 'low_stok');

$kodeExpr  = $hasKode  ? "IFNULL(o.kode_obat, CONCAT('OBT-', LPAD(o.id_obat,3,'0')))" : "CONCAT('OBT-', LPAD(o.id_obat,3,'0'))";
$hargaExpr = $hasHarga ? "IFNULL(o.harga, 0)" : "0";
$minExpr   = $hasMin   ? "IFNULL(o.min_stok, 100)" : "100";
$lowExpr   = $hasLow   ? "IFNULL(o.low_stok, 200)" : "200";

$whereGudangStok = $id_gudang ? " AND s.id_gudang = $id_gudang " : "";
$wherePembelianGudang = $id_gudang ? " AND p.id_gudang = $id_gudang " : "";
$whereMutasiGudang = $id_gudang ? " AND m.sumber = $id_gudang " : "";

// 1) info
$sqlInfo = "SELECT
              o.id_obat,
              $kodeExpr  AS kode_obat,
              o.nama     AS nama_obat,
              o.jenis    AS kategori,
              o.satuan   AS satuan,
              $hargaExpr AS harga,
              $minExpr   AS min_stok,
              $lowExpr   AS low_stok
            FROM data_obat o
            WHERE o.id_obat = $id_obat
            LIMIT 1";
$resInfo = $db->query($sqlInfo);
if (!$resInfo) json_out(['error' => 'SQL error', 'message' => $db->error], 500);
$info = $resInfo->fetch_assoc();
if (!$info) json_out(['error' => 'Obat not found'], 404);

// 2) kondisi stok
$sqlStok = "SELECT SUM(s.stok) AS total_stok
            FROM stok_batch s
            JOIN data_batch b ON b.id_batch = s.id_batch
            WHERE b.id_obat = $id_obat
            $whereGudangStok";
$resStok = $db->query($sqlStok);
if (!$resStok) json_out(['error' => 'SQL error', 'message' => $db->error], 500);
$stokRow = $resStok->fetch_assoc();

$kondisi = [
  'total_stok' => intval($stokRow['total_stok'] ?? 0),
  'min_stok'   => intval($info['min_stok'] ?? 100),
  'low_stok'   => intval($info['low_stok'] ?? 200),
];

// 3) batches + gudang (biar bisa join data seperti kiri kamu, tapi UI kanan ga tampil gudang)
$batches = [];
$sqlBatch = "SELECT
              b.id_batch,
              b.batch,
              b.exp_date,
              SUM(s.stok) AS sisa
            FROM stok_batch s
            JOIN data_batch b ON b.id_batch = s.id_batch
            WHERE b.id_obat = $id_obat
            $whereGudangStok
            GROUP BY b.id_batch, b.batch, b.exp_date
            ORDER BY b.exp_date ASC";
$resBatch = $db->query($sqlBatch);
if (!$resBatch) json_out(['error' => 'SQL error', 'message' => $db->error], 500);
while ($r = $resBatch->fetch_assoc()) $batches[] = $r;

// 4) purchases
$purchases = [];
$sqlBuy = "SELECT
            p.no_nota AS faktur,
            p.tanggal AS tgl,
            SUM(pd.jumlah) AS jml
          FROM pembelian_detail pd
          JOIN pembelian p ON p.id = pd.id_pembelian
          JOIN data_batch b ON b.id_batch = pd.id_batch
          WHERE b.id_obat = $id_obat
          $wherePembelianGudang
          GROUP BY p.no_nota, p.tanggal
          ORDER BY p.tanggal DESC
          LIMIT 20";
$resBuy = $db->query($sqlBuy);
if ($resBuy) while ($r = $resBuy->fetch_assoc()) $purchases[] = $r;

// 5) distributions
$distributions = [];
$sqlDist = "SELECT
              gt.nama_gudang AS tujuan,
              m.tanggal AS tgl,
              SUM(md.jumlah) AS jml
            FROM mutasi_detail md
            JOIN mutasi m ON m.id = md.id_mutasi
            JOIN data_batch b ON b.id_batch = md.id_batch
            LEFT JOIN gudang gt ON gt.id_gudang = m.tujuan
            WHERE b.id_obat = $id_obat
            $whereMutasiGudang
            GROUP BY tujuan, m.tanggal
            ORDER BY m.tanggal DESC
            LIMIT 20";
$resDist = $db->query($sqlDist);
if ($resDist) while ($r = $resDist->fetch_assoc()) $distributions[] = $r;

$db->close();

json_out([
  'info' => $info,
  'kondisi' => $kondisi,
  'batches' => $batches,
  'purchases' => $purchases,
  'distributions' => $distributions,
]);