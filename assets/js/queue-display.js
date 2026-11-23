/**
 * Klinik Anak Happy - Live Queue Board Controller
 */

let lastAnnouncedCall = null;
let autoVoiceEnabled = true;

// Poli configuration with icons and clean color schemes
const POLI_META = {
  umum: {
    name: 'Poli Anak Sakit & Umum',
    code: 'U',
    icon: 'fa-solid fa-stethoscope',
    color: 'sky',
    badgeClass: 'bg-sky-50 text-sky-700 border-sky-200',
    headerBg: 'from-sky-500 to-cyan-600',
    roomDoctor: 'Ruang Dokter 1',
    roomAsisten: 'Ruang Tanda Vital 1'
  },
  gigi: {
    name: 'Poli Gigi Anak',
    code: 'G',
    icon: 'fa-solid fa-tooth',
    color: 'emerald',
    badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    headerBg: 'from-emerald-500 to-teal-600',
    roomDoctor: 'Ruang Periksa Gigi',
    roomAsisten: 'Ruang Persiapan Gigi'
  },
  vaksin: {
    name: 'Poli Vaksin & Imunisasi',
    code: 'V',
    icon: 'fa-solid fa-syringe',
    color: 'indigo',
    badgeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    headerBg: 'from-indigo-500 to-violet-600',
    roomDoctor: 'Ruang Vaksinasi Dokter',
    roomAsisten: 'Ruang Skrining Vaksin'
  },
  tindakan: {
    name: 'Poli Tindakan Medis Ringan',
    code: 'T',
    icon: 'fa-solid fa-kit-medical',
    color: 'rose',
    badgeClass: 'bg-rose-50 text-rose-700 border-rose-200',
    headerBg: 'from-rose-500 to-pink-600',
    roomDoctor: 'Ruang Tindakan Medis',
    roomAsisten: 'Ruang Tindakan Awal'
  }
};

async function fetchLiveQueueData() {
  try {
    const res = await fetch('api/queues.php?action=live');
    const data = await res.json();
    if (!data.success) return;

    renderLiveBoard(data);
    handleVoiceAnnouncement(data.latest_call);
  } catch (err) {
    console.error('Failed to fetch live queue data:', err);
  }
}

function renderLiveBoard(data) {
  // Update server clock & date
  const clockEl = document.getElementById('live-clock');
  if (clockEl) clockEl.textContent = data.server_time + ' WIB';
  
  const statusBadge = document.getElementById('clinic-status-badge');
  if (statusBadge) {
    if (data.is_open) {
      statusBadge.innerHTML = `<span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span> <span class="text-xs font-semibold text-emerald-800">Sedang Melayani (08.00 - 17.00)</span>`;
      statusBadge.className = 'inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 border border-emerald-200 rounded-full';
    } else {
      statusBadge.innerHTML = `<span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span> <span class="text-xs font-semibold text-amber-800">Klinik Tutup (Buka 08.00 - 17.00)</span>`;
      statusBadge.className = 'inline-flex items-center gap-2 px-3 py-1 bg-amber-50 border border-amber-200 rounded-full';
    }
  }

  // Render each Poli card
  const container = document.getElementById('poli-grid-container');
  if (!container) return;

  const polis = ['umum', 'gigi', 'vaksin', 'tindakan'];
  
  polis.forEach(poliKey => {
    const info = data.summary[poliKey] || { total: 0, waiting_count: 0, current_doctor: null, current_assistant: null, waiting_list: [] };
    const meta = POLI_META[poliKey];
    const cardEl = document.getElementById(`poli-card-${poliKey}`);

    if (cardEl) {
      // 1. Current in Doctor Room
      const docBox = cardEl.querySelector('.current-doctor-box');
      if (docBox) {
        if (info.current_doctor) {
          docBox.innerHTML = `
            <div class="flex items-center justify-between mb-1.5">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-cyan-100 text-cyan-800 border border-cyan-300 pulse-badge">
                <span class="w-2 h-2 rounded-full bg-cyan-600 inline-block animate-ping"></span> Sedang Diperiksa Dokter
              </span>
              <span class="text-xs text-slate-500 font-medium">${meta.roomDoctor}</span>
            </div>
            <div class="text-3xl font-extrabold text-slate-900 tracking-tight">${info.current_doctor.nomor_antrean}</div>
            <div class="text-sm font-semibold text-cyan-900 mt-0.5">${info.current_doctor.nama_anak_masked}</div>
          `;
          docBox.className = 'current-doctor-box p-4 rounded-2xl bg-gradient-to-br from-cyan-50/80 to-sky-50/50 border border-cyan-200 shadow-sm';
        } else {
          docBox.innerHTML = `
            <div class="text-xs font-medium text-slate-500 mb-1">Ruang Dokter</div>
            <div class="text-xl font-bold text-slate-400">---</div>
            <div class="text-xs text-slate-500 mt-1">Belum ada pasien di ruangan</div>
          `;
          docBox.className = 'current-doctor-box p-4 rounded-2xl bg-slate-50 border border-dashed border-slate-200 text-center';
        }
      }

      // 2. Current in Assistant Room (Pemeriksaan Awal)
      const asstBox = cardEl.querySelector('.current-asst-box');
      if (asstBox) {
        if (info.current_assistant) {
          asstBox.innerHTML = `
            <div class="flex items-center justify-between mb-1">
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span> Tanda Vital Asisten
              </span>
              <span class="text-[11px] text-slate-500 font-medium">${meta.roomAsisten}</span>
            </div>
            <div class="flex items-baseline justify-between">
              <span class="text-xl font-bold text-slate-800">${info.current_assistant.nomor_antrean}</span>
              <span class="text-xs font-medium text-slate-600">${info.current_assistant.nama_anak_masked}</span>
            </div>
          `;
          asstBox.className = 'current-asst-box p-3 rounded-xl bg-amber-50/60 border border-amber-200';
        } else {
          asstBox.innerHTML = `
            <div class="text-[11px] text-slate-500">Ruang Persiapan: <span class="font-medium text-slate-600">Menunggu panggilan</span></div>
          `;
          asstBox.className = 'current-asst-box p-2.5 rounded-xl bg-slate-50/80 border border-slate-200 text-center';
        }
      }

      // 3. Waiting List
      const waitingListEl = cardEl.querySelector('.waiting-list-items');
      if (waitingListEl) {
        if (info.waiting_list.length > 0) {
          waitingListEl.innerHTML = info.waiting_list.slice(0, 4).map((w, idx) => `
            <li class="flex items-center justify-between py-1.5 px-2 rounded-lg ${idx === 0 ? 'bg-sky-50 text-sky-950 font-medium' : 'text-slate-600'} text-xs">
              <span class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-bold ${idx === 0 ? 'bg-sky-200 text-sky-800' : 'bg-slate-100 text-slate-500'}">${idx + 1}</span>
                <span class="font-bold">${w.nomor_antrean}</span>
              </span>
              <span class="truncate max-w-[140px] text-slate-500">${w.nama_anak_masked}</span>
            </li>
          `).join('');

          if (info.waiting_list.length > 4) {
            waitingListEl.innerHTML += `
              <div class="text-center text-[11px] text-slate-500 pt-1">+${info.waiting_list.length - 4} antrean lainnya</div>
            `;
          }
        } else {
          waitingListEl.innerHTML = `<li class="text-center py-3 text-xs text-slate-400">Tidak ada antrean menunggu</li>`;
        }
      }

      // 4. Waiting count badge
      const countEl = cardEl.querySelector('.waiting-count-badge');
      if (countEl) countEl.textContent = `${info.waiting_count} Menunggu`;
    }
  });
}

function handleVoiceAnnouncement(latestCall) {
  if (!autoVoiceEnabled || !latestCall) return;

  const callKey = `${latestCall.nomor_antrean}-${latestCall.status}`;
  if (lastAnnouncedCall !== callKey) {
    lastAnnouncedCall = callKey;
    
    const meta = POLI_META[latestCall.poli_tujuan] || POLI_META.umum;
    const room = latestCall.status === 'ruang_dokter' ? meta.roomDoctor : meta.roomAsisten;

    announceQueue(latestCall.nomor_antrean, latestCall.nama_anak, room);
  }
}

// Check ticket position by ticket number / RM
async function checkMyTicket() {
  const input = document.getElementById('ticket-search-input');
  const resultDiv = document.getElementById('ticket-search-result');
  if (!input || !resultDiv) return;

  const code = input.value.trim();
  if (!code) {
    showToast('Masukkan nomor antrean atau nomor Rekam Medis anak Anda.', 'warning');
    return;
  }

  resultDiv.innerHTML = `<div class="p-4 text-center text-sm text-slate-500"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Mencari antrean Anda...</div>`;

  try {
    const res = await fetch(`api/queues.php?action=track_ticket&code=${encodeURIComponent(code)}`);
    const data = await res.json();

    if (!data.success) {
      resultDiv.innerHTML = `
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3">
          <i class="fa-solid fa-circle-exclamation text-lg mt-0.5"></i>
          <div>
            <div class="font-bold">Antrean Tidak Ditemukan</div>
            <div class="text-xs text-rose-600 mt-0.5">${data.error} Pastikan nomor sudah sesuai untuk pendaftaran hari ini.</div>
          </div>
        </div>
      `;
      return;
    }

    const t = data.ticket;
    const meta = POLI_META[t.poli_tujuan] || POLI_META.umum;

    let statusPill = '';
    let statusDesc = '';

    if (t.status === 'ruang_dokter') {
      statusPill = '<span class="px-3 py-1 rounded-full bg-cyan-100 text-cyan-800 text-xs font-bold pulse-badge">Sedang Di Ruang Dokter</span>';
      statusDesc = 'Silakan segera masuk ke ruangan dokter.';
    } else if (t.status === 'ruang_asisten') {
      statusPill = '<span class="px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-bold">Pemeriksaan Awal (Asisten)</span>';
      statusDesc = 'Sedang dalam pengecekan tanda vital (suhu, berat & tinggi badan).';
    } else if (t.status === 'menunggu_dokter') {
      statusPill = '<span class="px-3 py-1 rounded-full bg-indigo-100 text-indigo-800 text-xs font-bold">Siap Masuk Dokter</span>';
      statusDesc = `Pemeriksaan awal selesai. Menunggu giliran masuk ruang dokter (${t.people_ahead} orang di depan Anda).`;
    } else if (t.status === 'menunggu_asisten') {
      statusPill = '<span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">Menunggu Panggilan</span>';
      statusDesc = `Menunggu pemeriksaan awal asisten. Estimasi sisa tunggu: ~${t.estimated_minutes_remaining} menit.`;
    } else if (t.status === 'kasir') {
      statusPill = '<span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold">Menuju Kasir</span>';
      statusDesc = 'Pemeriksaan selesai. Silakan menuju meja resepsionis/kasir untuk nota tagihan & resep obat.';
    } else if (t.status === 'selesai') {
      statusPill = '<span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">Pelayanan Selesai</span>';
      statusDesc = 'Terima kasih telah berkunjung ke Klinik Anak Happy. Semoga lekas sembuh!';
    }

    resultDiv.innerHTML = `
      <div class="p-5 rounded-2xl bg-white border border-cyan-200 shadow-md">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">${meta.name}</div>
            <div class="text-3xl font-extrabold text-cyan-900 mt-0.5">${t.nomor_antrean}</div>
            <div class="text-sm font-bold text-slate-800 mt-1">${t.nama_anak} <span class="font-normal text-slate-500">(${t.usia_formatted})</span></div>
            <div class="text-xs text-slate-500">No. Rekam Medis: <span class="font-mono font-semibold">${t.kode_rm}</span></div>
          </div>
          <div>${statusPill}</div>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
          <div>
            <span class="text-slate-500">Posisi Antrean:</span>
            <span class="font-bold text-slate-800 ml-1">${t.people_ahead === 0 ? 'Giliran Anda Berikutnya!' : t.people_ahead + ' Orang di Depan'}</span>
          </div>
          <div>
            <span class="text-slate-500">Estimasi Tunggu:</span>
            <span class="font-bold text-cyan-700 ml-1">~${t.estimated_minutes_remaining} Menit</span>
          </div>
        </div>

        <div class="mt-3 p-2.5 rounded-xl bg-slate-50 text-xs text-slate-600 flex items-center gap-2">
          <i class="fa-solid fa-bell text-cyan-600"></i>
          <span>${statusDesc}</span>
        </div>
      </div>
    `;

  } catch (err) {
    resultDiv.innerHTML = `<div class="p-3 text-center text-xs text-rose-600">Gagal memuat status tiket. Silakan coba lagi.</div>`;
  }
}

// Toggle Voice Announcement
function toggleAutoVoice() {
  autoVoiceEnabled = !autoVoiceEnabled;
  const btn = document.getElementById('btn-toggle-voice');
  if (btn) {
    if (autoVoiceEnabled) {
      btn.innerHTML = `<i class="fa-solid fa-volume-high text-cyan-600"></i> <span class="text-xs font-semibold text-slate-700">Audio Suara: AKTIF</span>`;
      btn.className = 'flex items-center gap-2 px-3 py-1.5 rounded-full bg-cyan-50 border border-cyan-200 text-xs';
      showToast('Audio panggilan suara diaktifkan.', 'info');
    } else {
      btn.innerHTML = `<i class="fa-solid fa-volume-xmark text-slate-400"></i> <span class="text-xs font-semibold text-slate-500">Audio Suara: NONAKTIF</span>`;
      btn.className = 'flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200 text-xs';
      showToast('Audio panggilan suara dinonaktifkan.', 'info');
    }
  }
}

// Fullscreen TV Mode
function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(err => {
      console.warn('Fullscreen denied:', err);
    });
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    }
  }
}

// Start polling
document.addEventListener('DOMContentLoaded', () => {
  fetchLiveQueueData();
  setInterval(fetchLiveQueueData, 4000);
});

