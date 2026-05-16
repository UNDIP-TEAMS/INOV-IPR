@extends('layouts.app')

@section('title','Hak Paten')
@section('body-class','paten-page')

@section('content')

@php $activeStep = 3; @endphp
@include('hakpaten.isiformulir.menuformulir')

@php
  $isiform = session('hakpaten.isiform', []);
  $invensi = session('hakpaten.invensi', []);
$verifSession = session('hakpaten.verif', []);
@endphp

@php
  $raw = old('inventor', data_get($isiform,'inventor', []));
  if (isset($raw[0]) && is_array($raw[0])) {
    $norm = [
      'nama'=>[], 'nip_nim'=>[], 'nidn'=>[], 'fakultas'=>[],
      'no_hp'=>[], 'email'=>[], 'status'=>[]
    ];
    foreach ($raw as $row) {
      $norm['nama'][] = $row['nama'] ?? '';
      $norm['nip_nim'][] = $row['nip_nim'] ?? '';
      $norm['nidn'][] = $row['nidn'] ?? '';
      $norm['fakultas'][] = $row['fakultas'] ?? '';
      $norm['no_hp'][] = $row['no_hp'] ?? '';
      $norm['email'][] = $row['email'] ?? '';
      $norm['status'][] = $row['status'] ?? '';
    }
  } else {
    $norm = $raw; // per-field
  }
@endphp

@php
  $prefill = [
    'nama'     => data_get($isiform, 'inventor.nama', []),  // ini yang udah pasti ada dari isiform
    'nip_nim'  => data_get($isiform, 'inventor.nip_nim', []), // kalau belum ada di isiform ya jadi []
    'nidn'     => data_get($isiform, 'inventor.nidn', []),
    'fakultas' => data_get($isiform, 'inventor.fakultas', []),
    'no_hp'    => data_get($isiform, 'inventor.no_hp', []),
    'email'    => data_get($isiform, 'inventor.email', []),
    'status'   => data_get($isiform, 'inventor.status', []),
  ];
@endphp

<script type="application/json" id="prefill-inventor-data">
{!! json_encode(old('inventor', $prefill)) !!}
</script>

<script type="application/json" id="prefill-count">
{!! json_encode(old('jumlah_inventor', data_get($isiform, 'jumlah_inventor', 1))) !!}
</script>

@php
  $prefillCount = (int) old('jumlah_inventor', data_get($isiform, 'jumlah_inventor', 1));
@endphp

<section class="section-full section-isi">
  <div class="section-inner">
    <h1 class="datadiri-title">Data Pemohon <span class="req">*</span></h1>
    @if ($errors->any())
      <div class="alert alert-danger">
          <ul>
              @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
              @endforeach
          </ul>
      </div>
  @endif

    <form id="draftForm"class="paten-form" action="{{ route('patenverif.start') }}" method="POST" novalidate>
      @csrf

      <div class="form-2col">
        {{-- LEFT --}}
        <div class="col-left">

          <div class="field">
            <label class="label">Jumlah inventor <span class="req">*</span></label>
            <div class="jumlah-inventor-wrap" style="display:flex; gap:10px; align-items:center;">
            <button type="button" id="invMinus" class="btn-minus" aria-label="Kurangi inventor">-</button>

            <input
              type="number"
              class="input"
              id="jumlah_inventor_verif"
              name="jumlah_inventor"
              min="1"
              max="20"
              value="{{ old('jumlah_inventor', $prefillCount) }}"
              required readonly
            >
           <button type="button" id="invPlus" class="btn-plus" aria-label="Tambah inventor">+</button>
          </div>
          </div>

          <div class="field">
            <label class="label">Jenis Pengajuan Paten <span class="req">*</span></label>
            <select class="input" disabled>
              <option value="" disabled>
                -- Jenis Pengajuan Paten --
              </option>

              <option value="Paten"
                {{ old('jenis_paten', data_get($isiform,'jenis_paten','')) == 'Paten' ? 'selected' : '' }}>
                Paten
              </option>

              <option value="Paten Sederhana"
                {{ old('jenis_paten', data_get($isiform,'jenis_paten','')) == 'Paten Sederhana' ? 'selected' : '' }}>
                Paten Sederhana
              </option>
            </select>

            {{-- hidden input yang tetap dikirim --}}
            <input
              type="hidden"
              name="jenis_paten"
              value="{{ old('jenis_paten', data_get($isiform,'jenis_paten','')) }}"
            >
          </div>

          <div class="field">
            <label class="label">Judul Paten <span class="req">*</span></label>
            <input
              type="text"
              class="input"
              name="judul_paten"
              value="{{ old('judul_paten', data_get($isiform,'judul_invensi','')) }}"
              required readonly
            >
          </div>
        </div>

        {{-- RIGHT --}}
        <div class="col-right">
          <div class="field">
            <label class="label">Prototipe <span class="req">*</span></label>
            <select class="input prototipe-select" name="prototipe" required>
                <option value="" disabled {{ old('prototipe', data_get($verifSession, 'prototipe')) ? '' : 'selected' }}>
                    -- Prototipe --
                </option>

                <option value="Sudah" {{ old('prototipe', data_get($verifSession, 'prototipe')) == 'Sudah' ? 'selected' : '' }}>
                    Sudah
                </option>

                <option value="Belum" {{ old('prototipe', data_get($verifSession, 'prototipe')) == 'Belum' ? 'selected' : '' }}>
                    Belum
                </option>
            </select>
          </div>
          <small class="hint prototipe-note">
            Warna biru menandakan <strong>prototipe sudah tersedia</strong>, 
            sedangkan warna kuning menandakan <strong>prototipe belum tersedia</strong>.
          </small>

          <div class="field">
            <label class="label">Nilai Perolehan <span class="req">*</span></label>
            <p class="hint">Jumlah biaya yang dibutuhkan untuk menghasilkan invensi</p>
            <input
              type="text"
              class="input"
              placeholder="Masukkan nilai perolehan"
              name="nilai_perolehan"
              value="{{ old('nilai_perolehan', data_get($verifSession,'nilai_perolehan','')) }}"
              required
            >
          </div>

          <div class="field">
            <label class="label">Sumber Dana <span class="req">*</span></label>
            <select class="input" name="sumber_dana" required>
              <option value="" disabled {{ old('sumber_dana', data_get($verifSession, 'sumber_dana')) ? '' : 'selected' }}>
                -- Sumber Dana --
              </option>

              <option value="Universitas Diponegoro"
                {{ old('sumber_dana', data_get($verifSession, 'sumber_dana')) == 'Universitas Diponegoro' ? 'selected' : '' }}>
                Universitas Diponegoro
              </option>

              <option value="APBN/APBD/Swasta"
                {{ old('sumber_dana', data_get($verifSession, 'sumber_dana')) == 'APBN/APBD/Swasta' ? 'selected' : '' }}>
                APBN/APBD/Swasta
              </option>

              <option value="Mandiri"
                {{ old('sumber_dana', data_get($verifSession, 'sumber_dana')) == 'Mandiri' ? 'selected' : '' }}>
                Mandiri
              </option>
            </select>
          </div>
        </div>
      </div>

      <div class="hr"></div>

      <div class="field field-full">
        <div class="field">
            <label class="label">Data Inventor <span class="req">*</span></label>

            <div id="inventor-container-verif"></div>

            <template id="inventor-template-first-verif">
              <div class="inventor-card">
                <p class="inventor-head">Inventor <span class="inv-no"></span></p>
                <p>Catatan: Data Inventor 1 HARUS diisi dengan data Dosen</p>

                <div class="inventor-grid">
                  <div class="inventor-col">
                    <div class="field">
                      <label class="label">Nama Inventor <span class="req">*</span></label>
                      <input type="text" class="input" name="inventor[nama][]" placeholder="Nama lengkap" required readonly>
                    </div>

                    <div class="field">
                      <label class="label">NIP/NIM <span class="req">*</span></label>
                      <input
                        type="text"
                        class="input nip-input"
                        name="inventor[nip_nim][]"
                        placeholder="Masukkan NIP/NIM Anda"
                        required readonly
                      >
                      <small class="nip-warning">
                        NIP/NIM harus terdiri dari 14 atau 18 digit angka
                      </small>

                    </div>

                    <div class="field">
                      <label class="label">Fakultas <span class="req">*</span></label>

                      {{-- Select hanya untuk tampilan --}}
                      <select class="input" disabled>
                        <option value="" disabled>-- Pilih Fakultas --</option>

                        <option value="Fakultas Teknik"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Teknik' ? 'selected' : '' }}>
                          Fakultas Teknik
                        </option>

                        <option value="Fakultas Sains dan Matematika"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Sains dan Matematika' ? 'selected' : '' }}>
                          Fakultas Sains dan Matematika
                        </option>

                        <option value="Fakultas Kesehatan Masyarakat"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Kesehatan Masyarakat' ? 'selected' : '' }}>
                          Fakultas Kesehatan Masyarakat
                        </option>

                        <option value="Fakultas Kedokteran"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Kedokteran' ? 'selected' : '' }}>
                          Fakultas Kedokteran
                        </option>

                        <option value="Fakultas Perikanan dan Ilmu Kelautan"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Perikanan dan Ilmu Kelautan' ? 'selected' : '' }}>
                          Fakultas Perikanan dan Ilmu Kelautan
                        </option>

                        <option value="Fakultas Peternakan dan Pertanian"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Peternakan dan Pertanian' ? 'selected' : '' }}>
                          Fakultas Peternakan dan Pertanian
                        </option>

                        <option value="Fakultas Psikologi"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Psikologi' ? 'selected' : '' }}>
                          Fakultas Psikologi
                        </option>

                        <option value="Fakultas Hukum"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Hukum' ? 'selected' : '' }}>
                          Fakultas Hukum
                        </option>

                        <option value="Fakultas Ilmu Sosial dan Ilmu Politik"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Ilmu Sosial dan Ilmu Politik' ? 'selected' : '' }}>
                          Fakultas Ilmu Sosial dan Ilmu Politik
                        </option>

                        <option value="Fakultas Ilmu Budaya"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Ilmu Budaya' ? 'selected' : '' }}>
                          Fakultas Ilmu Budaya
                        </option>

                        <option value="Fakultas Ekonomi dan Bisnis"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Ekonomi dan Bisnis' ? 'selected' : '' }}>
                          Fakultas Ekonomi dan Bisnis
                        </option>

                        <option value="Sekolah Vokasi"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Sekolah Vokasi' ? 'selected' : '' }}>
                          Sekolah Vokasi
                        </option>

                        <option value="Sekolah Pasca Sarjana"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Sekolah Pasca Sarjana' ? 'selected' : '' }}>
                          Sekolah Pasca Sarjana
                        </option>
                      </select>

                      {{-- Hidden input yang dikirim --}}
                      <input
                        type="hidden"
                        name="inventor[fakultas][]"
                        value="{{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) }}"
                      >
                    </div>
                  </div>

                  <div class="inventor-col">
                    <div class="field">
                      <label class="label">No. HP<span class="req">*</span></label>
                      <input type="text" class="input hp-input" name="inventor[no_hp][]" placeholder="08xxxxxxxxxx" required readonly>
                      <small class="hp-warning">Nomor HP tidak valid (contoh: 081234567890)</small>
                    </div>

                    <div class="field">
                      <label class="label">Email <span class="req">*</span></label>
                      <input type="email" class="input email-input" name="inventor[email][]" placeholder="nama@email.com" required readonly>
                      <small class="email-warning">Format email tidak valid</small>
                    </div>

                    <!-- NIDN wajib untuk inventor 1 -->
                    <div class="field">
                      <label class="label">NIDN <span class="req">*</span></label>
                      <input
                        type="text"
                        class="input nidn-input"
                        name="inventor[nidn][]"
                        placeholder="NIDN" readonly
                      >
                      <small class="nidn-warning">NIDN harus 8 karakter</small>
                    </div>

                    <div class="field">
                      <label class="label">Status Inventor <span class="req">*</span></label>
                      <input type="text" class="input" value="Dosen" disabled>
                      <input type="hidden" name="inventor[status][]" value="Dosen">
                    </div>
                  </div>
                </div>
              </div>
            </template>



            <template id="inventor-template-verif">
              <div class="inventor-card">
                <p class="inventor-head">Inventor <span class="inv-no"></span></p>

                <div class="inventor-grid">
                  <div class="inventor-col">
                    <div class="field">
                      <label class="label">Nama Inventor <span class="req">*</span></label>
                      <input type="text" class="input" name="inventor[nama][]" placeholder="Nama lengkap" required readonly>
                    </div>

                    <div class="field">
                      <label class="label">NIP/NIM <span class="req">*</span></label>
                      <input
                        type="text"
                        class="input nip-input"
                        name="inventor[nip_nim][]"
                        placeholder="Masukkan NIP/NIM Anda"
                        required readonly
                      >
                      <small class="nip-warning">
                        NIP/NIM harus terdiri dari 14 atau 18 digit angka
                      </small>

                    </div>

                    <div class="field">
                      <label class="label">Fakultas <span class="req">*</span></label>

                      {{-- Select hanya untuk tampilan --}}
                      <select class="input" disabled>
                        <option value="" disabled>-- Pilih Fakultas --</option>

                        <option value="Fakultas Teknik"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Teknik' ? 'selected' : '' }}>
                          Fakultas Teknik
                        </option>

                        <option value="Fakultas Sains dan Matematika"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Sains dan Matematika' ? 'selected' : '' }}>
                          Fakultas Sains dan Matematika
                        </option>

                        <option value="Fakultas Kesehatan Masyarakat"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Kesehatan Masyarakat' ? 'selected' : '' }}>
                          Fakultas Kesehatan Masyarakat
                        </option>

                        <option value="Fakultas Kedokteran"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Kedokteran' ? 'selected' : '' }}>
                          Fakultas Kedokteran
                        </option>

                        <option value="Fakultas Perikanan dan Ilmu Kelautan"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Perikanan dan Ilmu Kelautan' ? 'selected' : '' }}>
                          Fakultas Perikanan dan Ilmu Kelautan
                        </option>

                        <option value="Fakultas Peternakan dan Pertanian"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Peternakan dan Pertanian' ? 'selected' : '' }}>
                          Fakultas Peternakan dan Pertanian
                        </option>

                        <option value="Fakultas Psikologi"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Psikologi' ? 'selected' : '' }}>
                          Fakultas Psikologi
                        </option>

                        <option value="Fakultas Hukum"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Hukum' ? 'selected' : '' }}>
                          Fakultas Hukum
                        </option>

                        <option value="Fakultas Ilmu Sosial dan Ilmu Politik"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Ilmu Sosial dan Ilmu Politik' ? 'selected' : '' }}>
                          Fakultas Ilmu Sosial dan Ilmu Politik
                        </option>

                        <option value="Fakultas Ilmu Budaya"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Ilmu Budaya' ? 'selected' : '' }}>
                          Fakultas Ilmu Budaya
                        </option>

                        <option value="Fakultas Ekonomi dan Bisnis"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Fakultas Ekonomi dan Bisnis' ? 'selected' : '' }}>
                          Fakultas Ekonomi dan Bisnis
                        </option>

                        <option value="Sekolah Vokasi"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Sekolah Vokasi' ? 'selected' : '' }}>
                          Sekolah Vokasi
                        </option>

                        <option value="Sekolah Pasca Sarjana"
                          {{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) == 'Sekolah Pasca Sarjana' ? 'selected' : '' }}>
                          Sekolah Pasca Sarjana
                        </option>
                      </select>

                      {{-- Hidden input yang dikirim --}}
                      <input
                        type="hidden"
                        name="inventor[fakultas][]"
                        value="{{ old('inventor.fakultas.0', data_get($isiform,'inventor.fakultas.0')) }}"
                      >
                    </div>
                  
                  </div>

                  <div class="inventor-col">
                    <div class="field">
                      <label class="label">No. HP <span class="req">*</span></label>
                      <input type="text" class="input" name="inventor[no_hp][]" placeholder="08xxxxxxxxxx" required readonly>
                      <small class="hp-warning">Nomor HP tidak valid (contoh: 081234567890)</small>
                    </div>

                    <div class="field">
                      <label class="label">Email <span class="req">*</span></label>
                      <input type="email" class="input" name="inventor[email][]" placeholder="nama@email.com" required readonly>
                      <small class="email-warning">Format email tidak valid</small>
                    </div>

                    <div class="field nidn-field" style="display:none;">
                      <label class="label">NIDN <span class="req">*</span></label>
                      <input type="text" class="input" name="inventor[nidn][]" placeholder="Masukkan NIDN" readonly>
                      <small class="nidn-warning">NIDN harus 8 karakter</small>
                    </div>

                    <div class="field">
                      <label class="label">Status Inventor <span class="req">*</span></label>
                      <select class="input" name="inventor[status][]" required readonly>
                        <option value="" selected disabled>-- Status Inventor --</option>
                        <option value="Dosen">Dosen</option>
                        <option value="Mahasiswa">Mahasiswa</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>

        <label class="label">Dihasilkan dari Skema Penelitian? <span class="req">*</span></label>
       @php
        $skemaImgUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url('Skema Penelitian.jpg');
        @endphp

        <img 
          src="{{ $skemaImgUrl }}" 
          class="skema-img" 
          alt="Skema"
        >
        <select class="input input-full" name="skema_penelitian" required>
          <option value="" disabled {{ old('skema_penelitian', data_get($verifSession,'skema_penelitian')) ? '' : 'selected' }}>
            -- Pilih Skema --
          </option>
          <option value="Penelitian Dasar (TKT 1 - 3)"
          {{ old('skema_penelitian', data_get($verifSession,'skema_penelitian')) == 'Penelitian Dasar (TKT 1 - 3)' ? 'selected' : '' }}>
          Penelitian Dasar (TKT 1 - 3)
          </option>

          <option value="Penelitian Terapan (TKT 4 - 6)"
          {{ old('skema_penelitian', data_get($verifSession,'skema_penelitian')) == 'Penelitian Terapan (TKT 4 - 6)' ? 'selected' : '' }}>
          Penelitian Terapan (TKT 4 - 6)
          </option>

          <option value="Penelitian Pengembangan (TKT 7 - 9)"
          {{ old('skema_penelitian', data_get($verifSession,'skema_penelitian')) == 'Penelitian Pengembangan (TKT 7 - 9)' ? 'selected' : '' }}>
          Penelitian Pengembangan (TKT 7 - 9)
          </option>
          <option value="Bukan dihasilkan dari Skema Penelitian"
          {{ old('skema_penelitian', data_get($verifSession,'skema_penelitian')) == 'Bukan dihasilkan dari Skema Penelitian' ? 'selected' : '' }}>
          Bukan dihasilkan dari Skema Penelitian
        </option>
          </select>
      </div>

      {{-- ACTIONS --}}
      <div class="actions-bar">
        <div class="actions-left">
          <button type="submit" name="action" value="prev" class="btn-prev">
            &laquo; Sebelumnya
          </button>

          <button type="submit" name="action" value="next" class="btn-next">
            Selanjutnya &raquo;
          </button>
        </div>
      </div>
    </form>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
      const nextBtn = document.getElementById('nextLink');
      if (!nextBtn) return;

      nextBtn.addEventListener('click', async (e) => {
        e.preventDefault();

        const form = document.getElementById('draftForm');
        if (!form) return;

        // validasi HTML5
        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }

        const saveUrl = nextBtn.dataset.saveUrl;

        const fd = new FormData(form);

        try {
          const res = await fetch(saveUrl, {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
            },
            body: fd
          });

          const data = await res.json().catch(() => null);

          if (!res.ok) {
            console.error('HTTP error');
            return;
          }

          // kalau backend kirim redirect → tetap lanjut
          if (data?.redirect) {
            window.location.href = data.redirect;
            return;
          }

          // fallback kalau ok true
          if (data?.ok) {
            window.location.href = data.redirect;
            return;
          }

          window.location.href = data.redirect;

        } catch (err) {
          console.error(err);
        }
      });
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
      const select = document.querySelector('.prototipe-select');
      if (!select) return;

      function updateColor() {
        select.classList.remove('sudah', 'belum');

        if (select.value === 'Sudah') {
          select.classList.add('sudah');
        } else if (select.value === 'Belum') {
          select.classList.add('belum');
        }
      }

      // jalan saat load (biar old() ikut ke-style)
      updateColor();

      // jalan saat user ganti
      select.addEventListener('change', updateColor);
    });
    </script>

    <script>
      document.addEventListener("input", function (e) {
        if (e.target.matches(".nip-input")) {
          const value = e.target.value.trim();
          const warning = e.target.parentElement.querySelector(".nip-warning");
          const valid = /^\d{14}$|^\d{18}$/.test(value);

          if (warning) {
            warning.style.display = value === "" || valid ? "none" : "block";
            warning.style.color = "red";
          }
        }

        if (e.target.matches(".nidn-input")) {
          const value = e.target.value.trim();
          const warning = e.target.parentElement.querySelector(".nidn-warning");
          const valid = /^\d{8}$/.test(value);

          if (warning) {
            warning.style.display = value === "" || valid ? "none" : "block";
            warning.style.color = "red";
          }
        }

        if (e.target.matches(".hp-input")) {
          const value = e.target.value.trim();
          const warning = e.target.parentElement.querySelector(".hp-warning");
          const valid = /^08[0-9]{8,13}$/.test(value);

          if (warning) {
            warning.style.display = value === "" || valid ? "none" : "block";
            warning.style.color = "red";
          }
        }

        if (e.target.matches(".email-input")) {
          const value = e.target.value.trim();
          const warning = e.target.parentElement.querySelector(".email-warning");
          const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

          if (warning) {
            warning.style.display = value === "" || valid ? "none" : "block";
            warning.style.color = "red";
          }
        }
      });

      document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(
          ".nip-warning, .nidn-warning, .hp-warning, .email-warning"
        ).forEach(el => {
          el.style.display = "none";
        });
      });
      </script>
  </div>
</section>
@endsection