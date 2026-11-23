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
  if (sessionStorage.getItem('openTab') === 'daftar-dokter') {
    sessionStorage.removeItem('openTab');
    setTimeout(() => {
      openLoginModal();
      switchAuthTab('daftar-dokter');
    }, 300);
  }
});

// Check Session & Render User Header
async function initCurrentUser() {
  try {
    const res = await fetch('api/auth.php?action=current_user');
    const data = await res.json();
    
    if (data.db_info) {
      updateDbBadge(data.db_info);
    }

    if (data.logged_in) {
      currentUser = data.user;
      renderUserHeader();
      applyRoleTabs(currentUser.role);
    } else {
      // Default to Super Admin Aldrich Joerellie
      await switchRoleAccount('admin');
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
  if (!currentUser) return;
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

  // Toggle Admin Verification Tab visibility
  const verifNav = document.getElementById('nav-btn-verifikasi');
  if (verifNav) {
    if (currentUser.role === 'admin') {
      verifNav.classList.remove('hidden');
      loadAdminDoctorsList();
    } else {
      verifNav.classList.add('hidden');
    }
  }
}

function applyRoleTabs(role) {
  // Select default active tab based on role
  if (role === 'resepsionis') {
    switchTab('resepsionis');
  } else if (role === 'asisten') {
    switchTab('asisten');
  } else if (role === 'dokter') {
    switchTab('dokter');
  } else if (role === 'admin') {
    switchTab('resepsionis');
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
  } else if (activeTab === 'verifikasi') {
    loadAdminDoctorsList();
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
                <button onclick="viewPatientHistoryLog(${q.id_pasien}, '${q.kode_rm}')"
                        class="px-2.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold flex items-center gap-1" title="Lihat History Keluhan & Rekam Medis">
                  <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                  <span class="hidden xl:inline">History</span>
                </button>
                <button onclick="openDoctorWorkspace(${q.id}, '${q.nomor_antrean}', '${q.nama_anak.replace(/'/g, "\\'")}', '${q.usia_formatted}', '${q.poli_tujuan}', '${q.berat_badan || ''}', '${q.tinggi_badan || ''}', '${q.suhu_tubuh || ''}', '${q.status_gizi || ''}', '${q.keluhan_awal ? q.keluhan_awal.replace(/'/g, "\\'") : ''}', ${q.id_pasien}, '${q.kode_rm}')"
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
let activeExamPatientId = null;
let activeExamKodeRm = null;

function openDoctorWorkspace(idAntrean, noAntrean, namaAnak, usia, poli, bb, tb, suhu, gizi, keluhan, idPasien, kodeRm) {
  activeExamQueueId = idAntrean;
  activeExamPatientId = idPasien || null;
  activeExamKodeRm = kodeRm || null;

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
      <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
          <div><span class="text-slate-400">BB:</span> <strong class="text-slate-800">${bb || '-'} kg</strong></div>
          <div class="text-slate-300">|</div>
          <div><span class="text-slate-400">TB:</span> <strong class="text-slate-800">${tb || '-'} cm</strong></div>
          <div class="text-slate-300">|</div>
          <div><span class="text-slate-400">Suhu:</span> <strong class="text-rose-600">${suhu || '-'} °C</strong></div>
          <div class="text-slate-300">|</div>
          <div><span class="text-slate-400">Gizi:</span> <strong class="text-cyan-700">${gizi || '-'}</strong></div>
        </div>
        <button type="button" onclick="viewPatientHistoryLog(${idPasien || 0}, '${kodeRm || ''}')" class="px-3.5 py-1.5 rounded-xl bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
          <i class="fa-solid fa-clock-rotate-left"></i> Log & Keluh Kesah Pasien
        </button>
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

// ==========================================
// 6. DATABASE BADGE & SUPABASE CONFIG HELPER
// ==========================================

function updateDbBadge(info) {
  const text = document.getElementById('db-status-text');
  const dot = document.getElementById('db-status-dot');
  const modalText = document.getElementById('db-modal-status-text');
  const modalHost = document.getElementById('db-modal-host-text');
  if (!text) return;

  if (info.is_supabase) {
    text.textContent = 'Supabase PostgreSQL';
    if (dot) dot.className = 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse';
    if (modalText) modalText.innerHTML = '<span class="text-emerald-600 font-extrabold flex items-center gap-1.5"><i class="fa-solid fa-circle-check"></i> Terhubung ke Supabase Cloud (PostgreSQL)</span>';
  } else if (info.driver === 'mysql') {
    text.textContent = 'MySQL Lokal (XAMPP)';
    if (dot) dot.className = 'w-2 h-2 rounded-full bg-blue-500';
    if (modalText) modalText.innerHTML = '<span class="text-blue-600 font-extrabold flex items-center gap-1.5"><i class="fa-solid fa-database"></i> Terhubung ke MySQL Lokal (XAMPP)</span>';
  } else {
    text.textContent = 'SQLite Demo Mode';
    if (dot) dot.className = 'w-2 h-2 rounded-full bg-amber-500';
    if (modalText) modalText.innerHTML = '<span class="text-amber-600 font-extrabold flex items-center gap-1.5"><i class="fa-solid fa-triangle-exclamation"></i> Berjalan di SQLite Demo</span>';
  }
  if (modalHost) modalHost.textContent = `Host: ${info.host || 'localhost'}`;
}

function openDatabaseConfigModal() {
  const modal = document.getElementById('modal-db-config');
  if (modal) modal.classList.remove('hidden');
}

function closeDatabaseConfigModal() {
  const modal = document.getElementById('modal-db-config');
  if (modal) modal.classList.add('hidden');
}

// ==========================================
// 7. AUTHENTICATION & LOGIN / REGISTRATION
// ==========================================

function openLoginModal() {
  const modal = document.getElementById('modal-login-auth');
  if (modal) {
    modal.classList.remove('hidden');
    switchAuthTab('admin');
  }
}

function closeLoginModal() {
  const modal = document.getElementById('modal-login-auth');
  if (modal) modal.classList.add('hidden');
}

function switchAuthTab(tab) {
  document.querySelectorAll('.auth-tab-btn').forEach(btn => {
    if (btn.getAttribute('data-tab') === tab) {
      btn.className = 'auth-tab-btn py-2 rounded-xl text-center transition-all bg-white text-slate-900 shadow-sm font-bold';
    } else {
      btn.className = 'auth-tab-btn py-2 rounded-xl text-center transition-all text-slate-500 hover:text-slate-800 font-semibold';
    }
  });

  document.querySelectorAll('.auth-pane').forEach(pane => {
    pane.classList.add('hidden');
  });

  const activePane = document.getElementById(`auth-tab-${tab}`);
  if (activePane) activePane.classList.remove('hidden');
}

// Handle Login (Admin Aldrich Joerellie or Dokter)
async function handleAuthLogin(e, role) {
  e.preventDefault();
  let username = '';
  let password = '';

  if (role === 'admin') {
    username = document.getElementById('auth-admin-user').value.trim();
    password = document.getElementById('auth-admin-code').value.trim();
  } else if (role === 'dokter') {
    username = document.getElementById('auth-doctor-user').value.trim();
    password = document.getElementById('auth-doctor-code').value.trim();
  }

  if (!username || !password) {
    showToast('Harap masukkan username dan kode masuk!', 'error');
    return;
  }

  try {
    const res = await fetch('api/auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });
    const data = await res.json();

    if (data.success) {
      currentUser = data.user;
      renderUserHeader();
      applyRoleTabs(currentUser.role);
      closeLoginModal();
      showToast(data.message || 'Login berhasil!', 'success');
      loadActiveTabData();
    } else {
      showToast(data.error || 'Login gagal.', 'error');
    }
  } catch (err) {
    console.error(err);
    showToast('Terjadi kesalahan koneksi server.', 'error');
  }
}

// Handle Doctor Self-Registration
async function handleDoctorRegistration(e) {
  e.preventDefault();
  const nama_lengkap = document.getElementById('reg-doc-name').value.trim();
  const username = document.getElementById('reg-doc-user').value.trim();
  const spesialisasi = document.getElementById('reg-doc-spec').value.trim();
  const no_str = document.getElementById('reg-doc-str').value.trim();
  const no_hp = document.getElementById('reg-doc-hp').value.trim();
  const email = document.getElementById('reg-doc-email').value.trim();

  try {
    const res = await fetch('api/auth.php?action=doctor_register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nama_lengkap, username, spesialisasi, no_str, no_hp, email })
    });
    const data = await res.json();

    if (data.success) {
      alert(`Permohonan Berhasil Dikirim!\n\n${data.message}`);
      // Switch back to doctor login tab
      switchAuthTab('dokter');
      document.getElementById('auth-doctor-user').value = username;
      showToast('Pendaftaran dokter terkirim! Menunggu verifikasi Admin.', 'success');
    } else {
      showToast(data.error || 'Pendaftaran gagal.', 'error');
    }
  } catch (err) {
    console.error(err);
    showToast('Gagal mengirim pendaftaran.', 'error');
  }
}

// ==========================================
// 8. ADMIN: VERIFIKASI DOKTER & KODE ACAK
// ==========================================

async function loadAdminDoctorsList() {
  const tablePending = document.getElementById('table-pending-doctors');
  const tableApproved = document.getElementById('table-approved-doctors');
  const badgeNav = document.getElementById('badge-pending-doctors');
  const badgePendingCount = document.getElementById('badge-pending-count');

  if (!tablePending || !tableApproved) return;

  try {
    const res = await fetch('api/auth.php?action=get_doctors');
    const data = await res.json();

    if (!data.success) {
      tablePending.innerHTML = `<tr><td colspan="7" class="p-6 text-center text-xs text-rose-500">${data.error || 'Akses ditolak.'}</td></tr>`;
      return;
    }

    const doctors = data.data || [];
    const pendingDocs = doctors.filter(d => d.status === 'pending');
    const approvedDocs = doctors.filter(d => d.status === 'approved' || d.status === 'rejected');

    // Update pending badges
    if (badgeNav) {
      if (pendingDocs.length > 0) {
        badgeNav.textContent = pendingDocs.length;
        badgeNav.classList.remove('hidden');
      } else {
        badgeNav.classList.add('hidden');
      }
    }
    if (badgePendingCount) {
      badgePendingCount.textContent = `${pendingDocs.length} Menunggu`;
    }

    // Render Table 1: Pending Doctors
    if (pendingDocs.length === 0) {
      tablePending.innerHTML = `
        <tr>
          <td colspan="7" class="p-8 text-center text-xs text-slate-400">
            <i class="fa-solid fa-circle-check text-emerald-500 text-lg mb-1 block"></i>
            Tidak ada permohonan dokter baru yang menunggu verifikasi saat ini.
          </td>
        </tr>`;
    } else {
      tablePending.innerHTML = pendingDocs.map(d => `
        <tr class="hover:bg-amber-50/50 border-b border-slate-100 text-xs">
          <td class="p-3 font-extrabold text-slate-900">${d.nama_lengkap}</td>
          <td class="p-3 font-mono font-bold text-slate-700">${d.username}</td>
          <td class="p-3 font-mono text-purple-700 font-semibold">${d.no_str || '-'}</td>
          <td class="p-3 text-slate-700">${d.spesialisasi || '-'}</td>
          <td class="p-3">
            <a href="https://wa.me/${(d.no_hp || '').replace(/^0/, '62')}" target="_blank" class="text-emerald-700 font-bold hover:underline flex items-center gap-1">
              <i class="fa-brands fa-whatsapp"></i> ${d.no_hp || '-'}
            </a>
          </td>
          <td class="p-3 text-slate-500">${d.created_at ? d.created_at.substring(0, 16) : '-'}</td>
          <td class="p-3 text-right">
            <div class="flex items-center justify-end gap-1.5">
              <button onclick="verifyDoctor(${d.id}, 'approve', '${d.nama_lengkap.replace(/'/g, "\\'")}', '${d.username}', '${d.no_hp || ''}')"
                      class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                <i class="fa-solid fa-check"></i>
                <span>Setujui & Buat Kode Acak</span>
              </button>
              <button onclick="verifyDoctor(${d.id}, 'reject', '${d.nama_lengkap.replace(/'/g, "\\'")}')"
                      class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200">
                <i class="fa-solid fa-xmark"></i> Tolak
              </button>
            </div>
          </td>
        </tr>
      `).join('');
    }

    // Render Table 2: Approved Doctors
    if (approvedDocs.length === 0) {
      tableApproved.innerHTML = `<tr><td colspan="7" class="p-6 text-center text-xs text-slate-400">Belum ada dokter yang diverifikasi.</td></tr>`;
    } else {
      tableApproved.innerHTML = approvedDocs.map(d => `
        <tr class="hover:bg-slate-50 border-b border-slate-100 text-xs">
          <td class="p-3 font-extrabold text-slate-900">${d.nama_lengkap}</td>
          <td class="p-3 font-mono font-bold text-slate-700">${d.username}</td>
          <td class="p-3 font-mono text-purple-700 font-semibold">${d.no_str || '-'}</td>
          <td class="p-3 text-slate-700">${d.spesialisasi || '-'}</td>
          <td class="p-3 font-mono font-extrabold">
            ${d.kode_akses ? `
              <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-900 border border-emerald-300 select-all tracking-wider font-bold">
                ${d.kode_akses}
              </span>
            ` : '<span class="text-slate-400 italic">-</span>'}
          </td>
          <td class="p-3">
            <span class="px-2.5 py-0.5 rounded-full font-bold text-[11px] ${d.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">
              ${d.status.toUpperCase()}
            </span>
          </td>
          <td class="p-3 text-right">
            <div class="flex items-center justify-end gap-1.5">
              ${d.kode_akses ? `
                <button onclick="navigator.clipboard.writeText('${d.kode_akses}'); showToast('Kode disalin: ${d.kode_akses}', 'success')"
                        class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700" title="Salin Kode">
                  <i class="fa-solid fa-copy text-xs"></i>
                </button>
              ` : ''}
              ${d.no_hp && d.kode_akses ? `
                <a href="https://wa.me/${d.no_hp.replace(/^0/, '62')}?text=${encodeURIComponent('Halo ' + d.nama_lengkap + ', pendaftaran Anda di Klinik Anak Happy telah disetujui oleh Admin Aldrich Joerellie. Kode Masuk Permanen Anda untuk login adalah: ' + d.kode_akses)}"
                   target="_blank" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200" title="Kirim via WhatsApp">
                  <i class="fa-brands fa-whatsapp text-xs"></i>
                </a>
              ` : ''}
            </div>
          </td>
        </tr>
      `).join('');
    }

  } catch (err) {
    console.error('Failed to load doctors list:', err);
  }
}

// Admin Verifies Doctor
let lastGeneratedDoctor = null;
async function verifyDoctor(id, decision, doctorName, username, phone) {
  if (decision === 'reject') {
    if (!confirm(`Apakah Anda yakin ingin menolak permohonan dokter ${doctorName}?`)) {
      return;
    }
  }

  try {
    const res = await fetch('api/auth.php?action=verify_doctor', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_dokter: id, decision })
    });
    const data = await res.json();

    if (data.success) {
      if (decision === 'approve') {
        lastGeneratedDoctor = data.doctor;
        document.getElementById('gen-doc-name').textContent = doctorName;
        document.getElementById('gen-doc-user').textContent = username;
        document.getElementById('gen-doc-code').textContent = data.kode_akses;

        const cleanPhone = (phone || '').replace(/^0/, '62');
        const waMsg = encodeURIComponent(`Halo ${doctorName}, pendaftaran Anda di Klinik Anak Happy telah diverifikasi oleh Admin Aldrich Joerellie. Kode Masuk Permanen Anda untuk login ke web klinik adalah: ${data.kode_akses}`);
        document.getElementById('btn-wa-doctor-code').href = `https://wa.me/${cleanPhone}?text=${waMsg}`;

        document.getElementById('modal-doctor-code-generated').classList.remove('hidden');
        showToast(`Dokter ${doctorName} berhasil diverifikasi!`, 'success');
      } else {
        showToast(`Pendaftaran dokter ${doctorName} ditolak.`, 'info');
      }
      loadAdminDoctorsList();
    } else {
      showToast(data.error || 'Gagal memproses verifikasi.', 'error');
    }
  } catch (err) {
    console.error(err);
    showToast('Terjadi kesalahan saat memverifikasi.', 'error');
  }
}

function copyDoctorCode() {
  const codeEl = document.getElementById('gen-doc-code');
  if (codeEl) {
    navigator.clipboard.writeText(codeEl.textContent.trim());
    showToast(`Kode ${codeEl.textContent.trim()} berhasil disalin!`, 'success');
  }
}

function closeDoctorCodeModal() {
  const modal = document.getElementById('modal-doctor-code-generated');
  if (modal) modal.classList.add('hidden');
}

// ==========================================
// 9. DOKTER: HISTORY & LOG KELUH KESAH PASIEN
// ==========================================

async function viewPatientHistoryLog(patientId, kodeRm) {
  const modal = document.getElementById('modal-patient-history');
  const headerEl = document.getElementById('hist-patient-header');
  const timelineEl = document.getElementById('hist-timeline-container');
  const totalVisitsEl = document.getElementById('hist-total-visits');

  if (!modal) return;
  modal.classList.remove('hidden');

  headerEl.innerHTML = `<div class="text-xs text-slate-500">Memuat profil dan rekam jejak pasien...</div>`;
  timelineEl.innerHTML = `
    <div class="text-center py-10 text-slate-400 text-xs">
      <i class="fa-solid fa-spinner fa-spin text-2xl text-cyan-600 mb-2 block"></i>
      <div>Mengambil riwayat kunjungan dan keluh kesah pasien...</div>
    </div>`;

  try {
    const url = patientId > 0 
      ? `api/patients.php?action=history_log&id=${patientId}` 
      : `api/patients.php?action=history_log&kode_rm=${encodeURIComponent(kodeRm)}`;

    const res = await fetch(url);
    const data = await res.json();

    if (!data.success) {
      timelineEl.innerHTML = `<div class="p-6 text-center text-xs text-rose-500 font-semibold">${data.error || 'Data riwayat tidak ditemukan.'}</div>`;
      return;
    }

    const p = data.patient;
    const visits = data.visits || [];
    const vaccinations = data.vaccinations || [];

    if (totalVisitsEl) totalVisitsEl.textContent = visits.length;

    // Render Patient Bio Header
    headerEl.innerHTML = `
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-cyan-700 text-white flex items-center justify-center font-bold text-base">
            ${p.jenis_kelamin === 'L' ? '<i class="fa-solid fa-child"></i>' : '<i class="fa-solid fa-child-dress"></i>'}
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h4 class="font-extrabold text-slate-900 text-base">${p.nama_anak}</h4>
              <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-cyan-100 text-cyan-800">${p.kode_rm}</span>
              <span class="text-xs text-slate-500">(${p.usia_formatted})</span>
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">
              Ortu: <strong class="text-slate-700">${p.nama_ortu}</strong> • WA: <span class="font-mono">${p.no_hp}</span> • Gol. Darah: <strong>${p.golongan_darah || '-'}</strong>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          ${p.riwayat_alergi && p.riwayat_alergi !== 'Tidak ada' && p.riwayat_alergi !== 'Tidak ada riwayat alergi' ? `
            <div class="px-3 py-1 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-1.5">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <span>Alergi: ${p.riwayat_alergi}</span>
            </div>
          ` : `
            <div class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 text-xs font-semibold">
              <i class="fa-solid fa-shield-heart mr-1"></i> Tanpa Alergi
            </div>
          `}
        </div>
      </div>
    `;

    // Render Timeline of Visits
    if (visits.length === 0) {
      timelineEl.innerHTML = `
        <div class="text-center py-12 text-slate-400">
          <i class="fa-regular fa-folder-open text-3xl mb-2 text-slate-300"></i>
          <p class="text-sm font-semibold text-slate-700">Ini Adalah Kunjungan Pertama Pasien</p>
          <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
            Belum ada rekam keluhan atau diagnosis masa lalu yang tersimpan. Keluhan hari ini akan tersimpan setelah kunjungan ini selesai.
          </p>
        </div>`;
      return;
    }

    timelineEl.innerHTML = visits.map((v, idx) => {
      return `
        <div class="relative pl-6 sm:pl-8 border-l-2 border-cyan-200 space-y-3 pb-4">
          <!-- Timeline Node Icon -->
          <div class="absolute -left-3 top-0 w-6 h-6 rounded-full bg-cyan-700 text-white flex items-center justify-center text-[11px] font-bold shadow">
            ${idx + 1}
          </div>

          <!-- Visit Meta Header -->
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="font-mono text-xs font-extrabold px-2 py-0.5 rounded bg-slate-900 text-white">${v.nomor_antrean}</span>
              <span class="text-xs font-bold text-slate-900 capitalize">Poli ${v.poli_tujuan}</span>
              <span class="text-xs text-slate-400">•</span>
              <span class="text-xs text-slate-500 font-medium">${v.waktu_daftar || '-'}</span>
            </div>
            <div class="text-xs text-cyan-800 font-semibold">
              <i class="fa-solid fa-user-doctor mr-1"></i> ${v.nama_dokter || 'Dokter Spesialis Anak'}
            </div>
          </div>

          <!-- KELUH KESAH CALLOUT BOX (HIGHLIGHTED) -->
          <div class="p-3.5 rounded-2xl bg-amber-50/90 border border-amber-200 text-xs text-amber-950 space-y-1 shadow-xs">
            <div class="flex items-center gap-1.5 font-bold text-amber-800 uppercase tracking-wider text-[11px]">
              <i class="fa-solid fa-comment-medical text-amber-600"></i>
              <span>Keluh Kesah Pasien / Orang Tua Saat Pendaftaran:</span>
            </div>
            <div class="text-slate-800 font-medium italic pl-4">
              "${v.keluhan_awal || 'Tidak ada catatan keluhan awal khusus.'}"
            </div>
          </div>

          <!-- Vitals Summary Card -->
          ${v.berat_badan ? `
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
              <div><span class="text-slate-400">Berat Badan:</span> <strong class="text-slate-800 font-bold">${v.berat_badan} kg</strong></div>
              <div><span class="text-slate-400">Tinggi Badan:</span> <strong class="text-slate-800 font-bold">${v.tinggi_badan} cm</strong></div>
              <div><span class="text-slate-400">Suhu:</span> <strong class="text-rose-600 font-bold">${v.suhu_tubuh} °C</strong></div>
              <div><span class="text-slate-400">Status Gizi:</span> <strong class="text-cyan-700 font-bold">${v.status_gizi || 'Normal'}</strong></div>
            </div>
          ` : ''}

          <!-- Medical Record Details -->
          ${v.diagnosis ? `
            <div class="p-4 rounded-2xl bg-white border border-slate-200 text-xs space-y-2 shadow-xs">
              <div>
                <div class="text-slate-400 font-bold text-[11px] uppercase">Diagnosis Dokter:</div>
                <div class="font-extrabold text-sm text-slate-900 mt-0.5">${v.diagnosis}</div>
              </div>

              ${v.anamnesis ? `
                <div class="pt-1">
                  <span class="text-slate-400 font-semibold">Anamnesis Pemeriksaan:</span>
                  <div class="text-slate-700 mt-0.5">${v.anamnesis}</div>
                </div>
              ` : ''}

              ${v.tindakan_medis ? `
                <div class="pt-1">
                  <span class="text-slate-400 font-semibold">Tindakan Medis:</span>
                  <div class="text-slate-700 font-medium mt-0.5">${v.tindakan_medis}</div>
                </div>
              ` : ''}

              ${v.resep_obat ? `
                <div class="pt-2 border-t border-slate-100">
                  <span class="text-slate-400 font-semibold">Resep Obat & Terapi:</span>
                  <div class="font-mono text-slate-800 bg-slate-50 p-2 rounded-xl mt-1 whitespace-pre-line border border-slate-100 text-[11px]">${v.resep_obat}</div>
                </div>
              ` : ''}

              ${v.edukasi_ortu ? `
                <div class="pt-1 text-slate-500">
                  <span class="font-semibold text-slate-700">Pesan Dokter ke Ortu:</span> ${v.edukasi_ortu}
                </div>
              ` : ''}
            </div>
          ` : `
            <div class="p-3 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-xs text-slate-400 italic">
              Status antrean saat ini: ${getStatusBadge(v.status)}
            </div>
          `}
        </div>
      `;
    }).join('');

  } catch (err) {
    console.error('Failed to load history:', err);
    timelineEl.innerHTML = `<div class="p-6 text-center text-xs text-rose-500">Gagal mengambil log riwayat pasien.</div>`;
  }
}

function closePatientHistoryModal() {
  const modal = document.getElementById('modal-patient-history');
  if (modal) modal.classList.add('hidden');
}

