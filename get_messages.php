<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$current_id = $_SESSION['user_id'];

// Ambil semua pesan terbaru
$sql = "SELECT messages.id, messages.message, messages.created_at, messages.image, messages.user_id, users.nama 
        FROM messages 
        JOIN users ON messages.user_id = users.id 
        ORDER BY messages.created_at DESC 
        LIMIT 50";
$result = $conn->query($sql);

$messages = [];
while ($row = $result->fetch_assoc()) {
  $messages[] = $row;
}
$messages = array_reverse($messages);

// Fungsi untuk mengambil siapa yang sudah baca
function getReaders($conn, $message_id) {
  $stmt = $conn->prepare("SELECT users.nama FROM message_reads 
                          JOIN users ON message_reads.user_id = users.id 
                          WHERE message_reads.message_id = ?");
  $stmt->bind_param("i", $message_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $names = [];
  while ($row = $res->fetch_assoc()) {
    $names[] = htmlspecialchars($row['nama']);
  }
  return $names;
}

foreach ($messages as $msg):
  $isSent = ($msg['user_id'] == $current_id);
  $messageClass = $isSent ? 'sent' : 'received';
  $messageId = $msg['id'];

  // Ambil siapa yang membaca
  $readers = getReaders($conn, $messageId);
  $readerCount = count($readers);
?>
  <div class="message-wrapper <?= $messageClass ?>" data-id="<?= $messageId ?>">
    <div class="message <?= $messageClass ?>">
      <div class="font-medium text-gray-800 mb-1">
        <?= htmlspecialchars($msg['nama']) ?>
      </div>

      <?php if (!empty($msg['message'])): ?>
        <div class="text-gray-900 whitespace-pre-line">
          <?= nl2br(htmlspecialchars($msg['message'])) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($msg['image'])): ?>
        <div class="mt-2">
          <img src="uploads/<?= htmlspecialchars($msg['image']) ?>" alt="Image" class="rounded-md max-w-xs shadow">
        </div>
      <?php endif; ?>

      <?php if ($isSent): ?>
        <div class="flex items-center justify-end mt-1 gap-1 text-xs text-gray-500">
          <?= date('H:i', strtotime($msg['created_at'])) ?>
          <?php if ($readerCount > 0): ?>
            <i class="bi bi-check2-all text-blue-500 status-icon" title="Dibaca oleh: <?= implode(', ', $readers) ?>"></i>
          <?php else: ?>
            <i class="bi bi-check status-icon" title="Belum dibaca"></i>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="timestamp">
          <?= date('H:i', strtotime($msg['created_at'])) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
