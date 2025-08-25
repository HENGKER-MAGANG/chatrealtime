<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}

$id = intval($_GET['id']);

// Hapus pesan user (grup + private) → foreign key ON DELETE CASCADE akan otomatis ikut
$conn->query("DELETE FROM users WHERE id = $id");

header("Location: admin_panel.php");
exit;
?>
