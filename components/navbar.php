<?php
// components/navbar.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="glass-nav sticky top-0 z-40 transition-all">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-20">
      
      <!-- Brand Logo -->
      <a href="index.php" class="flex items-center gap-3 group">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-500 to-teal-500 flex items-center justify-center text-white shadow-md shadow-cyan-500/20 group-hover:scale-105 transition-transform">
          <i class="fa-solid fa-heart-pulse text-2xl"></i>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="font-extrabold text-xl tracking-tight text-slate-900">Klinik Anak Happy</span>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-800 border border-cyan-200">Penuh Harapan</span>
          </div>
          <p class="text-xs text-slate-500 font-medium">Layanan Kesehatan Anak Khusus &lt; 15 Tahun</p>
        </div>
      </a>

      <!-- Navigation Links -->
      <nav class="hidden md:flex items-center gap-1 bg-slate-100/80 p-1.5 rounded-2xl border border-slate-200/60">
        <a href="index.php" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all <?= ($currentPage === 'index.php') ? 'bg-white text-cyan-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
          <i class="fa-solid fa-house text-xs mr-1.5"></i> Beranda
        </a>
        <a href="antrian.php" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all <?= ($currentPage === 'antrian.php') ? 'bg-white text-cyan-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
          <i class="fa-solid fa-users-viewfinder text-xs mr-1.5"></i> Layar Antrean Live
        </a>
        <a href="index.php#jadwal-vaksin" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 transition-all">
          <i class="fa-solid fa-syringe text-xs mr-1.5"></i> Jadwal Vaksin
        </a>
        <a href="index.php#layanan-kami" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 transition-all">
          <i class="fa-solid fa-notes-medical text-xs mr-1.5"></i> Layanan Poli
        </a>
      </nav>

      <!-- Right CTAs -->
      <div class="flex items-center gap-3">
        <!-- Clinic Hours Status -->
        <div class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200 text-xs text-slate-600">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Buka 08:00 - 17:00 WIB</span>
        </div>

        <!-- Admin / Staff Button -->
        <a href="admin.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-cyan-700 text-white hover:bg-cyan-800 shadow-sm transition-all hover:scale-[1.02]">
          <i class="fa-solid fa-user-doctor text-sm"></i>
          <span>Portal Staf & Dokter</span>
        </a>
      </div>

    </div>
  </div>
</header>

