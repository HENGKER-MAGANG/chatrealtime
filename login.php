<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = trim(filter_input(INPUT_POST, 'nama', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
  $kode = trim(filter_input(INPUT_POST, 'kode', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

  if (!empty($nama) && !empty($kode)) {
    // Cek apakah user sudah ada
    $stmt = $conn->prepare("SELECT id, nama, kode_rahasia, role FROM users WHERE nama = ?");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();
      if (password_verify($kode, $user['kode_rahasia'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role']; // ⬅️ Tambahan penting
        header("Location: index.php");
        exit;
      } else {
        $error = "Kode rahasia salah.";
      }
    } else {
      // Belum terdaftar → Buat akun baru
      $kode_hash = password_hash($kode, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO users (nama, kode_rahasia, role) VALUES (?, ?, 'user')");
      $stmt->bind_param("ss", $nama, $kode_hash);
      if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['nama'] = $nama;
        $_SESSION['role'] = 'user'; // ⬅️ Tambahan agar role user baru tersimpan di session
        header("Location: index.php");
        exit;
      } else {
        $error = "Gagal membuat akun.";
      }
    }
    $stmt->close();
  } else {
    $error = "Nama dan kode rahasia wajib diisi.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login - Kode Rahasia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-indigo-200 min-h-screen flex items-center justify-center px-4">

  <div class="bg-white p-6 sm:p-8 rounded-xl shadow-xl w-full max-w-md text-center">

    <div class="mb-6">
      <img src="image.png" alt="Logo Chat" class="w-24 mx-auto">
    </div>

    <h2 class="text-3xl font-extrabold text-indigo-700 mb-2">Masuk</h2>
    <p class="text-gray-600 mb-6">Masukkan nama dan kode rahasia Anda. Jika belum terdaftar, akun akan dibuat otomatis.</p>

    <?php if ($error): ?>
      <div class="bg-red-100 text-red-700 p-2 rounded mb-4 text-sm border border-red-200">
        <i class="bi bi-x-circle-fill mr-2"></i><?= $error ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4 text-left">
      <div class="relative">
        <i class="bi bi-person-fill absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" name="nama" placeholder="Nama Lengkap"
               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-indigo-400" required>
      </div>

      <div class="relative">
        <i class="bi bi-shield-lock-fill absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="password" name="kode" placeholder="Kode Rahasia"
               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-indigo-400" required>
      </div>

      <button type="submit"
              class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700 transition font-semibold">
        <i class="bi bi-door-open-fill mr-1"></i> Masuk
      </button>
    </form>

    <p class="text-gray-500 text-xs mt-6">
      &copy; <?= date('Y') ?> <strong>Chat Realtime</strong> | Ikhsan Pratama
    </p>
  </div>

</body>
</html>
