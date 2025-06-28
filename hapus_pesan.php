<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}

if (isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
}

header("Location: admin_panel.php");
exit;
