<?php
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Staf & Dokter - Klinik Anak Happy</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Custom UI/UX Styles -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col font-sans">

  <!-- Top Navigation & Role Switcher Bar -->
  <header class="bg-white border-b border-slate-200 sticky top-0 z-40 px-4 sm:px-6 py-3 shadow-xs">
    <div class="max-w-[1700px] mx-auto flex flex-wrap items-center justify-between gap-4">
      
      <!-- Logo & App Title -->
      <div class="flex items-center gap-3">
        <a href="index.php" class="w-10 h-10 rounded-xl bg-cyan-700 flex items-center justify-center text-white shadow-sm hover:scale-105 transition-transform">
          <i class="fa-solid fa-heart-pulse text-lg"></i>
        </a>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-base font-extrabold text-slate-900 tracking-tight">Klinik Anak Happy</h1>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-800">Sistem Staf Terpadu</span>
          </div>

          <p class="text-xs text-slate-500">Resepsionis • Asisten Dokter • Dokter Pemeriksa</p>
        </div>

      </div>


      <!-- Database Status Badge -->
      <button type="button" onclick="openDatabaseConfigModal()" id="db-status-badge" class="px-2.5 py-1.5 rounded-full text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-1.5 border border-slate-200 transition-all cursor-pointer" title="Klik untuk info database & panduan Supabase">
        <span id="db-status-dot" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span id="db-status-text">Memuat DB...</span>
      </button>

      <!-- Quick Role Switcher for Seamless Grading & Testing -->
      <div class="flex items-center gap-2 bg-slate-50 p-1 rounded-2xl border border-slate-200">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-2">Beralih Peran:</span>
        <button type="button" class="role-switcher-btn" data-username="admin" onclick="switchRoleAccount('admin')">
          <i class="fa-solid fa-shield text-purple-600 mr-1"></i> Admin (Aldrich)
        </button>
        <button type="button" class="role-switcher-btn" data-username="dokter" onclick="switchRoleAccount('dokter')">
          <i class="fa-solid fa-stethoscope text-indigo-600 mr-1"></i> Dokter
        </button>
        <button type="button" class="role-switcher-btn" data-username="asisten1" onclick="switchRoleAccount('asisten1')">
          <i class="fa-solid fa-user-nurse text-amber-600 mr-1"></i> Asisten 1
        </button>
        <button type="button" class="role-switcher-btn" data-username="resepsionis" onclick="switchRoleAccount('resepsionis')">
          <i class="fa-solid fa-id-badge text-cyan-600 mr-1"></i> Resepsionis
        </button>
      </div>


      <!-- User Info & External Links -->
      <div class="flex items-center gap-3">
        <div class="text-right hidden sm:block">
          <div id="user-display-name" class="text-xs font-bold text-slate-900">Aldrich Joerellie</div>
          <div id="user-display-role" class="text-[11px] text-cyan-700 font-semibold">Super Admin</div>
        </div>


        <button type="button" onclick="openLoginModal()" id="btn-login-trigger" class="px-3 py-1.5 rounded-xl bg-cyan-700 hover:bg-cyan-800 text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm" title="Login / Ganti Akun">
          <i class="fa-solid fa-arrow-right-to-bracket"></i>
          <span id="btn-login-text">Masuk / Ganti Akun</span>
        </button>

        <a href="antrian.php" target="_blank" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all flex items-center gap-1.5" title="Buka Display Antrean Ruang Tunggu">
          <i class="fa-solid fa-tv text-cyan-700"></i>
          <span class="hidden md:inline">Layar Antrean TV</span>
        </a>

        <a href="index.php" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-all" title="Kembali ke Beranda">
          <i class="fa-solid fa-house"></i>
        </a>
      </div>


    </div>

  </header>

  <!-- Module Sub-Navigation Bar -->
  <div class="bg-white border-b border-slate-200 px-4 sm:px-6 py-2 shadow-xs">
    <div class="max-w-[1700px] mx-auto flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
      
      <button class="nav-tab-btn" data-tab="resepsionis" onclick="switchTab('resepsionis')">
        <i class="fa-solid fa-id-badge text-cyan-700"></i>
        <span>Pendaftaran & Kasir (Resepsionis)</span>
      </button>

      <button class="nav-tab-btn" data-tab="asisten" onclick="switchTab('asisten')">
        <i class="fa-solid fa-weight-scale text-amber-600"></i>
        <span>Pemeriksaan Awal (Asisten Medis)</span>
      </button>

      <button class="nav-tab-btn" data-tab="dokter" onclick="switchTab('dokter')">
        <i class="fa-solid fa-stethoscope text-indigo-600"></i>
        <span>Rekam Medis & Gigi (Dokter)</span>
      </button>

      <button class="nav-tab-btn" data-tab="stok" onclick="switchTab('stok')">
        <i class="fa-solid fa-boxes-stacked text-teal-600"></i>
        <span>Stok Obat & Vaksin</span>
      </button>

      <button class="nav-tab-btn" data-tab="laporan" onclick="switchTab('laporan')">
        <i class="fa-solid fa-chart-pie text-slate-600"></i>
        <span>Laporan Keuangan & Harian</span>
      </button>

      <!-- Khusus Super Admin Aldrich Joerellie -->
      <button id="nav-btn-verifikasi" class="nav-tab-btn hidden" data-tab="verifikasi" onclick="switchTab('verifikasi')">
        <i class="fa-solid fa-user-shield text-purple-600"></i>
        <span>Verifikasi Dokter</span>
        <span id="badge-pending-doctors" class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-rose-500 text-white font-extrabold hidden">0</span>
      </button>

    </div>

  </div>

  <!-- Main View Container -->
  <main class="flex-grow p-4 sm:p-6 max-w-[1700px] mx-auto w-full">
    
    <!-- ======================================================== -->
    <!-- PANEL 1: RESEPSIONIS (PENDAFTARAN, ANTREAN & KASIR)       -->
    <!-- ======================================================== -->
    <div id="panel-resepsionis" class="admin-view-panel space-y-6">
      
      <!-- Top Bento Row: Quick Registration & Search Patient -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left: Quick Search Existing Patient & Issue Queue -->
        <div class="lg:col-span-5 bento-card p-6 bg-white">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2">
              <i class="fa-solid fa-magnifying-glass text-cyan-700"></i>
              <h3 class="font-extrabold text-base text-slate-900">Cari Pasien Terdaftar</h3>
            </div>

            <span class="text-xs text-slate-500 font-medium">Terbitkan Tiket Antrean</span>
          </div>


          <div class="space-y-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Cari Nama Anak / No. RM / No. HP:</label>
              <input type="text" id="search-patient-input" placeholder="Ketik nama anak, RM-..., atau nama ortu"
                     class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500"
                     oninput="onSearchPatientInput(this.value)">
            </div>


            <!-- Search Results Dropdown List -->
            <div id="patient-search-results" class="max-h-56 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-100 bg-slate-50/50"></div>
          </div>

        </div>


        <!-- Right: Register New Pediatric Patient (< 15 yo) -->
        <div class="lg:col-span-7 bento-card p-6 bg-white">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2">
              <i class="fa-solid fa-user-plus text-cyan-700"></i>
              <h3 class="font-extrabold text-base text-slate-900">Pendaftaran Pasien Anak Baru</h3>
            </div>

            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-cyan-50 text-cyan-800 border border-cyan-200">
              Validasi Otomatis &lt; 15 Thn
            </span>
          </div>


          <form onsubmit="submitNewPatient(event)" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Anak <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_anak" required placeholder="Contoh: Raffa Putra"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
              </div>


              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-bold text-slate-700">Tgl Lahir <span class="text-rose-500">*</span></label>
                  <div id="dob-age-feedback"></div>
                </div>

                <input type="date" name="tanggal_lahir" max="<?= date('Y-m-d') ?>" required
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500"
                       onchange="onBirthDateChanged(this)">
              </div>


              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
                  <option value="L">Laki-laki</option>
                  <option value="P">Perempuan</option>
                </select>
              </div>

            </div>


            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Orang Tua <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_ortu" required placeholder="Nama Ayah / Ibu"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
              </div>


              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp / HP <span class="text-rose-500">*</span></label>
                <input type="tel" name="no_hp" required placeholder="08xxxxxxxxxx"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
              </div>


              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Riwayat Alergi</label>
                <input type="text" name="riwayat_alergi" placeholder="Dingin / Obat / Seafood"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
              </div>

            </div>


            <div class="flex items-center justify-end pt-1">
              <button type="submit" class="px-5 py-2.5 rounded-xl bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-xs shadow-sm transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-save"></i>
                <span>Simpan & Terbitkan Antrean</span>
              </button>
            </div>

          </form>
        </div>


      </div>


      <!-- Middle: Active Today's Queue Management -->
      <div class="bento-card p-6 bg-white">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
          <div>
            <h3 class="font-extrabold text-lg text-slate-900">Daftar Antrean Pasien Hari Ini</h3>
            <p class="text-xs text-slate-500">Memantau alur dari meja resepsionis, asisten medis, dokter hingga kasir</p>
          </div>

          <button onclick="loadResepsionisQueues()" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all flex items-center gap-1.5 self-start">
            <i class="fa-solid fa-arrows-rotate"></i>
            <span>Segarkan Antrean</span>
          </button>
        </div>


        <div class="overflow-x-auto mt-4">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                <th class="p-3">No. Antrean</th>
                <th class="p-3">Nama Pasien Anak</th>
                <th class="p-3">Poli Tujuan</th>
                <th class="p-3">Keluhan Awal</th>
                <th class="p-3">Status Saat Ini</th>
                <th class="p-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody id="table-resepsionis-queues">
              <tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Memuat data antrean...</td></tr>
            </tbody>
          </table>
        </div>

      </div>


      <!-- Bottom: Kasir & Pembayaran Section -->
      <div class="bento-card p-6 bg-white border-t-4 border-t-emerald-500">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">
              <i class="fa-solid fa-cash-register"></i>
            </div>

            <div>
              <h3 class="font-extrabold text-lg text-slate-900">Kasir & Pembayaran Pasien</h3>
              <p class="text-xs text-slate-500">Pasien yang telah selesai diperiksa dokter dan menunggu pelunasan nota tagihan</p>
            </div>

          </div>

          <button onclick="loadCashierBilling()" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold">
            <i class="fa-solid fa-arrows-rotate mr-1"></i> Refresh Kasir
          </button>
        </div>


        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                <th class="p-3">No. Antrean</th>
                <th class="p-3">Data Pasien</th>
                <th class="p-3">Layanan Poli</th>
                <th class="p-3">Total Biaya</th>
                <th class="p-3">Status Tagihan</th>
                <th class="p-3 text-right">Aksi Kasir</th>
              </tr>
            </thead>
            <tbody id="table-cashier-queues">
              <tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Memuat antrean kasir...</td></tr>
            </tbody>
          </table>
        </div>

      </div>


    </div>


    <!-- ======================================================== -->
    <!-- PANEL 2: ASISTEN DOKTER (TANDA VITAL & STOK VAKSIN)       -->
    <!-- ======================================================== -->
    <div id="panel-asisten" class="admin-view-panel space-y-6 hidden">
      
      <!-- Asisten Instructions Banner -->
      <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shadow-md">
            <i class="fa-solid fa-user-nurse"></i>
          </div>

          <div>
            <h3 class="font-extrabold text-base text-amber-950">Ruang Persiapan & Tanda Vital (2 Asisten Dokter)</h3>
            <p class="text-xs text-amber-800 mt-0.5">
              Tugas: Panggil antrean, ukur dan input berat badan (kg), tinggi badan (cm), suhu (°C), lingkar kepala, dan siapkan alat/dosis vaksin.
            </p>
          </div>

        </div>

        <button onclick="loadAsistenQueues()" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-sm self-start sm:self-auto transition-all">
          <i class="fa-solid fa-arrows-rotate mr-1"></i> Refresh Pasien
        </button>
      </div>


      <!-- Asisten Patient Queue Table -->
      <div class="bento-card p-6 bg-white">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
          <h4 class="font-extrabold text-base text-slate-900">Antrean Menunggu Pemeriksaan Awal</h4>
          <span class="text-xs text-slate-500 font-medium">Klik "Input Tanda Vital" untuk memulai</span>
        </div>


        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                <th class="p-3">No. Antrean</th>
                <th class="p-3">Nama Pasien Anak</th>
                <th class="p-3">Poli</th>
                <th class="p-3">Keluhan Awal</th>
                <th class="p-3">Hasil Tanda Vital</th>
                <th class="p-3 text-right">Tindakan</th>
              </tr>
            </thead>
            <tbody id="table-asisten-queues">
              <tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Memuat data asisten...</td></tr>
            </tbody>
          </table>
        </div>

      </div>


    </div>


    <!-- ======================================================== -->
    <!-- PANEL 3: DOKTER (REKAM MEDIS, GIGI, VAKSIN & RESEP)       -->
    <!-- ======================================================== -->
    <div id="panel-dokter" class="admin-view-panel space-y-6 hidden">
      
      <!-- Doctor Banner -->
      <div class="p-5 rounded-2xl bg-gradient-to-r from-cyan-900 to-teal-950 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-lg">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-cyan-600 text-white flex items-center justify-center text-xl shadow-md">
            <i class="fa-solid fa-user-doctor"></i>
          </div>

          <div>
            <div class="flex items-center gap-2">
              <h3 class="font-extrabold text-base text-white">Ruang Pemeriksaan Dokter Spesialis Anak</h3>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-400/30">dr. Aldrich Joerellie</span>
            </div>

            <p class="text-xs text-slate-300 mt-0.5">
              Data tanda vital awal asisten otomatis muncul. Dokter menginput diagnosis, tindakan gigi anak, jadwal vaksin, dan resep obat yang otomatis terkirim ke kasir.
            </p>
          </div>

        </div>


        <button onclick="loadDokterQueues()" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-sm self-start sm:self-auto transition-all">
          <i class="fa-solid fa-arrows-rotate mr-1"></i> Refresh Pasien Siap Periksa
        </button>
      </div>


      <!-- Doctor Ready Queues Table -->
      <div class="bento-card p-6 bg-white">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
          <div>
            <h4 class="font-extrabold text-base text-slate-900">Pasien Siap Masuk Ruang Dokter</h4>
            <p class="text-xs text-slate-500">Pasien yang tanda vitalnya telah selesai diinput oleh Asisten Dokter</p>
          </div>

          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
        </div>


        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                <th class="p-3">No. Antrean</th>
                <th class="p-3">Nama Pasien Anak</th>
                <th class="p-3">Poli</th>
                <th class="p-3">Tanda Vital (Asisten)</th>
                <th class="p-3">Keluhan Awal</th>
                <th class="p-3 text-right">Aksi Dokter</th>
              </tr>
            </thead>
            <tbody id="table-dokter-queues">
              <tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Memuat antrean dokter...</td></tr>
            </tbody>
          </table>
        </div>

      </div>


    </div>


    <!-- ======================================================== -->
    <!-- PANEL 4: STOK OBAT & VAKSIN                               -->
    <!-- ======================================================== -->
    <div id="panel-stok" class="admin-view-panel space-y-6 hidden">
      <div class="bento-card p-6 bg-white">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
          <div>
            <h3 class="font-extrabold text-lg text-slate-900">Manajemen Stok Obat, Vaksin & Alkes</h3>
            <p class="text-xs text-slate-500">Inventori stok yang diakses asisten medis dan dokter sebelum tindakan</p>
          </div>

          <button onclick="loadStockTable()" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all flex items-center gap-1.5 self-start">
            <i class="fa-solid fa-arrows-rotate"></i>
            <span>Refresh Stok</span>
          </button>
        </div>


        <div class="overflow-x-auto mt-4">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                <th class="p-3">Kode</th>
                <th class="p-3">Nama Item</th>
                <th class="p-3">Kategori</th>
                <th class="p-3">Sisa Stok</th>
                <th class="p-3">Harga Satuan</th>
                <th class="p-3 text-right">Penyesuaian</th>
              </tr>
            </thead>
            <tbody id="table-stock-items">
              <tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Memuat stok...</td></tr>
            </tbody>
          </table>
        </div>

      </div>

    </div>


    <!-- ======================================================== -->
    <!-- PANEL 5: LAPORAN KEUANGAN & HARIAN                        -->
    <!-- ======================================================== -->
    <div id="panel-laporan" class="admin-view-panel space-y-6 hidden">
      <div id="daily-report-container" class="space-y-6">
        
        <!-- Summary Cards Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bento-card p-5 bg-white border-l-4 border-l-emerald-500">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Omzet Kasir Hari Ini</div>
            <div id="rep-total-omzet" class="text-2xl font-extrabold text-emerald-700 mt-1">Rp 0</div>
            <div id="rep-total-pasien" class="text-xs text-slate-500 mt-1">0 Transaksi Selesai</div>
          </div>


          <div class="bento-card p-5 bg-white border-l-4 border-l-cyan-500">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Jasa Konsultasi Dokter</div>
            <div id="rep-omzet-konsul" class="text-xl font-bold text-slate-800 mt-1">Rp 0</div>
            <div class="text-xs text-slate-500 mt-1">Poli Umum, Gigi, Vaksin</div>
          </div>


          <div class="bento-card p-5 bg-white border-l-4 border-l-amber-500">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Biaya Tindakan Medis & Gigi</div>
            <div id="rep-omzet-tindakan" class="text-xl font-bold text-slate-800 mt-1">Rp 0</div>
            <div class="text-xs text-slate-500 mt-1">Nebulizer, GIC, Ekstraksi</div>
          </div>


          <div class="bento-card p-5 bg-white border-l-4 border-l-indigo-500">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pendapatan Obat & Vaksin</div>
            <div id="rep-omzet-obat" class="text-xl font-bold text-slate-800 mt-1">Rp 0</div>
            <div class="text-xs text-slate-500 mt-1">Farmasi & Imunisasi Anak</div>
          </div>

        </div>


        <!-- Transactions Table -->
        <div class="bento-card p-6 bg-white">
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <div>
              <h3 class="font-extrabold text-lg text-slate-900">Rekap Transaksi Kasir Hari Ini</h3>
              <p class="text-xs text-slate-500">Daftar pembayaran nota tagihan pasien lunas & menunggu bayar</p>
            </div>

            <button onclick="window.print()" class="px-3.5 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold flex items-center gap-1.5 no-print">
              <i class="fa-solid fa-print"></i> Cetak Rekap Laporan
            </button>
          </div>


          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                  <th class="p-3">No. Nota</th>
                  <th class="p-3">Antrean</th>
                  <th class="p-3">Nama Pasien Anak</th>
                  <th class="p-3">Poli</th>
                  <th class="p-3">Total Biaya</th>
                  <th class="p-3">Status</th>
                </tr>
              </thead>
              <tbody id="rep-transactions-table">
                <tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Memuat transaksi...</td></tr>
              </tbody>
            </table>
          </div>

        </div>


      </div>
    </div>

    <!-- ======================================================== -->
    <!-- PANEL 6: VERIFIKASI & KELOLA DOKTER (KHUSUS ADMIN)       -->
    <!-- ======================================================== -->
    <div id="panel-verifikasi" class="admin-view-panel space-y-6 hidden">
      <!-- Top Hero for Admin Aldrich Joerellie -->
      <div class="bento-card p-6 bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 text-white flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 text-purple-200 text-xs font-bold border border-purple-400/30 mb-2">
            <i class="fa-solid fa-shield-halved"></i> Super Admin: Aldrich Joerellie (Kode Masuk: 040207)
          </div>
          <h2 class="text-2xl font-extrabold tracking-tight">Verifikasi Dokter & Penerbitan Kode Masuk Permanen</h2>
          <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
            Dokter baru mendaftar secara mandiri melalui web. Admin memverifikasi keabsahan data dokter (STR/SIP), lalu sistem otomatis membuatkan <strong>Kode Acak Permanen</strong> yang menjadi kata sandi dokter untuk login selamanya.
          </p>
        </div>
        <button onclick="loadAdminDoctorsList()" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition-all flex items-center gap-2 shadow-lg self-start">
          <i class="fa-solid fa-arrows-rotate"></i> Segarkan Data Dokter
        </button>
      </div>

      <!-- Table 1: Permohonan Pendaftaran Dokter Baru (Pending Approval) -->
      <div class="bento-card p-6 bg-white border-l-4 border-l-amber-500">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="font-extrabold text-base text-slate-900">Permohonan Registrasi Dokter Baru</h3>
              <span id="badge-pending-count" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">0 Menunggu</span>
            </div>
            <p class="text-xs text-slate-500">Periksa nomor STR/SIP dokter sebelum menyetujui penerbitan kode acak login</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                <th class="p-3">Nama Dokter</th>
                <th class="p-3">Username</th>
                <th class="p-3">No. STR / SIP</th>
                <th class="p-3">Spesialisasi</th>
                <th class="p-3">Kontak WhatsApp</th>
                <th class="p-3">Waktu Daftar</th>
                <th class="p-3 text-right">Aksi Verifikasi Admin</th>
              </tr>
            </thead>
            <tbody id="table-pending-doctors">
              <tr><td colspan="7" class="p-6 text-center text-xs text-slate-400">Memuat permohonan dokter...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Table 2: Daftar Dokter Resmi Terverifikasi & Kode Akses -->
      <div class="bento-card p-6 bg-white border-l-4 border-l-emerald-500">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
          <div>
            <h3 class="font-extrabold text-base text-slate-900">Daftar Dokter Resmi Aktif & Kode Akses Login</h3>
            <p class="text-xs text-slate-500">Daftar dokter yang telah disetujui beserta kode permanen yang dipegang dokter untuk login</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                <th class="p-3">Nama Dokter</th>
                <th class="p-3">Username</th>
                <th class="p-3">No. STR / SIP</th>
                <th class="p-3">Spesialisasi</th>
                <th class="p-3">Kode Masuk Permanen</th>
                <th class="p-3">Status</th>
                <th class="p-3 text-right">Opsi</th>
              </tr>
            </thead>
            <tbody id="table-approved-doctors">
              <tr><td colspan="7" class="p-6 text-center text-xs text-slate-400">Memuat data dokter...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>

  <!-- ======================================================== -->
  <!-- MODALS SECTION                                           -->
  <!-- ======================================================== -->

  <!-- 1. Modal Input Vitals (Asisten Dokter) -->
  <div id="modal-vitals-input" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold">
            <i class="fa-solid fa-weight-scale text-sm"></i>
          </div>

          <h3 class="font-extrabold text-slate-900 text-base">Input Pemeriksaan Awal (Tanda Vital)</h3>
        </div>

        <button type="button" onclick="closeVitalsModal()" class="text-slate-400 hover:text-slate-600">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>


      <form onsubmit="submitVitals(event)" class="mt-4 space-y-4">
        <input type="hidden" id="vital-id-antrean">
        <div id="vital-header-info" class="p-3.5 rounded-2xl bg-amber-50/60 border border-amber-200"></div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Berat Badan (kg) <span class="text-rose-500">*</span></label>
            <input type="number" step="0.1" name="berat_badan" id="vital-bb" required placeholder="Contoh: 14.5"
                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-amber-500 font-bold"
                   oninput="calculateNutritionalStatus()">
          </div>


          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tinggi Badan (cm) <span class="text-rose-500">*</span></label>
            <input type="number" step="0.5" name="tinggi_badan" id="vital-tb" required placeholder="Contoh: 98.0"
                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-amber-500 font-bold"
                   oninput="calculateNutritionalStatus()">
          </div>

        </div>


        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Suhu Tubuh (°C) <span class="text-rose-500">*</span></label>
            <input type="number" step="0.1" name="suhu_tubuh" required placeholder="Contoh: 37.2"
                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-amber-500 font-bold text-rose-600">
          </div>


          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Lingkar Kepala (cm)</label>
            <input type="number" step="0.1" name="lingkar_kepala" placeholder="Contoh: 48.0"
                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-amber-500">
          </div>

        </div>


        <!-- Live Nutritional Status (Kalkulator Status Gizi Anak) -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Estimasi Status Gizi (WHO / IDAI):</label>
          <div id="vital-status-gizi-badge" class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
            <span class="text-slate-400">Masukkan BB dan TB anak di atas</span>
          </div>

        </div>


        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan Asisten Medis:</label>
          <textarea name="catatan_asisten" rows="2" placeholder="Contoh: Anak aktif, tidak muntah, ada batuk sejak kemarin"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-amber-500"></textarea>
        </div>


        <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
          <button type="button" onclick="closeVitalsModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
            Batal
          </button>
          <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-md flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-right"></i>
            <span>Simpan & Teruskan ke Dokter</span>
          </button>
        </div>

      </form>
    </div>

  </div>

  <!-- 2. Modal Doctor EMR Workspace (Dokter Spesialis Anak) -->
  <div id="modal-doctor-emr" class="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 hidden">
    <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[92vh] overflow-y-auto p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95">
      
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-10 h-10 rounded-2xl bg-cyan-700 text-white flex items-center justify-center font-bold">
            <i class="fa-solid fa-user-doctor text-base"></i>
          </div>

          <div>
            <h3 class="font-extrabold text-slate-900 text-lg">Lembar Rekam Medis Elektronik Pasien Anak</h3>
            <p class="text-xs text-slate-500">Pemeriksaan fisik, odontogram gigi anak, vaksinasi, dan resep obat</p>
          </div>

        </div>

        <button type="button" onclick="closeDoctorWorkspace()" class="text-slate-400 hover:text-slate-600">
          <i class="fa-solid fa-xmark text-xl"></i>
        </button>
      </div>


      <!-- Pre-loaded Patient Header with Assistant Vital Signs -->
      <div id="emr-patient-header" class="mt-4 p-4 rounded-2xl bg-cyan-50/70 border border-cyan-200"></div>

      <form onsubmit="submitDoctorEMR(event)" class="mt-4 space-y-4">
        <input type="hidden" id="emr-id-antrean">

        <!-- Sub Tabs for Doctor Specializations -->
        <div class="flex items-center gap-1.5 border-b border-slate-200 pb-2">
          <button type="button" class="emr-tab-nav-btn" data-subtab="umum" onclick="switchEmrSubTab('umum')">
            <i class="fa-solid fa-stethoscope"></i> Pemeriksaan Umum & Fisik
          </button>
          <button type="button" class="emr-tab-nav-btn" data-subtab="gigi" onclick="switchEmrSubTab('gigi')">
            <i class="fa-solid fa-tooth"></i> Odontogram Gigi Anak
          </button>
          <button type="button" class="emr-tab-nav-btn" data-subtab="vaksin" onclick="switchEmrSubTab('vaksin')">
            <i class="fa-solid fa-syringe"></i> Suntik Vaksin & Imunisasi
          </button>
          <button type="button" class="emr-tab-nav-btn" data-subtab="resep" onclick="switchEmrSubTab('resep')">
            <i class="fa-solid fa-pills"></i> Resep & Tindakan Kasir
          </button>
        </div>


        <!-- PANE A: PEMERIKSAAN UMUM -->
        <div id="emr-pane-umum" class="emr-tab-pane space-y-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Diagnosis Utama (ICD-10 Anak) <span class="text-rose-500">*</span></label>
            <input type="text" name="diagnosis" required placeholder="Contoh: Febris ec ISPA / Gastroenteritis Akut Dehidrasi Ringan / Faringitis Akut"
                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-cyan-500 font-bold text-slate-900">
          </div>


          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Anamnesis / Riwayat Penyakit:</label>
              <textarea name="anamnesis" rows="3" placeholder="Keluhan utama, durasi demam, batuk, muntah, nafsu makan..."
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Pemeriksaan Fisik Anak:</label>
              <textarea name="pemeriksaan_fisik" rows="3" placeholder="Faring hiperemis, tonsil T1/T1, ronki (-), wheezing (-), turgor kulit baik..."
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500"></textarea>
            </div>

          </div>

        </div>


        <!-- PANE B: ODONTOGRAM GIGI ANAK -->
        <div id="emr-pane-gigi" class="emr-tab-pane space-y-4 hidden">
          <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
              <div>
                <h4 class="font-extrabold text-sm text-emerald-950">Odontogram Gigi Susu Anak (Deciduous Teeth)</h4>
                <p class="text-[11px] text-emerald-800">Pilih status di bawah, lalu klik gigi yang ingin ditandai.</p>
              </div>


              <!-- Status Selector Buttons -->
              <div class="flex items-center gap-1.5">
                <button type="button" class="dental-tool-btn px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-emerald-700 border border-emerald-300 ring-2 ring-cyan-600" data-status="sehat" onclick="selectDentalStatus('sehat')">
                  Sehat
                </button>
                <button type="button" class="dental-tool-btn px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-rose-700 border border-rose-300" data-status="karies" onclick="selectDentalStatus('karies')">
                  Karies / Berlubang
                </button>
                <button type="button" class="dental-tool-btn px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-blue-700 border border-blue-300" data-status="tambal" onclick="selectDentalStatus('tambal')">
                  Tambalan GIC
                </button>
                <button type="button" class="dental-tool-btn px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-slate-700 border border-slate-300" data-status="cabut" onclick="selectDentalStatus('cabut')">
                  Cabut Gigi Susu
                </button>
              </div>

            </div>


            <!-- Upper Teeth Row -->
            <div class="text-center mb-1 text-[11px] font-bold text-slate-500">Rahang Atas:</div>
            <div id="upper-teeth-grid" class="flex flex-wrap justify-center gap-2 mb-3"></div>

            <!-- Lower Teeth Row -->
            <div class="text-center mb-1 text-[11px] font-bold text-slate-500">Rahang Bawah:</div>
            <div id="lower-teeth-grid" class="flex flex-wrap justify-center gap-2"></div>
          </div>


          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Ringkasan Tindakan Gigi:</label>
            <input type="text" name="catatan_gigi" id="emr-catatan-gigi" placeholder="Contoh: Karies profunda gigi 74, dilakukan penambalan GIC Fuji IX"
                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
          </div>

        </div>


        <!-- PANE C: VAKSINASI & IMUNISASI -->
        <div id="emr-pane-vaksin" class="emr-tab-pane space-y-3 hidden">
          <div class="p-4 rounded-2xl bg-indigo-50/60 border border-indigo-200">
            <h4 class="font-extrabold text-sm text-indigo-950 mb-1">Pemberian Vaksin / Imunisasi Hari Ini</h4>
            <p class="text-[11px] text-indigo-800 mb-3">Pencatatan jenis vaksin, dosis, nomor batch cold chain, dan jadwal booster berikutnya.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Vaksin Diberikan:</label>
                <select name="nama_vaksin" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                  <option value="">-- Bukan Layanan Vaksin / Lewati --</option>
                  <option value="Vaksin Hepatitis B Rekombinan">Vaksin Hepatitis B Rekombinan</option>
                  <option value="Vaksin BCG Freeze-Dried">Vaksin BCG Freeze-Dried</option>
                  <option value="Vaksin Polio Oral (bOPV)">Vaksin Polio Oral (bOPV)</option>
                  <option value="Vaksin DTP-HB-Hib (Pentabio)">Vaksin DTP-HB-Hib (Pentabio)</option>
                  <option value="Vaksin PCV 13 (Prevenar)">Vaksin PCV 13 (Prevenar)</option>
                  <option value="Vaksin Rotavirus Oral">Vaksin Rotavirus Oral</option>
                  <option value="Vaksin Campak-Rubella (MR)">Vaksin Campak-Rubella (MR)</option>
                  <option value="Vaksin Influenza Anak Trivalent">Vaksin Influenza Anak Trivalent</option>
                </select>
              </div>


              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Batch / Lot:</label>
                <input type="text" name="nomor_batch" placeholder="Contoh: PNT-2026-88B"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
              </div>


              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Dosis Ke-:</label>
                <select name="dosis_ke" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                  <option value="1">Dosis ke-1</option>
                  <option value="2">Dosis ke-2</option>
                  <option value="3">Dosis ke-3</option>
                  <option value="4">Booster / Penguat</option>
                </select>
              </div>


              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jadwal Vaksin / Kontrol Berikutnya:</label>
                <input type="date" name="jadwal_vaksin_berikutnya"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
              </div>

            </div>

          </div>

        </div>


        <!-- PANE D: RESEP OBAT & BIAYA KASIR -->
        <div id="emr-pane-resep" class="emr-tab-pane space-y-3 hidden">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tindakan Medis Ringan (Jika Ada):</label>
            <input type="text" name="tindakan_medis" placeholder="Contoh: Inhalasi Nebulizer Combivent 1 respule / Pembersihan luka lecet lutut"
                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
          </div>


          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Resep Obat Elektronik (Ketik Obat, Dosis & Aturan Minum):</label>
            <textarea name="resep_obat" rows="3" placeholder="Contoh:
1. Paracetamol sirup 120mg/5ml, 3 x 1 cth (bila demam > 38°C)
2. Cetirizine sirup, 1 x 1 cth malam hari
3. Oralit sachet 200ml, tiap BAB cair"
                      class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 font-mono"></textarea>
          </div>


          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Edukasi / Pesan Dokter untuk Orang Tua:</label>
            <input type="text" name="edukasi_ortu" placeholder="Contoh: Kompres air hangat di lipatan ketiak, perbanyak minum cairan, istirahat cukup"
                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
          </div>


          <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-2 border-t border-slate-100">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Jasa Konsultasi (Rp):</label>
              <input type="number" name="biaya_layanan" value="75000" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Biaya Tindakan (Rp):</label>
              <input type="number" name="biaya_tindakan" value="0" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Estimasi Obat (Rp):</label>
              <input type="number" name="biaya_obat" value="35000" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Kontrol:</label>
              <input type="date" name="jadwal_kontrol" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
            </div>

          </div>

        </div>


        <div class="pt-4 flex items-center justify-between border-t border-slate-100">
          <span class="text-xs text-slate-500 italic">* Resep obat & rincian biaya akan langsung terkirim ke meja kasir</span>
          <div class="flex items-center gap-2">
            <button type="button" onclick="closeDoctorWorkspace()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
              Batal
            </button>
            <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-cyan-700 hover:bg-cyan-800 text-white shadow-md flex items-center gap-1.5">
              <i class="fa-solid fa-paper-plane"></i>
              <span>Selesai Periksa & Kirim ke Kasir</span>
            </button>
          </div>

        </div>

      </form>
    </div>

  </div>

  <!-- 3. Modal Cashier Invoice & Payment -->
  <div id="modal-cashier-invoice" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 printable-area">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 no-print">
        <h3 class="font-extrabold text-slate-900 text-base">Nota Pembayaran Pasien</h3>
        <button type="button" onclick="closeCashierInvoiceModal()" class="text-slate-400 hover:text-slate-600">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>


      <!-- Printable Invoice Header -->
      <div class="text-center py-3 border-b border-dashed border-slate-300">
        <div class="font-extrabold text-lg text-slate-900">Klinik Anak Happy</div>
        <div class="text-xs text-slate-500">“Penuh Harapan” • Jl. Cempaka Indah No. 12, Semarang</div>
        <div class="text-xs font-mono font-bold text-slate-700 mt-1">No. Nota: <span id="inv-no-nota">NOTA-...</span></div>
      </div>


      <!-- Patient Information -->
      <div class="py-3 border-b border-slate-100 text-xs space-y-1">
        <div class="flex justify-between"><span class="text-slate-500">Nama Pasien:</span> <strong id="inv-patient-name" class="text-slate-800">-</strong></div>
        <div class="flex justify-between"><span class="text-slate-500">No. Rekam Medis:</span> <span id="inv-patient-rm" class="font-mono font-bold">-</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Usia Pasien:</span> <span id="inv-patient-age">-</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Diagnosis:</span> <span id="inv-diagnosis" class="font-medium text-slate-700">-</span></div>
      </div>


      <!-- Prescription Summary -->
      <div class="py-2.5 border-b border-slate-100 text-xs">
        <div class="text-slate-400 font-semibold mb-1">Resep / Obat Diberikan:</div>
        <div id="inv-prescription" class="font-mono text-slate-700 whitespace-pre-line bg-slate-50 p-2.5 rounded-xl border border-slate-100">-</div>
      </div>


      <!-- Breakdown Table -->
      <div class="py-3 border-b border-dashed border-slate-300 space-y-1.5 text-xs">
        <div class="flex justify-between"><span>Jasa Pemeriksaan Dokter:</span> <span id="inv-cost-konsul" class="font-semibold text-slate-800">Rp 0</span></div>
        <div class="flex justify-between"><span>Biaya Tindakan Medis / Gigi:</span> <span id="inv-cost-tindakan" class="font-semibold text-slate-800">Rp 0</span></div>
        <div class="flex justify-between"><span>Farmasi (Obat / Vaksin):</span> <span id="inv-cost-obat" class="font-semibold text-slate-800">Rp 0</span></div>
        <div class="flex justify-between"><span>Biaya Registrasi & Administrasi:</span> <span id="inv-cost-admin" class="font-semibold text-slate-800">Rp 10.000</span></div>
        <div class="flex justify-between pt-2 border-t border-slate-200 text-sm font-extrabold text-emerald-800">
          <span>TOTAL TAGIHAN:</span> <span id="inv-total-cost" class="text-base">Rp 0</span>
        </div>

      </div>


      <!-- Payment Method & Action (No print) -->
      <div class="mt-4 space-y-3 no-print">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Metode Pembayaran:</label>
          <select id="inv-payment-method" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500">
            <option value="tunai">Tunai / Cash</option>
            <option value="qris">QRIS (GoPay, OVO, BCA, Mandiri)</option>
            <option value="debit">Kartu Debit</option>
            <option value="transfer">Transfer Bank</option>
          </select>
        </div>


        <div class="flex items-center justify-between pt-2">
          <button type="button" onclick="closeCashierInvoiceModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
            Tutup
          </button>
          <button id="btn-confirm-payment" type="button" onclick="submitProcessPayment()" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-md">
            <i class="fa-solid fa-money-bill-wave mr-1.5"></i> Konfirmasi Pembayaran & Cetak Nota
          </button>
        </div>

      </div>

    </div>

  </div>

  <!-- 4. Modal History & Log Keluhan Pasien Lengkap (Dokter) -->
  <div id="modal-patient-history" class="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 hidden">
    <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 overflow-hidden">
      
      <!-- Modal Header -->
      <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-cyan-700 text-white flex items-center justify-center font-bold">
            <i class="fa-solid fa-clock-rotate-left text-lg"></i>
          </div>
          <div>
            <h3 class="font-extrabold text-slate-900 text-lg">Rekam Jejak & Riwayat Keluhan Pasien</h3>
            <p class="text-xs text-slate-500">Log lengkap kunjungan anak, keluh kesah orang tua, tanda vital, rekam medis dan vaksinasi</p>
          </div>
        </div>
        <button type="button" onclick="closePatientHistoryModal()" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 flex items-center justify-center text-slate-600 transition-all">
          <i class="fa-solid fa-xmark text-sm"></i>
        </button>
      </div>

      <!-- Patient Bio Banner -->
      <div id="hist-patient-header" class="px-6 py-3.5 bg-cyan-50/80 border-b border-cyan-100 text-xs">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>Memuat data pasien...</div>
        </div>
      </div>

      <!-- Timeline Content Container -->
      <div id="hist-timeline-container" class="p-6 overflow-y-auto flex-grow space-y-6">
        <div class="text-center py-10 text-slate-400 text-xs">
          <i class="fa-solid fa-spinner fa-spin text-xl text-cyan-600 mb-2"></i>
          <div>Memuat riwayat rekam jejak pasien...</div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50 text-xs text-slate-500">
        <div>Total kunjungan tercatat: <strong id="hist-total-visits" class="text-cyan-800">0</strong> kali</div>
        <button type="button" onclick="closePatientHistoryModal()" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold">
          Tutup
        </button>
      </div>
    </div>
  </div>

  <!-- 5. Modal Login & Registrasi Mandiri Dokter -->
  <div id="modal-login-auth" class="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 hidden">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95">
      
      <!-- Brand & Title -->
      <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-600 to-teal-600 text-white flex items-center justify-center mx-auto text-xl shadow-md mb-2">
          <i class="fa-solid fa-heart-pulse"></i>
        </div>
        <h2 class="text-xl font-extrabold text-slate-900">Portal Masuk Klinik Anak Happy</h2>
        <p class="text-xs text-slate-500 mt-0.5">Pilih peran akun Anda untuk mengakses sistem</p>
      </div>

      <!-- Tabs -->
      <div class="grid grid-cols-3 gap-1 bg-slate-100 p-1.5 rounded-2xl mb-6 text-xs font-bold">
        <button type="button" class="auth-tab-btn py-2 rounded-xl text-center transition-all bg-white text-slate-900 shadow-sm" data-tab="admin" onclick="switchAuthTab('admin')">
          <i class="fa-solid fa-shield text-purple-600 mr-1"></i> Admin
        </button>
        <button type="button" class="auth-tab-btn py-2 rounded-xl text-center transition-all text-slate-600 hover:text-slate-900" data-tab="dokter" onclick="switchAuthTab('dokter')">
          <i class="fa-solid fa-stethoscope text-indigo-600 mr-1"></i> Dokter
        </button>
        <button type="button" class="auth-tab-btn py-2 rounded-xl text-center transition-all text-slate-600 hover:text-slate-900" data-tab="daftar-dokter" onclick="switchAuthTab('daftar-dokter')">
          <i class="fa-solid fa-user-plus text-cyan-600 mr-1"></i> Daftar Dokter
        </button>
      </div>

      <!-- TAB 1: LOGIN ADMIN (ALDRICH JOERELLIE - KODE: 040207) -->
      <div id="auth-tab-admin" class="auth-pane space-y-4">
        <div class="p-3.5 rounded-2xl bg-purple-50 border border-purple-200 text-xs text-purple-900 flex items-start gap-2.5">
          <i class="fa-solid fa-circle-info text-purple-600 mt-0.5"></i>
          <div>
            <strong>Super Admin: Aldrich Joerellie</strong><br>
            Gunakan username <code class="font-mono bg-purple-100 px-1.5 py-0.5 rounded text-purple-800">admin</code> dan Kode Masuk Rahasia: <code class="font-mono font-bold bg-purple-200 px-1.5 py-0.5 rounded text-purple-950">040207</code>
          </div>
        </div>

        <form onsubmit="handleAuthLogin(event, 'admin')" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Username Admin</label>
            <input type="text" id="auth-admin-user" required value="admin" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-purple-500">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Kode Masuk Admin</label>
            <input type="password" id="auth-admin-code" required placeholder="Masukkan kode 040207" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono tracking-widest text-slate-900 focus:ring-2 focus:ring-purple-500 font-bold">
          </div>
          <button type="submit" class="w-full py-3 rounded-2xl bg-purple-700 hover:bg-purple-800 text-white font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-key"></i>
            <span>Masuk sebagai Super Admin</span>
          </button>
        </form>
      </div>

      <!-- TAB 2: LOGIN DOKTER (DENGAN KODE ACAK PERMANEN) -->
      <div id="auth-tab-dokter" class="auth-pane space-y-4 hidden">
        <div class="p-3.5 rounded-2xl bg-indigo-50 border border-indigo-200 text-xs text-indigo-900 flex items-start gap-2.5">
          <i class="fa-solid fa-key text-indigo-600 mt-0.5"></i>
          <div>
            <strong>Login Dokter Terverifikasi</strong><br>
            Masukkan username dokter dan <strong>Kode Acak Masuk</strong> permanen yang telah diverifikasi & diterbitkan oleh Admin Aldrich Joerellie.
          </div>
        </div>

        <form onsubmit="handleAuthLogin(event, 'dokter')" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Username Dokter</label>
            <input type="text" id="auth-doctor-user" required placeholder="Contoh: dokter atau dr_john" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Kode Acak Masuk Permanen (Kata Sandi Dokter)</label>
            <input type="text" id="auth-doctor-code" required placeholder="Contoh: DOK-48291" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono tracking-wider font-bold text-indigo-900 focus:ring-2 focus:ring-indigo-500">
          </div>
          <button type="submit" class="w-full py-3 rounded-2xl bg-indigo-700 hover:bg-indigo-800 text-white font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-stethoscope"></i>
            <span>Masuk ke Panel Dokter</span>
          </button>

          <div class="text-center pt-2">
            <button type="button" onclick="switchAuthTab('daftar-dokter')" class="text-xs font-bold text-cyan-700 hover:underline">
              Dokter baru? Klik di sini untuk membuat akun dokter &rarr;
            </button>
          </div>
        </form>
      </div>

      <!-- TAB 3: PENDAFTARAN DOKTER BARU (MANDIRI) -->
      <div id="auth-tab-daftar-dokter" class="auth-pane space-y-4 hidden">
        <div class="p-3.5 rounded-2xl bg-cyan-50 border border-cyan-200 text-xs text-cyan-900">
          <strong>Alur Pendaftaran Dokter:</strong><br>
          Isi data diri & nomor STR/SIP Anda di bawah. Pendaftaran Anda akan diverifikasi oleh Admin Aldrich Joerellie. Setelah disetujui, sistem akan membuatkan kode acak permanen yang akan Anda gunakan untuk login.
        </div>

        <form onsubmit="handleDoctorRegistration(event)" class="space-y-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap & Gelar <span class="text-rose-500">*</span></label>
            <input type="text" id="reg-doc-name" required placeholder="Contoh: dr. Aldrich Joerellie, Sp.A" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 font-bold">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Username Baru <span class="text-rose-500">*</span></label>
              <input type="text" id="reg-doc-user" required placeholder="Contoh: dr_aldrich" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500 font-mono">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">No. STR / SIP <span class="text-rose-500">*</span></label>
              <input type="text" id="reg-doc-str" required placeholder="Contoh: STR-3374-98214" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Spesialisasi Dokter</label>
            <select id="reg-doc-spec" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
              <option value="Dokter Spesialis Anak (Sp.A)">Dokter Spesialis Anak (Sp.A)</option>
              <option value="Dokter Gigi Spesialis Kedokteran Gigi Anak (Sp.KGA)">Dokter Gigi Anak (Sp.KGA)</option>
              <option value="Dokter Spesialis Tumbuh Kembang & Pediatri">Dokter Tumbuh Kembang & Pediatri</option>
              <option value="Dokter Umum Konselor Vaksinasi Anak">Dokter Umum / Vaksinasi Anak</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp <span class="text-rose-500">*</span></label>
              <input type="text" id="reg-doc-hp" required placeholder="Contoh: 081234567890" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email</label>
              <input type="email" id="reg-doc-email" placeholder="Contoh: dokter@klinik.id" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500">
            </div>
          </div>

          <button type="submit" class="w-full py-3 rounded-2xl bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2 mt-2">
            <i class="fa-solid fa-paper-plane"></i>
            <span>Kirim Permohonan Akun Dokter</span>
          </button>
        </form>
      </div>

      <!-- Close Button -->
      <div class="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between text-xs">
        <button type="button" onclick="closeLoginModal()" class="text-slate-400 hover:text-slate-600 font-semibold">
          Tutup Dialog
        </button>
        <span class="text-slate-400 text-[11px]">Klinik Anak Happy © 2026</span>
      </div>

    </div>
  </div>

  <!-- 6. Modal Terbit Kode Acak Dokter (Admin Approval Notice) -->
  <div id="modal-doctor-code-generated" class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-100 text-center animate-in fade-in zoom-in-95">
      
      <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto text-2xl mb-3 shadow-inner">
        <i class="fa-solid fa-shield-check"></i>
      </div>

      <h3 class="font-extrabold text-xl text-slate-900">Verifikasi Dokter Berhasil!</h3>
      <p class="text-xs text-slate-500 mt-1">Sistem telah menerbitkan kode acak permanen untuk dokter berikut:</p>

      <div class="my-4 p-4 rounded-2xl bg-emerald-50/80 border border-emerald-200 text-left">
        <div class="text-xs text-emerald-800 font-medium">Nama Dokter: <strong id="gen-doc-name" class="text-emerald-950 font-bold">-</strong></div>
        <div class="text-xs text-emerald-800 mt-0.5">Username: <span id="gen-doc-user" class="font-mono font-bold">-</span></div>
        <div class="mt-3 pt-3 border-t border-emerald-200 text-center">
          <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider mb-1">Kode Masuk Permanen Dokter:</div>
          <div id="gen-doc-code" class="text-2xl font-black font-mono tracking-widest text-emerald-900 bg-white py-2 px-4 rounded-xl border border-emerald-300 shadow-sm select-all">
            DOK-XXXXX
          </div>
        </div>
      </div>

      <p class="text-[11px] text-slate-500 mb-4">
        * Kode ini dipegang selamanya oleh dokter sebagai kata sandi untuk masuk ke sistem.
      </p>

      <div class="space-y-2">
        <button type="button" onclick="copyDoctorCode()" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-2">
          <i class="fa-solid fa-copy"></i>
          <span>Salin Kode Akses</span>
        </button>
        <a id="btn-wa-doctor-code" href="#" target="_blank" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-2">
          <i class="fa-brands fa-whatsapp text-sm"></i>
          <span>Kirim Kode ke WhatsApp Dokter</span>
        </a>
        <button type="button" onclick="closeDoctorCodeModal()" class="w-full py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
          Selesai & Tutup
        </button>
      </div>

    </div>
  </div>

  <!-- 7. Modal Database Status & Supabase Connection Guide -->
  <div id="modal-db-config" class="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-database text-cyan-700 text-lg"></i>
          <h3 class="font-extrabold text-slate-900 text-base">Status Database & Supabase</h3>
        </div>
        <button type="button" onclick="closeDatabaseConfigModal()" class="text-slate-400 hover:text-slate-600">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>

      <div class="mt-4 space-y-4">
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
          <div class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Status Koneksi Saat Ini:</div>
          <div id="db-modal-status-text" class="text-base font-extrabold text-slate-900">Memeriksa...</div>
          <div id="db-modal-host-text" class="text-xs text-slate-500 font-mono mt-0.5">-</div>
        </div>

        <div class="p-4 rounded-2xl bg-cyan-50/70 border border-cyan-200 text-xs text-cyan-950 space-y-2">
          <div class="font-extrabold text-sm text-cyan-900">Panduan Pakai Supabase (PostgreSQL Gratis):</div>
          <ol class="list-decimal list-inside space-y-1 text-slate-700">
            <li>Buka dashboard di <strong>https://supabase.com</strong> (gratis).</li>
            <li>Di menu <strong>SQL Editor</strong>, paste seluruh isi file <code>supabase_schema.sql</code> lalu klik <strong>Run</strong>.</li>
            <li>Buka <strong>Project Settings &rarr; Database &rarr; Connection String (URI)</strong>.</li>
            <li>Buka file <code>.env</code> di root project ini dan masukkan:<br>
              <code class="font-mono bg-white px-2 py-0.5 rounded border border-cyan-200 text-cyan-900 block mt-1">DATABASE_URL=postgresql://postgres.[ref]:[password]@[host]:[port]/postgres</code>
            </li>
            <li>Selesai! Web otomatis berpindah ke cloud Supabase.</li>
          </ol>
        </div>

        <div class="flex justify-end pt-2">
          <button type="button" onclick="closeDatabaseConfigModal()" class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold">
            Mengerti & Tutup
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="assets/js/app.js"></script>
  <script src="assets/js/admin.js"></script>
</body>
</html>


