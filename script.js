const form = document.getElementById('chat-form');
const messageInput = document.getElementById('message');
const imageInput = document.getElementById('image');
const chatBox = document.getElementById('chat-box');
const scrollBtn = document.getElementById('scroll-down-btn');

// Kirim pesan
form.addEventListener('submit', async function (e) {
  e.preventDefault();

  const message = messageInput.value.trim();
  const hasImage = imageInput.files.length > 0;

  if (message === '' && !hasImage) return;

  const formData = new FormData();
  formData.append('message', message);
  if (hasImage) {
    formData.append('image', imageInput.files[0]);
  }

  try {
    await fetch('send_message.php', {
      method: 'POST',
      body: formData
    });

    messageInput.value = '';
    imageInput.value = '';
    loadMessages();
  } catch (err) {
    console.error('Gagal mengirim pesan:', err);
  }
});

// Fungsi untuk mengambil pesan
let isLoading = false;
async function loadMessages() {
  if (isLoading) return;
  isLoading = true;

  try {
    const res = await fetch('get_messages.php');
    const html = await res.text();
    chatBox.innerHTML = html;
    markMessagesAsRead();
  } catch (err) {
    console.error('Gagal mengambil pesan:', err);
  } finally {
    isLoading = false;
  }
}

// Tandai pesan sebagai sudah dibaca
async function markMessagesAsRead() {
  const unreadMessages = Array.from(
    document.querySelectorAll('.message-wrapper.received[data-id]')
  )
    .map(msg => msg.dataset.id)
    .filter(Boolean);

  if (unreadMessages.length === 0) return;

  try {
    await fetch('mark_read.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ message_ids: unreadMessages })
    });
  } catch (err) {
    console.error('Gagal menandai pesan terbaca:', err);
  }
}

// Muat pesan secara berkala
setInterval(loadMessages, 1000);
window.onload = loadMessages;

// Tombol scroll manual
if (scrollBtn && chatBox) {
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
}

// Emoji Picker Setup
import('https://cdn.jsdelivr.net/npm/@joeattardi/emoji-button@4.6.2/dist/index.min.js').then(({ default: EmojiButton }) => {
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
});
