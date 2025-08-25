<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}

$id = intval($_GET['id']);
$user = $conn->query("SELECT nama FROM users WHERE id=$id")->fetch_assoc();
$result = $conn->query("
  SELECT pm.*, u1.nama AS pengirim, u2.nama AS penerima
  FROM private_messages pm
  JOIN users u1 ON pm.sender_id = u1.id
  JOIN users u2 ON pm.receiver_id = u2.id
  WHERE pm.sender_id=$id OR pm.receiver_id=$id
  ORDER BY pm.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pantau Chat Pribadi - <?= htmlspecialchars($user['nama']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
  <h2 class="text-xl font-bold mb-4">Chat Pribadi terkait <?= htmlspecialchars($user['nama']) ?></h2>
  <a href="admin_panel.php" class="text-blue-500 hover:underline mb-4 inline-block">← Kembali</a>
  <div class="bg-white shadow rounded-lg p-4 overflow-auto max-h-[70vh]">
    <?php while($msg = $result->fetch_assoc()): ?>
      <div class="border-b py-2">
        <p><strong><?= htmlspecialchars($msg['pengirim']) ?></strong> → <?= htmlspecialchars($msg['penerima']) ?></p>
        <p class="text-gray-800"><?= htmlspecialchars($msg['message']) ?></p>
        <?php if ($msg['image']): ?>
          <img src="uploads/<?= htmlspecialchars($msg['image']) ?>" class="w-32 h-auto rounded mt-1">
        <?php endif; ?>
        <small class="text-gray-500"><?= $msg['created_at'] ?> | Status: <?= $msg['status'] ?></small>
      </div>
    <?php endwhile; ?>
  </div>
</body>
</html>
