<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['receiver_id'])) {
  exit('Unauthorized');
}

$sender = $_SESSION['user_id'];
$receiver = (int) $_GET['receiver_id'];

$stmt = $conn->prepare("
  SELECT p.*, u.username FROM private_messages p
  JOIN users u ON p.sender_id = u.id
  WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
  ORDER BY created_at ASC
");
$stmt->bind_param("iiii", $sender, $receiver, $receiver, $sender);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $isSender = $row['sender_id'] == $sender;
  $messageClass = $isSender ? 'sent' : 'received';
  $checkIcon = '';

  if ($isSender) {
    if ($row['status'] === 'read') {
      $checkIcon = '<i class="bi bi-check2-all text-blue-500 text-sm"></i>';
    } else {
      $checkIcon = '<i class="bi bi-check text-gray-400 text-sm"></i>';
    }
  }

  echo "<div class='message-wrapper $messageClass'>";
  
  // Gambar jika ada
  if (!empty($row['image'])) {
    echo "<img src='uploads/" . htmlspecialchars($row['image']) . "' class='max-w-xs rounded mb-2'>";
  }

  // Teks pesan
  if (!empty($row['message'])) {
    echo "<div>" . nl2br(htmlspecialchars($row['message'])) . "</div>";
  }

  // Timestamp + status
  echo "<div class='timestamp'>" . htmlspecialchars($row['created_at']) . " $checkIcon</div>";
  echo "</div>";
}

// Tandai sebagai read
$update = $conn->prepare("
  UPDATE private_messages 
  SET status = 'read' 
  WHERE receiver_id = ? AND sender_id = ? AND status = 'sent'
");
$update->bind_param("ii", $sender, $receiver);
$update->execute();
?>
