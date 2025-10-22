import os
import cv2
import numpy as np
import pymysql
from PIL import Image, ImageChops, ImageEnhance
import piexif

# ==========================
# 🔧 KONEKSI DATABASE
# ==========================
db = pymysql.connect(
    host="localhost",
    user="root",
    password="",
    database="jalan_monitoring"
)
cursor = db.cursor()

# ==========================
# 🔍 AMBIL FOTO YANG BELUM DIVERIFIKASI
# ==========================
cursor.execute("SELECT id_laporan, foto, status_verifikasi FROM laporan WHERE foto != ''")
laporan_list = cursor.fetchall()


# ==========================
# 🔬 FUNGSI: CEK EXIF
# ==========================
def check_exif(file_path):
    try:
        exif_data = piexif.load(file_path)
        software = exif_data["0th"].get(piexif.ImageIFD.Software, b'').decode('utf-8', errors='ignore').lower()
        if "photoshop" in software or "canva" in software or "snapseed" in software:
            return False  # diduga editan
        else:
            return True
    except Exception:
        # jika tidak ada exif, bisa jadi screenshot atau hasil edit
        return False


# ==========================
# 🔬 FUNGSI: ERROR LEVEL ANALYSIS (ELA)
# ==========================
def error_level_analysis(file_path, threshold=30):
    try:
        original = Image.open(file_path).convert('RGB')
        # Simpan ulang dengan kualitas 90%
        temp_path = "temp_ela.jpg"
        original.save(temp_path, 'JPEG', quality=90)

        # Bandingkan perbedaan
        resaved = Image.open(temp_path)
        diff = ImageChops.difference(original, resaved)

        # Tingkatkan kontras perbedaan
        enhancer = ImageEnhance.Brightness(diff)
        diff = enhancer.enhance(10)

        # Hitung rata-rata perbedaan pixel
        np_diff = np.array(diff)
        mean_diff = np.mean(np_diff)

        os.remove(temp_path)

        return mean_diff < threshold  # True = asli, False = editan
    except Exception as e:
        print("Error ELA:", e)
        return False


# ==========================
# 🔁 PROSES VERIFIKASI FOTO
# ==========================
for laporan in laporan_list:
    id_laporan = laporan[0]
    foto = laporan[1]
    status_verifikasi = laporan[2]

    foto_path = os.path.join("uploads", foto)
    if not os.path.exists(foto_path):
        continue

    # Jika sudah diverifikasi, lanjut ke berikutnya
    if status_verifikasi and status_verifikasi.strip() != "":
        continue

    # Lakukan dua analisis
    exif_ok = check_exif(foto_path)
    ela_ok = error_level_analysis(foto_path)

    # Gabungkan hasil analisis
    if exif_ok and ela_ok:
        hasil = "Asli ✅"
    else:
        hasil = "Diduga Palsu ❌"

    cursor.execute("UPDATE laporan SET status_verifikasi=%s WHERE id_laporan=%s", (hasil, id_laporan))
    print(f"[{id_laporan}] {foto} → {hasil}")

# ==========================
# ✅ SELESAI
# ==========================
db.commit()
cursor.close()
db.close()
print("✅ Verifikasi foto selesai.")
