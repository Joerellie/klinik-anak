<?php
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Klinik Anak Happy "Penuh Harapan" - Layanan Kesehatan Ramah Anak</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Custom UI/UX Styles -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

  <?php include __DIR__ . '/components/navbar.php'; ?>

  <main class="flex-grow">
    
    <!-- Hero Section -->
    <section class="relative overflow-hidden pt-12 pb-16 lg:pt-20 lg:pb-24 bg-gradient-to-b from-cyan-50/70 via-white to-slate-50">
      <!-- Decorative background blur blobs -->
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-96 pointer-events-none overflow-hidden opacity-40">
        <div class="absolute -top-10 left-10 w-72 h-72 rounded-full bg-cyan-200 blur-3xl"></div>
        <div class="absolute top-20 right-10 w-80 h-80 rounded-full bg-teal-200 blur-3xl"></div>
      </div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
          
          <!-- Left Text Content -->
          <div class="lg:col-span-7 space-y-6">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-100/80 border border-cyan-200 text-cyan-900 text-xs font-bold shadow-sm">
              <span class="w-2 h-2 rounded-full bg-cyan-600 animate-pulse"></span>
              <span>Layanan Spesialis Anak Usia &lt; 15 Tahun</span>
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.15]">
              Kesehatan Buah Hati, <br>
              <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 to-teal-600">Penuh Senyuman & Harapan</span>
            </h1>

            <p class="text-base sm:text-lg text-slate-600 max-w-2xl leading-relaxed">
              Selamat datang di <strong>Klinik Anak Happy</strong>. Ruang periksa nyaman tanpa rasa takut untuk si kecil, dengan dokter spesialis anak berpengalaman, pemantauan antrean real-time, dan pencatatan vaksinasi digital terpercaya.
            </p>

            <!-- Hero Action Buttons -->
            <div class="flex flex-wrap items-center gap-3 pt-2">
              <a href="#daftar-online" class="px-6 py-3.5 rounded-2xl bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-sm shadow-lg shadow-cyan-700/25 transition-all hover:scale-[1.02] flex items-center gap-2">
                <i class="fa-solid fa-calendar-plus text-base"></i>
                <span>Daftar Antrean Hari Ini</span>
              </a>

              <a href="antrian.php" class="px-6 py-3.5 rounded-2xl bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 font-bold text-sm shadow-sm transition-all hover:scale-[1.02] flex items-center gap-2">
                <i class="fa-solid fa-tv text-cyan-600 text-base"></i>
                <span>Pantau Layar Antrean Live</span>
              </a>
            </div>

            <!-- Mini Trust Badges -->
            <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-200/80 text-xs">
              <div class="flex items-center gap-2 text-slate-600">
                <div class="w-8 h-8 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold">
                  <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                  <div class="font-bold text-slate-800">08.00 - 17.00</div>
                  <div class="text-[11px] text-slate-500">Buka Hari Kerja</div>
                </div>
              </div>

              <div class="flex items-center gap-2 text-slate-600">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                  <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                  <div class="font-bold text-slate-800">Khusus Anak</div>
                  <div class="text-[11px] text-slate-500">Usia &lt; 15 Tahun</div>
                </div>
              </div>

              <div class="flex items-center gap-2 text-slate-600">
                <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                  <i class="fa-solid fa-bell"></i>
                </div>
                <div>
                  <div class="font-bold text-slate-800">Antrean Suara</div>
                  <div class="text-[11px] text-slate-500">Panggilan Audio</div>
                </div>
              </div>
            </div>

          </div>

          <!-- Right Feature: Interactive "Cek Posisi Antrean Saya" Card -->
          <div class="lg:col-span-5">
            <div class="bento-card p-6 sm:p-8 bg-white border border-cyan-100 shadow-xl shadow-cyan-900/5 relative">
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                  <div class="w-9 h-9 rounded-xl bg-cyan-500 text-white flex items-center justify-center">
                    <i class="fa-solid fa-ticket-simple text-sm"></i>
                  </div>
                  <div>
                    <h3 class="font-extrabold text-base text-slate-900">Cek Posisi Antrean Anda</h3>
                    <p class="text-xs text-slate-500">Cek apakah giliran anak Anda sudah dekat</p>
                  </div>
                </div>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" title="Sistem Aktif"></span>
              </div>

              <!-- Input Form -->
              <div class="space-y-3">
                <div>
                  <label for="ticket-search-input" class="block text-xs font-semibold text-slate-700 mb-1">Nomor Antrean / No. Rekam Medis:</label>
                  <div class="relative">
                    <input type="text" id="ticket-search-input" placeholder="Contoh: U-001 atau RM-2026-0001"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 uppercase tracking-wide font-mono font-semibold"
                           onkeydown="if(event.key === 'Enter') checkMyTicket()">
                    <button type="button" onclick="checkMyTicket()"
                            class="absolute right-1.5 top-1.5 bottom-1.5 px-4 rounded-lg bg-cyan-700 hover:bg-cyan-800 text-white text-xs font-bold transition-all">
                      Periksa
                    </button>
                  </div>
                </div>

                <!-- Result placeholder -->
                <div id="ticket-search-result" class="min-h-[100px] flex items-center justify-center">
                  <div class="text-center p-4 text-xs text-slate-400">
                    <i class="fa-solid fa-qrcode text-2xl text-slate-300 mb-1 block"></i>
                    Ketik nomor antrean Anda di atas untuk melihat status real-time di ruang dokter atau ruang asisten.
                  </div>
                </div>

                <div class="pt-2 text-center">
                  <a href="antrian.php" class="text-xs font-bold text-cyan-700 hover:text-cyan-800 hover:underline inline-flex items-center gap-1">
                    Buka Halaman Penuh Display Antrean <i class="fa-solid fa-arrow-right text-[10px]"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- Bento Box: 4 Core Pediatric Services (Dokumen Proposal) -->
    <section id="layanan-kami" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-2xl mx-auto mb-12">
        <span class="text-xs font-bold uppercase tracking-wider text-cyan-700 bg-cyan-50 px-3 py-1 rounded-full border border-cyan-200">Layanan Komprehensif</span>
        <h2 class="text-3xl font-extrabold text-slate-900 mt-2">Klinik Terpadu Khusus Anak</h2>
        <p class="text-sm text-slate-600 mt-2">Disesuaikan dengan kebutuhan medis anak di bawah usia 15 tahun dengan suasana yang ceria dan menenangkan.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- 1. Poli Anak Sakit & Umum -->
        <div class="bento-card bento-card-interactive p-6 flex flex-col justify-between border-t-4 border-t-sky-500">
          <div>
            <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center text-xl mb-4">
              <i class="fa-solid fa-stethoscope"></i>
            </div>
            <h3 class="font-extrabold text-lg text-slate-900">Pemeriksaan Umum & Anak Sakit</h3>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
              Konsultasi keluhan anak sakit: demam, flu, batuk berdahak, muntah, diare, alergi, serta pemantauan kurva tumbuh kembang berat & tinggi badan.
            </p>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-sky-700 font-bold flex items-center justify-between">
            <span>Kode Antrean: U-xxx</span>
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>

        <!-- 2. Poli Gigi Anak -->
        <div class="bento-card bento-card-interactive p-6 flex flex-col justify-between border-t-4 border-t-emerald-500">
          <div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl mb-4">
              <i class="fa-solid fa-tooth"></i>
            </div>
            <h3 class="font-extrabold text-lg text-slate-900">Poli Perawatan Gigi Anak</h3>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
              Perawatan ramah anak: pencabutan gigi susu tanpa rasa sakit, penambalan gigi berlubang (GIC), pembersihan karang, dan edukasi pencegahan karies.
            </p>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-emerald-700 font-bold flex items-center justify-between">
            <span>Kode Antrean: G-xxx</span>
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>

        <!-- 3. Vaksinasi & Imunisasi -->
        <div class="bento-card bento-card-interactive p-6 flex flex-col justify-between border-t-4 border-t-indigo-500">
          <div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl mb-4">
              <i class="fa-solid fa-syringe"></i>
            </div>
            <h3 class="font-extrabold text-lg text-slate-900">Suntik Vaksin & Imunisasi</h3>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
              Program vaksinasi lengkap sesuai jadwal IDAI: Hepatitis B, BCG, Polio, Pentabio (DTP-HB-Hib), PCV, Rotavirus, MR/MMR, dan Influenza.
            </p>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-indigo-700 font-bold flex items-center justify-between">
            <span>Kode Antrean: V-xxx</span>
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>

        <!-- 4. Tindakan Medis Ringan -->
        <div class="bento-card bento-card-interactive p-6 flex flex-col justify-between border-t-4 border-t-rose-500">
          <div>
            <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-xl mb-4">
              <i class="fa-solid fa-kit-medical"></i>
            </div>
            <h3 class="font-extrabold text-lg text-slate-900">Tindakan Medis Ringan</h3>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
              Penanganan cepat tindakan minor: inhalasi nebulizer untuk sesak & batuk grok-grok, perawatan luka lecet/jatuh, hecting luka kecil, dan ekstraksi serumen telinga.
            </p>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-rose-700 font-bold flex items-center justify-between">
            <span>Kode Antrean: T-xxx</span>
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>

      </div>
    </section>

    <!-- Live Mini-Queue Board on Homepage -->
    <section class="py-12 bg-white border-y border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <div>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
              <h3 class="text-2xl font-extrabold text-slate-900">Layar Antrean Sedang Dilayani</h3>
            </div>
            <p class="text-xs text-slate-500 mt-1">Status nomor antrean yang sedang berada di dalam ruang dokter saat ini.</p>
          </div>

          <div class="flex items-center gap-3">
            <span id="live-clock" class="font-mono text-xs font-bold text-cyan-800 bg-cyan-50 px-3 py-1.5 rounded-xl border border-cyan-200">--:-- WIB</span>
            <a href="antrian.php" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 transition-all flex items-center gap-1.5">
              <span>Buka Monitor Display TV</span>
              <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
            </a>
          </div>
        </div>

        <!-- 4 Poli Live Mini Cards Container -->
        <div id="poli-grid-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          
          <!-- Poli Umum -->
          <div id="poli-card-umum" class="bento-card p-5 bg-white border border-slate-200">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-stethoscope text-sky-600"></i>
                <span class="font-bold text-xs text-slate-900">Poli Umum</span>
              </div>
              <span class="waiting-count-badge text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">0 Menunggu</span>
            </div>
            <div class="current-doctor-box mb-3">
              <div class="text-xs text-slate-400 text-center py-2">Memuat status...</div>
            </div>
            <div class="current-asst-box mb-3"></div>
            <div class="pt-2 border-t border-slate-100">
              <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Berikutnya:</span>
              <ul class="waiting-list-items space-y-1"></ul>
            </div>
          </div>

          <!-- Poli Gigi -->
          <div id="poli-card-gigi" class="bento-card p-5 bg-white border border-slate-200">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-tooth text-emerald-600"></i>
                <span class="font-bold text-xs text-slate-900">Poli Gigi Anak</span>
              </div>
              <span class="waiting-count-badge text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">0 Menunggu</span>
            </div>
            <div class="current-doctor-box mb-3">
              <div class="text-xs text-slate-400 text-center py-2">Memuat status...</div>
            </div>
            <div class="current-asst-box mb-3"></div>
            <div class="pt-2 border-t border-slate-100">
              <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Berikutnya:</span>
              <ul class="waiting-list-items space-y-1"></ul>
            </div>
          </div>

          <!-- Poli Vaksin -->
          <div id="poli-card-vaksin" class="bento-card p-5 bg-white border border-slate-200">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-syringe text-indigo-600"></i>
                <span class="font-bold text-xs text-slate-900">Poli Vaksinasi</span>
              </div>
              <span class="waiting-count-badge text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">0 Menunggu</span>
            </div>
            <div class="current-doctor-box mb-3">
              <div class="text-xs text-slate-400 text-center py-2">Memuat status...</div>
            </div>
            <div class="current-asst-box mb-3"></div>
            <div class="pt-2 border-t border-slate-100">
              <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Berikutnya:</span>
              <ul class="waiting-list-items space-y-1"></ul>
            </div>
          </div>

          <!-- Poli Tindakan -->
          <div id="poli-card-tindakan" class="bento-card p-5 bg-white border border-slate-200">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-kit-medical text-rose-600"></i>
                <span class="font-bold text-xs text-slate-900">Poli Tindakan</span>
              </div>
              <span class="waiting-count-badge text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">0 Menunggu</span>
            </div>
            <div class="current-doctor-box mb-3">
              <div class="text-xs text-slate-400 text-center py-2">Memuat status...</div>
            </div>
            <div class="current-asst-box mb-3"></div>
            <div class="pt-2 border-t border-slate-100">
              <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Berikutnya:</span>
              <ul class="waiting-list-items space-y-1"></ul>
            </div>
          </div>

        </div>

      </div>
    </section>

    <!-- Vaccine Schedule Calculator Section -->
    <section id="jadwal-vaksin" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bento-card p-8 lg:p-12 bg-gradient-to-br from-cyan-900 via-slate-900 to-teal-950 text-white shadow-2xl relative overflow-hidden">
        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
          
          <div class="lg:col-span-5 space-y-4">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-cyan-400/20 text-cyan-300 border border-cyan-400/30">
              Kalkulator Rekomendasi Vaksin IDAI
            </span>
            <h3 class="text-3xl font-extrabold tracking-tight">Kapan Jadwal Imunisasi Si Kecil?</h3>
            <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
              Cek rekomendasi jadwal vaksinasi anak sesuai panduan Ikatan Dokter Anak Indonesia (IDAI). Masukkan tanggal lahir si kecil untuk melihat vaksin yang sudah dan perlu diberikan.
            </p>

            <div class="pt-2">
              <label for="calc-dob" class="block text-xs font-semibold text-slate-300 mb-1">Pilih Tanggal Lahir Anak:</label>
              <div class="flex items-center gap-2">
                <input type="date" id="calc-dob" max="<?= date('Y-m-d') ?>"
                       class="px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-400 w-full"
                       onchange="calculateVaccineRecommendations(this.value)">
              </div>
            </div>
          </div>

          <!-- Dynamic Vaccine List Output -->
          <div class="lg:col-span-7 bg-white/5 border border-white/10 rounded-2xl p-6 backdrop-blur-md">
            <div id="vaccine-calc-result">
              <div class="text-center py-8 text-slate-400 text-xs">
                <i class="fa-solid fa-baby text-3xl mb-2 text-cyan-400/50 block"></i>
                Pilih tanggal lahir anak di samping untuk melihat jadwal rekomendasi vaksinasi IDAI.
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- Online Patient Registration / Booking Form Section -->
    <section id="daftar-online" class="py-16 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bento-card p-8 sm:p-10 bg-white border border-slate-200 shadow-xl">
        
        <div class="text-center max-w-lg mx-auto mb-8">
          <span class="text-xs font-bold text-cyan-700 bg-cyan-50 px-3 py-1 rounded-full border border-cyan-200 uppercase tracking-wide">Pendaftaran Mandiri</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">Daftar Pasien Anak Baru</h2>
          <p class="text-xs text-slate-500 mt-1.5">
            Sistem otomatis memvalidasi usia anak &lt; 15 tahun sesuai ketentuan operasional Klinik Anak Happy.
          </p>
        </div>

        <form id="public-register-form" onsubmit="submitNewPatient(event)" class="space-y-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            
            <!-- Child Name -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap Anak <span class="text-rose-500">*</span></label>
              <input type="text" name="nama_anak" required placeholder="Contoh: Kenzo Pratama"
                     class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </div>

            <!-- Date of Birth with Live Age Validator -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold text-slate-700">Tanggal Lahir Anak <span class="text-rose-500">*</span></label>
                <div id="dob-age-feedback"></div>
              </div>
              <input type="date" name="tanggal_lahir" max="<?= date('Y-m-d') ?>" required
                     class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500"
                     onchange="onBirthDateChanged(this)">
            </div>

            <!-- Gender -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Jenis Kelamin <span class="text-rose-500">*</span></label>
              <select name="jenis_kelamin" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>

            <!-- Blood Type -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Golongan Darah (Bila tahu)</label>
              <select name="golongan_darah" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
                <option value="-">Belum Tahu / Belum Diperiksa</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="AB">AB</option>
                <option value="O">O</option>
              </select>
            </div>

            <!-- Parent Name -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Orang Tua / Wali <span class="text-rose-500">*</span></label>
              <input type="text" name="nama_ortu" required placeholder="Contoh: Budi Pratama"
                     class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </div>

            <!-- WhatsApp / Phone -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">No. WhatsApp / HP Aktif <span class="text-rose-500">*</span></label>
              <input type="tel" name="no_hp" required placeholder="Contoh: 081234567890"
                     class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </div>

          </div>

          <!-- Allergy history -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Riwayat Alergi Anak (Makanan / Obat / Dingin)</label>
            <input type="text" name="riwayat_alergi" placeholder="Contoh: Alergi Dingin / Alergi Telur / Tidak Ada Alergi"
                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500">
          </div>

          <!-- Address -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Tempat Tinggal</label>
            <textarea name="alamat" rows="2" placeholder="Alamat domisili saat ini"
                      class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500"></textarea>
          </div>

          <div class="pt-2 flex items-center justify-between">
            <span class="text-xs text-slate-500 italic">* Khusus pasien anak di bawah usia 15 tahun</span>
            <button type="submit" class="px-8 py-3 rounded-xl bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-sm shadow-md transition-all hover:scale-[1.02]">
              <i class="fa-solid fa-user-plus mr-1.5"></i> Daftarkan Pasien
            </button>
          </div>
        </form>

      </div>
    </section>

    <!-- Clinic Medical Team (From Proposal) -->
    <section class="py-16 bg-slate-100/60 border-t border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-xl mx-auto mb-10">
          <span class="text-xs font-bold uppercase tracking-wider text-cyan-700 bg-cyan-50 px-3 py-1 rounded-full border border-cyan-200">Tenaga Medis</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">Staf Profesional & Ramah Anak</h2>
          <p class="text-xs text-slate-500 mt-1">Sesuai struktur tim operasional Klinik Anak Happy</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          
          <!-- Dokter Utama -->
          <div class="bento-card p-6 bg-white text-center">
            <div class="w-16 h-16 rounded-full bg-cyan-100 text-cyan-700 flex items-center justify-center text-2xl mx-auto mb-3 font-bold">
              <i class="fa-solid fa-user-doctor"></i>
            </div>
            <h4 class="font-bold text-base text-slate-900">dr. Aldrich Joerellie</h4>
            <p class="text-xs font-semibold text-cyan-700 mt-0.5">Dokter Pemeriksa & Bedah Minor</p>
            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Spesialisasi kesehatan anak, tindakan medis minor, dan perawatan gigi anak terpadu.</p>
          </div>

          <!-- Asisten Dokter 1 -->
          <div class="bento-card p-6 bg-white text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl mx-auto mb-3 font-bold">
              <i class="fa-solid fa-user-nurse"></i>
            </div>
            <h4 class="font-bold text-base text-slate-900">Juan Darren, A.Md.Kep</h4>
            <p class="text-xs font-semibold text-emerald-700 mt-0.5">Asisten Medis 1</p>
            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Pemeriksaan awal tanda vital anak, pengecekan kurva tumbuh kembang dan stok vaksin.</p>
          </div>

          <!-- Asisten Dokter 2 -->
          <div class="bento-card p-6 bg-white text-center">
            <div class="w-16 h-16 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-2xl mx-auto mb-3 font-bold">
              <i class="fa-solid fa-user-nurse"></i>
            </div>
            <h4 class="font-bold text-base text-slate-900">Siti Nurhaliza, A.Md.Kep</h4>
            <p class="text-xs font-semibold text-teal-700 mt-0.5">Asisten Medis 2</p>
            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Persiapan alat tindakan, pemanggilan antrean ramah anak, dan manajemen vaksin.</p>
          </div>

          <!-- Resepsionis & Kasir -->
          <div class="bento-card p-6 bg-white text-center">
            <div class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl mx-auto mb-3 font-bold">
              <i class="fa-solid fa-id-badge"></i>
            </div>
            <h4 class="font-bold text-base text-slate-900">Kristoforus Vieri</h4>
            <p class="text-xs font-semibold text-indigo-700 mt-0.5">Front Office & Kasir</p>
            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Pelayanan pendaftaran ramah keluarga, pencetakan tiket antrean, kasir, dan edukasi jadwal vaksin.</p>
          </div>

        </div>
      </div>
    </section>

  </main>

  <?php include __DIR__ . '/components/footer.php'; ?>

  <!-- Modal Create Queue (From Registration) -->
  <div id="modal-create-queue" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100">
        <h3 class="font-extrabold text-slate-900 text-lg">Terbitkan Nomor Antrean</h3>
        <button type="button" onclick="closeCreateQueueModal()" class="text-slate-400 hover:text-slate-600">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>

      <form onsubmit="submitIssueQueue(event)" class="mt-4 space-y-4">
        <input type="hidden" id="queue-modal-pasien-id" name="id_pasien">
        
        <div id="queue-modal-pasien-info" class="p-3 rounded-2xl bg-cyan-50/70 border border-cyan-200"></div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Poli Tujuan:</label>
          <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
              <input type="radio" name="poli_tujuan" value="umum" checked class="text-cyan-600">
              <span class="text-xs font-semibold">Poli Umum</span>
            </label>
            <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
              <input type="radio" name="poli_tujuan" value="gigi" class="text-cyan-600">
              <span class="text-xs font-semibold">Poli Gigi</span>
            </label>
            <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
              <input type="radio" name="poli_tujuan" value="vaksin" class="text-cyan-600">
              <span class="text-xs font-semibold">Poli Vaksin</span>
            </label>
            <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
              <input type="radio" name="poli_tujuan" value="tindakan" class="text-cyan-600">
              <span class="text-xs font-semibold">Tindakan Ringan</span>
            </label>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Keluhan / Alasan Kunjungan:</label>
          <textarea name="keluhan_awal" rows="2" placeholder="Contoh: Demam sejak kemarin, batuk pilek"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-cyan-500"></textarea>
        </div>

        <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
          <button type="button" onclick="closeCreateQueueModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
            Batal
          </button>
          <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-cyan-700 text-white hover:bg-cyan-800 shadow-sm">
            Terbitkan Antrean
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Print Ticket -->
  <div id="modal-ticket-print" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 printable-area">
      <div class="text-center pb-4 border-b border-dashed border-slate-300">
        <div class="w-10 h-10 rounded-xl bg-cyan-600 text-white flex items-center justify-center text-lg mx-auto mb-1">
          <i class="fa-solid fa-heart-pulse"></i>
        </div>
        <div class="font-extrabold text-base text-slate-900">Klinik Anak Happy</div>
        <div class="text-[11px] text-slate-500">“Penuh Harapan” — Jam: 08.00 - 17.00</div>
        <div id="ticket-print-poli" class="text-xs font-bold text-cyan-800 mt-2 uppercase tracking-wider bg-cyan-50 py-1 rounded-lg">Poli Umum</div>
      </div>

      <div class="text-center py-6">
        <div class="text-xs text-slate-500 font-medium">Nomor Antrean Anda:</div>
        <div id="ticket-print-number" class="text-5xl font-black text-slate-900 my-2 font-mono">U-001</div>
        <div class="text-xs font-semibold text-slate-700" id="ticket-print-name">Kenzo Pratama</div>
        <div class="text-[11px] text-slate-500">RM: <span id="ticket-print-rm">RM-2026-0001</span> • Usia: <span id="ticket-print-age">5 thn</span></div>
      </div>

      <div class="text-[10px] text-slate-400 text-center border-t border-dashed border-slate-300 pt-3 space-y-1">
        <div>Waktu Cetak: <span id="ticket-print-date">06 Nov 2026 15:30</span></div>
        <div>Harap menunggu panggilan di ruang tunggu ramah anak.</div>
      </div>

      <div class="mt-6 flex items-center justify-between no-print">
        <button type="button" onclick="closePrintTicketModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
          Tutup
        </button>
        <button type="button" onclick="window.print()" class="px-5 py-2 rounded-xl text-xs font-bold bg-cyan-700 text-white hover:bg-cyan-800">
          <i class="fa-solid fa-print mr-1.5"></i> Cetak Struk
        </button>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="assets/js/app.js"></script>
  <script src="assets/js/queue-display.js"></script>
  <script src="assets/js/admin.js"></script>

  <script>
    // Vaccine Schedule Recommendations by Child Age
    function calculateVaccineRecommendations(dobStr) {
      const container = document.getElementById('vaccine-calc-result');
      if (!dobStr) return;

      const age = calculateChildAge(dobStr);
      if (!age) return;

      const totalMonths = (age.years * 12) + age.months;

      // Recommended IDAI vaccines categorized
      const schedule = [
        { name: 'Hepatitis B (HB-0)', minMonth: 0, maxMonth: 1, desc: 'Diberikan segera setelah lahir (0-24 jam).' },
        { name: 'BCG (Tuberkulosis)', minMonth: 0, maxMonth: 1, desc: 'Pencegahan TBC, paling baik usia 1 bulan.' },
        { name: 'Polio Tetes (OPV-1)', minMonth: 0, maxMonth: 1, desc: 'Diberikan bersamaan saat lahir / BCG.' },
        { name: 'DTP-HB-Hib 1, Polio 2, PCV 1, Rotavirus 1', minMonth: 2, maxMonth: 3, desc: 'Imunisasi dasar lengkap tahap 2 bulan.' },
        { name: 'DTP-HB-Hib 2, Polio 3, PCV 2, Rotavirus 2', minMonth: 3, maxMonth: 4, desc: 'Imunisasi dasar lengkap tahap 3 bulan.' },
        { name: 'DTP-HB-Hib 3, Polio 4, PCV 3, Rotavirus 3', minMonth: 4, maxMonth: 5, desc: 'Imunisasi dasar lengkap tahap 4 bulan.' },
        { name: 'Influenza Tahunan 1 & 2', minMonth: 6, maxMonth: 12, desc: 'Diberikan mulai usia 6 bulan, diulang tahunan.' },
        { name: 'Campak-Rubella (MR) / Polio Suntik (IPV)', minMonth: 9, maxMonth: 12, desc: 'Pencegahan campak dan cacar air tahap dasar.' },
        { name: 'PCV Booster & Japanese Encephalitis', minMonth: 12, maxMonth: 18, desc: 'Booster radang paru dan radang otak.' },
        { name: 'DTP-HB-Hib Booster & MR Booster', minMonth: 18, maxMonth: 24, desc: 'Penguat antibodi sebelum usia balita.' },
        { name: 'Vaksin Pra-Sekolah & Imunisasi BIAS', minMonth: 60, maxMonth: 180, desc: 'Booster DT, Td, HPV pada usia sekolah.' }
      ];

      const dueNow = [];
      const upcoming = [];
      const completed = [];

      schedule.forEach(v => {
        if (totalMonths > v.maxMonth) {
          completed.push(v);
        } else if (totalMonths >= v.minMonth && totalMonths <= v.maxMonth) {
          dueNow.push(v);
        } else {
          upcoming.push(v);
        }
      });

      container.innerHTML = `
        <div class="space-y-4">
          <div class="flex items-center justify-between border-b border-white/10 pb-3">
            <div>
              <div class="text-xs text-cyan-300 font-semibold">Usia Buah Hati:</div>
              <div class="text-xl font-bold text-white">${age.text} (${totalMonths} bulan)</div>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold ${age.isUnder15 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'}">
              ${age.isUnder15 ? 'Kriteria Pasien Anak' : 'Usia &gt;= 15 thn'}
            </span>
          </div>

          <!-- Due Now -->
          <div>
            <div class="text-xs font-bold text-amber-300 mb-2 flex items-center gap-1.5">
              <i class="fa-solid fa-clock text-amber-400"></i> Vaksin Dianjurkan Saat Ini:
            </div>
            ${dueNow.length > 0 ? dueNow.map(d => `
              <div class="p-2.5 rounded-xl bg-amber-400/10 border border-amber-400/20 text-xs mb-1.5">
                <div class="font-bold text-white">${d.name}</div>
                <div class="text-[11px] text-slate-300 mt-0.5">${d.desc}</div>
              </div>
            `).join('') : '<div class="text-xs text-slate-400 italic">Tidak ada vaksin spesifik di bulan ini. Cek vaksin penguat berikutnya.</div>'}
          </div>

          <!-- Upcoming -->
          <div>
            <div class="text-xs font-bold text-cyan-300 mb-2 flex items-center gap-1.5">
              <i class="fa-solid fa-calendar-check text-cyan-400"></i> Jadwal Imunisasi Selanjutnya:
            </div>
            ${upcoming.slice(0, 3).map(u => `
              <div class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-xs mb-1.5">
                <div class="font-semibold text-white">${u.name} <span class="text-[10px] text-cyan-300">(Usia ${u.minMonth} bln)</span></div>
                <div class="text-[11px] text-slate-400 mt-0.5">${u.desc}</div>
              </div>
            `).join('')}
          </div>
        </div>
      `;
    }
  </script>
</body>
</html>

