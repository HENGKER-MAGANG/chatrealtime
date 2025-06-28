<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$current_id = $_SESSION['user_id'];
$current_name = $_SESSION['username'];
$current_role = $_SESSION['role'];

$users = $conn->query("SELECT id, username, is_online FROM users WHERE id != $current_id");
$total_pesan = $conn->query("SELECT COUNT(*) as total FROM messages")->fetch_assoc()['total'];
$total_user = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Chat Realtime</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/@joeattardi/emoji-button@4.6.2/dist/index.min.js"></script>
  <style>
    #chat-box {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      position: relative;
    }
    .message-wrapper {
      display: flex;
      flex-direction: column;
      max-width: 75%;
      padding: 0.6rem 1rem;
      border-radius: 1.25rem;
      line-height: 1.6;
      font-size: 0.95rem;
      box-shadow: 0 1px 2px rgba(0,0,0,0.05);
      position: relative;
      background-color: #f0f0f0;
    }
    .sent {
      align-self: flex-end;
      background-color: #cce4f6;
    }
    .received {
      align-self: flex-start;
      background-color: #f6f6f6;
    }
    .timestamp {
      font-size: 0.65rem;
      color: #6b7280;
      margin-top: 0.2rem;
      text-align: right;
      padding-right: 6px;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 0.25rem;
    }
    .status-icon {
      font-size: 0.85rem;
      margin-left: 2px;
    }
  </style>
</head>
<body class="bg-[#e6e6e6] text-gray-800">

<div class="min-h-screen flex flex-col items-center py-6 px-4">
  <div class="w-full max-w-4xl bg-white rounded-xl shadow-xl p-6 space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-3">
      <h2 class="text-xl font-bold text-blue-600 flex items-center gap-2">
        <i class="bi bi-chat-dots"></i> Hai, <?= htmlspecialchars($current_name) ?>!
      </h2>
      <div class="flex gap-2">
        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-full text-sm">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
        <?php if ($current_role === 'admin'): ?>
          <a href="admin_panel.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-full text-sm">
            <i class="bi bi-gear-fill"></i> Admin
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="text-sm text-gray-600 flex gap-4">
      <span><i class="bi bi-chat-left-text"></i> Pesan: <?= $total_pesan ?></span>
      <span><i class="bi bi-people"></i> Pengguna: <?= $total_user ?></span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="md:col-span-1 bg-gray-100 p-4 rounded-lg shadow-inner">
        <h4 class="font-semibold mb-2 text-gray-700 flex items-center gap-1">
          <i class="bi bi-person-lines-fill"></i> Chat Pribadi:
        </h4>
        <ul class="space-y-1">
          <?php while ($u = $users->fetch_assoc()): ?>
            <li class="flex items-center gap-1">
              <i class="bi bi-circle-fill text-xs <?= $u['is_online'] ? 'text-green-500' : 'text-gray-400' ?>"></i>
              <a href="chat_private.php?user=<?= $u['id'] ?>" class="text-blue-600 hover:underline">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($u['username']) ?>
              </a>
            </li>
          <?php endwhile; ?>
        </ul>
      </div>

      <div class="md:col-span-2 flex flex-col gap-4">
        <div class="relative">
          <div id="chat-box" class="bg-white h-80 overflow-y-auto rounded-lg p-4 border border-gray-300 text-sm">
            <!-- Pesan tampil disini -->
          </div>
          <button id="scroll-down-btn" class="hidden absolute right-4 bottom-4 bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full shadow-md">
            <i class="bi bi-arrow-down-short text-xl"></i>
          </button>
        </div>

        <form id="chat-form" enctype="multipart/form-data" class="space-y-2">
          <div class="flex items-center gap-2">
            <button type="button" id="emoji-btn" class="text-blue-500 text-xl">
              <i class="bi bi-emoji-smile"></i>
            </button>
            <input type="file" name="image" id="image" accept="image/*" class="hidden">
            <label for="image" class="cursor-pointer text-blue-500 text-xl">
              <i class="bi bi-image-fill"></i>
            </label>
            <input type="text" name="message" id="message" class="flex-1 border rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Tulis pesan..." required>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-full flex items-center gap-1">
              <i class="bi bi-send"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('#emoji-btn');
    const input = document.querySelector('#message');
    const picker = new EmojiButton({
      position: 'top-end',
      theme: 'auto'
    });

    button.addEventListener('click', () => {
      picker.togglePicker(button);
    });

    picker.on('emoji', emoji => {
      input.value += emoji;
    });

    const chatBox = document.getElementById('chat-box');
    const scrollBtn = document.getElementById('scroll-down-btn');

    scrollBtn.addEventListener('click', () => {
      chatBox.scrollTop = chatBox.scrollHeight;
      scrollBtn.classList.add('hidden');
    });

    chatBox.addEventListener('scroll', () => {
      const nearBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 50;
      if (nearBottom) {
        scrollBtn.classList.add('hidden');
      } else {
        scrollBtn.classList.remove('hidden');
      }
    });
  });
</script>
<script src="script.js"></script>
</body>
</html>
