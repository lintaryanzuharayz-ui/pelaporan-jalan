<?php
session_start();
include "db.php";

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Warga';

// Ambil data laporan milik user yang login
$sql = "SELECT * FROM laporan WHERE id_user = '$user_id' ORDER BY id_laporan DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Laporan Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="riwayat_laporan.css"> <!-- 🔗 Hubungkan ke file CSS eksternal -->
</head>
<body>

<div class="container">
    <div class="header-bar">
        <h2>📋 Riwayat Laporan Anda</h2>
        <div class="action-btns">
            <a href="warga_laporan.php" class="btn btn-success">➕ Buat Laporan Baru</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="laporan-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="laporan-card shadow">
                    <?php if (!empty($row['foto'])): ?>
                        <img src="uploads/<?= htmlspecialchars($row['foto']) ?>" class="laporan-foto" alt="Foto Laporan">
                    <?php else: ?>
                        <div class="no-image">Tidak ada foto</div>
                    <?php endif; ?>

                    <div class="laporan-content">
                        <h5><?= htmlspecialchars($row['lokasi']) ?></h5>
                        <p><strong>Pelapor:</strong> <?= htmlspecialchars($row['nama_pelapor']) ?></p>
                        <p class="deskripsi"><?= htmlspecialchars($row['deskripsi']) ?></p>

                        <?php
                            $status = strtolower($row['status']);
                            $class = "status-menunggu";
                            if ($status == "diproses") $class = "status-diproses";
                            elseif ($status == "selesai") $class = "status-selesai";
                        ?>
                        <p><strong>Status:</strong> <span class="status <?= $class ?>"><?= ucfirst($status) ?></span></p>

                        <p><strong>Koordinat:</strong> <?= $row['latitude'] ?>, <?= $row['longitude'] ?></p>
                        <a href="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>" target="_blank" class="btn-map">🌍 Lihat di Peta</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center mt-4">Anda belum mengirim laporan apapun.</div>
    <?php endif; ?>
</div>

</body>
</html>
