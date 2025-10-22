<?php
session_start();
include "db.php";

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Admin';

// 🔹 Proses update status laporan
if (isset($_POST['update_status'])) {
    $id_laporan = $_POST['id_laporan'];
    $status_baru = $_POST['status'];
    $conn->query("UPDATE laporan SET status='$status_baru' WHERE id_laporan='$id_laporan'");
    header("Location: admin_laporan.php?updated=1");
    exit();
}

// 🔹 Ambil semua laporan dari database
$result = $conn->query("SELECT * FROM laporan ORDER BY id_laporan DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Laporan Jalan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin_laporan.css">
</head>
<body>

<!-- 🔹 HEADER / TOP BAR -->
<div class="top-bar bg-primary text-white p-3 d-flex justify-content-between align-items-center">
    <h4 class="m-0">🚧 Dashboard Admin - Laporan Jalan Rusak</h4>
    <div>
        <span>👋 Halo, <?= htmlspecialchars($username) ?></span>
        <a href="logout.php" class="btn btn-light btn-sm ms-3">Logout</a>
    </div>
</div>

<div class="container mt-4">

    <!-- 🔸 Notifikasi -->
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success mb-3">✅ Status laporan berhasil diperbarui!</div>
    <?php endif; ?>
    <?php if (isset($_GET['verifikasi_sukses'])): ?>
        <div class="alert alert-info mb-3">🔍 Verifikasi foto berhasil dijalankan! Hasil sudah diperbarui di tabel.</div>
    <?php endif; ?>

    <!-- 🔹 Tombol Verifikasi Foto -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>📋 Semua Laporan Warga</h5>
        <form method="POST" action="verifikasi_foto_exec.php">
            <button type="submit" class="btn btn-outline-info">🔍 Verifikasi Semua Foto</button>
        </form>
    </div>

    <!-- 🔹 CARD LIST LAPORAN -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Daftar Laporan Warga</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nama Pelapor</th>
                        <th>Lokasi</th>
                        <th>Deskripsi</th>
                        <th>Foto</th>
                        <th>Koordinat</th>
                        <th>Status</th>
                        <th>Verifikasi Foto</th> 
                        <th>Detail Verifikasi</th>
                        <th>Tindakan</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id_laporan'] ?></td>
                                <td><?= htmlspecialchars($row['nama_pelapor']) ?></td>
                                <td><?= htmlspecialchars($row['lokasi']) ?></td>
                                <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                                <td>
                                    <?php if (!empty($row['foto'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($row['foto']) ?>" alt="Foto Laporan" style="width:100px;border-radius:8px;">
                                    <?php else: ?>
                                        <span class="text-muted">Tidak ada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $row['latitude'] ?>, <?= $row['longitude'] ?><br>
                                    <a href="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>" 
                                       target="_blank" class="btn btn-outline-success btn-sm mt-1">🌍 Lihat</a>
                                </td>
                                <td>
                                    <span class="badge 
                                        <?= strtolower($row['status']) == 'menunggu' ? 'bg-warning text-dark' : 
                                            (strtolower($row['status']) == 'diproses' ? 'bg-info text-dark' : 'bg-success') ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>

                                <!-- ✅ Kolom Status Verifikasi -->
                                <td>
                                    <?php if (empty($row['status_verifikasi'])): ?>
                                        <span class="badge bg-secondary">Belum Diverifikasi</span>
                                    <?php elseif (strtolower($row['status_verifikasi']) == 'asli'): ?>
                                        <span class="badge bg-success">Asli ✅</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Diduga Palsu ❌</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td style="font-size: 12px; color: #000000ff;">
                                <?= !empty($row['verifikasi_detail']) ? nl2br(htmlspecialchars($row['verifikasi_detail'])) : '<span class="text-muted">Belum diverifikasi</span>' ?>
                                </td>

                                <td>
                                    <form method="POST" class="d-flex flex-column">
                                        <input type="hidden" name="id_laporan" value="<?= $row['id_laporan'] ?>">
                                        <select name="status" class="form-select form-select-sm mb-2">
                                            <option value="Menunggu" <?= $row['status'] == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                            <option value="Diproses" <?= $row['status'] == 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                                            <option value="Selesai" <?= $row['status'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center text-muted">Belum ada laporan dari warga.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
