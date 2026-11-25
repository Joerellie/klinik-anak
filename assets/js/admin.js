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
    loadAsistenVaccineStock('vaksin');
  } else if (activeTab === 'dokter') {
    loadDokterQueues();
  } else if (activeTab === 'kasir') {
    loadCashierBilling();
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
    const res = await fetch('api/queues.php?action=list&status=kasir_all');
    const data = await res.json();

    const dedicatedContainer = document.getElementById('table-cashier-dedicated');

    if (data.success && data.data.length > 0) {
      // Sort waiting payment first, then lunas
      const sorted = [...data.data].sort((a, b) => {
        if (a.status_bayar === 'lunas' && b.status_bayar !== 'lunas') return 1;
        if (a.status_bayar !== 'lunas' && b.status_bayar === 'lunas') return -1;
        return b.id - a.id;
      });

      const rowsHtml = sorted.map(item => {
        const isLunas = item.status_bayar === 'lunas';
        return `
        <tr class="${isLunas ? 'bg-slate-50/60' : 'hover:bg-emerald-50/40'} border-b border-slate-100 transition-colors">
          <td class="p-3 font-bold font-mono text-cyan-900">${item.nomor_antrean}</td>
          <td class="p-3">
            <div class="font-bold text-slate-800 text-sm">${item.nama_anak}</div>
            <div class="text-xs text-slate-500">${item.kode_rm} • Wali: ${item.nama_ortu}</div>
          </td>
          <td class="p-3">
            <div class="text-xs font-bold text-slate-800 capitalize">Poli ${item.poli_tujuan}</div>
            <div class="text-[11px] text-slate-500 max-w-xs truncate">${item.diagnosis || item.keluhan_awal || '-'}</div>
          </td>
          <td class="p-3 text-sm font-extrabold text-emerald-700">${item.total_biaya ? formatRupiah(item.total_biaya) : 'Siap Dihitung'}</td>
          <td class="p-3">
            <span class="px-2.5 py-1 rounded-full text-xs font-bold ${isLunas ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}">
              ${isLunas ? `LUNAS (${(item.metode_pembayaran || 'TUNAI').toUpperCase()})` : 'MENUNGGU BAYAR'}
            </span>
          </td>
          <td class="p-3 text-right">
            <button onclick="openCashierInvoiceModal(${item.id})"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold ${isLunas ? 'bg-slate-800 text-white hover:bg-slate-900' : 'bg-emerald-600 text-white hover:bg-emerald-700'} shadow-sm">
              <i class="fa-solid ${isLunas ? 'fa-print' : 'fa-receipt'} mr-1"></i> ${isLunas ? 'Lihat / Cetak Nota' : 'Proses Nota Kasir'}
            </button>
          </td>
        </tr>
      `}).join('');

      container.innerHTML = rowsHtml;
      if (dedicatedContainer) dedicatedContainer.innerHTML = rowsHtml;
    } else {
      const emptyHtml = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Tidak ada antrean yang menunggu pembayaran di kasir saat ini.</td></tr>`;
      container.innerHTML = emptyHtml;
      if (dedicatedContainer) dedicatedContainer.innerHTML = emptyHtml;
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

    let vitalsSummary = inv.usia_formatted;
    if (inv.berat_badan) {
      vitalsSummary += ` • BB: ${inv.berat_badan}kg, Suhu: ${inv.suhu_tubuh}°C, Tensi: ${inv.tensi_darah || '100/65 mmHg'}`;
    }
    document.getElementById('inv-patient-age').textContent = vitalsSummary;
    document.getElementById('inv-diagnosis').textContent = inv.diagnosis || 'Pemeriksaan Rutin';

    const tindakanParts = [];
    if (inv.tindakan_medis) tindakanParts.push(inv.tindakan_medis);
    if (inv.catatan_gigi) tindakanParts.push(`Gigi: ${inv.catatan_gigi}`);
    if (inv.nama_vaksin) tindakanParts.push(`Vaksin: ${inv.nama_vaksin} (Dosis ${inv.dosis_ke || 1}, Batch: ${inv.nomor_batch || '-'})`);
    const tindakanEl = document.getElementById('inv-tindakan-detail');
    if (tindakanEl) {
      tindakanEl.textContent = tindakanParts.length > 0 ? tindakanParts.join(' | ') : '-';
    }

    let rxText = inv.resep_obat || 'Tidak ada resep obat khusus';
    if (inv.nama_vaksin && !rxText.includes(inv.nama_vaksin)) {
      rxText = `[Imunisasi: ${inv.nama_vaksin} - Batch ${inv.nomor_batch || '-'}]\n` + rxText;
    }
    document.getElementById('inv-prescription').textContent = rxText;

    // Populate editable cost inputs
    const inKonsul = document.getElementById('inv-input-konsul');
    const inTindakan = document.getElementById('inv-input-tindakan');
    const inObat = document.getElementById('inv-input-obat');
    const inAdmin = document.getElementById('inv-input-admin');

    if (inKonsul) inKonsul.value = inv.biaya_konsultasi || 75000;
    if (inTindakan) inTindakan.value = inv.biaya_tindakan || 0;
    if (inObat) inObat.value = inv.biaya_obat_vaksin || 0;
    if (inAdmin) inAdmin.value = inv.biaya_admin || 10000;

    if (inv.metode_pembayaran) {
      document.getElementById('inv-payment-method').value = inv.metode_pembayaran;
    }

    const totalCostNum = parseInt(inv.total_biaya || 85000, 10);
    const cashInput = document.getElementById('inv-cash-received');
    if (cashInput) cashInput.value = totalCostNum;

    recalcCashierModalTotal();

    const payBtn = document.getElementById('btn-confirm-payment');
    const isLunas = inv.status_bayar === 'lunas';
    if (inKonsul) inKonsul.disabled = isLunas;
    if (inTindakan) inTindakan.disabled = isLunas;
    if (inObat) inObat.disabled = isLunas;
    if (inAdmin) inAdmin.disabled = isLunas;

    if (isLunas) {
      payBtn.disabled = true;
      payBtn.innerHTML = '<i class="fa-solid fa-check-double mr-1.5"></i> Sudah Lunas';
      payBtn.className = 'px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-200 text-slate-500 cursor-not-allowed';
    } else {
      payBtn.disabled = false;
      payBtn.innerHTML = '<i class="fa-solid fa-money-bill-wave mr-1.5"></i> Konfirmasi Pembayaran & Lunas';
      payBtn.className = 'px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-md';
    }

    modal.classList.remove('hidden');
  } catch (err) {
    showToast('Gagal memuat rincian tagihan kasir', 'error');
  }
}

function recalcCashierModalTotal() {
  const k = parseInt(document.getElementById('inv-input-konsul')?.value || 0, 10);
  const t = parseInt(document.getElementById('inv-input-tindakan')?.value || 0, 10);
  const o = parseInt(document.getElementById('inv-input-obat')?.value || 0, 10);
  const a = parseInt(document.getElementById('inv-input-admin')?.value || 0, 10);
  const total = k + t + o + a;

  document.getElementById('inv-cost-konsul').textContent = formatRupiah(k);
  document.getElementById('inv-cost-tindakan').textContent = formatRupiah(t);
  document.getElementById('inv-cost-obat').textContent = formatRupiah(o);
  document.getElementById('inv-cost-admin').textContent = formatRupiah(a);
  document.getElementById('inv-total-cost').textContent = formatRupiah(total);

  const cashInput = document.getElementById('inv-cash-received');
  const changeEl = document.getElementById('inv-cash-change');
  if (cashInput && changeEl) {
    const received = parseInt(cashInput.value || 0, 10);
    const diff = received - total;
    if (diff >= 0) {
      changeEl.textContent = formatRupiah(diff);
      changeEl.className = 'text-sm font-extrabold text-emerald-700';
    } else {
      changeEl.textContent = `Kurang ${formatRupiah(Math.abs(diff))}`;
      changeEl.className = 'text-sm font-extrabold text-rose-600';
    }
  }
}

function setQuickCashReceived(amount) {
  const k = parseInt(document.getElementById('inv-input-konsul')?.value || 0, 10);
  const t = parseInt(document.getElementById('inv-input-tindakan')?.value || 0, 10);
  const o = parseInt(document.getElementById('inv-input-obat')?.value || 0, 10);
  const a = parseInt(document.getElementById('inv-input-admin')?.value || 0, 10);
  const total = k + t + o + a;

  const cashInput = document.getElementById('inv-cash-received');
  if (cashInput) {
    cashInput.value = amount === 'pas' ? total : amount;
    recalcCashierModalTotal();
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
  const biaya_konsultasi = parseInt(document.getElementById('inv-input-konsul')?.value || currentInvoiceData.biaya_konsultasi || 75000, 10);
  const biaya_tindakan = parseInt(document.getElementById('inv-input-tindakan')?.value || currentInvoiceData.biaya_tindakan || 0, 10);
  const biaya_obat_vaksin = parseInt(document.getElementById('inv-input-obat')?.value || currentInvoiceData.biaya_obat_vaksin || 0, 10);
  const biaya_admin = parseInt(document.getElementById('inv-input-admin')?.value || currentInvoiceData.biaya_admin || 10000, 10);
  const total = biaya_konsultasi + biaya_tindakan + biaya_obat_vaksin + biaya_admin;
  const received = parseInt(document.getElementById('inv-cash-received')?.value || total, 10);

  if (paymentMethod === 'tunai' && received < total) {
    showToast('Nominal uang tunai yang diterima kurang dari total tagihan!', 'error');
    return;
  }

  try {
    const res = await fetch('api/billing.php?action=pay', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_antrean: currentInvoiceData.id_antrean,
        metode_pembayaran: paymentMethod,
        biaya_konsultasi,
        biaya_tindakan,
        biaya_obat_vaksin,
        biaya_admin
      })
    });
    const result = await res.json();
    if (result.success) {
      showToast('Pembayaran berhasil dikonfirmasi! Pelayanan pasien tuntas.', 'success');
      closeCashierInvoiceModal();
      loadCashierBilling();
      loadResepsionisQueues();
      loadDailyReport();
    } else {
      showToast(result.error, 'error');
    }
  } catch (e) {
    showToast('Gagal memproses pembayaran', 'error');
  }
}

// ==========================================
// 2. ASISTEN DOKTER MODULE (Tanda Vital: Tensi/Suhu/Berat & Stok Vaksin)
// ==========================================

async function loadAsistenQueues() {
  const container = document.getElementById('table-asisten-queues');
  if (!container) return;

  try {
    const res = await fetch('api/queues.php?action=list');
    const data = await res.json();

    if (data.success && data.data.length > 0) {
      // Filter queues relevant to assistant
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
            <td class="p-3 text-xs capitalize font-semibold">Poli ${q.poli_tujuan}</td>
            <td class="p-3 text-xs text-slate-600 max-w-xs truncate">${q.keluhan_awal || '-'}</td>
            <td class="p-3">
              ${q.berat_badan ? `
                <div class="inline-flex flex-wrap items-center gap-1.5 text-xs font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-xl border border-emerald-200">
                  <span><i class="fa-solid fa-gauge-high text-indigo-600 mr-0.5"></i> Tensi: <strong>${q.tensi_darah || '100/65 mmHg'}</strong></span>
                  <span>•</span>
                  <span>Suhu: <strong class="${parseFloat(q.suhu_tubuh) >= 37.8 ? 'text-rose-600' : ''}">${q.suhu_tubuh}°C</strong></span>
                  <span>•</span>
                  <span>BB: <strong>${q.berat_badan}kg</strong></span>
                  <span>•</span>
                  <span>TB: <strong>${q.tinggi_badan}cm</strong></span>
                </div>
                ${q.catatan_asisten ? `<div class="text-[11px] text-slate-500 mt-1 truncate max-w-xs">${q.catatan_asisten}</div>` : ''}
              ` : '<span class="text-xs text-amber-600 font-semibold italic">Belum diukur (Menunggu Input)</span>'}
            </td>
            <td class="p-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <button onclick="callPatientVoice('${q.nomor_antrean}', '${q.nama_anak.replace(/'/g, "\\'")}', 'Ruang Persiapan Asisten Medis')"
                        class="p-2 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200" title="Panggil Suara">
                  <i class="fa-solid fa-bullhorn text-xs"></i>
                </button>
                <button onclick="openVitalsModal(${q.id}, '${q.nomor_antrean}', '${q.nama_anak.replace(/'/g, "\\'")}', '${q.usia_formatted}', '${q.keluhan_awal ? q.keluhan_awal.replace(/'/g, "\\'") : ''}', '${q.poli_tujuan}')"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500 text-white hover:bg-amber-600 shadow-sm">
                  <i class="fa-solid fa-heart-pulse mr-1"></i> ${q.berat_badan ? 'Edit Tensi & Vital' : 'Input Tensi & Vital'}
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

// Load Vaccine & Medicine Stock inside Asisten Panel
let currentAsistenStockFilter = 'vaksin';
async function loadAsistenVaccineStock(category = currentAsistenStockFilter) {
  currentAsistenStockFilter = category;
  const container = document.getElementById('table-asisten-vaccine-stock');
  if (!container) return;

  const btnVaksin = document.getElementById('btn-asisten-filter-vaksin');
  const btnAll = document.getElementById('btn-asisten-filter-all');
  if (btnVaksin && btnAll) {
    if (category === 'vaksin') {
      btnVaksin.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-xs';
      btnAll.className = 'px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200';
    } else {
      btnAll.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-xs';
      btnVaksin.className = 'px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200';
    }
  }

  try {
    const url = category ? `api/stock.php?action=list&category=${encodeURIComponent(category)}` : 'api/stock.php?action=list';
    const res = await fetch(url);
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
            ${item.is_low ? '<span class="ml-1 text-[10px] bg-rose-100 text-rose-800 px-1.5 py-0.5 rounded font-bold">STOK MENIPIS</span>' : ''}
          </td>
          <td class="p-3 text-xs font-semibold text-slate-700">${item.harga_formatted}</td>
          <td class="p-3 text-right">
            <div class="inline-flex items-center gap-1">
              <button onclick="adjustItemStock(${item.id}, 10)" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold hover:bg-emerald-100" title="Restock +10">+10</button>
              <button onclick="adjustItemStock(${item.id}, 1)" class="px-2.5 py-1 bg-cyan-50 text-cyan-700 border border-cyan-200 rounded-lg text-xs font-bold hover:bg-cyan-100" title="Tambah +1">+1</button>
              <button onclick="adjustItemStock(${item.id}, -1)" class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold hover:bg-rose-100" title="Pakai Dosis -1">-1</button>
            </div>
          </td>
        </tr>
      `).join('');
    } else {
      container.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Tidak ada item stok vaksin ditemukan.</td></tr>`;
    }
  } catch (e) {
    console.error(e);
  }
}

// Open Vitals Modal (with Tensi, Suhu, BB, TB, and Vaccine Preparation dropdown)
async function openVitalsModal(idAntrean, noAntrean, namaAnak, usia, keluhan, poli = 'umum') {
  const modal = document.getElementById('modal-vitals-input');
  if (!modal) return;

  const form = document.getElementById('form-vitals-input');
  if (form) form.reset();

  document.getElementById('vital-id-antrean').value = idAntrean;
  document.getElementById('vital-tensi').value = '100/65 mmHg';
  document.getElementById('vital-header-info').innerHTML = `
    <div class="flex items-center justify-between">
      <div class="font-extrabold text-base text-slate-900">${noAntrean} — ${namaAnak}</div>
      <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-200/70 text-amber-950 uppercase">Poli ${poli}</span>
    </div>
    <div class="text-xs text-slate-600 mt-0.5">Usia: <strong>${usia}</strong> • Keluhan: <em>"${keluhan || '-'}"</em></div>
  `;

  // Populate vaccine preparation dropdown from stock
  const vSelect = document.getElementById('vital-vaksin-prep');
  if (vSelect) {
    try {
      const sRes = await fetch('api/stock.php?action=list&category=vaksin');
      const sData = await sRes.json();
      if (sData.success) {
        vSelect.innerHTML = `<option value="">-- Tidak Menyiapkan Vaksin / Pemeriksaan Non-Vaksin --</option>` +
          sData.data.map(v => `<option value="${v.nama_item} (Sisa Stok: ${v.stok} ${v.satuan})">${v.nama_item} — Stok: ${v.stok} ${v.satuan}</option>`).join('');
      }
    } catch (e) {}
  }

  // Load existing vitals if already filled earlier
  try {
    const vRes = await fetch(`api/vitals.php?action=get&id_antrean=${idAntrean}`);
    const vData = await vRes.json();
    if (vData.success && vData.data) {
      const vs = vData.data;
      document.getElementById('vital-tensi').value = vs.tensi_darah || '100/65 mmHg';
      document.getElementById('vital-bb').value = vs.berat_badan || '';
      document.getElementById('vital-tb').value = vs.tinggi_badan || '';
      document.getElementById('vital-suhu').value = vs.suhu_tubuh || '';
      document.getElementById('vital-lk').value = vs.lingkar_kepala || '';
      document.getElementById('vital-catatan').value = vs.catatan_asisten || '';
      calculateNutritionalStatus();
      checkTemperatureAlert();
    }
  } catch (e) {}

  // Set status to ruang_asisten so live queue board shows active
  fetch('api/queues.php?action=update_status', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: idAntrean, status: 'ruang_asisten' })
  });

  modal.classList.remove('hidden');
}

function checkTemperatureAlert() {
  const suhu = parseFloat(document.getElementById('vital-suhu')?.value || 0);
  const alertEl = document.getElementById('vital-temp-alert');
  if (!alertEl) return;
  if (!suhu) {
    alertEl.innerHTML = 'Normal: 36.5°C – 37.5°C';
    alertEl.className = 'text-[10px] font-semibold text-slate-400 mt-1';
  } else if (suhu >= 38.5) {
    alertEl.innerHTML = '<i class="fa-solid fa-fire text-rose-600"></i> Demam Tinggi (Hiperpireksia) — Segera prioritaskan!';
    alertEl.className = 'text-[10px] font-extrabold text-rose-600 mt-1';
  } else if (suhu >= 37.6) {
    alertEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Demam / Febris (> 37.5°C)';
    alertEl.className = 'text-[10px] font-bold text-amber-600 mt-1';
  } else {
    alertEl.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-600"></i> Suhu Tubuh Normal (Afebris)';
    alertEl.className = 'text-[10px] font-bold text-emerald-600 mt-1';
  }
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
    tensi_darah: document.getElementById('vital-tensi').value,
    berat_badan: form.berat_badan.value,
    tinggi_badan: form.tinggi_badan.value,
    suhu_tubuh: form.suhu_tubuh.value,
    lingkar_kepala: form.lingkar_kepala.value,
    vaksin_disiapkan: document.getElementById('vital-vaksin-prep')?.value || '',
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

let cachedStockItems = [];

async function loadDokterQueues() {
  const container = document.getElementById('table-dokter-queues');
  const completedContainer = document.getElementById('table-dokter-completed');
  if (!container) return;

  try {
    const res = await fetch('api/queues.php?action=list');
    const data = await res.json();

    if (data.success && data.data.length > 0) {
      // 1. Patients waiting for doctor or currently in doctor room (or waiting assistant if doctor wants to examine directly)
      const doctorQueues = data.data.filter(q => ['menunggu_dokter', 'ruang_dokter', 'menunggu_asisten', 'ruang_asisten'].includes(q.status));
      // 2. Patients already examined today (in kasir or selesai)
      const completedQueues = data.data.filter(q => ['kasir', 'selesai'].includes(q.status));

      if (doctorQueues.length === 0) {
        container.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Tidak ada pasien yang menunggu di ruang periksa saat ini.</td></tr>`;
      } else {
        container.innerHTML = doctorQueues.map(q => {
          let isInside = q.status === 'ruang_dokter';
          return `
            <tr class="${isInside ? 'bg-cyan-50/70 border-cyan-200' : 'hover:bg-slate-50 border-slate-100'} border-b transition-colors">
              <td class="p-3 font-extrabold font-mono text-cyan-900">${q.nomor_antrean}</td>
              <td class="p-3">
                <div class="font-bold text-slate-800 text-sm">${q.nama_anak}</div>
                <div class="text-xs text-slate-500">${q.kode_rm} • ${q.usia_formatted}</div>
              </td>
              <td class="p-3 text-xs capitalize font-semibold">Poli ${q.poli_tujuan}</td>
              <td class="p-3">
                ${q.berat_badan ? `
                  <div class="text-xs font-semibold text-slate-800">
                    Tensi: <strong class="text-indigo-700">${q.tensi_darah || '100/65 mmHg'}</strong> • Suhu: <strong class="text-rose-600">${q.suhu_tubuh}°C</strong>
                  </div>
                  <div class="text-[11px] text-cyan-800 font-medium">
                    BB: ${q.berat_badan}kg • TB: ${q.tinggi_badan}cm • ${q.status_gizi || 'Normal'}
                  </div>
                  ${q.catatan_asisten ? `<div class="text-[10px] text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded mt-0.5 inline-block border border-amber-200">${q.catatan_asisten}</div>` : ''}
                ` : '<span class="text-xs text-amber-600 italic">Tanda vital belum diinput asisten</span>'}
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
                  <button onclick="openDoctorWorkspace(${q.id}, '${q.nomor_antrean}', '${q.nama_anak.replace(/'/g, "\\'")}', '${q.usia_formatted}', '${q.poli_tujuan}', '${q.berat_badan || ''}', '${q.tinggi_badan || ''}', '${q.suhu_tubuh || ''}', '${q.status_gizi || ''}', '${q.keluhan_awal ? q.keluhan_awal.replace(/'/g, "\\'") : ''}', ${q.id_pasien}, '${q.kode_rm}', '${q.tensi_darah || '100/65 mmHg'}', '${q.catatan_asisten ? q.catatan_asisten.replace(/'/g, "\\'") : ''}')"
                          class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-cyan-700 text-white hover:bg-cyan-800 shadow-sm">
                    <i class="fa-solid fa-stethoscope mr-1"></i> Periksa Pasien
                  </button>
                </div>
              </td>
            </tr>
          `;
        }).join('');
      }

      if (completedContainer) {
        if (completedQueues.length === 0) {
          completedContainer.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-xs text-slate-400">Belum ada pasien selesai diperiksa hari ini.</td></tr>`;
        } else {
          completedContainer.innerHTML = completedQueues.map(q => `
            <tr class="hover:bg-slate-50 border-b border-slate-100 text-xs">
              <td class="p-3 font-extrabold font-mono text-slate-700">${q.nomor_antrean}</td>
              <td class="p-3">
                <div class="font-bold text-slate-800">${q.nama_anak}</div>
                <div class="text-[11px] text-slate-500">${q.kode_rm} • ${q.usia_formatted}</div>
              </td>
              <td class="p-3 capitalize font-semibold">Poli ${q.poli_tujuan}</td>
              <td class="p-3">
                <div class="font-bold text-slate-900">${q.diagnosis || '-'}</div>
                <div class="text-[11px] text-slate-500 truncate max-w-xs">${q.tindakan_medis || q.catatan_gigi || q.resep_obat || '-'}</div>
              </td>
              <td class="p-3">
                ${getStatusBadge(q.status)}
              </td>
              <td class="p-3 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button onclick="viewPatientHistoryLog(${q.id_pasien}, '${q.kode_rm}')"
                          class="px-2.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold">
                    <i class="fa-solid fa-clock-rotate-left mr-1"></i> Log Pasien
                  </button>
                  <button onclick="openDoctorWorkspace(${q.id}, '${q.nomor_antrean}', '${q.nama_anak.replace(/'/g, "\\'")}', '${q.usia_formatted}', '${q.poli_tujuan}', '${q.berat_badan || ''}', '${q.tinggi_badan || ''}', '${q.suhu_tubuh || ''}', '${q.status_gizi || ''}', '${q.keluhan_awal ? q.keluhan_awal.replace(/'/g, "\\'") : ''}', ${q.id_pasien}, '${q.kode_rm}', '${q.tensi_darah || '100/65 mmHg'}', '${q.catatan_asisten ? q.catatan_asisten.replace(/'/g, "\\'") : ''}')"
                          class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold">
                    <i class="fa-solid fa-pen-to-square mr-1"></i> Edit RM
                  </button>
                </div>
              </td>
            </tr>
          `).join('');
        }
      }
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

async function openDoctorWorkspace(idAntrean, noAntrean, namaAnak, usia, poli, bb, tb, suhu, gizi, keluhan, idPasien, kodeRm, tensi = '100/65 mmHg', catatanAsisten = '') {
  activeExamQueueId = idAntrean;
  activeExamPatientId = idPasien || null;
  activeExamKodeRm = kodeRm || null;

  const modal = document.getElementById('modal-doctor-emr');
  if (!modal) return;

  const form = document.getElementById('form-doctor-emr');
  if (form) form.reset();
  dentalRecords = {};
  initToothChart();

  document.getElementById('emr-id-antrean').value = idAntrean;
  document.getElementById('emr-patient-header').innerHTML = `
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold px-2 py-0.5 rounded bg-cyan-100 text-cyan-800 font-mono">${noAntrean}</span>
          <span class="text-lg font-bold text-slate-900">${namaAnak}</span>
          <span class="text-xs text-slate-500">(${usia})</span>
          <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 uppercase">Poli ${poli}</span>
        </div>
        <div class="text-xs text-slate-600 mt-0.5">Keluhan Awal: <span class="font-bold text-slate-800">"${keluhan || '-'}"</span></div>
        ${catatanAsisten ? `<div class="text-xs text-amber-900 mt-0.5"><i class="fa-solid fa-user-nurse text-amber-600 mr-1"></i> Catatan Asisten: <strong>${catatanAsisten}</strong></div>` : ''}
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <div class="flex flex-wrap items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-cyan-200 text-xs shadow-2xs">
          <div><span class="text-slate-400">Tensi:</span> <strong class="text-indigo-700">${tensi || '100/65 mmHg'}</strong></div>
          <div class="text-slate-300">|</div>
          <div><span class="text-slate-400">Suhu:</span> <strong class="text-rose-600">${suhu || '-'} °C</strong></div>
          <div class="text-slate-300">|</div>
          <div><span class="text-slate-400">BB:</span> <strong class="text-slate-800">${bb || '-'} kg</strong></div>
          <div class="text-slate-300">|</div>
          <div><span class="text-slate-400">TB:</span> <strong class="text-slate-800">${tb || '-'} cm</strong></div>
          <div class="text-slate-300">|</div>
          <div><span class="text-slate-400">Gizi:</span> <strong class="text-cyan-700">${gizi || '-'}</strong></div>
        </div>
        <button type="button" onclick="viewPatientHistoryLog(${idPasien || 0}, '${kodeRm || ''}')" class="px-3.5 py-1.5 rounded-xl bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
          <i class="fa-solid fa-clock-rotate-left"></i> Log & Keluh Kesah Pasien
        </button>
      </div>
    </div>
  `;

  // Load Vaccines & Medicines from Stock for Doctor EMR pickers
  try {
    const sRes = await fetch('api/stock.php?action=list');
    const sData = await sRes.json();
    if (sData.success) {
      cachedStockItems = sData.data || [];
      const vacSelect = document.getElementById('emr-select-vaksin');
      if (vacSelect) {
        const vaccines = cachedStockItems.filter(i => i.kategori === 'vaksin');
        vacSelect.innerHTML = `<option value="" data-id="0" data-price="0" data-kode="">-- Bukan Layanan Vaksin / Lewati --</option>` +
          vaccines.map(v => `<option value="${v.nama_item}" data-id="${v.id}" data-price="${v.harga_satuan}" data-kode="${v.kode}">${v.nama_item} — Sisa Stok: ${v.stok} ${v.satuan} (${v.harga_formatted})</option>`).join('');
      }
      const medSelect = document.getElementById('emr-quick-medicine-select');
      if (medSelect) {
        const meds = cachedStockItems.filter(i => i.kategori !== 'vaksin');
        medSelect.innerHTML = `<option value="" data-price="0">-- Pilih Obat / Alkes dari Stok --</option>` +
          meds.map(m => `<option value="${m.nama_item}" data-price="${m.harga_satuan}">${m.nama_item} (${m.harga_formatted} / ${m.satuan}) — Stok: ${m.stok}</option>`).join('');
      }
    }
  } catch (e) {}

  // Pre-load existing Medical Record if already saved before
  try {
    const mrRes = await fetch(`api/medical_records.php?action=get_by_queue&id_antrean=${idAntrean}`);
    const mrData = await mrRes.json();
    if (mrData.success && mrData.data) {
      const mr = mrData.data;
      document.getElementById('emr-diagnosis').value = mr.diagnosis || '';
      document.getElementById('emr-anamnesis').value = mr.anamnesis || '';
      document.getElementById('emr-fisik').value = mr.pemeriksaan_fisik || '';
      document.getElementById('emr-catatan-gigi').value = mr.catatan_gigi || '';
      document.getElementById('emr-tindakan-medis').value = mr.tindakan_medis || '';
      document.getElementById('emr-resep-obat').value = mr.resep_obat || '';
      document.getElementById('emr-edukasi-ortu').value = mr.edukasi_ortu || '';
      document.getElementById('emr-jadwal-kontrol').value = mr.jadwal_kontrol || '';
      document.getElementById('emr-biaya-layanan').value = mr.biaya_layanan || 75000;
    } else {
      // Pre-fill anamnesis with initial complaint
      document.getElementById('emr-anamnesis').value = keluhan ? `Pasien datang dengan keluhan: ${keluhan}` : '';
      document.getElementById('emr-biaya-layanan').value = 75000;
      document.getElementById('emr-biaya-tindakan').value = 0;
      document.getElementById('emr-biaya-obat').value = 0;
    }
    if (mrData.success && mrData.vaccine) {
      const vac = mrData.vaccine;
      document.getElementById('emr-select-vaksin').value = vac.nama_vaksin || '';
      document.getElementById('emr-nomor-batch').value = vac.nomor_batch || '';
      document.getElementById('emr-dosis-ke').value = vac.dosis_ke || 1;
      document.getElementById('emr-jadwal-vaksin').value = vac.jadwal_vaksin_berikutnya || '';
      document.getElementById('emr-catatan-reaksi').value = vac.catatan_reaksi || '';
    }
    if (mrData.success && mrData.transaction) {
      const tx = mrData.transaction;
      document.getElementById('emr-biaya-layanan').value = tx.biaya_konsultasi || 75000;
      document.getElementById('emr-biaya-tindakan').value = tx.biaya_tindakan || 0;
      document.getElementById('emr-biaya-obat').value = tx.biaya_obat_vaksin || 0;
    }
  } catch (e) {}

  recalcEmrTotalPreview();

  // Pre-open subtab based on Poli
  if (poli === 'gigi') {
    switchEmrSubTab('gigi');
  } else if (poli === 'vaksin') {
    switchEmrSubTab('vaksin');
  } else {
    switchEmrSubTab('umum');
  }

  // Update status to 'ruang_dokter' on the live board if not already finished
  fetch('api/queues.php?action=update_status', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: idAntrean, status: 'ruang_dokter' })
  });

  modal.classList.remove('hidden');
}

function setQuickDiagnosis(diagText) {
  const el = document.getElementById('emr-diagnosis');
  if (el) el.value = diagText;
}

function addQuickDentalAction(actionName, cost) {
  const catatanGigiEl = document.getElementById('emr-catatan-gigi');
  const tindakanEl = document.getElementById('emr-tindakan-medis');
  const biayaTindakanEl = document.getElementById('emr-biaya-tindakan');
  const diagEl = document.getElementById('emr-diagnosis');

  if (catatanGigiEl) {
    catatanGigiEl.value = catatanGigiEl.value ? `${catatanGigiEl.value}; ${actionName}` : actionName;
  }
  if (tindakanEl) {
    tindakanEl.value = tindakanEl.value ? `${tindakanEl.value}; ${actionName}` : actionName;
  }
  if (biayaTindakanEl) {
    const cur = parseInt(biayaTindakanEl.value || 0, 10);
    biayaTindakanEl.value = cur + cost;
  }
  if (diagEl && !diagEl.value) {
    diagEl.value = 'K02.1 - Karies & Perawatan Gigi Susu Anak';
  }
  recalcEmrTotalPreview();
  showToast(`Tindakan gigi "${actionName}" ditambahkan (+${formatRupiah(cost)})`, 'success');
}

function onEmrVaccineSelect(selectEl) {
  const opt = selectEl.options[selectEl.selectedIndex];
  if (!opt) return;

  const idStok = parseInt(opt.getAttribute('data-id') || 0, 10);
  const price = parseInt(opt.getAttribute('data-price') || 0, 10);
  const kode = opt.getAttribute('data-kode') || 'VKN';

  document.getElementById('emr-id-stok-vaksin').value = idStok;

  if (idStok > 0) {
    const batchEl = document.getElementById('emr-nomor-batch');
    if (batchEl && !batchEl.value) {
      batchEl.value = `BATCH-${kode}-${new Date().getFullYear()}`;
    }
    const nextDateEl = document.getElementById('emr-jadwal-vaksin');
    if (nextDateEl && !nextDateEl.value) {
      const nextMonth = new Date();
      nextMonth.setMonth(nextMonth.getMonth() + 1);
      nextDateEl.value = nextMonth.toISOString().split('T')[0];
    }
    const obatCostEl = document.getElementById('emr-biaya-obat');
    if (obatCostEl) {
      obatCostEl.value = price;
    }
    const diagEl = document.getElementById('emr-diagnosis');
    if (diagEl && !diagEl.value) {
      diagEl.value = `Z23 - Imunisasi ${selectEl.value}`;
    }
    recalcEmrTotalPreview();
    showToast(`Vaksin dipilih: harga ${formatRupiah(price)} otomatis masuk ke tagihan kasir`, 'info');
  }
}

function addMedicineToPrescription() {
  const medSelect = document.getElementById('emr-quick-medicine-select');
  const doseInput = document.getElementById('emr-quick-medicine-dose');
  const resepEl = document.getElementById('emr-resep-obat');
  const biayaObatEl = document.getElementById('emr-biaya-obat');

  if (!medSelect || !medSelect.value) {
    showToast('Pilih obat dari daftar stok terlebih dahulu', 'error');
    return;
  }

  const opt = medSelect.options[medSelect.selectedIndex];
  const price = parseInt(opt.getAttribute('data-price') || 0, 10);
  const medName = medSelect.value;
  const dose = doseInput?.value || '3 x 1 takar sesudah makan';

  const line = `- ${medName} (${dose})`;
  if (resepEl) {
    resepEl.value = resepEl.value ? `${resepEl.value}\n${line}` : line;
  }
  if (biayaObatEl) {
    const cur = parseInt(biayaObatEl.value || 0, 10);
    biayaObatEl.value = cur + price;
  }

  recalcEmrTotalPreview();
  showToast(`${medName} ditambahkan ke resep (+${formatRupiah(price)})`, 'success');
}

function recalcEmrTotalPreview() {
  const k = parseInt(document.getElementById('emr-biaya-layanan')?.value || 0, 10);
  const t = parseInt(document.getElementById('emr-biaya-tindakan')?.value || 0, 10);
  const o = parseInt(document.getElementById('emr-biaya-obat')?.value || 0, 10);
  const adminFee = 10000;
  const total = k + t + o + adminFee;

  const previewEl = document.getElementById('emr-total-bill-preview');
  if (previewEl) {
    previewEl.textContent = formatRupiah(total);
  }
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
    id_stok_vaksin: document.getElementById('emr-id-stok-vaksin')?.value || 0,
    nama_vaksin: form.nama_vaksin ? form.nama_vaksin.value : '',
    nomor_batch: form.nomor_batch ? form.nomor_batch.value : '',
    dosis_ke: form.dosis_ke ? form.dosis_ke.value : 1,
    jadwal_vaksin_berikutnya: form.jadwal_vaksin_berikutnya ? form.jadwal_vaksin_berikutnya.value : null,
    catatan_reaksi: form.catatan_reaksi ? form.catatan_reaksi.value : 'Diberikan tanpa kontraindikasi'
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
      loadCashierBilling();
      loadStockTable();
      loadAsistenVaccineStock();
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
              <button onclick="adjustItemStock(${item.id}, 1)" class="px-2 py-1 bg-cyan-50 text-cyan-700 rounded-lg text-xs font-bold hover:bg-cyan-100" title="Tambah 1 stok">+1</button>
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
      loadAsistenVaccineStock();
    }
  } catch (e) {
    showToast('Gagal menyesuaikan stok', 'error');
  }
}

function openAddStockModal(defaultCategory = 'vaksin') {
  const modal = document.getElementById('modal-add-stock');
  if (!modal) return;
  const katEl = document.getElementById('stock-new-kategori');
  const kodeEl = document.getElementById('stock-new-kode');
  const satuanEl = document.getElementById('stock-new-satuan');
  if (katEl) katEl.value = defaultCategory;
  if (kodeEl) {
    const prefix = defaultCategory === 'vaksin' ? 'VKN-' : (defaultCategory === 'obat' ? 'OBT-' : 'ALT-');
    kodeEl.value = prefix + Math.floor(100 + Math.random() * 900);
  }
  if (satuanEl) {
    satuanEl.value = defaultCategory === 'vaksin' ? 'Vial' : 'Botol';
  }
  modal.classList.remove('hidden');
}

function closeAddStockModal() {
  const modal = document.getElementById('modal-add-stock');
  if (modal) modal.classList.add('hidden');
}

async function submitAddStockItem(e) {
  e.preventDefault();
  const payload = {
    kategori: document.getElementById('stock-new-kategori').value,
    kode: document.getElementById('stock-new-kode').value.trim(),
    nama_item: document.getElementById('stock-new-nama').value.trim(),
    stok: parseInt(document.getElementById('stock-new-stok').value || 10, 10),
    satuan: document.getElementById('stock-new-satuan').value.trim(),
    harga_satuan: parseInt(document.getElementById('stock-new-harga').value || 0, 10),
    deskripsi: document.getElementById('stock-new-deskripsi').value.trim()
  };

  try {
    const res = await fetch('api/stock.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      closeAddStockModal();
      loadStockTable();
      loadAsistenVaccineStock();
    } else {
      showToast(data.error || 'Gagal menambah stok baru', 'error');
    }
  } catch (err) {
    showToast('Gagal menyimpan item stok baru', 'error');
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
      document.getElementById('rep-omzet-konsul').textContent = data.summary.omzet_konsultasi_fmt || data.summary.omzet_konsul_fmt || 'Rp 0';
      document.getElementById('rep-omzet-tindakan').textContent = data.summary.omzet_tindakan_fmt;
      document.getElementById('rep-omzet-obat').textContent = data.summary.omzet_obat_fmt;
      const adminEl = document.getElementById('rep-omzet-admin');
      if (adminEl) adminEl.textContent = data.summary.omzet_admin_fmt;

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
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
              <div><span class="text-slate-400">Tensi:</span> <strong class="text-indigo-700 font-bold">${v.tensi_darah || '100/65 mmHg'}</strong></div>
              <div><span class="text-slate-400">Suhu:</span> <strong class="text-rose-600 font-bold">${v.suhu_tubuh} °C</strong></div>
              <div><span class="text-slate-400">Berat Badan:</span> <strong class="text-slate-800 font-bold">${v.berat_badan} kg</strong></div>
              <div><span class="text-slate-400">Tinggi Badan:</span> <strong class="text-slate-800 font-bold">${v.tinggi_badan} cm</strong></div>
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

