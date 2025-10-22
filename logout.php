<?php
session_start();
session_unset();    // hapus semua session
session_destroy();  // hancurkan session
header("Location: login.php"); // kembali ke login
exit();
?>
