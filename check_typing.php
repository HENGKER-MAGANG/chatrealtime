<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
  http_response_code(400);
  echo '0';
  exit;
}

$me = $_SESSION['user_id'];
$you = (int)$_GET['user_id'];

// Ambil status mengetik
$stmt = $conn->prepare("
  SELECT typing 
  FROM typing_status 
  WHERE user_id = ? AND receiver_id = ? AND updated_at > NOW() - INTERVAL 5 SECOND
");
$stmt->bind_param("ii", $you, $me);
$stmt->execute();
$stmt->bind_result($typing);
$stmt->fetch();
$stmt->close();

echo isset($typing) ? $typing : '0';
