import mysql.connector
import os

# Koneksi ke database (sama seperti db.php)
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="jalan_monitoring"
)
cursor = conn.cursor(dictionary=True)

# Folder tempat foto laporan disimpan
UPLOADS_FOLDER = "uploads"

# Ambil semua laporan yang belum diverifikasi
cursor.execute("SELECT * FROM laporan WHERE status_verifikasi IS NULL")
laporan_list = cursor.fetchall()

print(f"🔍 Mengecek {len(laporan_list)} laporan yang belum diverifikasi...\n")

for laporan in laporan_list:
    foto_path = os.path.join(UPLOADS_FOLDER, laporan['foto'])

    # Pastikan file foto ada
    if not os.path.exists(foto_path):
        print(f"❌ Foto tidak ditemukan untuk laporan ID {laporan['id_laporan']}")
        continue

    # ============================
    # Di sinilah kamu bisa menambahkan
    # model AI / Python script untuk cek keaslian foto
    # ============================

    # Untuk contoh awal: kita anggap semua foto dianggap asli
    hasil_verifikasi = "Asli"

    # Simpan hasil ke database
    cursor.execute(
        "UPDATE laporan SET status_verifikasi = %s WHERE id_laporan = %s",
        (hasil_verifikasi, laporan['id_laporan'])
    )
    conn.commit()

    print(f"✅ Laporan ID {laporan['id_laporan']} diverifikasi sebagai: {hasil_verifikasi}")

cursor.close()
conn.close()

print("\n✨ Selesai memverifikasi semua laporan!")
