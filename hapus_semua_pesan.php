<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}

// Hapus file gambar jika ada
$result = $conn->query("SELECT image FROM messages WHERE image IS NOT NULL");
while ($row = $result->fetch_assoc()) {
  $image = $row['image'];
  if (file_exists("uploads/$image")) {
    unlink("uploads/$image");
  }
}

// Hapus semua pesan dari database
$conn->query("DELETE FROM messages");

header("Location: admin_panel.php");
exit;
