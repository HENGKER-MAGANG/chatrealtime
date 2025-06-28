<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user'])) {
  header("Location: login.php");
  exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = (int) $_GET['user'];

// Ambil nama user
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$stmt->bind_result($receiver_name);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat dengan <?= htmlspecialchars($receiver_name) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
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
    #scroll-down-btn {
      position: absolute;
      right: 10px;
      bottom: 10px;
      z-index: 10;
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center py-6 px-4 text-gray-800">

<div class="w-full max-w-2xl bg-white rounded-xl shadow-xl p-6 space-y-6">
  <div class="flex justify-between items-center">
    <h3 class="text-lg font-semibold text-blue-600">
      <i class="bi bi-person-circle"></i> Chat dengan <?= htmlspecialchars($receiver_name) ?>
    </h3>
    <a href="index.php" class="text-sm text-blue-500 hover:underline"><i class="bi bi-arrow-left"></i> Kembali</a>
  </div>

  <div class="relative">
    <div id="chat-box" class="bg-gray-50 h-80 overflow-y-auto rounded-lg p-4 border border-gray-300 text-sm">
      <!-- Pesan tampil di sini -->
      <button id="scroll-down-btn" class="hidden bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-full text-sm">
        <i class="bi bi-arrow-down-short"></i>
      </button>
    </div>
  </div>

  <form id="chat-form" enctype="multipart/form-data" class="space-y-2">
    <div class="flex items-center gap-2">
      <input type="text" id="message" placeholder="Tulis pesan..." class="flex-1 border rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
      <input type="file" id="image" name="image" accept="image/*" class="hidden">
      <label for="image" class="cursor-pointer text-blue-500 text-xl">
        <i class="bi bi-image-fill"></i>
      </label>
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-full">
        <i class="bi bi-send"></i>
      </button>
    </div>
  </form>
</div>

<script>
  const senderId = <?= $sender_id ?>;
  const receiverId = <?= $receiver_id ?>;

  const form = document.getElementById('chat-form');
  const messageInput = document.getElementById('message');
  const imageInput = document.getElementById('image');
  const chatBox = document.getElementById('chat-box');
  const scrollBtn = document.getElementById('scroll-down-btn');

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('message', messageInput.value);
    formData.append('receiver_id', receiverId);
    if (imageInput.files[0]) {
      formData.append('image', imageInput.files[0]);
    }

    fetch('send_private.php', {
      method: 'POST',
      body: formData
    }).then(() => {
      messageInput.value = '';
      imageInput.value = '';
    });
  });

  function loadMessages() {
    fetch('get_private.php?receiver_id=' + receiverId)
      .then(res => res.text())
      .then(data => {
        chatBox.innerHTML = data;
        if (chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 100) {
          chatBox.scrollTop = chatBox.scrollHeight;
        }
      });
  }

  chatBox.addEventListener('scroll', () => {
    const nearBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 50;
    if (nearBottom) {
      scrollBtn.classList.add('hidden');
    } else {
      scrollBtn.classList.remove('hidden');
    }
  });

  scrollBtn.addEventListener('click', () => {
    chatBox.scrollTop = chatBox.scrollHeight;
    scrollBtn.classList.add('hidden');
  });

  setInterval(loadMessages, 1000);
  window.onload = loadMessages;
</script>
</body>
</html>
