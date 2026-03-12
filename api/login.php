<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

$config = require __DIR__ . '/../config.php';
$in = json_input();

function pick_column(string $table, array $candidates): ?string {
  $sql = "SHOW COLUMNS FROM `{$table}`";
  $stmt = db()->query($sql);
  $cols = [];
  foreach ($stmt->fetchAll() as $r) $cols[] = $r['Field'];
  foreach ($candidates as $c) if (in_array($c, $cols, true)) return $c;
  return null;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  respond(405, ['error' => 'Method Not Allowed. Use POST JSON: {username|nama, password}']);
}

$login = trim((string)($in['username'] ?? $in['nama'] ?? ''));
$password = (string)($in['password'] ?? '');

if ($login === '' || $password === '') {
  respond(400, ['error' => 'Nama dan password wajib diisi']);
}

$T_USER = 'user';
$T_GUDANG = 'gudang';

$C_USER_ID = pick_column($T_USER, ['id_admin', 'id_user', 'id']);
$C_USER_NAME = pick_column($T_USER, ['username', 'nama', 'name']);
$C_USER_PASS = pick_column($T_USER, ['password_hash', 'password']);
$C_USER_ROLE = pick_column($T_USER, ['role']);
$C_USER_GUDANG_ID = pick_column($T_USER, ['id_gudang', 'gudang_id']);

$C_GUDANG_ID = pick_column($T_GUDANG, ['id_gudang', 'id']);
$C_GUDANG_NAME = pick_column($T_GUDANG, ['nama_gudang', 'nama', 'name']);

if (!$C_USER_ID || !$C_USER_NAME || !$C_USER_PASS || !$C_USER_GUDANG_ID || !$C_GUDANG_ID || !$C_GUDANG_NAME) {
  respond(500, [
    'error' => 'Mapping kolom gagal. Cek struktur tabel user/gudang.',
    'debug' => compact('C_USER_ID','C_USER_NAME','C_USER_PASS','C_USER_ROLE','C_USER_GUDANG_ID','C_GUDANG_ID','C_GUDANG_NAME')
  ]);
}

$sql = "
  SELECT
    u.`{$C_USER_ID}` AS id,
    u.`{$C_USER_NAME}` AS login_name,
    u.`{$C_USER_PASS}` AS pass_stored,
    " . ($C_USER_ROLE ? "u.`{$C_USER_ROLE}` AS role," : "'user' AS role,") . "
    u.`{$C_USER_GUDANG_ID}` AS gudang_id,
    g.`{$C_GUDANG_NAME}` AS gudang_name
  FROM `{$T_USER}` u
  JOIN `{$T_GUDANG}` g ON g.`{$C_GUDANG_ID}` = u.`{$C_USER_GUDANG_ID}`
  WHERE u.`{$C_USER_NAME}` = ?
  LIMIT 1
";

$row = db_one($sql, [$login]);
if (!$row) respond(401, ['error' => 'Nama atau password salah']);

$stored = (string)$row['pass_stored'];

// Support bcrypt (kalau nanti kamu upgrade), tapi default DB kamu plaintext
$ok = password_verify($password, $stored) || hash_equals($stored, $password);
if (!$ok) respond(401, ['error' => 'Nama atau password salah']);

$gudangName = (string)$row['gudang_name'];
$type = (stripos($gudangName, 'dinkes') !== false || stripos((string)$row['role'], 'dinkes') !== false)
  ? 'DINKES'
  : 'PUSKESMAS';

$payload = [
  'sub' => (int)$row['id'],
  'username' => (string)$row['login_name'], // tetap pakai key "username" untuk frontend
  'role' => (string)$row['role'],
  'warehouse' => [
    'code' => (string)$row['gudang_id'],
    'name' => $gudangName,
    'type' => $type,
  ],
];

$token = jwt_sign($payload, $config['jwt_secret'], 8 * 60 * 60);

respond(200, [
  'token' => $token,
  'user' => [
    'id' => (int)$row['id'],
    'username' => (string)$row['login_name'],
    'displayName' => (string)$row['login_name'],
    'role' => (string)$row['role'],
    'warehouse' => $payload['warehouse'],
  ],
]);