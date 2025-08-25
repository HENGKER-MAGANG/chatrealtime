<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }

$me = $_SESSION['user_id'];
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$ids = $data['ids'] ?? [];

if (!$ids || !is_array($ids)) { echo json_encode(['ok'=>false]); exit; }

// Hapus hanya pesan milik sendiri
$in  = implode(',', array_fill(0, count($ids), '?'));
$params = $ids;
$types  = str_repeat('i', count($ids));

$sql = "DELETE FROM private_messages WHERE sender_id = ? AND id IN ($in)";
$stmt = $conn->prepare("DELETE FROM private_messages WHERE sender_id = ? AND id IN ($in)");
$types = 'i'.$types;
array_unshift($params, $me);

// bind dynamic
$stmt->bind_param($types, ...$params);
$stmt->execute();

echo json_encode(['ok'=>true, 'deleted'=>$stmt->affected_rows]);
