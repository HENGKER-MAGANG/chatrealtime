<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
  $message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';
  $image_name = null;

  // Jika user mengunggah gambar
  if (!empty($_FILES['image']['name'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
      mkdir($targetDir, 0755, true);
    }

    $fileTmp = $_FILES['image']['tmp_name'];
    $fileName = time() . '_' . basename($_FILES['image']['name']);
    $targetFilePath = $targetDir . $fileName;

    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($fileType, $allowedTypes)) {
      if (move_uploaded_file($fileTmp, $targetFilePath)) {
        $image_name = $fileName;
      }
    }
  }

  // Jangan insert kalau message kosong dan tidak ada gambar
  if (!empty($message) || $image_name !== null) {
    $stmt = $conn->prepare("INSERT INTO messages (user_id, message, image) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $message, $image_name);
    $stmt->execute();
  }
}
?>
