<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}

if (isset($_GET['id'])) {
  $user_id = (int)$_GET['id'];

  // Jangan izinkan ban sesama admin
  $check = $conn->prepare("SELECT role, banned FROM users WHERE id = ?");
  $check->bind_param("i", $user_id);
  $check->execute();
  $check->bind_result($role, $banned);
  $check->fetch();
  $check->close();

  if ($role === 'admin') {
    // Tidak boleh ban admin
    header("Location: admin_panel.php?error=forbidden");
    exit;
  }

  // Toggle banned status
  $new_status = $banned ? 0 : 1;
  $stmt = $conn->prepare("UPDATE users SET banned = ? WHERE id = ?");
  $stmt->bind_param("ii", $new_status, $user_id);
  $stmt->execute();
}

header("Location: admin_panel.php");
exit;
