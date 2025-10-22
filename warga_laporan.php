<?php
ini_set('session.save_path', realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/tmp'));
session_start();

// Cek login hanya untuk warga/user
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['warga', 'user'])) {
    header("Location: login.php");
    exit();
}

include "db.php";
$username = $_SESSION['username'] ?? '';
$id_user = $_SESSION['user_id'];

// Jika form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $lokasi = $_POST['lokasi'];
    $keterangan = $_POST['keterangan'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    // Upload foto
    $foto_name = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $foto_name = time() . "_" . basename($_FILES["foto"]["name"]);
        move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $foto_name);
    }

    // Simpan laporan ke database
    $sql = "INSERT INTO laporan (id_user, nama_pelapor, lokasi, deskripsi, latitude, longitude, foto, status, tanggal_lapor)
            VALUES ('$id_user', '$nama', '$lokasi', '$keterangan', '$latitude', '$longitude', '$foto_name', 'Menunggu', NOW())";
    $conn->query($sql);

    // Redirect agar bisa tampil notifikasi sukses
    header("Location: warga_laporan.php?success=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Laporan Jalan - Warga</title>
    <link rel="stylesheet" href="warga_laporan.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
    /* ===== Overlay Notifikasi ===== */
    .notif-overlay {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        animation: fadeIn 0.4s ease forwards;
    }

    /* ===== Kotak Notifikasi ===== */
    .notif-box {
        background: #28a745;
        color: #fff;
        padding: 25px 40px;
        border-radius: 12px;
        font-size: 18px;
        font-weight: bold;
        box-shadow: 0 5px 25px rgba(0,0,0,0.3);
        text-align: center;
        animation: popIn 0.4s ease, fadeOut 0.5s ease 2.5s forwards;
    }

    @keyframes popIn {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    @keyframes fadeOut {
        from { opacity: 1; transform: scale(1); }
        to { opacity: 0; transform: scale(0.9); }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    </style>
</head>

<body>

<!-- 🔹 Background Video -->
<video autoplay muted loop id="bgVideo">
    <source src="background_jalan.mp4" type="video/mp4">
    Browser Anda tidak mendukung video tag.
</video>

<!-- Header -->
<div class="top-bar">
    <div class="top-left">
        🚧 <span class="app-title">Sistem Laporan Jalan</span>
    </div>
    <div class="top-right">
        <span class="user-name">👋 Halo, <?= htmlspecialchars($username ?: 'Warga') ?>!</span>
        <a href="riwayat_laporan.php" class="riwayat-btn">Riwayat Laporan</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="laporan-container">
    <h2>📋 Silakan Isi Form Laporan Jalan</h2>
    <p class="sub-text">Wilayah: <b>Kalideres, Jakarta Barat</b></p>

    <form method="POST" enctype="multipart/form-data">
        <label>Nama Pelapor</label>
        <input type="text" name="nama" class="form-control" placeholder="Masukkan nama Anda" value="<?= htmlspecialchars($username) ?>" required>

        <label>Nama / Lokasi Jalan</label>
        <div class="input-wrapper">
            <input type="text" name="lokasi" id="lokasi" class="form-control" placeholder="Contoh: Jl. Daan Mogot" autocomplete="off" required>
            <ul id="suggestions" class="suggestions"></ul>
        </div>

        <label>Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="3" placeholder="Tulis kondisi jalan..." required></textarea>

        <label>Foto Jalan (JPG/PNG)</label>
        <input type="file" name="foto" id="fotoInput" class="form-control" accept=".jpg,.jpeg,.png" required>
        <div id="previewContainer">
            <img id="fotoPreview" src="#" alt="Preview Foto">
        </div>

        <label>Koordinat Lokasi</label>
        <div class="koordinat">
            <input type="text" id="latitude" name="latitude" placeholder="Latitude" readonly required>
            <input type="text" id="longitude" name="longitude" placeholder="Longitude" readonly required>
        </div>

        <label>Pilih Lokasi di Peta</label>
        <div id="map"></div>

        <button type="button" id="btnLokasi">📍 Gunakan Lokasi Saya</button>
        <button type="submit" class="btn-submit">Kirim Laporan</button>
    </form>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
<!-- 🔔 Notifikasi Pop-Up di Tengah -->
<div class="notif-overlay" id="notif">
    <div class="notif-box">
        ✅ Laporan berhasil dikirim!  
        <br>Terima kasih atas partisipasi Anda 🙌
    </div>
</div>

<script>
setTimeout(() => {
    const notif = document.getElementById('notif');
    if (notif) notif.style.display = 'none';
}, 3000);
</script>
<?php endif; ?>

<script>
// ==================== Peta & Form ====================
var map = L.map('map').setView([-6.140833, 106.705], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

var marker = L.marker([-6.140833, 106.705], {draggable: true}).addTo(map);
document.getElementById('latitude').value = -6.140833;
document.getElementById('longitude').value = 106.705;

async function updateAlamatDariKoordinat(lat, lon) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`);
        const data = await response.json();
        if (data && data.display_name) {
            document.getElementById('lokasi').value = data.display_name;
        }
    } catch (err) {
        console.error("Gagal mengambil alamat:", err);
    }
}

marker.on('dragend', function(e) {
    const latlng = marker.getLatLng();
    document.getElementById('latitude').value = latlng.lat.toFixed(6);
    document.getElementById('longitude').value = latlng.lng.toFixed(6);
    updateAlamatDariKoordinat(latlng.lat, latlng.lng);
});

document.getElementById("btnLokasi").addEventListener("click", function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(async function(pos) {
            const lat = pos.coords.latitude;
            const lon = pos.coords.longitude;
            map.setView([lat, lon], 17);
            marker.setLatLng([lat, lon]);
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lon.toFixed(6);
            updateAlamatDariKoordinat(lat, lon);
        }, function() {
            alert("❌ Tidak dapat mengakses lokasi. Aktifkan GPS Anda.");
        });
    } else {
        alert("Browser tidak mendukung fitur lokasi.");
    }
});

document.getElementById('fotoInput').addEventListener('change', function() {
    const file = this.files[0];
    const preview = document.getElementById('fotoPreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = "block";
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = "none";
    }
});
</script>

</body>
</html>
