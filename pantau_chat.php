<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}

$id = intval($_GET['id']);
$user = $conn->query("SELECT nama FROM users WHERE id=$id")->fetch_assoc();
$result = $conn->query("SELECT * FROM messages WHERE user_id=$id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pantau Chat Grup - <?= htmlspecialchars($user['nama']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
  <h2 class="text-xl font-bold mb-4">Chat Grup milik <?= htmlspecialchars($user['nama']) ?></h2>
  <a href="admin_panel.php" class="text-blue-500 hover:underline mb-4 inline-block">← Kembali</a>
  <div class="bg-white shadow rounded-lg p-4 overflow-auto max-h-[70vh]">
    <?php while($msg = $result->fetch_assoc()): ?>
      <div class="border-b py-2">
        <p class="text-gray-800"><?= htmlspecialchars($msg['message']) ?></p>
        <?php if ($msg['image']): ?>
          <img src="uploads/<?= htmlspecialchars($msg['image']) ?>" class="w-32 h-auto rounded mt-1">
        <?php endif; ?>
        <small class="text-gray-500"><?= $msg['created_at'] ?></small>
      </div>
    <?php endwhile; ?>
  </div>
</body>
</html>
