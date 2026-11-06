# 🏥 Sistem Informasi Pelayanan Klinik Anak Happy ("Penuh Harapan")

Aplikasi Web Sistem Informasi Manajemen Klinik Anak Terpadu khusus pasien usia **< 15 tahun** dengan antarmuka **Minimalist Child-Friendly (Soft UI Evolution & Bento Box)** berbasis **UI/UX Pro Max**.

---

## 🌟 Fitur Utama

- **Pendaftaran Pasien Otomatis (< 15 Tahun)**: Validasi otomatis tanggal lahir dan perhitungan usia real-time.
- **4 Layanan Medis Terintegrasi**:
  1. Poli Pemeriksaan Umum & Anak Sakit (`U-xxx`)
  2. Poli Perawatan Gigi Anak dengan **Odontogram Gigi Susu Interaktif** (`G-xxx`)
  3. Poli Vaksinasi & Imunisasi dengan **Kalkulator Jadwal Vaksin IDAI** (`V-xxx`)
  4. Poli Tindakan Medis Ringan & Inhalasi Nebulizer (`T-xxx`)
- **Layar Monitor Antrean Live (`antrian.php`)**:
  - Tampilan monitor TV ruang tunggu & mobile ramah orang tua.
  - Pemisahan status: *Sedang Diperiksa Dokter*, *Pemeriksaan Tanda Vital Asisten*, dan *Daftar Menunggu*.
  - **Audio Ding-Dong Chime** (Web Audio API) & **Panggilan Suara Otomatis Bahasa Indonesia** (Web Speech API).
- **Portal Staf Terpadu (`admin.php`)**:
  - **1-Click Quick Role Switcher**: Resepsionis (Kristoforus), Asisten Medis (Juan Darren & Siti), Dokter (dr. Aldrich), dan Admin.
  - Rekam Medis Elektronik (EMR) terintegrasi dengan tanda vital dari asisten.
  - Kasir otomatis terhubung resep dokter & tindakan medis + cetak kuitansi.
  - Laporan omzet dan rekap kunjungan harian.

---

## 🚀 Cara Menjalankan di Lokal (XAMPP)

1. Pastikan folder berada di: `C:\xampp\htdocs\klinik`
2. Buka **XAMPP Control Panel**, lalu aktifkan **Apache** dan **MySQL**.
3. Buka browser dan akses:
   - **Halaman Pasien/Publik**: [http://localhost/klinik/](http://localhost/klinik/)
   - **Layar Antrean TV**: [http://localhost/klinik/antrian.php](http://localhost/klinik/antrian.php)
   - **Portal Staf & Dokter**: [http://localhost/klinik/admin.php](http://localhost/klinik/admin.php)

---

## ☁️ Deployment Gratis (GitHub + Render / Supabase)

### 1. Database Gratis (Supabase)
1. Buka [https://supabase.com](https://supabase.com) dan buat akun gratis.
2. Buat Project Baru (pilih Region terdekat: **Singapore**).
3. Buka menu **SQL Editor** di sidebar Supabase.
4. Salin seluruh isi file [`supabase_schema.sql`](supabase_schema.sql), tempel di SQL Editor, lalu klik **Run**.
5. Buka **Project Settings** -> **Database**, lalu salin **Connection String** (`URI`).

### 2. Upload ke GitHub
```bash
git init
git add .
git commit -m "feat: initial commit klinik anak happy"
git branch -M main
git remote add origin https://github.com/USERNAME_ANDA/klinik-anak.git
git push -u origin main
```

### 3. Deploy Gratis di Render.com
1. Buka [https://render.com](https://render.com) (login dengan akun GitHub).
2. Klik **New +** -> **Web Service**.
3. Pilih repository `klinik-anak` Anda.
4. Render akan otomatis mendeteksi `Dockerfile`.
5. Di bagian **Environment Variables**, tambahkan:
   - `DATABASE_URL`: `[Connection String URI dari Supabase]`
6. Klik **Deploy Web Service**. Website Anda langsung online dan mendapatkan link publik gratis!
