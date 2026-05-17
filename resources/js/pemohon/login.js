document.addEventListener("DOMContentLoaded", () => {
  // ===== MODAL GANTI PASSWORD =====
  const openBtn = document.getElementById("openChangePw");
  const modal = document.getElementById("cpModal");
  const backdrop = document.getElementById("cpBackdrop");
  const closeBtn = document.getElementById("closeChangePw");
  const cancelBtn = document.getElementById("cancelChangePw");

  const modalUsername = document.getElementById("cpUsername");
  const ownerEmail = document.getElementById("cpOwnerEmail");

  const open = () => {
    if (!modal || !backdrop) return;
    modal.hidden = false;
    backdrop.hidden = false;
    document.body.style.overflow = "hidden";
    if (modalUsername) modalUsername.focus();
  };

  const close = () => {
    if (!modal || !backdrop) return;
    modal.hidden = true;
    backdrop.hidden = true;
    document.body.style.overflow = "";
  };

  async function loadOwnerEmail() {
    if (!modalUsername || !ownerEmail) return;

    const kode = (modalUsername.value || "").trim();
    ownerEmail.value = "";

    if (!kode) return;

    try {
      const res = await fetch(`/pemohon/get-owner-email?kode=${encodeURIComponent(kode)}`, {
        headers: { Accept: "application/json" }
      });

      const data = await res.json();
      console.log("OWNER EMAIL RESPONSE:", data);

      if (data.ok && data.email) {
        ownerEmail.value = data.email;
      } else {
        ownerEmail.value = "";
      }
    } catch (err) {
      console.error("Gagal ambil owner email:", err);
      ownerEmail.value = "";
    }
  }

  if (openBtn && modal && backdrop) {
    openBtn.addEventListener("click", (e) => {
      e.preventDefault();
      open();
    });

    backdrop.addEventListener("click", close);
    closeBtn?.addEventListener("click", close);
    cancelBtn?.addEventListener("click", close);

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !modal.hidden) close();
    });
  }

  let ownerEmailTimer = null;

if (modalUsername) {
  modalUsername.addEventListener("input", () => {
    clearTimeout(ownerEmailTimer);

    ownerEmailTimer = setTimeout(() => {
      loadOwnerEmail();
    }, 300); 
  });
}

  // ===== SHOW / HIDE PASSWORD LOGIN =====
  const pwInput = document.getElementById("pwInput");
  const toggleBtn = document.getElementById("pwToggle");

  if (pwInput && toggleBtn) {
    toggleBtn.hidden = false;

    toggleBtn.addEventListener("click", () => {
      const shown = pwInput.type === "text";
      pwInput.type = shown ? "password" : "text";
      toggleBtn.classList.toggle("is-shown", !shown);
      pwInput.focus();
    });
  }
});