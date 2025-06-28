<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id']) && isset($_POST['receiver_id'])) {
  $sender = $_SESSION['user_id'];
  $receiver = (int) $_POST['receiver_id'];
  $message = htmlspecialchars(trim($_POST['message']));
  $image_name = null;

  if (!empty($_FILES['image']['name'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir);
    $image_name = time() . '_' . basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . $image_name;
    move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile);
  }

  if (!empty($message) || $image_name) {
    $stmt = $conn->prepare("INSERT INTO private_messages (sender_id, receiver_id, message, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $sender, $receiver, $message, $image_name);
    $stmt->execute();
  }
}
?>
