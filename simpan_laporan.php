<?php
session_start();
include "db.php"; // koneksi ke database

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Periksa apakah form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $conn->real_escape_string($_POST['nama']);
    $lokasi = $conn->real_escape_string($_POST['lokasi']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
    $tanggal = date("Y-m-d H:i:s");
    $status = "Menunggu"; // status default laporan baru

    // Simpan ke database
    $sql = "INSERT INTO laporan (nama_pelapor, lokasi, deskripsi, tanggal_lapor, status)
            VALUES ('$nama', '$lokasi', '$deskripsi', '$tanggal', '$status')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
                alert('Laporan berhasil dikirim!');
                window.location.href = 'form_laporan.php';
              </script>";
    } else {
        echo "<script>
                alert('Gagal menyimpan laporan: " . addslashes($conn->error) . "');
                window.history.back();
              </script>";
    }
}
?>
