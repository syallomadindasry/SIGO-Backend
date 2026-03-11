<?php
require_once __DIR__ . '/_bootstrap.php';

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $config = require __DIR__ . '/../config.php';
  $d = $config['db'];

  $dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $d['host'],
    (int)$d['port'],
    $d['name'],
    $d['charset']
  );

  $pdo = new PDO($dsn, $d['user'], $d['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  return $pdo;
}

function db_one(string $sql, array $params = []): ?array {
  $st = db()->prepare($sql);
  $st->execute($params);
  $row = $st->fetch();
  return $row ?: null;
}