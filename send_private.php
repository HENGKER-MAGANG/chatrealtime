<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id']) && isset($_POST['receiver_id'])) {
  $sender = $_SESSION['user_id'];
  $receiver = (int) $_POST['receiver_id'];
  $message = htmlspecialchars(trim($_POST['message'] ?? ''));
  $image_name = null;
  $voice_name = null;
  $status = 'sent';

  $targetDir = "uploads/";
  if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

  // Upload gambar jika ada
  if (!empty($_FILES['image']['name'])) {
    $image_name = time() . '_' . basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . $image_name;
    move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile);
  }

  // Upload voice jika ada
  if (!empty($_FILES['voice']['name'])) {
    $voice_name = time() . '_' . basename($_FILES["voice"]["name"]);
    $targetVoice = $targetDir . $voice_name;
    move_uploaded_file($_FILES["voice"]["tmp_name"], $targetVoice);
  }

  // Simpan ke DB jika ada salah satu: pesan, gambar, atau voice
  if (!empty($message) || $image_name || $voice_name) {
    $stmt = $conn->prepare("INSERT INTO private_messages (sender_id, receiver_id, message, image, voice, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $sender, $receiver, $message, $image_name, $voice_name, $status);
    $stmt->execute();
  }
}
?>
