<?php
// File: backend/api/_bootstrap.php

// Load config
$config = require __DIR__ . '/../config.php';

// ===========================
// CORS (untuk Vite :5173)
// ===========================
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = $config['cors_origin'] ?? 'http://localhost:5173';

// Kalau origin cocok, balikin origin tsb (bukan "*", karena kamu pakai Authorization header)
if ($origin === $allowed) {
  header("Access-Control-Allow-Origin: $allowed");
  header("Vary: Origin");
} else {
  // fallback aman: tetap izinkan Vite default kamu
  header("Access-Control-Allow-Origin: $allowed");
  header("Vary: Origin");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// Default JSON
header('Content-Type: application/json; charset=utf-8');

// ===========================
// Helper response
// ===========================
function respond(int $code, $payload) {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

// ===========================
// Bearer token helper
// ===========================
function bearer_token(): ?string {
  $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!$h) return null;
  if (preg_match('/Bearer\s+(.*)$/i', $h, $m)) return trim($m[1]);
  return null;
}

// ===========================
// JSON input helper
// ===========================
function json_input(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}