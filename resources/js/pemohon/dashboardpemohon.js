// resources/js/pemohon/dashboardpemohon.js

function onReady(fn) {
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
  else fn();
}

function initAccountModal() {
  const openBtn = document.getElementById('openAccount');
  const modal = document.getElementById('paModal');
  const backdrop = document.getElementById('paBackdrop');
  const closeBtn = document.getElementById('closeAccount');
  if (!openBtn || !modal || !backdrop) return;

  const open = () => {
    modal.hidden = false;
    backdrop.hidden = false;
    document.body.style.overflow = 'hidden';
  };

  const close = () => {
    modal.hidden = true;
    backdrop.hidden = true;
    document.body.style.overflow = '';
  };

  openBtn.addEventListener('click', (e) => {
    e.preventDefault();
    open();
  });

  backdrop.addEventListener('click', close);
  closeBtn?.addEventListener('click', close);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.hidden) close();
  });
}

function initTrackerHighlight() {
  const tracker = document.querySelector('.pd-tracker');
  if (!tracker) return;

  const active = tracker.getAttribute('data-active');
  const order = ['terkirim', 'proses', 'revisi', 'approve'];
  const activeIndex = order.indexOf(active);

  document.querySelectorAll('.pd-step').forEach((el) => {
    const key = el.getAttribute('data-step');
    const idx = order.indexOf(key);

    el.classList.remove('is-done', 'is-run', 'is-todo');

    if (activeIndex === -1) return el.classList.add('is-todo');

    if (idx < activeIndex) el.classList.add('is-done');
    else if (idx === activeIndex) el.classList.add(active === 'approve' ? 'is-done' : 'is-run');
    else el.classList.add('is-todo');
  });
}


function refreshSelengkapnya(root = document) {
  root.querySelectorAll('.pd-note-cell').forEach((cell) => {
    const clamp = cell.querySelector('.pd-note-clamp');
    const full  = cell.querySelector('.pd-note-full');
    const btn   = cell.querySelector('.jsNoteToggle');
    if (!clamp || !full || !btn) return;

    const isOpen = !full.hasAttribute('hidden');
    if (isOpen) return;
    const rect = clamp.getBoundingClientRect();
    if (rect.width === 0 || rect.height === 0) {
      btn.hidden = true;
      return;
    }

    const overflow = clamp.scrollHeight > clamp.clientHeight + 1;
    btn.hidden = !overflow;

    if (!overflow) {
      full.setAttribute('hidden', 'hidden');
      clamp.removeAttribute('hidden');
      btn.textContent = 'Selengkapnya';
    }
  });
}

function initPemohonDashboardUI() {
  // ===== modal note elements
  const modal = document.getElementById('pdNoteModal');
  const backdrop = document.getElementById('pdNoteBackdrop');
  const btnClose = document.getElementById('pdNoteClose');
  const titleEl = document.getElementById('pdNoteTitle');
  const bodyEl = document.getElementById('pdNoteBody');

  const openNote = (title, note) => {
    if (!modal || !backdrop || !titleEl || !bodyEl) return;
    titleEl.textContent = title || 'Detail';
    bodyEl.textContent = note || '-';
    modal.hidden = false;
    backdrop.hidden = false;
  };

  const closeNote = () => {
    if (modal) modal.hidden = true;
    if (backdrop) backdrop.hidden = true;
  };

  btnClose?.addEventListener('click', closeNote);
  backdrop?.addEventListener('click', closeNote);

  document.addEventListener(
    'click',
    (e) => {
      // ===== 1) Toggle Detail Dokumen (TERKIRIM)
      const btnDok = e.target.closest('.btnDetailDok');
      if (btnDok) {
        e.preventDefault();
        const wrap = btnDok.closest('.pd-step-body') || btnDok.parentElement;
        const box = wrap?.querySelector('.boxDetailDok');
        if (box) {
          const willOpen = (box.style.display === 'none' || box.style.display === '');
          box.style.display = willOpen ? 'block' : 'none';

          if (willOpen) {
            requestAnimationFrame(() => refreshSelengkapnya(box));
          }
        }
        return;
      }

      // ===== 2) Toggle Detail Revisi
      const btnRev = e.target.closest('.btnRevisi');
      if (btnRev) {
        e.preventDefault();
        const box = document.getElementById('boxRevisi');
        if (box) {
          const willOpen = (box.style.display === 'none' || box.style.display === '');
          box.style.display = willOpen ? 'block' : 'none';

          if (willOpen) {
            requestAnimationFrame(() => refreshSelengkapnya(box));
          }
        }
        return;
      }

      // ===== 3) Klik "Lihat detail" → buka modal (CATATAN ADMIN)
      const btnOpenNote = e.target.closest('.jsOpenNote');
      if (btnOpenNote) {
        e.preventDefault();
        openNote(btnOpenNote.dataset.title, btnOpenNote.dataset.note);
        return;
      }

      // ===== 4) Toggle Selengkapnya / Tutup (INLINE)
      const btnToggle = e.target.closest('.jsNoteToggle');
      if (btnToggle) {
        e.preventDefault();

        const cell = btnToggle.closest('.pd-note-cell');
        if (!cell) return;

        const clamp = cell.querySelector('.pd-note-clamp');
        const full  = cell.querySelector('.pd-note-full');
        if (!clamp || !full) return;

        const isOpen = !full.hasAttribute('hidden');

        if (isOpen) {
          full.setAttribute('hidden', 'hidden');
          clamp.removeAttribute('hidden');
          btnToggle.textContent = 'Selengkapnya';
       } else {
  full.removeAttribute('hidden');
  clamp.setAttribute('hidden', 'hidden');
  btnToggle.textContent = 'Tutup';
}
        return;
      }
    },
    true
  );

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeNote();
  });

  requestAnimationFrame(() => refreshSelengkapnya(document));
}

function boot() {
  if (document.documentElement.dataset.pemohonDashInit === '1') return;
  document.documentElement.dataset.pemohonDashInit = '1';

  initAccountModal();
  initTrackerHighlight();
  initPemohonDashboardUI();

  window.addEventListener('resize', () => refreshSelengkapnya(document));
}

onReady(boot);

document.addEventListener("DOMContentLoaded", () => {
  const btnLogout = document.getElementById("btnLogout");
  const form = document.getElementById("logoutForm");

  if (!btnLogout || !form) return;

  btnLogout.addEventListener("click", function (e) {
    e.preventDefault();

    Swal.fire({
      title: "Logout?",
      text: "Kamu yakin mau logout?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#2563eb",
      cancelButtonColor: "#6b7280",
      confirmButtonText: "Ya, logout",
      cancelButtonText: "Batal",
      focusCancel: true,
      allowEnterKey: false
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  });
});

document.addEventListener("click", function (e) {
  if (e.target.classList.contains("btn-choose-file")) {
    const form = e.target.closest(".pd-upload-form");
    const input = form?.querySelector(".js-file-input");
    if (input) input.click();
  }
});

document.addEventListener("change", function (e) {
  if (e.target.matches(".js-file-input")) {
    const file = e.target.files[0];
    const fileName = file ? file.name : "Belum pilih file";

    const form = e.target.closest(".pd-upload-form");
    const label = form?.querySelector(".pd-file-name");

    if (label) {
      label.textContent = fileName;
    }
  }
});

document.addEventListener("change", function (e) {
  if (e.target.matches(".js-file-input")) {
    const file = e.target.files[0];
    if (!file) return;

    const maxSize = 10 * 1024 * 1024;

    // CEK SIZE
    if (file.size > maxSize) {
      Swal.fire({
        icon: "error",
        title: "Ukuran Terlalu Besar",
        text: "Maksimal file 10MB",
        confirmButtonColor: "#2563eb"
      });

      e.target.value = "";
      return;
    }

    // CEK FORMAT
    const allowed = ["pdf", "doc", "docx"];
    const ext = file.name.split(".").pop().toLowerCase();

    if (!allowed.includes(ext)) {
      Swal.fire({
        icon: "error",
        title: "Format Tidak Didukung",
        text: "Gunakan file PDF, DOC, atau DOCX.",
        confirmButtonColor: "#2563eb"
      });

      e.target.value = "";
      return;
    }
  }
});

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".btnPendaftaran").forEach((btn) => {

    btn.addEventListener("click", function (e) {
      e.preventDefault();

      const url = this.getAttribute("href");

      Swal.fire({
        title: "Konfirmasi Pendaftaran",
        text: "Apakah sudah melakukan konfirmasi datang ke Innovation Hub dan menyerahkan tanda terima?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#2563eb",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Sudah",
        cancelButtonText: "Belum",
        allowEnterKey: false,
        focusCancel: true
      }).then((result) => {

        // kalau klik SUDAH
        if (result.isConfirmed) {

          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: "Pendaftaran akan dilanjutkan.",
            timer: 1500,
            showConfirmButton: false
          });

          setTimeout(() => {
            window.location.href = url;
          }, 1500);

        }

        // kalau klik BELUM
        else if (result.dismiss === Swal.DismissReason.cancel) {

          Swal.fire({
            icon: "warning",
            title: "Konfirmasi Diperlukan",
            text: "Mohon lakukan konfirmasi datang ke Innovation Hub dan menyerahkan tanda terima terlebih dahulu.",
            confirmButtonColor: "#2563eb"
          });

        }

      });

    });

  });
});