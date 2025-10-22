<?php
ini_set('session.save_path', realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/tmp'));
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['warga', 'user'])) {
    header("Location: login.php");
    exit();
}

include "db.php";
$username = $_SESSION['username'] ?? 'Warga';

// Ambil semua laporan
$result = $conn->query("SELECT * FROM laporan ORDER BY tanggal_lapor DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Laporan Warga</title>
    <link rel="stylesheet" href="laporan.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <style>
        .table-container {
            max-width: 95%;
            margin: 60px auto;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            color: #000;
        }

        h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
        }

        .status-menunggu { color: #b8860b; font-weight: bold; }
        .status-diproses { color: #007bff; font-weight: bold; }
        .status-selesai { color: #28a745; font-weight: bold; }

        .foto-laporan {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #007bff;
        }

        .map-popup {
            width: 100%;
            height: 200px;
        }

        .lihat-peta-btn {
            padding: 6px 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .lihat-peta-btn:hover {
            background: #0056b3;
        }

        #bgVideo {
            position: fixed;
            top: 0;
            left: 0;
            min-width: 100%;
            min-height: 100%;
            object-fit: cover;
            z-index: -1;
            filter: brightness(50%);
        }
    </style>
</head>

<body>

<!-- 🔹 Background Video -->
<video autoplay muted loop id="bgVideo">
    <source src="background_jalan.mp4" type="video/mp4">
</video>

<div class="top-bar">
    <div class="top-left">
        🚧 <span class="app-title">Sistem Laporan Jalan</span>
    </div>
    <div class="top-right">
        <span class="user-name">👋 Halo, <?= htmlspecialchars($username) ?>!</span>
        <a href="laporan.php" class="logout-btn" style="background:#28a745;">+ Buat Laporan</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="table-container">
    <h2>📋 Daftar Laporan Warga</h2>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nama Pelapor</th>
                <th>Lokasi</th>
                <th>Keterangan</th>
                <th>Koordinat</th>
                <th>Foto</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Peta</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while ($row = $result->fetch_assoc()):
                $statusClass = match($row['status']) {
                    'Menunggu' => 'status-menunggu',
                    'Diproses' => 'status-diproses',
                    'Selesai' => 'status-selesai',
                    default => ''
                };
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_pelapor']) ?></td>
                <td><?= htmlspecialchars($row['lokasi']) ?></td>
                <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                <td><?= $row['latitude'] . ', ' . $row['longitude'] ?></td>
                <td>
                    <?php if (!empty($row['foto'])): ?>
                        <img src="uploads/<?= htmlspecialchars($row['foto']) ?>" class="foto-laporan" alt="Foto Jalan">
                    <?php else: ?>
                        Tidak ada
                    <?php endif; ?>
                </td>
                <td class="<?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></td>
                <td><?= $row['tanggal_lapor'] ?></td>
                <td>
                    <button class="lihat-peta-btn" onclick="lihatPeta(<?= $row['latitude'] ?>, <?= $row['longitude'] ?>, '<?= htmlspecialchars(addslashes($row['lokasi'])) ?>')">Lihat Peta</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal Peta -->
<div id="modalPeta" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding:15px; border-radius:10px; width:90%; max-width:600px; position:relative;">
        <button onclick="tutupPeta()" style="position:absolute; top:10px; right:15px; border:none; background:#dc3545; color:white; padding:6px 10px; border-radius:5px;">✖</button>
        <div id="mapModal" class="map-popup"></div>
    </div>
</div>

<script>
let mapModal;

function lihatPeta(lat, lon, lokasi) {
    document.getElementById("modalPeta").style.display = "flex";
    if (mapModal) {
        mapModal.remove();
    }
    mapModal = L.map('mapModal').setView([lat, lon], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(mapModal);
    L.marker([lat, lon]).addTo(mapModal).bindPopup(lokasi).openPopup();
}

function tutupPeta() {
    document.getElementById("modalPeta").style.display = "none";
    if (mapModal) {
        mapModal.remove();
    }
}
</script>

</body>
</html>
