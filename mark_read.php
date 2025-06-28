<?php
session_start();
require 'db.php';

// Cegah akses tanpa login
if (!isset($_SESSION['user_id'])) {
  http_response_code(401); // Unauthorized
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];

// Ambil dan validasi data JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data) || !isset($data['message_ids']) || !is_array($data['message_ids'])) {
  http_response_code(400); // Bad Request
  echo json_encode(['error' => 'Data tidak valid']);
  exit;
}

$inserted = 0;
$stmt = $conn->prepare("INSERT IGNORE INTO message_reads (message_id, user_id) VALUES (?, ?)");

if (!$stmt) {
  http_response_code(500); // Internal Server Error
  echo json_encode(['error' => 'Query preparation failed']);
  exit;
}

foreach ($data['message_ids'] as $msgId) {
  $msgId = (int)$msgId;
  if ($msgId > 0) {
    $stmt->bind_param("ii", $msgId, $userId);
    if ($stmt->execute()) {
      $inserted++;
    }
  }
}

echo json_encode([
  'status' => 'success',
  'marked' => $inserted
]);
