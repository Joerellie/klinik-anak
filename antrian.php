<?php
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Layar Monitor Antrean Pasien - Klinik Anak Happy</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Custom UI/UX Styles -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-slate-900 text-slate-100 antialiased min-h-screen flex flex-col font-sans selection:bg-cyan-500 selection:text-white">

  <!-- Top Real-time Header for Display TV & Mobile Parents -->
  <header class="bg-slate-950/80 backdrop-blur-md border-b border-slate-800/80 sticky top-0 z-40 px-4 sm:px-8 py-3.5">
    <div class="max-w-[1700px] mx-auto flex flex-wrap items-center justify-between gap-4">
      
      <!-- Clinic Brand -->
      <div class="flex items-center gap-3">
        <a href="index.php" class="w-11 h-11 rounded-2xl bg-gradient-to-br from-cyan-500 to-teal-500 flex items-center justify-center text-white shadow-lg shadow-cyan-500/20 hover:scale-105 transition-transform">
          <i class="fa-solid fa-heart-pulse text-xl"></i>
        </a>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl font-black text-white tracking-tight">Klinik Anak Happy</h1>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-cyan-950 text-cyan-300 border border-cyan-800">Live Queue Monitor</span>
          </div>
          <p class="text-xs text-slate-400">Pemantauan Antrean Pasien Ruang Dokter & Ruang Persiapan</p>
        </div>
      </div>

      <!-- Center: Clinic Operating Hours Indicator -->
      <div class="flex items-center gap-3">
        <div id="clinic-status-badge" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-slate-900 border border-slate-700 rounded-full text-xs">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="text-xs font-semibold text-emerald-400">Melayani 08.00 - 17.00 WIB</span>
        </div>

        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono font-bold text-cyan-400">
          <i class="fa-regular fa-clock text-cyan-500"></i>
          <span id="live-clock">--:--:-- WIB</span>
        </div>
      </div>

      <!-- Right Controls: Audio Toggle, Fullscreen & Back Link -->
      <div class="flex items-center gap-2.5">
        <!-- Voice Announcement Toggle -->
        <button id="btn-toggle-voice" onclick="toggleAutoVoice()"
                class="flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-950/80 border border-cyan-700/60 text-cyan-300 text-xs font-semibold hover:bg-cyan-900/50 transition-all">
          <i class="fa-solid fa-volume-high text-cyan-400"></i>
          <span class="hidden sm:inline">Audio Suara: AKTIF</span>
        </button>

        <!-- Sound Test Button -->
        <button onclick="playQueueChime(() => announceQueue('U-001', 'Kenzo Pratama', 'Ruang Dokter Spesialis Anak'))"
                class="p-2 sm:px-3 sm:py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium border border-slate-700 flex items-center gap-1.5"
                title="Uji coba suara lonceng dan panggilan antrean">
          <i class="fa-solid fa-bullhorn text-xs"></i>
          <span class="hidden sm:inline">Tes Panggilan</span>
        </button>

        <!-- Fullscreen TV Mode -->
        <button onclick="toggleFullscreen()"
                class="p-2 sm:px-3 sm:py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium border border-slate-700 flex items-center gap-1.5"
                title="Tampilkan layar penuh (TV Mode)">
          <i class="fa-solid fa-expand text-xs"></i>
          <span class="hidden sm:inline">Layar Penuh TV</span>
        </button>

        <a href="index.php" class="px-3.5 py-1.5 rounded-xl bg-cyan-700 hover:bg-cyan-600 text-white text-xs font-bold transition-all flex items-center gap-1.5">
          <i class="fa-solid fa-house text-xs"></i>
          <span class="hidden sm:inline">Beranda</span>
        </a>
      </div>

    </div>
  </header>

  <!-- Main Live Queue Display Content -->
  <main class="flex-grow p-4 sm:p-6 lg:p-8 max-w-[1700px] mx-auto w-full">
    
    <!-- Quick Search Strip for Waiting Parents -->
    <div class="mb-6 p-4 rounded-2xl bg-slate-950 border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-cyan-950 border border-cyan-800 text-cyan-400 flex items-center justify-center text-sm font-bold">
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <div>
          <div class="text-sm font-bold text-white">Lacak Posisi Antrean Anda</div>
          <div class="text-xs text-slate-400">Masukkan kode tiket (misal: U-001) atau nomor Rekam Medis anak Anda</div>
        </div>
      </div>

      <div class="flex items-center gap-2 w-full md:w-auto">
        <input type="text" id="ticket-search-input" placeholder="Contoh: U-001 / RM-2026-0001"
               class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 font-mono font-semibold uppercase placeholder:text-slate-500 w-full md:w-64"
               onkeydown="if(event.key === 'Enter') checkMyTicket()">
        <button onclick="checkMyTicket()"
                class="px-4 py-2 rounded-xl bg-cyan-700 hover:bg-cyan-600 text-white text-xs font-bold whitespace-nowrap transition-all">
          Cari Tiket
        </button>
      </div>
    </div>

    <!-- Ticket Search Result Display (Appears if searched) -->
    <div id="ticket-search-result" class="mb-6 empty:hidden"></div>

    <!-- 4 Main Poli Cards Grid -->
    <div id="poli-grid-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
      
      <!-- 1. POLI UMUM / ANAK SAKIT -->
      <div id="poli-card-umum" class="bento-card bg-slate-950/90 border border-slate-800/80 rounded-3xl p-5 flex flex-col justify-between shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-sky-400 to-cyan-500"></div>

        <div>
          <!-- Header -->
          <div class="flex items-center justify-between pb-4 border-b border-slate-800/80 mb-4">
            <div class="flex items-center gap-2.5">
              <div class="w-10 h-10 rounded-2xl bg-sky-950 border border-sky-800 text-sky-400 flex items-center justify-center text-lg shadow-sm">
                <i class="fa-solid fa-stethoscope"></i>
              </div>
              <div>
                <h3 class="font-extrabold text-base text-white">Poli Anak Umum</h3>
                <span class="text-[11px] text-sky-400 font-medium">dr. Aldrich Joerellie, Sp.A</span>
              </div>
            </div>
            <span class="waiting-count-badge text-xs font-bold text-sky-300 bg-sky-950/80 border border-sky-800 px-3 py-1 rounded-full">0 Menunggu</span>
          </div>

          <!-- Section: Currently Inside Doctor Room -->
          <div class="mb-4">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Sedang Diperiksa:</span>
            <div class="current-doctor-box min-h-[120px] flex flex-col justify-center">
              <div class="text-xs text-slate-500 text-center py-4">Memuat antrean...</div>
            </div>
          </div>

          <!-- Section: Currently In Assistant Vital Check -->
          <div class="mb-4">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Pemeriksaan Awal (Tanda Vital):</span>
            <div class="current-asst-box"></div>
          </div>
        </div>

        <!-- Section: Next In Line Waiting List -->
        <div class="pt-4 border-t border-slate-800/80">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Antrean Berikutnya</span>
            <i class="fa-solid fa-list-ol text-slate-500 text-xs"></i>
          </div>
          <ul class="waiting-list-items space-y-1.5 min-h-[90px]"></ul>
        </div>
      </div>

      <!-- 2. POLI GIGI ANAK -->
      <div id="poli-card-gigi" class="bento-card bg-slate-950/90 border border-slate-800/80 rounded-3xl p-5 flex flex-col justify-between shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-400 to-teal-500"></div>

        <div>
          <!-- Header -->
          <div class="flex items-center justify-between pb-4 border-b border-slate-800/80 mb-4">
            <div class="flex items-center gap-2.5">
              <div class="w-10 h-10 rounded-2xl bg-emerald-950 border border-emerald-800 text-emerald-400 flex items-center justify-center text-lg shadow-sm">
                <i class="fa-solid fa-tooth"></i>
              </div>
              <div>
                <h3 class="font-extrabold text-base text-white">Poli Gigi Anak</h3>
                <span class="text-[11px] text-emerald-400 font-medium">Perawatan Ramah Anak</span>
              </div>
            </div>
            <span class="waiting-count-badge text-xs font-bold text-emerald-300 bg-emerald-950/80 border border-emerald-800 px-3 py-1 rounded-full">0 Menunggu</span>
          </div>

          <!-- Section: Currently Inside Doctor Room -->
          <div class="mb-4">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Sedang Diperiksa:</span>
            <div class="current-doctor-box min-h-[120px] flex flex-col justify-center">
              <div class="text-xs text-slate-500 text-center py-4">Memuat antrean...</div>
            </div>
          </div>

          <!-- Section: Currently In Assistant Vital Check -->
          <div class="mb-4">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Pemeriksaan Awal (Tanda Vital):</span>
            <div class="current-asst-box"></div>
          </div>
        </div>

        <!-- Section: Next In Line Waiting List -->
        <div class="pt-4 border-t border-slate-800/80">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Antrean Berikutnya</span>
            <i class="fa-solid fa-list-ol text-slate-500 text-xs"></i>
          </div>
          <ul class="waiting-list-items space-y-1.5 min-h-[90px]"></ul>
        </div>
      </div>

      <!-- 3. POLI VAKSIN & IMUNISASI -->
      <div id="poli-card-vaksin" class="bento-card bg-slate-950/90 border border-slate-800/80 rounded-3xl p-5 flex flex-col justify-between shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-indigo-400 to-purple-500"></div>

        <div>
          <!-- Header -->
          <div class="flex items-center justify-between pb-4 border-b border-slate-800/80 mb-4">
            <div class="flex items-center gap-2.5">
              <div class="w-10 h-10 rounded-2xl bg-indigo-950 border border-indigo-800 text-indigo-400 flex items-center justify-center text-lg shadow-sm">
                <i class="fa-solid fa-syringe"></i>
              </div>
              <div>
                <h3 class="font-extrabold text-base text-white">Poli Vaksinasi</h3>
                <span class="text-[11px] text-indigo-400 font-medium">Jadwal Imunisasi IDAI</span>
              </div>
            </div>
            <span class="waiting-count-badge text-xs font-bold text-indigo-300 bg-indigo-950/80 border border-indigo-800 px-3 py-1 rounded-full">0 Menunggu</span>
          </div>

          <!-- Section: Currently Inside Doctor Room -->
          <div class="mb-4">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Sedang Diperiksa:</span>
            <div class="current-doctor-box min-h-[120px] flex flex-col justify-center">
              <div class="text-xs text-slate-500 text-center py-4">Memuat antrean...</div>
            </div>
          </div>

          <!-- Section: Currently In Assistant Vital Check -->
          <div class="mb-4">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Pemeriksaan Awal (Tanda Vital):</span>
            <div class="current-asst-box"></div>
          </div>
        </div>

        <!-- Section: Next In Line Waiting List -->
        <div class="pt-4 border-t border-slate-800/80">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Antrean Berikutnya</span>
            <i class="fa-solid fa-list-ol text-slate-500 text-xs"></i>
          </div>
          <ul class="waiting-list-items space-y-1.5 min-h-[90px]"></ul>
        </div>
      </div>

      <!-- 4. POLI TINDAKAN MEDIS RINGAN -->
      <div id="poli-card-tindakan" class="bento-card bg-slate-950/90 border border-slate-800/80 rounded-3xl p-5 flex flex-col justify-between shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-rose-400 to-pink-500"></div>

        <div>
          <!-- Header -->
          <div class="flex items-center justify-between pb-4 border-b border-slate-800/80 mb-4">
            <div class="flex items-center gap-2.5">
              <div class="w-10 h-10 rounded-2xl bg-rose-950 border border-rose-800 text-rose-400 flex items-center justify-center text-lg shadow-sm">
                <i class="fa-solid fa-kit-medical"></i>
              </div>
              <div>
                <h3 class="font-extrabold text-base text-white">Tindakan Ringan</h3>
                <span class="text-[11px] text-rose-400 font-medium">Nebulizer & Luka Lecet</span>
              </div>
            </div>
            <span class="waiting-count-badge text-xs font-bold text-rose-300 bg-rose-950/80 border border-rose-800 px-3 py-1 rounded-full">0 Menunggu</span>
          </div>

          <!-- Section: Currently Inside Doctor Room -->
          <div class="mb-4">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Sedang Diperiksa:</span>
            <div class="current-doctor-box min-h-[120px] flex flex-col justify-center">
              <div class="text-xs text-slate-500 text-center py-4">Memuat antrean...</div>
            </div>
          </div>

          <!-- Section: Currently In Assistant Vital Check -->
          <div class="mb-4">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Pemeriksaan Awal (Tanda Vital):</span>
            <div class="current-asst-box"></div>
          </div>
        </div>

        <!-- Section: Next In Line Waiting List -->
        <div class="pt-4 border-t border-slate-800/80">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Antrean Berikutnya</span>
            <i class="fa-solid fa-list-ol text-slate-500 text-xs"></i>
          </div>
          <ul class="waiting-list-items space-y-1.5 min-h-[90px]"></ul>
        </div>
      </div>

    </div>

    <!-- Bottom Instructions Footer for Waiting Lounge -->
    <div class="mt-8 p-4 rounded-2xl bg-slate-950/50 border border-slate-800/60 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-3">
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-circle-info text-cyan-400"></i>
        <span>Saat nomor antrean Anda dipanggil melalui pengeras suara, silakan menuju pintu ruangan yang tertera pada layar.</span>
      </div>
      <div class="text-slate-500">
        Pembaruan Otomatis Setiap 4 Detik • Sistem Informasi Klinik Anak Happy
      </div>
    </div>

  </main>

  <!-- Scripts -->
  <script src="assets/js/app.js"></script>
  <script src="assets/js/queue-display.js"></script>
</body>
</html>

