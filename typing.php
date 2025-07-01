<?php
session_start();
require 'db.php';

$my_id = $_SESSION['user_id'] ?? null;
if (!$my_id) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $from = (int) $_POST['from'];
  $to = (int) $_POST['to'];
  $typing = (int) $_POST['typing'];

  if ($from === $my_id) {
    $stmt = $conn->prepare("REPLACE INTO typing_status (user_id, receiver_id, typing) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $from, $to, $typing);
    $stmt->execute();
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $from = (int) $_GET['from'];
  $to = (int) $_GET['to'];

  if ($to === $my_id) {
    $stmt = $conn->prepare("SELECT typing FROM typing_status WHERE user_id = ? AND receiver_id = ?");
    $stmt->bind_param("ii", $from, $to);
    $stmt->execute();
    $stmt->bind_result($typing);
    $stmt->fetch();
    echo json_encode(['typing' => $typing ?? 0]);
    exit;
  }
  echo json_encode(['typing' => 0]);
}
