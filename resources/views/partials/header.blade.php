<header>

  <div class="topbar">
    <div class="topbar-inner container">
      <span><i class="bi bi-telephone-fill"></i> +62 811-3848-555</span>
      <a class="topbar-link" href="mailto:dirinovki2026@gmail.com">
        <i class="bi bi-envelope-fill"></i> dirinovki2026@gmail.com
        </a>

    </div>
  </div>

  <div class="navbar">
    <div class="navbar-inner container">

      <div class="brand-group">
        <a href="https://dirinovki.undip.ac.id" class="brand">
         @php
          $dirinovkiLogoUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url('Logo Dirinovki 2026.jpg');
          @endphp

          <img 
            src="{{ $dirinovkiLogoUrl }}" 
            class="brand-logo" 
            alt="Logo"
          >
          </a>

        <span class="brand-sep"></span>

        <a href="https://undip.ac.id/" class="brand brand-impact">
          @php
            $impactLogoUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url('Dikti-Berdampak-Undip Bermartabat Bermanfaat.png');
            @endphp

            <img 
              src="{{ $impactLogoUrl }}"
              class="brand-logo brand-logo--impact"
              alt="Logo Undip Berdampak"
            >
        </a>
      </div>


      <div class="nav-right">
        <nav class="nav-menu">
            <a href="https://dirinovki.undip.ac.id/" class="nav-link">Beranda</a>

            <a href="https://dirinovki.undip.ac.id/" class="nav-link">
                Profil <i class="bi bi-chevron-down"></i>

            </a>

            <a href="https://dirinovki.undip.ac.id/" class="nav-link">
                Data Kekayaan Intelektual <i class="bi bi-chevron-down"></i>
            </a>

            <a href="https://dirinovki.undip.ac.id/" class="nav-link">
                Panduan KI <i class="bi bi-chevron-down"></i>
            </a>

            <a href="https://dirinovki.undip.ac.id/" class="nav-link">
                Inovasi dan Hilirisasi Industri <i class="bi bi-chevron-down"></i>
            </a>
            </nav>

        <a href="#" class="nav-button">
            Pendaftaran Kekayaan Intelektual
        </a>
    </div>

    </div>
  </div>

</header>