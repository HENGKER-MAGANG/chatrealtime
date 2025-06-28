<?php
$host = 'losssksw484s48coo4gook4k'; // hostname dari MySQL URL internal (tanpa "mysql://")
$user = 'chat';                     // Normal User
$pass = 'chat123';                 // Normal User Password
$dbname = 'chat_db';              // Initial Database
$port = 3306;                      // Port default MySQL

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
