<?php
session_start();
require 'db.php';

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $kode = trim($_POST['kode']);
  if (!empty($kode)) {
    $hash = password_hash($kode, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET secret_code = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $_SESSION['user_id']);
    $stmt->execute();

    unset($_SESSION['otp_verified']);
    header("Location: index.php");
    exit;
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Buat Kode Rahasia</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen">
  <form method="post" class="bg-white p-6 rounded shadow max-w-md w-full space-y-4 text-center">
    <h2 class="text-xl font-bold">Buat Kode Rahasia</h2>
    <input type="password" name="kode" placeholder="Masukkan kode rahasia" class="w-full border p-2 rounded text-center" required>
    <button class="bg-indigo-600 text-white py-2 px-4 rounded w-full">Simpan & Masuk</button>
  </form>
</body>
</html>
