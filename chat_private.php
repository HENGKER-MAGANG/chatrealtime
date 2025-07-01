<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  exit('Unauthorized');
}

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_GET['user']) ? (int) $_GET['user'] : 0;

// Ambil nama penerima
$receiver_name = 'Pengguna';
if ($receiver_id > 0) {
  $stmt = $conn->prepare("SELECT nama FROM users WHERE id = ?");
  $stmt->bind_param("i", $receiver_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $receiver_name = $row['nama'];
  }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $receiver_id = isset($_POST['receiver_id']) ? (int) $_POST['receiver_id'] : 0;
  $message = isset($_POST['message']) ? trim($_POST['message']) : '';
  $image = null;
  $voice = null;

  if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $imageName = uniqid('img_') . '_' . basename($_FILES['image']['name']);
    $targetPath = 'uploads/' . $imageName;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
      $image = $imageName;
    }
  }

  if (!empty($_FILES['voice']['tmp_name']) && is_uploaded_file($_FILES['voice']['tmp_name'])) {
    $voiceName = uniqid('vn_') . '.webm';
    $targetPath = 'uploads/' . $voiceName;
    if (move_uploaded_file($_FILES['voice']['tmp_name'], $targetPath)) {
      $voice = $voiceName;
    }
  }

  if ($receiver_id > 0 && ($message || $image || $voice)) {
    $stmt = $conn->prepare("INSERT INTO private_messages (sender_id, receiver_id, message, image, voice, status) VALUES (?, ?, ?, ?, ?, 'sent')");
    $stmt->bind_param("iisss", $sender_id, $receiver_id, $message, $image, $voice);
    $stmt->execute();
  }
}
?>
<!-- HTML dimulai -->
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat dengan <?= htmlspecialchars($receiver_name) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    html, body { height: 100%; }
    .message-wrapper {
      max-width: 75%; padding: 0.5rem 1rem; border-radius: 1.25rem;
      line-height: 1.6; font-size: 0.95rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
      word-break: break-word;
    }
    .sent { background-color: rgb(198, 241, 248); align-self: flex-end; border-bottom-right-radius: 0; }
    .received { background-color: #ffffff; align-self: flex-start; border-bottom-left-radius: 0; }
    .timestamp {
      font-size: 0.65rem; color: #6b7280; margin-top: 0.2rem;
      text-align: right; padding-right: 6px; display: flex;
      justify-content: flex-end; align-items: center; gap: 0.25rem;
    }
    img { max-width: 100%; height: auto; }
    #scroll-down-btn {
      position: absolute; right: 10px; bottom: 10px; z-index: 10;
    }
    #typing-indicator {
      display: flex; gap: 6px; align-items: center; margin-top: 0.5rem;
    }
    #typing-indicator.hidden { display: none; }
    #typing-indicator span {
      display: inline-block; width: 8px; height: 8px;
      background-color: #9ca3af; border-radius: 50%;
      animation: typingBlink 1.4s infinite ease-in-out;
    }
    #typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    #typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typingBlink {
      0%, 80%, 100% { transform: scale(0); opacity: 0.2; }
      40% { transform: scale(1); opacity: 1; }
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-800 h-full">
<div class="flex flex-col h-full max-w-full mx-auto px-2 sm:px-4 py-4">
  <div class="flex justify-between items-center mb-4">
    <h3 class="text-xl font-semibold text-blue-600 flex items-center gap-2">
      <i class="bi bi-person-circle"></i> Chat dengan <?= htmlspecialchars($receiver_name) ?>
    </h3>
    <div class="flex gap-3">
      <a href="hapus_semua.php?receiver_id=<?= $receiver_id ?>" onclick="return confirm('Hapus semua pesan?')" class="text-red-500 hover:underline text-sm"><i class="bi bi-trash"></i></a>
      <a href="index.php" class="text-sm text-blue-500 hover:underline"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
  </div>

  <div class="flex-1 overflow-y-auto relative bg-gray-50 rounded-lg border border-gray-300 p-4 mb-4" id="chat-box">
    <div class="flex flex-col w-full mb-2 items-start" id="typing-wrapper">
      <div id="typing-indicator" class="hidden">
        <span></span><span></span><span></span>
      </div>
    </div>
    <button id="scroll-down-btn" class="hidden bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-full text-sm absolute bottom-4 right-4">
      <i class="bi bi-arrow-down-short text-xl"></i>
    </button>
  </div>

  <form id="chat-form" enctype="multipart/form-data" class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
    <label for="image" class="cursor-pointer text-blue-500 text-xl"><i class="bi bi-image-fill"></i></label>
    <input type="file" id="image" name="image" accept="image/*" class="hidden">
    <button type="button" id="record-btn" class="text-blue-500 text-xl"><i class="bi bi-mic-fill"></i></button>
    <span id="recording-status" class="text-sm text-red-500 hidden">Rekam...</span>
    <input type="text" id="message" name="message" placeholder="Tulis pesan..." class="flex-1 border rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 min-w-[100px]">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-full"><i class="bi bi-send"></i></button>
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
const recordBtn = document.getElementById('record-btn');
const recordingStatus = document.getElementById('recording-status');
const typingIndicator = document.getElementById('typing-indicator');

let typingTimeout;
let mediaRecorder;
let audioChunks = [];

messageInput.addEventListener('input', () => {
  sendTyping(true);
  clearTimeout(typingTimeout);
  typingTimeout = setTimeout(() => sendTyping(false), 2000);
});

form.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData();
  formData.append('message', messageInput.value);
  formData.append('receiver_id', receiverId);
  if (imageInput.files[0]) formData.append('image', imageInput.files[0]);

  fetch('chat_private.php', { method: 'POST', body: formData }).then(() => {
    messageInput.value = '';
    imageInput.value = '';
    sendTyping(false);
  });
});

recordBtn.addEventListener('click', async () => {
  if (!mediaRecorder || mediaRecorder.state === "inactive") {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    mediaRecorder = new MediaRecorder(stream);
    mediaRecorder.start();
    recordingStatus.classList.remove('hidden');
    audioChunks = [];
    mediaRecorder.addEventListener("dataavailable", e => audioChunks.push(e.data));
    mediaRecorder.addEventListener("stop", () => {
      const blob = new Blob(audioChunks, { type: "audio/webm" });
      const formData = new FormData();
      formData.append("receiver_id", receiverId);
      formData.append("voice", blob, `voice_${Date.now()}.webm`);
      fetch("chat_private.php", { method: "POST", body: formData }).then(loadMessages);
      recordingStatus.classList.add('hidden');
    });
    setTimeout(() => { if (mediaRecorder.state !== "inactive") mediaRecorder.stop(); }, 10000);
  } else {
    mediaRecorder.stop();
  }
});

function loadMessages() {
  fetch(`get_private.php?receiver_id=${receiverId}`)
    .then(res => res.text())
    .then(data => {
      const temp = document.createElement('div');
      temp.innerHTML = data;
      const messages = [...temp.children];
      const typingWrapper = document.getElementById('typing-wrapper');
      chatBox.innerHTML = '';
      chatBox.append(...messages);
      chatBox.appendChild(typingWrapper);
      if (chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 100) {
        chatBox.scrollTop = chatBox.scrollHeight;
      }
    });
}

function sendTyping(isTyping) {
  fetch('typing.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `from=${senderId}&to=${receiverId}&typing=${isTyping ? 1 : 0}`
  });
}

function checkTyping() {
  fetch(`typing.php?from=${receiverId}&to=${senderId}`)
    .then(res => res.json())
    .then(data => {
      typingIndicator.classList.toggle('hidden', !data?.typing);
    });
}

chatBox.addEventListener('scroll', () => {
  const nearBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 50;
  scrollBtn.classList.toggle('hidden', nearBottom);
});

scrollBtn.addEventListener('click', () => {
  chatBox.scrollTop = chatBox.scrollHeight;
  scrollBtn.classList.add('hidden');
});

setInterval(() => {
  loadMessages();
  checkTyping();
}, 1000);

window.onload = () => {
  loadMessages();
  checkTyping();
};

window.addEventListener('beforeunload', () => {
  sendTyping(false);
});
</script>
</body>
</html>
