<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);

  if (!empty($username)) {
    $stmt = $conn->prepare("SELECT id, role, banned FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();

      if ($user['banned']) {
        $error = "❌ Akun Anda telah diblokir oleh admin.";
      } else {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
      }

    } else {
      $default_role = 'user';
      $stmt = $conn->prepare("INSERT INTO users (username, role) VALUES (?, ?)");
      $stmt->bind_param("ss", $username, $default_role);
      $stmt->execute();

      $_SESSION['user_id'] = $stmt->insert_id;
      $_SESSION['username'] = $username;
      $_SESSION['role'] = $default_role;
      header("Location: index.php");
      exit;
    }
  } else {
    $error = "Nama pengguna tidak boleh kosong.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Chat</title>

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Animasi fade-in -->
  <style>
    .fade-in {
      animation: fadeIn 1s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-100 to-indigo-200 min-h-screen flex items-center justify-center px-4">

  <div class="bg-white p-6 sm:p-8 rounded-xl shadow-xl w-full max-w-md text-center fade-in">
    
    <!-- Icon/Ilustrasi -->
    <div class="mb-5">
      <img src="image.png" alt="Chat Icon" class="w-24 mx-auto">
    </div>

    <h2 class="text-3xl font-extrabold text-indigo-700 mb-2">Selamat Datang 👋</h2>
    <p class="text-gray-600 mb-6">Masuk untuk mulai chatting dengan teman-temanmu!</p>

    <?php if ($error): ?>
      <div class="bg-red-100 text-red-700 p-2 rounded mb-4 text-sm border border-red-200">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <input type="text" name="username" placeholder="Nama kamu"
             class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-indigo-400" required>

      <button type="submit"
              class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700 transition">
        <i class="bi bi-box-arrow-in-right mr-1"></i> Masuk
      </button>
    </form>

    <p class="text-gray-500 text-xs mt-6">
      &copy; <?= date('Y') ?> <strong>Chat Realtime</strong> | Ikhsan Pratama
    </p>
  </div>

</body>
</html>
