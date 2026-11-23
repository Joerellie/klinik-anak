<?php
// components/footer.php
?>
<footer class="bg-white border-t border-slate-200 mt-20 pt-16 pb-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
      
      <!-- Brand & About -->
      <div class="md:col-span-2">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-cyan-600 flex items-center justify-center text-white font-bold text-xl shadow-md">
            <i class="fa-solid fa-heart-pulse"></i>
          </div>
          <div>
            <span class="font-extrabold text-lg text-slate-900">Klinik Anak Happy</span>
            <p class="text-xs text-slate-500">“Penuh Harapan” — Layanan Ramah Buah Hati</p>
          </div>
        </div>
        <p class="text-sm text-slate-600 mt-4 max-w-md leading-relaxed">
          Fasilitas kesehatan khusus anak di bawah usia 15 tahun. Mengedepankan pelayanan hangat, ramah anak tanpa rasa takut, sistem rekam medis digital terpadu, dan pemantauan antrean real-time.
        </p>
        <div class="mt-4 flex items-center gap-4 text-xs text-slate-500">
          <span class="flex items-center gap-1.5"><i class="fa-regular fa-clock text-cyan-600"></i> Jam Operasional: 08.00 - 17.00 WIB</span>
          <span class="flex items-center gap-1.5"><i class="fa-solid fa-phone text-emerald-600"></i> (024) 876-5432</span>
        </div>
      </div>

      <!-- Quick Links -->
      <div>
        <h4 class="font-bold text-sm text-slate-900 mb-4">Layanan Spesialis</h4>
        <ul class="space-y-2.5 text-xs text-slate-600 font-medium">
          <li><a href="index.php#layanan-kami" class="hover:text-cyan-700 flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px] text-slate-400"></i> Poli Anak Sakit & Umum</a></li>
          <li><a href="index.php#layanan-kami" class="hover:text-cyan-700 flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px] text-slate-400"></i> Poli Gigi Anak & Perawatan</a></li>
          <li><a href="index.php#layanan-kami" class="hover:text-cyan-700 flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px] text-slate-400"></i> Vaksinasi & Imunisasi IDAI</a></li>
          <li><a href="index.php#layanan-kami" class="hover:text-cyan-700 flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px] text-slate-400"></i> Tindakan Medis Ringan & Nebulizer</a></li>
          <li><a href="antrian.php" class="hover:text-cyan-700 flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px] text-slate-400"></i> Live Queue Board Monitor</a></li>
        </ul>
      </div>

      <!-- Team & Development -->
      <div>
        <h4 class="font-bold text-sm text-slate-900 mb-4">Tim Pengembang Sistem</h4>
        <div class="space-y-2 text-xs text-slate-600">
          <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
            <div class="font-semibold text-slate-800">1. Kristoforus Vieri R. K.</div>
            <div class="text-[11px] text-slate-500">24.N1.0001 • Project Manager & Analyst</div>
          </div>
          <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
            <div class="font-semibold text-slate-800">2. Juan Darren</div>
            <div class="text-[11px] text-slate-500">24.N1.0011 • Database & UI/UX / Tester</div>
          </div>
          <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
            <div class="font-semibold text-slate-800">3. Aldrich Joerellie</div>
            <div class="text-[11px] text-slate-500">24.N1.0032 • Web Developer (Full-Stack)</div>
          </div>
        </div>
      </div>

    </div>

    <!-- Bottom copyright -->
    <div class="mt-12 pt-8 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-4">
      <div>© <?= date('Y') ?> Sistem Informasi Pelayanan Klinik Anak Happy "Penuh Harapan". All rights reserved.</div>
      <div class="flex items-center gap-4">
        <span>Akses Standar WCAG & Ramah Anak</span>
        <span>•</span>
        <span>Jam Operasional 08:00 - 17:00</span>
      </div>
    </div>
  </div>
</footer>

