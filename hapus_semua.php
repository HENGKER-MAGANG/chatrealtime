<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['receiver_id'])) {
  header("Location: login.php");
  exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = (int) $_GET['receiver_id'];

// Hapus semua pesan dua arah antara sender dan receiver
$stmt = $conn->prepare("DELETE FROM private_messages WHERE 
  (sender_id = ? AND receiver_id = ?) OR 
  (sender_id = ? AND receiver_id = ?)");
$stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);

if ($stmt->execute()) {
  $_SESSION['success'] = "Semua pesan berhasil dihapus.";
} else {
  $_SESSION['error'] = "Gagal menghapus pesan.";
}

$stmt->close();
header("Location: chat_private.php?user=" . $receiver_id);
exit;
