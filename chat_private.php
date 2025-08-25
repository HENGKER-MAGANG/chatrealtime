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

// Handle POST (kirim pesan baru / edit pesan)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $receiver_id = isset($_POST['receiver_id']) ? (int) $_POST['receiver_id'] : 0;
  $message     = isset($_POST['message']) ? trim($_POST['message']) : '';
  $reply_to    = isset($_POST['reply_to']) ? (int) $_POST['reply_to'] : null;
  $edit_id     = isset($_POST['edit_id']) ? (int) $_POST['edit_id'] : null;
  $image = null;
  $voice = null;

  if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    @mkdir('uploads', 0777, true);
    $imageName = uniqid('img_') . '_' . basename($_FILES['image']['name']);
    $targetPath = 'uploads/' . $imageName;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
      $image = $imageName;
    }
  }

  if (!empty($_FILES['voice']['tmp_name']) && is_uploaded_file($_FILES['voice']['tmp_name'])) {
    @mkdir('uploads', 0777, true);
    $voiceName = uniqid('vn_') . '.webm';
    $targetPath = 'uploads/' . $voiceName;
    if (move_uploaded_file($_FILES['voice']['tmp_name'], $targetPath)) {
      $voice = $voiceName;
    }
  }

  // MODE EDIT
  if ($edit_id && ($message || $image || $voice)) {
    // hanya boleh edit pesan milik sendiri
    $stmt = $conn->prepare("UPDATE private_messages SET message = ?, edited_at = NOW() WHERE id = ? AND sender_id = ?");
    $stmt->bind_param("sii", $message, $edit_id, $sender_id);
    $stmt->execute();
  }
  // MODE KIRIM BARU
  else if ($receiver_id > 0 && ($message || $image || $voice)) {
    if ($reply_to) {
      $stmt = $conn->prepare("INSERT INTO private_messages (sender_id, receiver_id, message, image, voice, status, reply_to) VALUES (?, ?, ?, ?, ?, 'sent', ?)");
      $stmt->bind_param("iisssi", $sender_id, $receiver_id, $message, $image, $voice, $reply_to);
    } else {
      $stmt = $conn->prepare("INSERT INTO private_messages (sender_id, receiver_id, message, image, voice, status) VALUES (?, ?, ?, ?, ?, 'sent')");
      $stmt->bind_param("iisss", $sender_id, $receiver_id, $message, $image, $voice);
    }
    $stmt->execute();
  }

  // Ajax form submit dari JS → cukup stop di sini
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    exit('OK');
  }
}
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
    html, body { height: 100%; }
    /* Anti seleksi & drag (opsional, tetap dibiarkan) */
    * { -webkit-user-select: none; -moz-user-select: none; user-select: none; }
    img { pointer-events: none; }
    body { -webkit-touch-callout: none; }
    /* Chat bubble */
    .message-wrapper {
      max-width: 75%; padding: 0.5rem 1rem; border-radius: 1.25rem;
      line-height: 1.6; font-size: 0.95rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
      word-break: break-word; position: relative;
    }
    .sent { background-color: rgb(198, 241, 248); align-self: flex-end; border-bottom-right-radius: 0; }
    .received { background-color: #ffffff; align-self: flex-start; border-bottom-left-radius: 0; }
    .timestamp { font-size: 0.65rem; color: #6b7280; margin-top: 0.2rem; text-align: right; padding-right: 6px; display: flex; justify-content: flex-end; gap: .25rem; }
    .reply-snippet { font-size: .8rem; border-left: 3px solid #60a5fa; padding-left: .5rem; margin-bottom: .35rem; color: #374151; background: rgba(96,165,250,.08); border-radius: .35rem; }
    .edited-mark { font-size: .65rem; color: #6b7280; margin-left: .35rem; }
    img { max-width: 100%; height: auto; }
    #scroll-down-btn { position: absolute; right: 10px; bottom: 10px; z-index: 10; }

    /* Typing */
    #typing-indicator { display: flex; gap: 6px; align-items: center; margin-top: 0.5rem; }
    #typing-indicator.hidden { display: none; }
    #typing-indicator span { display: inline-block; width: 8px; height: 8px; background-color: #9ca3af; border-radius: 50%; animation: typingBlink 1.4s infinite ease-in-out; }
    #typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    #typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typingBlink { 0%,80%,100% { transform: scale(0); opacity:.2 } 40% { transform: scale(1); opacity:1 } }

    /* Selection mode */
    .select-mode .message-wrapper { cursor: pointer; }
    .message-wrapper.selected { outline: 2px solid #2563eb; background-image: linear-gradient(0deg, rgba(37,99,235,.08), rgba(37,99,235,.08)); }
    #action-bar { display: none; }
    .select-mode #action-bar { display: flex; }
    .select-mode #default-bar { display: none; }

    /* Reply preview */
    #reply-preview { display:none; }
    #reply-preview.active { display:flex; }

    /* Shield (anti minimize best-effort) */
    .secure-blur { filter: blur(10px); }
    .secure-dim { opacity: 0.08; }
    #shield { position: fixed; inset: 0; background: rgba(0,0,0,0.65); pointer-events: none; z-index: 9998; display: none; }
  </style>
</head>
<body class="bg-gray-100 text-gray-800 h-full" oncontextmenu="return false;">
<div id="shield"></div>

<div class="flex flex-col h-full max-w-full mx-auto px-2 sm:px-4 py-4">
  <!-- Top Bars -->
  <div id="default-bar" class="flex justify-between items-center mb-4">
    <h3 class="text-xl font-semibold text-blue-600 flex items-center gap-2">
      <i class="bi bi-person-circle"></i> Chat dengan <?= htmlspecialchars($receiver_name) ?>
    </h3>
    <div class="flex gap-3">
      <a href="hapus_semua.php?receiver_id=<?= $receiver_id ?>" onclick="return confirm('Hapus semua pesan?')" class="text-red-500 hover:underline text-sm"><i class="bi bi-trash"></i></a>
      <a href="index.php" class="text-sm text-blue-500 hover:underline"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
  </div>

  <div id="action-bar" class="flex justify-between items-center mb-2 bg-blue-600 text-white px-3 py-2 rounded-lg">
    <div class="text-sm flex items-center gap-2">
      <i class="bi bi-check2-square"></i>
      <span id="sel-count">0</span> dipilih
    </div>
    <div class="flex items-center gap-2">
      <button id="btn-reply" class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 text-sm"><i class="bi bi-reply"></i> Reply</button>
      <button id="btn-edit" class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 text-sm"><i class="bi bi-pencil-square"></i> Edit</button>
      <button id="btn-delete" class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 text-sm"><i class="bi bi-trash"></i> Hapus</button>
      <button id="btn-cancel" class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 text-sm"><i class="bi bi-x-lg"></i> Batal</button>
    </div>
  </div>

  <!-- Chat Box -->
  <div class="flex-1 overflow-y-auto relative bg-gray-50 rounded-lg border border-gray-300 p-4 mb-2" id="chat-box">
    <div class="flex flex-col w-full mb-2 items-start" id="typing-wrapper">
      <div id="typing-indicator" class="hidden">
        <span></span><span></span><span></span>
      </div>
    </div>
    <button id="scroll-down-btn" class="hidden bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-full text-sm absolute bottom-4 right-4">
      <i class="bi bi-arrow-down-short text-xl"></i>
    </button>
  </div>

  <!-- Reply preview -->
  <div id="reply-preview" class="items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 mb-2">
    <div class="text-xs text-blue-700 flex-1" id="reply-text"></div>
    <button type="button" id="reply-cancel" class="text-blue-600 text-xs hover:underline"><i class="bi bi-x-lg"></i> batal</button>
  </div>

  <!-- Image preview -->
<div id="image-preview" class="hidden items-start gap-2 bg-gray-50 border rounded-lg px-3 py-2 mb-2">
  <img id="preview-img" src="" class="max-h-28 rounded-lg shadow-sm" alt="preview">
  <div class="flex-1 flex flex-col">
    <input type="text" id="caption-input" placeholder="Tambahkan caption..."
           class="border rounded px-2 py-1 text-sm mb-1 focus:outline-none focus:ring-1 focus:ring-blue-400">
    <button type="button" id="cancel-image" class="text-red-500 text-xs self-end"><i class="bi bi-x-lg"></i> batal</button>
  </div>
</div>

  <!-- Form -->
  <form id="chat-form" enctype="multipart/form-data" class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
    <label for="image" class="cursor-pointer text-blue-500 text-xl"><i class="bi bi-image-fill"></i></label>
    <input type="file" id="image" name="image" accept="image/*" class="hidden">
    <button type="button" id="record-btn" class="text-blue-500 text-xl"><i class="bi bi-mic-fill"></i></button>
    <span id="recording-status" class="text-sm text-red-500 hidden">Rekam...</span>
    <input type="text" id="message" name="message" placeholder="Tulis pesan..." autocomplete="off"
           class="flex-1 border rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 min-w-[100px]">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-full"><i class="bi bi-send"></i></button>
  </form>
</div>

<script>
const senderId = <?= (int)$sender_id ?>;
const receiverId = <?= (int)$receiver_id ?>;
const currentUserName = <?= json_encode($_SESSION['nama'] ?? 'User') ?>;

const form = document.getElementById('chat-form');
const messageInput = document.getElementById('message');
const imageInput = document.getElementById('image');
const chatBox = document.getElementById('chat-box');
const scrollBtn = document.getElementById('scroll-down-btn');
const recordBtn = document.getElementById('record-btn');
const recordingStatus = document.getElementById('recording-status');
const typingIndicator = document.getElementById('typing-indicator');
const shield = document.getElementById('shield');

// selection & reply state
let typingTimeout;
let mediaRecorder;
let audioChunks = [];
let selectMode = false;
let selectedIds = new Set();
let replyTo = null; // message id
let editId = null;  // message id for edit

const actionBar = document.getElementById('action-bar');
const defaultBar = document.getElementById('default-bar');
const selCount = document.getElementById('sel-count');
const btnReply = document.getElementById('btn-reply');
const btnEdit = document.getElementById('btn-edit');
const btnDelete = document.getElementById('btn-delete');
const btnCancel = document.getElementById('btn-cancel');
const replyPreview = document.getElementById('reply-preview');
const replyText = document.getElementById('reply-text');
const replyCancel = document.getElementById('reply-cancel');

/* -------------------- Best-effort minimize shield -------------------- */
function engageShield(on) {
  if (on) { chatBox.classList.add('secure-blur','secure-dim'); shield.style.display = 'block'; }
  else { chatBox.classList.remove('secure-blur','secure-dim'); shield.style.display = 'none'; }
}
document.addEventListener('visibilitychange', () => engageShield(document.hidden));
window.addEventListener('blur', () => engageShield(true));
window.addEventListener('focus', () => engageShield(false));

/* -------------------- Typing -------------------- */
messageInput.addEventListener('input', () => {
  sendTyping(true);
  clearTimeout(typingTimeout);
  typingTimeout = setTimeout(() => sendTyping(false), 1800);
});


/* -------------------- Voice record -------------------- */
recordBtn.addEventListener('click', async () => {
  try {
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
        fetch(window.location.href, { method: "POST", body: formData, headers: { 'X-Requested-With':'fetch' } })
          .then(()=>loadMessages(true));
        recordingStatus.classList.add('hidden');
      });
      setTimeout(() => { if (mediaRecorder.state !== "inactive") mediaRecorder.stop(); }, 10000);
    } else {
      mediaRecorder.stop();
    }
  } catch (err) {
    console.warn('Mic error:', err);
  }
});

/* -------------------- Load / Render messages -------------------- */
function loadMessages(stickBottom=false) {
  fetch(`get_private.php?receiver_id=${receiverId}`)
    .then(res => res.text())
    .then(html => {
      const temp = document.createElement('div');
      temp.innerHTML = html;
      const rows = [...temp.children];
      const typingWrapper = document.getElementById('typing-wrapper');
      const nearBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 80;

      chatBox.innerHTML = '';
      chatBox.append(...rows);
      chatBox.appendChild(typingWrapper);

      attachMessageEvents();

      if (stickBottom || nearBottom) {
        chatBox.scrollTop = chatBox.scrollHeight;
      }
    }).catch(()=>{});
}

/* -------------------- Selection / Long press -------------------- */
let holdTimer = null;

function attachMessageEvents() {
  document.querySelectorAll('.message-wrapper').forEach(el => {
    // desktop: right click
    el.oncontextmenu = (e) => {
      e.preventDefault();
      startSelectMode();
      toggleSelect(el);
    };
    // mobile: long press
    el.addEventListener('touchstart', () => {
      holdTimer = setTimeout(() => {
        startSelectMode();
        toggleSelect(el);
      }, 520);
    }, {passive:true});
    el.addEventListener('touchend', () => clearTimeout(holdTimer));

    // click to toggle when in select mode
    el.addEventListener('click', (e) => {
      if (!selectMode) return;
      toggleSelect(el);
      e.preventDefault();
    });
  });
}

function startSelectMode() {
  if (selectMode) return;
  selectMode = true;
  document.body.classList.add('select-mode');
  updateSelCount();
}

function exitSelectMode() {
  selectMode = false;
  selectedIds.clear();
  document.body.classList.remove('select-mode');
  document.querySelectorAll('.message-wrapper.selected').forEach(x => x.classList.remove('selected'));
  updateSelCount();
}

function toggleSelect(el) {
  const id = el.dataset.id;
  if (!id) return;
  if (el.classList.contains('selected')) {
    el.classList.remove('selected');
    selectedIds.delete(id);
  } else {
    el.classList.add('selected');
    selectedIds.add(id);
  }
  updateSelCount();
}

function updateSelCount() {
  selCount.textContent = selectedIds.size;
}

/* -------------------- Action Bar buttons -------------------- */
btnCancel.addEventListener('click', exitSelectMode);

btnReply.addEventListener('click', () => {
  if (selectedIds.size < 1) return;
  const firstId = [...selectedIds][0];
  const el = document.querySelector(`.message-wrapper[data-id="${firstId}"]`);
  if (!el) return;
  const text = el.dataset.text || '';
  setReply(firstId, text);
  exitSelectMode();
});

btnEdit.addEventListener('click', () => {
  if (selectedIds.size !== 1) { alert('Pilih tepat 1 pesan untuk edit.'); return; }
  const id = [...selectedIds][0];
  const el = document.querySelector(`.message-wrapper[data-id="${id}"]`);
  if (!el) return;

  if (el.dataset.own !== '1') { alert('Hanya bisa edit pesan milik sendiri.'); return; }
  const text = el.dataset.text || '';
  editId = id;
  replyTo = null;
  replyPreview.classList.remove('active');
  messageInput.value = text;
  messageInput.focus();
  messageInput.setAttribute('data-edit-id', id);
  messageInput.placeholder = 'Edit pesan...';
  exitSelectMode();
});

btnDelete.addEventListener('click', () => {
  if (selectedIds.size < 1) return;
  if (!confirm(`Hapus ${selectedIds.size} pesan?`)) return;
  const ids = [...selectedIds];
  fetch('delete_private.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ ids })
  }).then(()=> {
    exitSelectMode();
    loadMessages(true);
  });
});

/* -------------------- Reply helpers -------------------- */
function setReply(id, text) {
  replyTo = id;
  replyText.textContent = text.length>120 ? text.slice(0,120)+'…' : text;
  replyPreview.classList.add('active');
}
function clearReply() {
  replyTo = null;
  replyPreview.classList.remove('active');
  replyText.textContent = '';
}
replyCancel.addEventListener('click', clearReply);

/* -------------------- Typing ping/poll -------------------- */
function sendTyping(isTyping) {
  fetch('typing.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `from=${senderId}&to=${receiverId}&typing=${isTyping?1:0}`
  }).catch(()=>{});
}
function checkTyping() {
  fetch(`typing.php?from=${receiverId}&to=${senderId}`)
    .then(res => res.json())
    .then(data => typingIndicator.classList.toggle('hidden', !data?.typing))
    .catch(()=>{});
}

/* -------------------- Scroll helpers -------------------- */
chatBox.addEventListener('scroll', () => {
  const nearBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 50;
  scrollBtn.classList.toggle('hidden', nearBottom);
});
scrollBtn.addEventListener('click', () => {
  chatBox.scrollTop = chatBox.scrollHeight;
  scrollBtn.classList.add('hidden');
});

/* -------------------- Loop -------------------- */
setInterval(() => { loadMessages(); checkTyping(); }, 1000);
window.onload = () => { loadMessages(true); checkTyping(); };
window.addEventListener('beforeunload', () => sendTyping(false));

const imagePreview = document.getElementById('image-preview');
const previewImg   = document.getElementById('preview-img');
const captionInput = document.getElementById('caption-input');
const cancelImage  = document.getElementById('cancel-image');

// Saat pilih gambar
imageInput.addEventListener('change', () => {
  if (imageInput.files && imageInput.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      previewImg.src = e.target.result;
      imagePreview.classList.remove('hidden');
    };
    reader.readAsDataURL(imageInput.files[0]);
  }
});

// Batal pilih gambar
cancelImage.addEventListener('click', () => {
  imageInput.value = '';
  imagePreview.classList.add('hidden');
  previewImg.src = '';
  captionInput.value = '';
});

// Override submit supaya caption ikut jadi message
form.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData();
  const msg = (captionInput.value.trim() || messageInput.value.trim());

  if (!msg && !imageInput.files[0]) return;

  formData.append('message', msg);
  formData.append('receiver_id', receiverId);
  if (imageInput.files[0]) formData.append('image', imageInput.files[0]);
  if (replyTo) formData.append('reply_to', replyTo);
  if (editId) formData.append('edit_id', editId);

  fetch(window.location.href, { method: 'POST', body: formData, headers: { 'X-Requested-With':'fetch' } })
    .then(() => {
      // reset states
      messageInput.value = '';
      captionInput.value = '';
      imageInput.value = '';
      imagePreview.classList.add('hidden');
      previewImg.src = '';
      sendTyping(false);
      clearReply();
      editId = null;
      messageInput.removeAttribute('data-edit-id');
      messageInput.placeholder = 'Tulis pesan...';
      loadMessages(true);
    });
});

</script>
</body>
</html>
