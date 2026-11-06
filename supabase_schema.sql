-- =======================================================
-- KLINIK ANAK HAPPY "PENUH HARAPAN"
-- Supabase (PostgreSQL) Database Schema & Seed Data
-- =======================================================

-- 1. Table: users
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('resepsionis', 'asisten', 'dokter', 'admin')),
    spesialisasi VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. Table: patients
CREATE TABLE IF NOT EXISTS patients (
    id SERIAL PRIMARY KEY,
    kode_rm VARCHAR(25) UNIQUE NOT NULL,
    nama_anak VARCHAR(100) NOT NULL,
    nik VARCHAR(20) DEFAULT NULL,
    tanggal_lahir DATE NOT NULL,
    jenis_kelamin VARCHAR(2) NOT NULL CHECK (jenis_kelamin IN ('L', 'P')),
    nama_ortu VARCHAR(100) NOT NULL,
    no_hp VARCHAR(25) NOT NULL,
    alamat TEXT DEFAULT NULL,
    golongan_darah VARCHAR(5) DEFAULT NULL,
    riwayat_alergi TEXT DEFAULT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 3. Table: queues (Antrean Harian)
CREATE TABLE IF NOT EXISTS queues (
    id SERIAL PRIMARY KEY,
    nomor_antrean VARCHAR(15) NOT NULL,
    id_pasien INT NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    poli_tujuan VARCHAR(20) NOT NULL CHECK (poli_tujuan IN ('umum', 'gigi', 'vaksin', 'tindakan')),
    keluhan_awal TEXT DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'menunggu_asisten' CHECK (status IN ('menunggu_asisten', 'ruang_asisten', 'menunggu_dokter', 'ruang_dokter', 'kasir', 'selesai', 'batal')),
    estimasi_menit INT DEFAULT 15,
    waktu_daftar TIMESTAMPTZ DEFAULT NOW(),
    waktu_panggil_asisten TIMESTAMPTZ DEFAULT NULL,
    waktu_selesai_asisten TIMESTAMPTZ DEFAULT NULL,
    waktu_panggil_dokter TIMESTAMPTZ DEFAULT NULL,
    waktu_selesai_dokter TIMESTAMPTZ DEFAULT NULL,
    waktu_selesai_kasir TIMESTAMPTZ DEFAULT NULL
);

-- 4. Table: vital_signs (Pemeriksaan Awal Asisten)
CREATE TABLE IF NOT EXISTS vital_signs (
    id SERIAL PRIMARY KEY,
    id_antrean INT NOT NULL REFERENCES queues(id) ON DELETE CASCADE,
    id_pasien INT NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    id_asisten INT DEFAULT NULL,
    berat_badan NUMERIC(5,2) NOT NULL,
    tinggi_badan NUMERIC(5,2) NOT NULL,
    suhu_tubuh NUMERIC(4,1) NOT NULL,
    lingkar_kepala NUMERIC(4,1) DEFAULT NULL,
    status_gizi VARCHAR(50) DEFAULT 'Normal',
    catatan_asisten TEXT DEFAULT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 5. Table: medical_records (Rekam Medis Dokter)
CREATE TABLE IF NOT EXISTS medical_records (
    id SERIAL PRIMARY KEY,
    id_antrean INT NOT NULL REFERENCES queues(id) ON DELETE CASCADE,
    id_pasien INT NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    id_dokter INT DEFAULT NULL,
    anamnesis TEXT DEFAULT NULL,
    pemeriksaan_fisik TEXT DEFAULT NULL,
    diagnosis VARCHAR(255) NOT NULL,
    catatan_gigi TEXT DEFAULT NULL,
    tindakan_medis TEXT DEFAULT NULL,
    resep_obat TEXT DEFAULT NULL,
    edukasi_ortu TEXT DEFAULT NULL,
    jadwal_kontrol DATE DEFAULT NULL,
    biaya_layanan INT DEFAULT 75000,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 6. Table: vaccination_records (Pencatatan Vaksinasi)
CREATE TABLE IF NOT EXISTS vaccination_records (
    id SERIAL PRIMARY KEY,
    id_pasien INT NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    id_antrean INT DEFAULT NULL,
    id_dokter INT DEFAULT NULL,
    nama_vaksin VARCHAR(100) NOT NULL,
    dosis_ke INT DEFAULT 1,
    nomor_batch VARCHAR(50) DEFAULT NULL,
    tanggal_vaksin DATE NOT NULL,
    jadwal_vaksin_berikutnya DATE DEFAULT NULL,
    catatan_reaksi TEXT DEFAULT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 7. Table: medicines_stock (Stok Obat & Vaksin)
CREATE TABLE IF NOT EXISTS medicines_stock (
    id SERIAL PRIMARY KEY,
    kode VARCHAR(30) UNIQUE NOT NULL,
    nama_item VARCHAR(100) NOT NULL,
    kategori VARCHAR(20) NOT NULL CHECK (kategori IN ('obat', 'vaksin', 'alat_medis')),
    stok INT NOT NULL DEFAULT 0,
    min_stok INT NOT NULL DEFAULT 10,
    satuan VARCHAR(30) NOT NULL,
    harga_satuan INT NOT NULL DEFAULT 0,
    deskripsi VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 8. Table: transactions (Kasir)
CREATE TABLE IF NOT EXISTS transactions (
    id SERIAL PRIMARY KEY,
    no_nota VARCHAR(35) UNIQUE NOT NULL,
    id_antrean INT NOT NULL REFERENCES queues(id) ON DELETE CASCADE,
    id_pasien INT NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    id_kasir INT DEFAULT NULL,
    biaya_konsultasi INT NOT NULL DEFAULT 75000,
    biaya_tindakan INT NOT NULL DEFAULT 0,
    biaya_obat_vaksin INT NOT NULL DEFAULT 0,
    biaya_admin INT NOT NULL DEFAULT 10000,
    total_biaya INT NOT NULL DEFAULT 85000,
    metode_pembayaran VARCHAR(20) DEFAULT 'tunai',
    status_bayar VARCHAR(20) NOT NULL DEFAULT 'belum_bayar',
    catatan VARCHAR(255) DEFAULT NULL,
    waktu_bayar TIMESTAMPTZ DEFAULT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- =======================================================
-- SEED DATA
-- =======================================================

-- Users (Password for all: password123)
-- bcrypt hash for 'password123': $2y$10$wKqKzS633V/b2ZlY2iZ8t.sQ0lV3z/1sWbL64JmG3vT.lYV5hE2xK
INSERT INTO users (username, password, nama_lengkap, role, spesialisasi) VALUES
('resepsionis', '$2y$10$wKqKzS633V/b2ZlY2iZ8t.sQ0lV3z/1sWbL64JmG3vT.lYV5hE2xK', 'Kristoforus Vieri, S.Kom', 'resepsionis', 'Front Office & Kasir'),
('asisten1', '$2y$10$wKqKzS633V/b2ZlY2iZ8t.sQ0lV3z/1sWbL64JmG3vT.lYV5hE2xK', 'Juan Darren, A.Md.Kep', 'asisten', 'Asisten Medis 1 (Poli Anak & Tumbuh Kembang)'),
('asisten2', '$2y$10$wKqKzS633V/b2ZlY2iZ8t.sQ0lV3z/1sWbL64JmG3vT.lYV5hE2xK', 'Siti Nurhaliza, A.Md.Kep', 'asisten', 'Asisten Medis 2 (Poli Gigi & Vaksinasi)'),
('dokter', '$2y$10$wKqKzS633V/b2ZlY2iZ8t.sQ0lV3z/1sWbL64JmG3vT.lYV5hE2xK', 'dr. Aldrich Joerellie, Sp.A, B.Med', 'dokter', 'Dokter Spesialis Anak & Bedah Minor'),
('admin', '$2y$10$wKqKzS633V/b2ZlY2iZ8t.sQ0lV3z/1sWbL64JmG3vT.lYV5hE2xK', 'Administrator Klinik', 'admin', 'Super Admin Sistem')
ON CONFLICT (username) DO NOTHING;

-- Medicines & Vaccines Stock
INSERT INTO medicines_stock (kode, nama_item, kategori, stok, min_stok, satuan, harga_satuan, deskripsi) VALUES
('OBT-001', 'Paracetamol Drop 100mg/ml (15ml)', 'obat', 45, 10, 'Botol', 28000, 'Pereda demam & nyeri bayi/balita'),
('OBT-002', 'Paracetamol Sirup 120mg/5ml (60ml)', 'obat', 60, 15, 'Botol', 22000, 'Pereda demam anak rasa strawberry'),
('OBT-003', 'Amoxicillin Sirup Kering 125mg/5ml', 'obat', 35, 10, 'Botol', 35000, 'Antibiotik infeksi bakteri'),
('OBT-004', 'Cetirizine Drop 10mg/ml (10ml)', 'obat', 28, 8, 'Botol', 42000, 'Antihistamin alergi & gatal'),
('OBT-005', 'Zinc Sirup 20mg/5ml (60ml)', 'obat', 50, 10, 'Botol', 30000, 'Suplemen terapi diare balita'),
('OBT-006', 'Oralit Serbuk Sachet 200ml', 'obat', 120, 30, 'Sachet', 4500, 'Cairan rehidrasi elektrolit diare'),
('VKN-001', 'Vaksin Hepatitis B Rekombinan (Uniject)', 'vaksin', 25, 8, 'Dosis/Pcs', 95000, 'Imunisasi hepatitis B bayi baru lahir'),
('VKN-002', 'Vaksin BCG Freeze-Dried + Pelarut', 'vaksin', 20, 5, 'Ampul', 110000, 'Pencegahan TBC anak'),
('VKN-003', 'Vaksin Polio Tetes (bOPV 10 dosis)', 'vaksin', 30, 8, 'Vial', 75000, 'Imunisasi polio tetes oral'),
('VKN-004', 'Vaksin DTP-HB-Hib (Pentabio)', 'vaksin', 22, 6, 'Vial', 185000, 'Difteri, Tetanus, Pertusis, Hep B, Hib'),
('VKN-005', 'Vaksin Campak-Rubella (MR)', 'vaksin', 18, 5, 'Vial', 160000, 'Vaksin pencegahan campak & rubella'),
('VKN-006', 'Vaksin PCV 13 (Prevenar)', 'vaksin', 14, 4, 'Syringe', 650000, 'Pencegahan radang paru & selaput otak'),
('ALT-001', 'Masker Nebulizer Pediatric Size', 'alat_medis', 50, 15, 'Pcs', 35000, 'Masker uap inhalasi anak'),
('ALT-003', 'Bahan Tambal Gigi Glass Ionomer (GIC)', 'alat_medis', 12, 3, 'Set', 175000, 'Penambalan gigi susu anak')
ON CONFLICT (kode) DO NOTHING;

-- Sample Patients (< 15 years old)
INSERT INTO patients (kode_rm, nama_anak, nik, tanggal_lahir, jenis_kelamin, nama_ortu, no_hp, alamat, golongan_darah, riwayat_alergi) VALUES
('RM-2026-0001', 'Kenzo Pratama', '3374012005190001', '2021-05-14', 'L', 'Budi Pratama', '081234567890', 'Jl. Cempaka Indah No. 12, Semarang', 'O', 'Tidak ada riwayat alergi'),
('RM-2026-0002', 'Aisyah Putri Azzahra', '3374016508220002', '2022-08-25', 'P', 'Rina Wulandari', '081398765432', 'Jl. Melati Selatan No. 45, Semarang', 'A', 'Alergi Dingin'),
('RM-2026-0003', 'Gibran Rakabuming Satria', '3374011202200003', '2020-02-12', 'L', 'Bambang Satria', '085711223344', 'Perum Bukit Asri Blok C-3, Semarang', 'B', 'Alergi Seafood')
ON CONFLICT (kode_rm) DO NOTHING;

-- Initial Demo Queues
INSERT INTO queues (id, nomor_antrean, id_pasien, poli_tujuan, keluhan_awal, status, estimasi_menit, waktu_daftar) VALUES
(1, 'U-001', 1, 'umum', 'Demam tinggi 2 hari, batuk berdahak dan pilek', 'ruang_dokter', 10, NOW() - INTERVAL '30 minutes'),
(2, 'G-001', 2, 'gigi', 'Gigi geraham bawah berlubang dan sakit mengunyah', 'ruang_asisten', 12, NOW() - INTERVAL '20 minutes'),
(3, 'V-001', 3, 'vaksin', 'Jadwal suntik Vaksin Influenza dan PCV booster', 'menunggu_dokter', 15, NOW() - INTERVAL '10 minutes')
ON CONFLICT (id) DO NOTHING;
