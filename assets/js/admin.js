/**
 * Klinik Anak Happy - Staff & Admin Module Controller
 */

let currentUser = null;
let activeTab = 'resepsionis'; // resepsionis, asisten, dokter, stok, laporan
let selectedDentalStatus = 'karies';
let dentalRecords = {}; // { toothId: status }

// Role demo accounts
const DEMO_ACCOUNTS = [
  { username: 'resepsionis', name: 'Kristoforus Vieri', role: 'resepsionis', label: 'Resepsionis & Kasir' },
  { username: 'asisten1', name: 'Juan Darren', role: 'asisten', label: 'Asisten Dokter 1' },
  { username: 'dokter', name: 'dr. Aldrich Joerellie, Sp.A', role: 'dokter', label: 'Dokter Spesialis Anak' },
  { username: 'admin', name: 'Administrator', role: 'admin', label: 'Super Admin' }
];

// Initialize on Load
document.addEventListener('DOMContentLoaded', () => {
  initCurrentUser();
  initToothChart();
});

// Check Session & Render User Header
async function initCurrentUser() {
  try {
    const res = await fetch('api/auth.php?action=current_user');
    const data = await res.json();
    
    if (data.logged_in) {
      currentUser = data.user;
      renderUserHeader();
      applyRoleTabs(currentUser.role);
    } else {
      // Default auto-login as Resepsionis for seamless demo if not logged in
      await switchRoleAccount('resepsionis');
    }
  } catch (e) {
    console.error('Failed to init user:', e);
  }
}

// Switch Role Demo Account
async function switchRoleAccount(username) {
  try {
    const res = await fetch('api/auth.php?action=switch_role', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username })
    });
    const data = await res.json();
    if (data.success) {
      currentUser = data.user;
      renderUserHeader();
      applyRoleTabs(currentUser.role);
      showToast(`Beralih peran ke: ${currentUser.nama_lengkap} (${currentUser.role})`, 'success');
      loadActiveTabData();
    }
  } catch (e) {
    showToast('Gagal beralih peran', 'error');
  }
}

function renderUserHeader() {
  const nameEl = document.getElementById('user-display-name');
  const roleEl = document.getElementById('user-display-role');
  if (nameEl) nameEl.textContent = currentUser.nama_lengkap;
  if (roleEl) roleEl.textContent = currentUser.spesialisasi || currentUser.role.toUpperCase();

  // Highlight active role pill
  document.querySelectorAll('.role-switcher-btn').forEach(btn => {
    if (btn.getAttribute('data-username') === currentUser.username) {
      btn.className = 'role-switcher-btn px-3 py-1.5 rounded-full text-xs font-bold bg-cyan-600 text-white shadow-sm';
    } else {
      btn.className = 'role-switcher-btn px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200';
    }
  });
}

function applyRoleTabs(role) {
  // Select default active tab based on role
  if (role === 'resepsionis') {
    switchTab('resepsionis');
  } else if (role === 'asisten') {
    switchTab('asisten');
  } else if (role === 'dokter') {
    switchTab('dokter');
  } else {
    switchTab('resepsionis');
  }
}

function switchTab(tabName) {
  activeTab = tabName;
  
  // Highlight navigation button
  document.querySelectorAll('.nav-tab-btn').forEach(b => {
    if (b.getAttribute('data-tab') === tabName) {
      b.className = 'nav-tab-btn px-4 py-2 rounded-xl text-sm font-bold bg-cyan-50 text-cyan-800 border border-cyan-200 shadow-sm flex items-center gap-2';
    } else {
      b.className = 'nav-tab-btn px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-50 flex items-center gap-2';
    }
  });

  // Show corresponding view container
  document.querySelectorAll('.admin-view-panel').forEach(panel => {
    panel.classList.add('hidden');
  });
  const activePanel = document.getElementById(`panel-${tabName}`);
  if (activePanel) activePanel.classList.remove('hidden');

  loadActiveTabData();
}

function loadActiveTabData() {
  if (activeTab === 'resepsionis') {
    loadResepsionisQueues();
    loadCashierBilling();
  } else if (activeTab === 'asisten') {
    loadAsistenQueues();
  } else if (activeTab === 'dokter') {
    loadDokterQueues();
  } else if (activeTab === 'stok') {
    loadStockTable();
  } else if (activeTab === 'laporan') {
    loadDailyReport();
  }
}

// ==========================================
// 1. RESEPSIONIS MODULE (Pendaftaran, Antrean & Kasir)
// ==========================================

// Age validation live feedback on input change
function onBirthDateChanged(input) {
  const dob = input.value;
  const feedback = document.getElementById('dob-age-feedback');
  if (!feedback) return;

  if (!dob) {
    feedback.innerHTML = '';
    return;
  }

  const age = calculateChildAge(dob);
  if (!age) {
    feedback.innerHTML = '<span class="text-rose-600 text-xs font-semibold">Format tanggal tidak valid</span>';
    return;
  }

  if (age.isUnder15) {
    feedback.innerHTML = `
      <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
        <i class="fa-solid fa-circle-check text-emerald-600"></i> Usia: ${age.text} (Sesuai kriteria &lt; 15 tahun)
      </span>
    `;
  } else {
    feedback.innerHTML = `
      <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200">
        <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Usia: ${age.text} (Melebihi batas maksimal 15 tahun!)
      </span>
    `;
  }
}

// Register new pediatric patient
async function submitNewPatient(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const data = Object.fromEntries(formData.entries());

  // Check age client-side first
  const age = calculateChildAge(data.tanggal_lahir);
  if (!age || !age.isUnder15) {
    showToast('Pendaftaran ditolak: Pasien harus berusia di bawah 15 tahun.', 'error');
    return;
  }

  const btn = form.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Menyimpan...';
  btn.disabled = true;

  try {
    const res = await fetch('api/patients.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();

    if (result.success) {
      showToast(`Pasien ${data.nama_anak} (${result.kode_rm}) berhasil didaftarkan!`, 'success');
      form.reset();
      document.getElementById('dob-age-feedback').innerHTML = '';
      
      // Auto preselect for ticket queue creation
      showCreateQueueModal(result.id, result.kode_rm, data.nama_anak, result.usia_formatted);
    } else {
      showToast(result.error || 'Gagal mendaftarkan pasien', 'error');
    }
  } catch (err) {
    showToast('Terjadi kesalahan jaringan.', 'error');
  } finally {
    btn.innerHTML = originalText;
    btn.disabled = false;
  }
}

// Search Patients for Queue Registration
let searchPatientTimeout = null;
function onSearchPatientInput(val) {
  clearTimeout(searchPatientTimeout);
  searchPatientTimeout = setTimeout(async () => {
    const container = document.getElementById('patient-search-results');
    if (!container) return;

    if (val.trim().length === 0) {
      container.innerHTML = '';
      return;
    }

    try {
      const res = await fetch(`api/patients.php?action=search&q=${encodeURIComponent(val)}`);
      const data = await res.json();
      
      if (data.success && data.data.length > 0) {
        container.innerHTML = data.data.map(p => `
          <div class="p-3 hover:bg-cyan-50/70 border-b border-slate-100 flex items-center justify-between cursor-pointer transition-colors"
               onclick="selectPatientForQueue(${p.id}, '${p.kode_rm}', '${p.nama_anak.replace(/'/g, "\\'")}', '${p.usia_formatted}', '${p.nama_ortu.replace(/'/g, "\\'")}')">
            <div>
              <div class="font-bold text-sm text-slate-800">${p.nama_anak} <span class="text-xs font-normal text-slate-500">(${p.usia_formatted}, ${p.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'})</span></div>
              <div class="text-xs text-slate-500">No. RM: <span class="font-mono font-semibold">${p.kode_rm}</span> • Ortu: ${p.nama_ortu} (${p.no_hp})</div>
            </div>
            <button type="button" class="px-3 py-1 rounded-lg text-xs font-bold bg-cyan-600 text-white hover:bg-cyan-700">
              Buat Antrean
            </button>
          </div>
        `).join('');
      } else {
        container.innerHTML = `<div class="p-3 text-xs text-slate-500 text-center">Pasien tidak ditemukan. Buat pendaftaran pasien baru di bawah.</div>`;
      }
    } catch (e) {
      console.error(e);
    }
  }, 250);
}

function selectPatientForQueue(id, kodeRm, namaAnak, usia, namaOrtu) {
  document.getElementById('patient-search-results').innerHTML = '';
  document.getElementById('search-patient-input').value = `${namaAnak} (${kodeRm})`;
  showCreateQueueModal(id, kodeRm, namaAnak, usia);
}

function showCreateQueueModal(idPasien, kodeRm, namaAnak, usia) {
  const modal = document.getElementById('modal-create-queue');
  if (!modal) return;

  document.getElementById('queue-modal-pasien-id').value = idPasien;
  document.getElementById('queue-modal-pasien-info').innerHTML = `
    <div class="font-bold text-base text-cyan-900">${namaAnak}</div>
    <div class="text-xs text-slate-600 font-mono">${kodeRm} • Usia: ${usia}</div>
  `;
  modal.classList.remove('hidden');
}

function closeCreateQueueModal() {
  const modal = document.getElementById('modal-create-queue');
  if (modal) modal.classList.add('hidden');
}

// Issue Queue Ticket Submit
async function submitIssueQueue(e) {
  e.preventDefault();
  const form = e.target;
  const id_pasien = document.getElementById('queue-modal-pasien-id').value;
  const poli_tujuan = form.poli_tujuan.value;
  const keluhan_awal = form.keluhan_awal.value;

  try {
    const res = await fetch('api/queues.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_pasien, poli_tujuan, keluhan_awal })
    });
    const result = await res.json();

    if (result.success) {
      closeCreateQueueModal();
      showToast(`Antrean ${result.nomor_antrean} berhasil diterbitkan!`, 'success');
      loadResepsionisQueues();
      
      // Open Printable Ticket Modal
      openPrintTicketModal(result);
    } else {
      showToast(result.error, 'error');
    }
  } catch (err) {
    showToast('Gagal menerbitkan nomor antrean', 'error');
  }
}

function openPrintTicketModal(ticketData) {
  const modal = document.getElementById('modal-ticket-print');
  if (!modal) return;

  const t = ticketData;
  const poliNames = {
    umum: 'Poli Anak Sakit & Umum',
    gigi: 'Poli Gigi Anak',
    vaksin: 'Poli Vaksin & Imunisasi',
    tindakan: 'Poli Tindakan Medis Ringan'
  };

  document.getElementById('ticket-print-poli').textContent = poliNames[t.poli_tujuan] || t.poli_tujuan.toUpperCase();
  document.getElementById('ticket-print-number').textContent = t.nomor_antrean;
  document.getElementById('ticket-print-name').textContent = t.patient.nama_anak;
  document.getElementById('ticket-print-rm').textContent = t.patient.kode_rm;
  document.getElementById('ticket-print-age').textContent = t.patient.usia_formatted;
  document.getElementById('ticket-print-date').textContent = new Date().toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

  modal.classList.remove('hidden');
}

function closePrintTicketModal() {
  const modal = document.getElementById('modal-ticket-print');
  if (modal) modal.classList.add('hidden');
}

// Load Resepsionis Queues Table
async function loadResepsionisQueues() {
  const tableBody = document.getElementById('table-resepsionis-queues');
  if (!tableBody) return;

  try {
    const res = await fetch('api/queues.php?action=list');
    const data = await res.json();

    if (data.success && data.data.length > 0) {
      tableBody.innerHTML = data.data.map(q => {
        let statusBadge = getStatusBadge(q.status);
        return `
          <tr class="hover:bg-slate-50 transition-colors border-b border-slate-100">
            <td class="p-3 font-extrabold text-cyan-900 font-mono text-sm">${q.nomor_antrean}</td>
            <td class="p-3">
              <div class="font-bold text-slate-800 text-sm">${q.nama_anak}</div>
              <div class="text-xs text-slate-500">${q.kode_rm} • ${q.usia_formatted}</div>
            </td>
            <td class="p-3 text-xs font-semibold capitalize">${q.poli_tujuan}</td>
            <td class="p-3 text-xs text-slate-500 max-w-xs truncate">${q.keluhan_awal || '-'}</td>
            <td class="p-3">${statusBadge}</td>
            <td class="p-3 text-right">
              <button onclick="openPrintTicketModal({ nomor_antrean: '${q.nomor_antrean}', poli_tujuan: '${q.poli_tujuan}', patient: { nama_anak: '${q.nama_anak}', kode_rm: '${q.kode_rm}', usia_formatted: '${q.usia_formatted}' } })"
                      class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                <i class="fa-solid fa-print mr-1"></i> Cetak Tiket
              </button>
            </td>
          </tr>
        `;
      }).join('');
    } else {
      tableBody.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Belum ada antrean hari ini.</td></tr>`;
    }
  } catch (e) {
    console.error(e);
  }
}

// Load Kasir / Billing Table
async function loadCashierBilling() {
  const container = document.getElementById('table-cashier-queues');
  if (!container) return;

  try {
    const res = await fetch('api/queues.php?action=list&status=kasir');
    const data = await res.json();

    if (data.success && data.data.length > 0) {
      container.innerHTML = data.data.map(item => `
        <tr class="hover:bg-emerald-50/40 border-b border-slate-100 transition-colors">
          <td class="p-3 font-bold font-mono text-cyan-900">${item.nomor_antrean}</td>
          <td class="p-3">
            <div class="font-bold text-slate-800 text-sm">${item.nama_anak}</div>
            <div class="text-xs text-slate-500">${item.kode_rm} • Wali: ${item.nama_ortu}</div>
          </td>
          <td class="p-3 text-xs capitalize font-semibold">${item.poli_tujuan}</td>
          <td class="p-3 text-sm font-extrabold text-emerald-700">${item.total_biaya ? formatRupiah(item.total_biaya) : 'Siap Dihitung'}</td>
          <td class="p-3">
            <span class="px-2.5 py-1 rounded-full text-xs font-bold ${item.status_bayar === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}">
              ${item.status_bayar === 'lunas' ? 'LUNAS' : 'MENUNGGU BAYAR'}
            </span>
          </td>
          <td class="p-3 text-right">
            <button onclick="openCashierInvoiceModal(${item.id})"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
              <i class="fa-solid fa-receipt mr-1"></i> Proses Nota Kasir
            </button>
          </td>
        </tr>
      `).join('');
    } else {
      container.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Tidak ada antrean yang menunggu pembayaran di kasir saat ini.</td></tr>`;
    }
  } catch (e) {
    console.error(e);
  }
}

// Open Cashier Invoice Modal
let currentInvoiceData = null;
async function openCashierInvoiceModal(idAntrean) {
  const modal = document.getElementById('modal-cashier-invoice');
  if (!modal) return;

  try {
    const res = await fetch(`api/billing.php?action=get_invoice&id_antrean=${idAntrean}`);
    const data = await res.json();
    if (!data.success) {
      showToast(data.error, 'error');
      return;
    }

    currentInvoiceData = data.invoice;
    const inv = data.invoice;

    document.getElementById('inv-no-nota').textContent = inv.no_nota;
    document.getElementById('inv-patient-name').textContent = inv.nama_anak;
    document.getElementById('inv-patient-rm').textContent = inv.kode_rm;
    document.getElementById('inv-patient-age').textContent = inv.usia_formatted;
    document.getElementById('inv-diagnosis').textContent = inv.diagnosis || 'Pemeriksaan Rutin';
    document.getElementById('inv-prescription').textContent = inv.resep_obat || 'Tidak ada resep obat khusus';

    document.getElementById('inv-cost-konsul').textContent = inv.biaya_konsultasi_fmt;
    document.getElementById('inv-cost-tindakan').textContent = inv.biaya_tindakan_fmt;
    document.getElementById('inv-cost-obat').textContent = inv.biaya_obat_vaksin_fmt;
    document.getElementById('inv-cost-admin').textContent = inv.biaya_admin_fmt;
    document.getElementById('inv-total-cost').textContent = inv.total_biaya_fmt;

    const payBtn = document.getElementById('btn-confirm-payment');
    if (inv.status_bayar === 'lunas') {
      payBtn.disabled = true;
      payBtn.innerHTML = '<i class="fa-solid fa-check-double mr-1.5"></i> Sudah Lunas';
      payBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold bg-slate-200 text-slate-500 cursor-not-allowed';
    } else {
      payBtn.disabled = false;
      payBtn.innerHTML = '<i class="fa-solid fa-money-bill-wave mr-1.5"></i> Konfirmasi Pembayaran & Cetak Nota';
      payBtn.className = 'px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-md';
    }

    modal.classList.remove('hidden');
  } catch (err) {
    showToast('Gagal memuat rincian tagihan kasir', 'error');
  }
}

function closeCashierInvoiceModal() {
  const modal = document.getElementById('modal-cashier-invoice');
  if (modal) modal.classList.add('hidden');
}

// Confirm Payment
async function submitProcessPayment() {
  if (!currentInvoiceData) return;

  const paymentMethod = document.getElementById('inv-payment-method').value;
  try {
    const res = await fetch('api/billing.php?action=pay', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_antrean: currentInvoiceData.id_antrean,
        metode_pembayaran: paymentMethod
      })
    });
    const result = await res.json();
    if (result.success) {
      showToast('Pembayaran berhasil dikonfirmasi! Pelayanan pasien tuntas.', 'success');
      window.print(); // Prompt print receipt
      closeCashierInvoiceModal();
      loadCashierBilling();
      loadResepsionisQueues();
    } else {
      showToast(result.error, 'error');
    }
  } catch (e) {
    showToast('Gagal memproses pembayaran', 'error');
  }
}

// ==========================================
// 2. ASISTEN DOKTER MODULE (Tanda Vital & Stok)
// ==========================================

async function loadAsistenQueues() {
  const container = document.getElementById('table-asisten-queues');
  if (!container) return;

  try {
    const res = await fetch('api/queues.php?action=list');
    const data = await res.json();

    if (data.success && data.data.length > 0) {
      // Filter queues relevant to assistant (waiting for assistant or currently in assistant room)
      const relevant = data.data.filter(q => ['menunggu_asisten', 'ruang_asisten', 'menunggu_dokter'].includes(q.status));

      if (relevant.length === 0) {
        container.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Tidak ada antrean pasien untuk asisten saat ini.</td></tr>`;
        return;
      }

      container.innerHTML = relevant.map(q => {
        let isCurrent = q.status === 'ruang_asisten';
        return `
          <tr class="${isCurrent ? 'bg-amber-50/70 border-amber-200' : 'hover:bg-slate-50 border-slate-100'} border-b transition-colors">
            <td class="p-3 font-extrabold font-mono text-cyan-900">${q.nomor_antrean}</td>
            <td class="p-3">
              <div class="font-bold text-slate-800 text-sm">${q.nama_anak}</div>
              <div class="text-xs text-slate-500">${q.kode_rm} • ${q.usia_formatted}</div>
            </td>
            <td class="p-3 text-xs capitalize font-semibold">${q.poli_tujuan}</td>
            <td class="p-3 text-xs text-slate-600 max-w-xs truncate">${q.keluhan_awal || '-'}</td>
            <td class="p-3">
              ${q.berat_badan ? `
                <span class="text-xs font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                  BB: ${q.berat_badan}kg, TB: ${q.tinggi_badan}cm, Suhu: ${q.suhu_tubuh}°C
                </span>
              ` : '<span class="text-xs text-slate-400 italic">Belum diinput</span>'}
            </td>
            <td class="p-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <button onclick="callPatientVoice('${q.nomor_antrean}', '${q.nama_anak.replace(/'/g, "\\'")}', 'Ruang Persiapan Asisten Medis')"
                        class="p-2 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200" title="Panggil Suara">
                  <i class="fa-solid fa-bullhorn text-xs"></i>
                </button>
                <button onclick="openVitalsModal(${q.id}, '${q.nomor_antrean}', '${q.nama_anak.replace(/'/g, "\\'")}', '${q.usia_formatted}', '${q.keluhan_awal ? q.keluhan_awal.replace(/'/g, "\\'") : ''}')"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500 text-white hover:bg-amber-600 shadow-sm">
                  <i class="fa-solid fa-weight-scale mr-1"></i> Input Tanda Vital
                </button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    } else {
      container.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Tidak ada antrean aktif.</td></tr>`;
    }
  } catch (e) {
    console.error(e);
  }
}

// Open Vitals Modal
function openVitalsModal(idAntrean, noAntrean, namaAnak, usia, keluhan) {
  const modal = document.getElementById('modal-vitals-input');
  if (!modal) return;

  document.getElementById('vital-id-antrean').value = idAntrean;
  document.getElementById('vital-header-info').innerHTML = `
    <div class="font-extrabold text-lg text-slate-900">${noAntrean} - ${namaAnak}</div>
    <div class="text-xs text-slate-500">Usia: ${usia} • Keluhan Awal: ${keluhan || '-'}</div>
  `;

  // Set status to ruang_asisten so live queue board shows active
  fetch('api/queues.php?action=update_status', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: idAntrean, status: 'ruang_asisten' })
  });

  modal.classList.remove('hidden');
}

function closeVitalsModal() {
  const modal = document.getElementById('modal-vitals-input');
  if (modal) modal.classList.add('hidden');
}

// Live calculation of BMI & Nutritional status in form
function calculateNutritionalStatus() {
  const bb = parseFloat(document.getElementById('vital-bb').value) || 0;
  const tb = parseFloat(document.getElementById('vital-tb').value) || 0;
  const badge = document.getElementById('vital-status-gizi-badge');
  if (!badge) return;

  if (bb <= 0 || tb <= 0) {
    badge.innerHTML = '<span class="text-slate-400 text-xs">Masukkan BB & TB untuk kalkulasi</span>';
    return;
  }

  const heightM = tb / 100.0;
  const bmi = bb / (heightM * heightM);

  let statusText = 'Normal';
  let badgeColor = 'bg-emerald-50 text-emerald-800 border-emerald-200';

  if (bmi < 13.5) {
    statusText = 'Gizi Kurang (Underweight)';
    badgeColor = 'bg-amber-50 text-amber-800 border-amber-200';
  } else if (bmi <= 18.0) {
    statusText = 'Gizi Baik (Normal)';
    badgeColor = 'bg-emerald-50 text-emerald-800 border-emerald-200';
  } else if (bmi <= 21.0) {
    statusText = 'Berisiko Gizi Lebih (Overweight)';
    badgeColor = 'bg-amber-50 text-amber-800 border-amber-200';
  } else {
    statusText = 'Gizi Lebih (Obesitas)';
    badgeColor = 'bg-rose-50 text-rose-800 border-rose-200';
  }

  badge.innerHTML = `
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold ${badgeColor} border">
      <i class="fa-solid fa-chart-line"></i> IMT: ${bmi.toFixed(1)} — ${statusText}
    </span>
  `;
}

// Submit Vitals
async function submitVitals(e) {
  e.preventDefault();
  const form = e.target;
  const data = {
    id_antrean: document.getElementById('vital-id-antrean').value,
    berat_badan: form.berat_badan.value,
    tinggi_badan: form.tinggi_badan.value,
    suhu_tubuh: form.suhu_tubuh.value,
    lingkar_kepala: form.lingkar_kepala.value,
    catatan_asisten: form.catatan_asisten.value
  };

  try {
    const res = await fetch('api/vitals.php?action=save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();

    if (result.success) {
      showToast(result.message, 'success');
      closeVitalsModal();
      form.reset();
      loadAsistenQueues();
    } else {
      showToast(result.error, 'error');
    }
  } catch (err) {
    showToast('Gagal menyimpan tanda vital', 'error');
  }
}

// ==========================================
// 3. DOKTER MODULE (EMR, Odontogram Gigi, Vaksinasi, Resep)
// ==========================================

async function loadDokterQueues() {
  const container = document.getElementById('table-dokter-queues');
  if (!container) return;

  try {
    const res = await fetch('api/queues.php?action=list');
    const data = await res.json();

    if (data.success && data.data.length > 0) {
      // Filter queues ready for doctor or currently in doctor room
      const doctorQueues = data.data.filter(q => ['menunggu_dokter', 'ruang_dokter'].includes(q.status));

      if (doctorQueues.length === 0) {
        container.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Tidak ada pasien yang menunggu di ruang periksa saat ini.</td></tr>`;
        return;
      }

      container.innerHTML = doctorQueues.map(q => {
        let isInside = q.status === 'ruang_dokter';
        return `
          <tr class="${isInside ? 'bg-cyan-50/70 border-cyan-200' : 'hover:bg-slate-50 border-slate-100'} border-b transition-colors">
            <td class="p-3 font-extrabold font-mono text-cyan-900">${q.nomor_antrean}</td>
            <td class="p-3">
              <div class="font-bold text-slate-800 text-sm">${q.nama_anak}</div>
              <div class="text-xs text-slate-500">${q.kode_rm} • ${q.usia_formatted}</div>
            </td>
            <td class="p-3 text-xs capitalize font-semibold">${q.poli_tujuan}</td>
            <td class="p-3">
              ${q.berat_badan ? `
                <div class="text-xs font-semibold text-slate-700">BB: ${q.berat_badan}kg, TB: ${q.tinggi_badan}cm</div>
                <div class="text-[11px] text-cyan-700 font-medium">Suhu: ${q.suhu_tubuh}°C • ${q.status_gizi || 'Normal'}</div>
              ` : '<span class="text-xs text-amber-600 italic">Pemeriksaan awal belum lengkap</span>'}
            </td>
            <td class="p-3 text-xs text-slate-600 max-w-xs truncate">${q.keluhan_awal || '-'}</td>
            <td class="p-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <button onclick="callPatientVoice('${q.nomor_antrean}', '${q.nama_anak.replace(/'/g, "\\'")}', 'Ruang Dokter Spesialis Anak')"
                        class="p-2 rounded-lg bg-cyan-50 text-cyan-700 hover:bg-cyan-100 border border-cyan-200" title="Panggil ke Ruang Dokter">
                  <i class="fa-solid fa-bullhorn text-xs"></i>
                </button>
                <button onclick="openDoctorWorkspace(${q.id}, '${q.nomor_antrean}', '${q.nama_anak.replace(/'/g, "\\'")}', '${q.usia_formatted}', '${q.poli_tujuan}', '${q.berat_badan || ''}', '${q.tinggi_badan || ''}', '${q.suhu_tubuh || ''}', '${q.status_gizi || ''}', '${q.keluhan_awal ? q.keluhan_awal.replace(/'/g, "\\'") : ''}')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-cyan-700 text-white hover:bg-cyan-800 shadow-sm">
                  <i class="fa-solid fa-stethoscope mr-1"></i> Periksa Pasien
                </button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    } else {
      container.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Tidak ada antrean menunggu dokter.</td></tr>`;
    }
  } catch (e) {
    console.error(e);
  }
}

// Open Doctor EMR Workspace Modal
let activeExamQueueId = null;
function openDoctorWorkspace(idAntrean, noAntrean, namaAnak, usia, poli, bb, tb, suhu, gizi, keluhan) {
  activeExamQueueId = idAntrean;
  const modal = document.getElementById('modal-doctor-emr');
  if (!modal) return;

  document.getElementById('emr-id-antrean').value = idAntrean;
  document.getElementById('emr-patient-header').innerHTML = `
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold px-2 py-0.5 rounded bg-cyan-100 text-cyan-800 font-mono">${noAntrean}</span>
          <span class="text-lg font-bold text-slate-900">${namaAnak}</span>
          <span class="text-xs text-slate-500">(${usia})</span>
        </div>
        <div class="text-xs text-slate-500 mt-0.5">Keluhan Awal: <span class="font-medium text-slate-700">${keluhan || '-'}</span></div>
      </div>
      <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
        <div><span class="text-slate-400">BB:</span> <strong class="text-slate-800">${bb || '-'} kg</strong></div>
        <div class="text-slate-300">|</div>
        <div><span class="text-slate-400">TB:</span> <strong class="text-slate-800">${tb || '-'} cm</strong></div>
        <div class="text-slate-300">|</div>
        <div><span class="text-slate-400">Suhu:</span> <strong class="text-rose-600">${suhu || '-'} °C</strong></div>
        <div class="text-slate-300">|</div>
        <div><span class="text-slate-400">Gizi:</span> <strong class="text-cyan-700">${gizi || '-'}</strong></div>
      </div>
    </div>
  `;

  // Pre-open subtab based on Poli
  if (poli === 'gigi') {
    switchEmrSubTab('gigi');
  } else if (poli === 'vaksin') {
    switchEmrSubTab('vaksin');
  } else {
    switchEmrSubTab('umum');
  }

  // Update status to 'ruang_dokter' on the live board
  fetch('api/queues.php?action=update_status', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: idAntrean, status: 'ruang_dokter' })
  });

  modal.classList.remove('hidden');
}

function closeDoctorWorkspace() {
  const modal = document.getElementById('modal-doctor-emr');
  if (modal) modal.classList.add('hidden');
}

function switchEmrSubTab(subTab) {
  document.querySelectorAll('.emr-tab-pane').forEach(p => p.classList.add('hidden'));
  document.querySelectorAll('.emr-tab-nav-btn').forEach(b => {
    if (b.getAttribute('data-subtab') === subTab) {
      b.className = 'emr-tab-nav-btn px-4 py-2 font-bold text-xs rounded-xl bg-cyan-600 text-white shadow-sm flex items-center gap-1.5';
    } else {
      b.className = 'emr-tab-nav-btn px-4 py-2 font-semibold text-xs rounded-xl text-slate-600 hover:bg-slate-100 flex items-center gap-1.5';
    }
  });

  const pane = document.getElementById(`emr-pane-${subTab}`);
  if (pane) pane.classList.remove('hidden');
}

// Pediatric Odontogram Interactive Chart
// FDI 2-digit Notation for primary (baby) teeth:
// Upper right: 55, 54, 53, 52, 51 | Upper left: 61, 62, 63, 64, 65
// Lower right: 85, 84, 83, 82, 81 | Lower left: 71, 72, 73, 74, 75
function initToothChart() {
  const upperGrid = document.getElementById('upper-teeth-grid');
  const lowerGrid = document.getElementById('lower-teeth-grid');
  if (!upperGrid || !lowerGrid) return;

  const upperTeeth = ['55', '54', '53', '52', '51', '61', '62', '63', '64', '65'];
  const lowerTeeth = ['85', '84', '83', '82', '81', '71', '72', '73', '74', '75'];

  upperGrid.innerHTML = upperTeeth.map(num => `
    <div class="tooth-item status-sehat" id="tooth-${num}" onclick="onToothClick('${num}')">
      <span class="text-[9px] text-slate-400">Gigi</span>
      <span>${num}</span>
    </div>
  `).join('');

  lowerGrid.innerHTML = lowerTeeth.map(num => `
    <div class="tooth-item status-sehat" id="tooth-${num}" onclick="onToothClick('${num}')">
      <span class="text-[9px] text-slate-400">Gigi</span>
      <span>${num}</span>
    </div>
  `).join('');
}

function selectDentalStatus(status) {
  selectedDentalStatus = status;
  document.querySelectorAll('.dental-tool-btn').forEach(btn => {
    if (btn.getAttribute('data-status') === status) {
      btn.classList.add('ring-2', 'ring-cyan-600');
    } else {
      btn.classList.remove('ring-2', 'ring-cyan-600');
    }
  });
}

function onToothClick(toothNum) {
  const el = document.getElementById(`tooth-${toothNum}`);
  if (!el) return;

  el.className = `tooth-item status-${selectedDentalStatus}`;
  dentalRecords[toothNum] = selectedDentalStatus;

  // Update summary notes
  updateDentalNotes();
}

function updateDentalNotes() {
  const notesEl = document.getElementById('emr-catatan-gigi');
  if (!notesEl) return;

  const entries = Object.entries(dentalRecords);
  if (entries.length === 0) return;

  const text = entries.map(([tooth, stat]) => `Gigi ${tooth}: ${stat.toUpperCase()}`).join('; ');
  notesEl.value = text;
}

// Doctor EMR Save & Forward to Cashier
async function submitDoctorEMR(e) {
  e.preventDefault();
  const form = e.target;
  const id_antrean = document.getElementById('emr-id-antrean').value;

  const data = {
    id_antrean: id_antrean,
    diagnosis: form.diagnosis.value,
    anamnesis: form.anamnesis.value,
    pemeriksaan_fisik: form.pemeriksaan_fisik.value,
    catatan_gigi: form.catatan_gigi.value,
    tindakan_medis: form.tindakan_medis.value,
    resep_obat: form.resep_obat.value,
    edukasi_ortu: form.edukasi_ortu.value,
    jadwal_kontrol: form.jadwal_kontrol.value,
    biaya_layanan: form.biaya_layanan.value || 75000,
    biaya_tindakan: form.biaya_tindakan.value || 0,
    biaya_obat: form.biaya_obat.value || 0,
    // Vaccine details if any
    nama_vaksin: form.nama_vaksin ? form.nama_vaksin.value : '',
    nomor_batch: form.nomor_batch ? form.nomor_batch.value : '',
    dosis_ke: form.dosis_ke ? form.dosis_ke.value : 1,
    jadwal_vaksin_berikutnya: form.jadwal_vaksin_berikutnya ? form.jadwal_vaksin_berikutnya.value : null
  };

  try {
    const res = await fetch('api/medical_records.php?action=save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();

    if (result.success) {
      showToast(result.message, 'success');
      closeDoctorWorkspace();
      form.reset();
      dentalRecords = {};
      initToothChart();
      loadDokterQueues();
    } else {
      showToast(result.error, 'error');
    }
  } catch (err) {
    showToast('Gagal menyimpan rekam medis dokter', 'error');
  }
}

// ==========================================
// 4. STOCK & INVENTORY MODULE
// ==========================================

async function loadStockTable() {
  const container = document.getElementById('table-stock-items');
  if (!container) return;

  try {
    const res = await fetch('api/stock.php?action=list');
    const data = await res.json();

    if (data.success && data.data.length > 0) {
      container.innerHTML = data.data.map(item => `
        <tr class="hover:bg-slate-50 border-b border-slate-100 transition-colors">
          <td class="p-3 font-mono text-xs font-semibold text-slate-500">${item.kode}</td>
          <td class="p-3">
            <div class="font-bold text-slate-800 text-sm">${item.nama_item}</div>
            <div class="text-xs text-slate-500">${item.deskripsi || '-'}</div>
          </td>
          <td class="p-3">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold ${
              item.kategori === 'vaksin' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' :
              item.kategori === 'obat' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' :
              'bg-slate-100 text-slate-700'
            }">${item.kategori.toUpperCase()}</span>
          </td>
          <td class="p-3 text-sm font-extrabold ${item.is_low ? 'text-rose-600' : 'text-slate-800'}">
            ${item.stok} <span class="text-xs font-normal text-slate-500">${item.satuan}</span>
            ${item.is_low ? '<span class="ml-1 text-[10px] bg-rose-100 text-rose-800 px-1.5 py-0.5 rounded font-bold">MENIPIS</span>' : ''}
          </td>
          <td class="p-3 text-xs text-slate-700">${item.harga_formatted}</td>
          <td class="p-3 text-right">
            <div class="inline-flex items-center gap-1">
              <button onclick="adjustItemStock(${item.id}, 10)" class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold hover:bg-emerald-100" title="Tambah 10 stok">+10</button>
              <button onclick="adjustItemStock(${item.id}, -1)" class="px-2 py-1 bg-rose-50 text-rose-700 rounded-lg text-xs font-bold hover:bg-rose-100" title="Kurang 1 stok">-1</button>
            </div>
          </td>
        </tr>
      `).join('');
    } else {
      container.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Tidak ada item stok.</td></tr>`;
    }
  } catch (e) {
    console.error(e);
  }
}

async function adjustItemStock(id, amount) {
  try {
    const res = await fetch('api/stock.php?action=adjust', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, amount })
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      loadStockTable();
    }
  } catch (e) {
    showToast('Gagal menyesuaikan stok', 'error');
  }
}

// ==========================================
// 5. DAILY REPORT MODULE
// ==========================================

async function loadDailyReport() {
  const container = document.getElementById('daily-report-container');
  if (!container) return;

  try {
    const res = await fetch('api/billing.php?action=daily_report');
    const data = await res.json();

    if (data.success) {
      document.getElementById('rep-total-omzet').textContent = data.summary.total_omzet_fmt;
      document.getElementById('rep-total-pasien').textContent = `${data.summary.total_lunas} Transaksi Selesai`;
      document.getElementById('rep-omzet-konsul').textContent = data.summary.omzet_konsul_fmt;
      document.getElementById('rep-omzet-tindakan').textContent = data.summary.omzet_tindakan_fmt;
      document.getElementById('rep-omzet-obat').textContent = data.summary.omzet_obat_fmt;
      document.getElementById('rep-omzet-admin').textContent = data.summary.omzet_admin_fmt;

      // Table of transactions
      const txBody = document.getElementById('rep-transactions-table');
      if (txBody) {
        if (data.transactions.length > 0) {
          txBody.innerHTML = data.transactions.map(t => `
            <tr class="hover:bg-slate-50 border-b border-slate-100 text-xs">
              <td class="p-3 font-mono font-bold text-slate-700">${t.no_nota}</td>
              <td class="p-3 font-mono font-extrabold text-cyan-800">${t.nomor_antrean}</td>
              <td class="p-3 font-bold text-slate-800">${t.nama_anak} <span class="text-slate-400 font-normal">(${t.kode_rm})</span></td>
              <td class="p-3 capitalize">${t.poli_tujuan}</td>
              <td class="p-3 font-bold text-emerald-700">${t.total_biaya_fmt}</td>
              <td class="p-3">
                <span class="px-2 py-0.5 rounded text-[11px] font-bold ${t.status_bayar === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}">
                  ${t.status_bayar.toUpperCase()} (${t.metode_pembayaran ? t.metode_pembayaran.toUpperCase() : 'TUNAI'})
                </span>
              </td>
            </tr>
          `).join('');
        } else {
          txBody.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Belum ada transaksi tercatat hari ini.</td></tr>`;
        }
      }
    }
  } catch (e) {
    console.error(e);
  }
}

// Shared Call Patient Voice trigger helper
function callPatientVoice(nomorAntrean, namaAnak, namaRuangan) {
  announceQueue(nomorAntrean, namaAnak, namaRuangan);
  showToast(`Memanggil antrean ${nomorAntrean} (${namaAnak})`, 'info');
}

function getStatusBadge(status) {
  switch (status) {
    case 'menunggu_asisten':
      return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Menunggu Asisten</span>`;
    case 'ruang_asisten':
      return `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Ruang Persiapan</span>`;
    case 'menunggu_dokter':
      return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">Menunggu Dokter</span>`;
    case 'ruang_dokter':
      return `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-cyan-100 text-cyan-800 pulse-badge">Ruang Dokter</span>`;
    case 'kasir':
      return `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Kasir / Bayar</span>`;
    case 'selesai':
      return `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">Selesai</span>`;
    default:
      return `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">${status}</span>`;
  }
}

