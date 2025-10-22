<?php
ini_set('session.save_path', realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/tmp'));
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE username='$username' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if ($password == $row['password']) { // sementara masih plain text
            $_SESSION['user_id'] = $row['id_user'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = strtolower($row['role']); // pastikan huruf kecil semua

            if ($_SESSION['role'] == 'admin') {
                header("Location: admin_laporan.php");
                exit();
            } elseif (in_array($_SESSION['role'], ['warga', 'user'])) {
                header("Location: warga_laporan.php");
                exit();
            } else {
                $error = "Role tidak dikenali!";
            }
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

<video autoplay muted loop id="bg-video">
    <source src="background_jalan.mp4" type="video/mp4">
</video>

<div class="login-container">
    <div class="info-note">
        <span class="info-icon">⚠️</span>
        <div>
            <strong>Untuk Warga:</strong><br>
            Username: <b>user</b><br>
            Password: <b>warga123</b>
        </div>
    </div>

    <div class="login-card">
        <div class="login-header">
            <div class="logo-emoji">🚧</div>
            <h4><strong>Login Sistem Monitoring Jalan</strong></h4>
            <h3>Wilayah Kalideres, Jakarta Barat</h3>
        </div>

        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <label>👤 Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan Username" required autofocus>

                <label>🔑 Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" class="form-control" id="password" placeholder="Masukkan Password" required>
                    <span class="toggle-password" onclick="togglePassword()">👁️</span>
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>

        <div class="footer">© 2025 Sistem Monitoring Jalan</div>
    </div>
</div>

<script>
function togglePassword() {
    const pwd = document.getElementById("password");
    pwd.type = (pwd.type === "password") ? "text" : "password";
}
</script>
</body>
</html>
