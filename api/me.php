<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/jwt.php';

$config = require __DIR__ . '/../config.php';
$token = bearer_token();

if (!$token) respond(401, ['message' => 'Unauthorized']);

$payload = jwt_verify($token, $config['jwt_secret']);
if (!$payload) respond(401, ['message' => 'Invalid token']);

respond(200, ['user' => $payload]);