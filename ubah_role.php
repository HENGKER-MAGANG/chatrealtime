<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}

$id = intval($_GET['id']);
$newRole = ($_GET['role'] === 'admin') ? 'admin' : 'user';

$conn->query("UPDATE users SET role='$newRole' WHERE id=$id");
header("Location: admin_panel.php");
exit;
?>
