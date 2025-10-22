<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "jalan_monitoring"; // ganti sesuai nama database kamu

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
