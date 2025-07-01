<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 text-gray-800">
  <div class="max-w-6xl mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
      <h2 class="text-2xl font-bold text-blue-600 flex items-center gap-2">
        <i class="bi bi-shield-lock-fill"></i> Admin Panel
      </h2>
      <a href="index.php" class="text-sm text-blue-500 hover:underline flex items-center gap-1"><i class="bi bi-arrow-left"></i> Kembali ke Chat</a>
    </div>

    <!-- Statistik -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6">
      <h3 class="text-lg font-semibold mb-4 text-gray-700 flex items-center gap-2"><i class="bi bi-bar-chart-fill"></i> Statistik Chat</h3>
      <?php
      $total_messages = $conn->query("SELECT COUNT(*) as total FROM messages")->fetch_assoc()['total'];
      $total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
      $banned_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE banned = 1")->fetch_assoc()['total'];
      $active_users = $total_users - $banned_users;
      ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm text-gray-700">
        <div class="bg-gray-50 p-3 rounded shadow">
          <strong><?= $total_messages ?></strong> Pesan Grup
        </div>
        <div class="bg-gray-50 p-3 rounded shadow">
          <strong><?= $active_users ?></strong> Pengguna Aktif
        </div>
        <div class="bg-gray-50 p-3 rounded shadow">
          <strong><?= $banned_users ?></strong> Pengguna Banned
        </div>
      </div>
    </div>

    <!-- Daftar Pengguna -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6 overflow-auto">
      <h3 class="text-lg font-semibold mb-4 text-gray-700 flex items-center gap-2"><i class="bi bi-people-fill"></i> Daftar Pengguna</h3>
      <div class="w-full overflow-x-auto">
        <table class="min-w-full text-sm text-left border border-gray-200">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="px-3 py-2 border">ID</th>
              <th class="px-3 py-2 border">Nama</th>
              <th class="px-3 py-2 border">No. HP</th>
              <th class="px-3 py-2 border">Kode Rahasia</th>
              <th class="px-3 py-2 border">Role</th>
              <th class="px-3 py-2 border">Status</th>
              <th class="px-3 py-2 border">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $result = $conn->query("SELECT id, nama, no_hp, secret_code, role, banned FROM users");
            while ($row = $result->fetch_assoc()):
            ?>
            <tr class="border-t">
              <td class="px-3 py-2"><?= $row['id'] ?></td>
              <td class="px-3 py-2"><?= htmlspecialchars($row['nama']) ?></td>
              <td class="px-3 py-2"><?= htmlspecialchars($row['no_hp']) ?></td>
              <td class="px-3 py-2 text-center">
                <?= empty($row['secret_code']) ? '<span class="text-red-500">Belum Ada</span>' : '<span class="text-green-600">Ada</span>' ?>
              </td>
              <td class="px-3 py-2 capitalize"><?= $row['role'] ?></td>
              <td class="px-3 py-2">
                <?php if ($row['banned']): ?>
                  <span class="text-red-500"><i class="bi bi-x-circle"></i> Banned</span>
                <?php else: ?>
                  <span class="text-green-600"><i class="bi bi-check-circle"></i> Aktif</span>
                <?php endif; ?>
              </td>
              <td class="px-3 py-2">
                <?php if ($row['role'] !== 'admin'): ?>
                  <a href="toggle_ban.php?id=<?= $row['id'] ?>"
                     class="px-3 py-1 rounded-full text-white text-xs font-medium <?= $row['banned'] ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600' ?>"
                     onclick="return confirm('Yakin ingin <?= $row['banned'] ? 'unban' : 'ban' ?> user ini?')">
                    <i class="bi <?= $row['banned'] ? 'bi-unlock' : 'bi-lock-fill' ?>"></i> <?= $row['banned'] ? 'Unban' : 'Ban' ?>
                  </a>
                <?php else: ?>
                  <span class="text-gray-400 text-xs">-</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pesan Grup -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6 overflow-auto">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
        <h3 class="text-lg font-semibold text-gray-700 flex items-center gap-2"><i class="bi bi-chat-dots-fill"></i> Pesan Grup Terakhir</h3>
        <form action="hapus_semua_pesan.php" method="post" onsubmit="return confirm('Yakin ingin menghapus SEMUA pesan grup?');">
          <button type="submit" class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-full text-sm shadow">
            <i class="bi bi-trash3-fill"></i> Hapus Semua
          </button>
        </form>
      </div>
      <div class="w-full overflow-x-auto">
        <table class="min-w-full text-sm border border-gray-200">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="px-3 py-2 border">Pengirim</th>
              <th class="px-3 py-2 border">Pesan</th>
              <th class="px-3 py-2 border">Gambar</th>
              <th class="px-3 py-2 border">Waktu</th>
              <th class="px-3 py-2 border">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $result = $conn->query("SELECT messages.id, users.nama, messages.message, messages.image, messages.created_at FROM messages JOIN users ON messages.user_id = users.id ORDER BY messages.created_at DESC LIMIT 50");
            while ($msg = $result->fetch_assoc()):
            ?>
            <tr class="border-t">
              <td class="px-3 py-2 whitespace-nowrap"><?= htmlspecialchars($msg['nama']) ?></td>
              <td class="px-3 py-2 whitespace-normal break-words max-w-xs"><?= htmlspecialchars($msg['message']) ?></td>
              <td class="px-3 py-2">
                <?php if ($msg['image']): ?>
                  <img src="uploads/<?= htmlspecialchars($msg['image']) ?>" class="w-20 h-auto rounded shadow">
                <?php endif; ?>
              </td>
              <td class="px-3 py-2 whitespace-nowrap"><?= $msg['created_at'] ?></td>
              <td class="px-3 py-2">
                <a href="hapus_pesan.php?id=<?= $msg['id'] ?>" 
                   class="text-red-600 hover:text-red-800 text-sm"
                   onclick="return confirm('Yakin hapus pesan ini?')">
                  <i class="bi bi-trash-fill"></i> Hapus
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
