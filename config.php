<?php
// File: backend/config.php

// =====================
// CORS (WAJIB) - supaya fetch dari Vite (5173) tidak "Failed to fetch"
// =====================
$allowed_origins = [
  'http://localhost:5173',
  'http://127.0.0.1:5173',
  'http://localhost',
  'http://127.0.0.1',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin && in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
  header("Vary: Origin");
} else {
  // fallback aman untuk local dev
  header("Access-Control-Allow-Origin: http://localhost:5173");
  header("Vary: Origin");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Handle preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// =====================
// DB helper (dipakai stok.php, gudang.php, dst.)
// =====================
if (!function_exists('getDB')) {
  function getDB(): mysqli {
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $name = 'sigo_db';
    $port = 3306;

    $db = new mysqli($host, $user, $pass, $name, $port);
    if ($db->connect_error) {
      http_response_code(500);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['error' => 'DB connect failed', 'detail' => $db->connect_error], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $db->set_charset('utf8mb4');
    return $db;
  }
}

// =====================
// Config array
// =====================
return [
  'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'sigo_db',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
  ],
  'jwt_secret' => 'CHANGE_ME_SUPER_SECRET_123',
  'cors_origin' => 'http://localhost:5173',
];