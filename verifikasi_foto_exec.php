<?php
include "db.php";

// Ambil semua laporan yang punya foto
$result = $conn->query("SELECT id_laporan, foto FROM laporan WHERE foto IS NOT NULL AND foto != ''");

function getExifDetail($file_path) {
    if (!file_exists($file_path)) {
        return [false, "File tidak ditemukan."];
    }

    $exif = @exif_read_data($file_path, 0, true);
    if (!$exif) {
        return [false, "Tidak ada metadata EXIF (kemungkinan hasil unduhan atau editan)."];
    }

    $make = $exif['IFD0']['Make'] ?? '';
    $model = $exif['IFD0']['Model'] ?? '';
    $software = $exif['IFD0']['Software'] ?? '';
    $datetime = $exif['EXIF']['DateTimeOriginal'] ?? '';

    $detail = "Kamera: $make $model | Software: $software | Tanggal: $datetime";

    // Deteksi indikasi AI / editing
    $software_l = strtolower($software);
    if (strpos($software_l, 'photoshop') !== false || strpos($software_l, 'canva') !== false ||
        strpos($software_l, 'generator') !== false || strpos($software_l, 'ai') !== false) {
        return [false, "$detail → Dideteksi software editing/AI."];
    }

    if (empty($make) && empty($model)) {
        return [false, "$detail → Tidak ditemukan metadata kamera (kemungkinan unduhan/screenshot)."];
    }

    return [true, "$detail → Foto diambil menggunakan kamera asli."];
}

while ($row = $result->fetch_assoc()) {
    $id = $row['id_laporan'];
    $file_path = "uploads/" . $row['foto'];

    list($is_real, $detail) = getExifDetail($file_path);

    // Simpan hasil ke database
    $status = $is_real ? "Asli" : "Diduga Palsu";
    $stmt = $conn->prepare("UPDATE laporan SET status_verifikasi=?, verifikasi_detail=? WHERE id_laporan=?");
    $stmt->bind_param("ssi", $status, $detail, $id);
    $stmt->execute();
}

header("Location: admin_laporan.php?verifikasi_sukses=1");
exit();
?>
