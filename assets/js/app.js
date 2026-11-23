/**
 * Klinik Anak Happy - Core JavaScript Utilities
 */

// Web Audio API Ding-Dong Chime Generator (No external audio file needed!)
function playQueueChime(callback) {
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) {
      if (callback) callback();
      return;
    }
    const ctx = new AudioContext();
    if (ctx.state === 'suspended') {
      ctx.resume();
    }

    const now = ctx.currentTime;
    
    // Note 1 (High chime - 880Hz / A5)
    const osc1 = ctx.createOscillator();
    const gain1 = ctx.createGain();
    osc1.type = 'sine';
    osc1.frequency.setValueAtTime(659.25, now); // E5
    gain1.gain.setValueAtTime(0.3, now);
    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
    osc1.connect(gain1);
    gain1.connect(ctx.destination);
    osc1.start(now);
    osc1.stop(now + 0.6);

    // Note 2 (Low chime - 523.25Hz / C5)
    const osc2 = ctx.createOscillator();
    const gain2 = ctx.createGain();
    osc2.type = 'sine';
    osc2.frequency.setValueAtTime(523.25, now + 0.35); // C5
    gain2.gain.setValueAtTime(0.35, now + 0.35);
    gain2.gain.exponentialRampToValueAtTime(0.001, now + 1.1);
    osc2.connect(gain2);
    gain2.connect(ctx.destination);
    osc2.start(now + 0.35);
    osc2.stop(now + 1.1);

    setTimeout(() => {
      if (callback) callback();
    }, 1100);
  } catch (e) {
    console.warn('AudioContext not permitted yet:', e);
    if (callback) callback();
  }
}

// Indonesian Text-to-Speech Vocalizer for Queue Calling
function announceQueue(nomorAntrean, namaAnak, namaRuangan) {
  playQueueChime(() => {
    if (!('speechSynthesis' in window)) return;
    
    // Stop any ongoing speech
    window.speechSynthesis.cancel();

    // Format text: e.g. "Nomor antrean U kosong kosong satu, ananda Kenzo, silakan menuju Ruang Dokter."
    // Spreading letters for natural pronunciation
    let formattedNo = nomorAntrean.replace('-', ' ').split('').join(' ').replace(/0/g, 'kosong');
    let text = `Nomor antrean ${formattedNo}. Ananda ${namaAnak}. Silakan menuju ke ${namaRuangan}.`;

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID';
    utterance.rate = 0.92; // Slightly calm and clear for clinic environment
    utterance.pitch = 1.05;

    // Try finding Indonesian voice if available
    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(v => v.lang.startsWith('id') || v.name.includes('Indonesian') || v.name.includes('Indonesia'));
    if (idVoice) {
      utterance.voice = idVoice;
    }

    window.speechSynthesis.speak(utterance);
  });
}

// Calculate child age from birthdate string (YYYY-MM-DD)
function calculateChildAge(birthDateStr) {
  if (!birthDateStr) return null;
  const dob = new Date(birthDateStr);
  const today = new Date();
  
  if (isNaN(dob.getTime())) return null;

  let years = today.getFullYear() - dob.getFullYear();
  let months = today.getMonth() - dob.getMonth();
  let days = today.getDate() - dob.getDate();

  if (days < 0) {
    months--;
  }
  if (months < 0) {
    years--;
    months += 12;
  }

  const isUnder15 = years < 15;
  const text = (years > 0 ? `${years} tahun ` : '') + `${months} bulan`;

  return {
    years,
    months,
    text,
    isUnder15
  };
}

// Toast Notification
function showToast(message, type = 'info') {
  const container = document.getElementById('toast-container') || createToastContainer();
  const toast = document.createElement('div');
  
  let bg = 'bg-slate-900 text-white';
  let icon = 'ℹ️';
  if (type === 'success') {
    bg = 'bg-emerald-600 text-white';
    icon = '✓';
  } else if (type === 'error') {
    bg = 'bg-rose-600 text-white';
    icon = '⚠️';
  } else if (type === 'warning') {
    bg = 'bg-amber-600 text-white';
    icon = '🔔';
  }

  toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 transform translate-y-2 opacity-0 ${bg}`;
  toast.innerHTML = `<span class="text-base font-bold">${icon}</span> <span>${message}</span>`;
  
  container.appendChild(toast);

  requestAnimationFrame(() => {
    toast.classList.remove('translate-y-2', 'opacity-0');
  });

  setTimeout(() => {
    toast.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

function createToastContainer() {
  const c = document.createElement('div');
  c.id = 'toast-container';
  c.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm pointer-events-none';
  document.body.appendChild(c);
  return c;
}

// Format Rupiah
function formatRupiah(num) {
  return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

