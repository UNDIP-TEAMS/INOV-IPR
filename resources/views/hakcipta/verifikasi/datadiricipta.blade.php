@extends('layouts.app')

@section('title','Hak Cipta')

@section('content')

@php $activeStep = 3; @endphp
@include('hakcipta.isiform.menuformcipta')


@php
    $data = session('hakcipta.form');
     $verifSession = session('hakcipta.verif', []);
@endphp

@php
  $inventorData = [];
  $oldInventor = old('inventor', data_get($data, 'inventor', []));

  
  if (!empty($oldInventor)) {
    $inventorData = $oldInventor;
  }
@endphp

<script type="application/json" id="old-inventor-data">
{!! json_encode($inventorData) !!}
</script>

<script type="application/json" id="prefill-count">
{!! json_encode(old('jumlah_inventor', data_get($data, 'jumlah_inventor', 1))) !!}
</script>

@php
  $prefillCount = (int) old('jumlah_inventor', data_get($data, 'jumlah_inventor', 1));
@endphp

<section class="section-full section-content">
  <div class="section-inner">
    <h1 class="page-title">Data Pemohon <span class="req">*</span></h1>

    {{-- Error summary --}}
    @if($errors->any())
      <div class="alert-error" style="background:#fee2e2;padding:12px;border-radius:8px;margin-bottom:14px;">
        <b>Validasi gagal:</b>
        <ul style="margin:8px 0 0 18px;">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form id="draftForm" action="{{ route('ciptaverif.start') }}" method="POST" novalidate>
      @csrf

      <div class="form-2col">
        {{-- LEFT --}}
        <div class="col-left">
          <div class="field">
            <label class="label">Jumlah Pencipta <span class="req">*</span></label>
            <div class="jumlah-inventor-wrap" style="display:flex; gap:10px; align-items:center;">
            <button type="button" id="invMinus" class="btn-minus" aria-label="Kurangi inventor">-</button>
            <input
              type="number"
              class="input"
              id="jumlah_inventor"
              name="jumlah_inventor"
              min="1"
              max="20"
              value="{{ old('jumlah_inventor', $prefillCount) }}"
              required readonly
            >
            <button type="button" id="invPlus" class="btn-plus" aria-label="Tambah inventor">+</button>
          </div>
          @error('jumlah_inventor')
              <small class="err">{{ $message }}</small>
          @enderror
          </div>

          {{-- Jenis Cipta (radio) --}}
        @php
  $jenisOld = old('jenis_cipta', data_get($data,'jenis_cipta'));
  $jenisLainnyaOld = old('jenis_cipta_lainnya', data_get($data,'jenis_cipta_lainnya'));
  $jenisLocked = !empty($jenisOld);
@endphp

<div class="field">
    <label class="label">Jenis Hak Cipta <span class="req">*</span></label>

    <div class="jenis-radio">

        <label class="radio-item">
            <input type="radio"
                name="jenis_cipta"
                value="Buku"
                {{ $jenisOld === 'Buku' ? 'checked' : '' }}
                {{ $jenisLocked ? 'disabled' : '' }}>
            Buku
        </label>
        <br>

        <label class="radio-item">
            <input type="radio"
                name="jenis_cipta"
                value="Program Komputer"
                {{ $jenisOld === 'Program Komputer' ? 'checked' : '' }}
                {{ $jenisLocked ? 'disabled' : '' }}>
            Program Komputer
        </label>
        <br>

        <label class="radio-item">
            <input type="radio"
                name="jenis_cipta"
                value="Karya Rekaman Video"
                {{ $jenisOld === 'Karya Rekaman Video' ? 'checked' : '' }}
                {{ $jenisLocked ? 'disabled' : '' }}>
            Karya Rekaman Video
        </label>
        <br>

        <label class="radio-item">
            <input type="radio"
                name="jenis_cipta"
                value="Lainnya"
                {{ $jenisOld === 'Lainnya' ? 'checked' : '' }}
                {{ $jenisLocked ? 'disabled' : '' }}>
            Lainnya
        </label>

    </div>

    {{-- kalau Lainnya --}}
    <div id="jenis-lainnya-wrap" style="{{ $jenisOld === 'Lainnya' ? '' : 'display:none;' }}">
        <input
            type="text"
            name="jenis_cipta_lainnya"
            value="{{ $jenisLainnyaOld }}"
            {{ $jenisLocked ? 'readonly' : '' }}
        >
        <small style="color:#6b7280;">Isi jika anda memilih “Lainnya”.</small>
    </div>
    <input type="hidden" name="jenis_cipta" value="{{ $jenisOld }}">

    @if($jenisOld === 'Lainnya')
        <input type="hidden" name="jenis_cipta_lainnya" value="{{ $jenisLainnyaOld }}">
    @endif

    @error('jenis_cipta')
        <small style="color:red">{{ $message }}</small>
    @enderror

    @error('jenis_cipta_lainnya')
        <small style="color:red">{{ $message }}</small>
    @enderror
</div>

        </div>

        {{-- RIGHT --}}
        <div class="col-right">
            <div class="field">
            <label class="label">Judul Cipta <span class="req">*</span></label>
            <input
              type="text"
              class="input"
              name="judul_ciptaan"
              placeholder="Masukkan judul cipta"
              value="{{ old('judul_ciptaan', data_get($data,'judul_ciptaan')) }}"
              required readonly
            >
          </div>

          <div class="field">
            <label class="label">Nilai Perolehan <span class="req">*</span></label>
            <p class="hint">Jumlah biaya yang dibutuhkan untuk menghasilkan ciptaan</p>
            <input
              type="text"
              class="input"
              id="nilai_perolehan"
              name="nilai_perolehan"
              placeholder="Nilai Perolehan"
              value="{{ old('nilai_perolehan', data_get($verifSession, 'nilai_perolehan', data_get($data, 'nilai_perolehan', ''))) }}"
              required
            >
            @error('nilai_perolehan')
              <small style="color:red">{{ $message }}</small>
            @enderror
          </div>

          <div class="field">
            <label class="label">Sumber Dana <span class="req">*</span></label>
            <select class="input" id="sumber_dana" name="sumber_dana" required>
              <option value="" disabled {{ old('sumber_dana', data_get($verifSession, 'sumber_dana', data_get($data, 'sumber_dana'))) ? '' : 'selected' }}>
                -- Sumber Dana --
              </option>

              <option value="Universitas Diponegoro"
  {{ old('sumber_dana', data_get($verifSession, 'sumber_dana', data_get($data, 'sumber_dana'))) == 'Universitas Diponegoro' ? 'selected' : '' }}>
                Universitas Diponegoro
              </option>

              <option value="APBN/APBD/Swasta"
  {{ old('sumber_dana', data_get($verifSession, 'sumber_dana', data_get($data, 'sumber_dana'))) == 'APBN/APBD/Swasta' ? 'selected' : '' }}>
                APBN/APBD/Swasta
              </option>

              <option value="Mandiri"
  {{ old('sumber_dana', data_get($verifSession, 'sumber_dana', data_get($data, 'sumber_dana'))) == 'Mandiri' ? 'selected' : '' }}>
                Mandiri
              </option>
            </select>
            @error('sumber_dana')
              <small style="color:red">{{ $message }}</small>
            @enderror
          </div>
        </div>
      </div>

      <div class="hr"></div>

      {{-- DATA INVENTOR --}}
      <div class="field field-full">
        <label class="label">Data Pencipta <span class="req">*</span></label>

        <div id="inventor-container"></div>

        <template id="inventor-template">
          <div class="inventor-card">
            <p class="inventor-head">
              Pencipta <span class="inv-no"></span>
            </p>

            <div class="inventor-grid">
              <div class="inventor-col">
                <div class="field">
                  <label class="label">Nama Pencipta <span class="req">*</span></label>
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
                  <select class="input" name="inventor[fakultas][]" required>
                    <option value="" selected disabled>-- Pilih Fakultas --</option>
                    <option value="Fakultas Teknik">Fakultas Teknik</option>
                    <option value="Fakultas Sains dan Matematika">Fakultas Sains dan Matematika</option>
                    <option value="Fakultas Kesehatan Masyarakat">Fakultas Kesehatan Masyarakat</option>
                    <option value="Fakultas Kedokteran">Fakultas Kedokteran</option>
                    <option value="Fakultas Perikanan dan Ilmu Kelautan">Fakultas Perikanan dan Ilmu Kelautan</option>
                    <option value="Fakultas Peternakan dan Pertanian">Fakultas Peternakan dan Pertanian</option>
                    <option value="Fakultas Psikologi">Fakultas Psikologi</option>
                    <option value="Fakultas Hukum">Fakultas Hukum</option>
                    <option value="Fakultas Ilmu Sosial dan Ilmu Politik">Fakultas Ilmu Sosial dan Ilmu Politik</option>
                    <option value="Fakultas Ilmu Budaya">Fakultas Ilmu Budaya</option>
                    <option value="Fakultas Ekonomi dan Bisnis">Fakultas Ekonomi dan Bisnis</option>
                    <option value="Sekolah Vokasi">Sekolah Vokasi</option>
                    <option value="Sekolah Pasca Sarjana">Sekolah Pasca Sarjana</option>
                  </select>
                </div>
              </div>

              <div class="inventor-col">
                <div class="field">
                  <label class="label">No. HP <span class="req">*</span></label>
                  <input type="text"
                    class="input hp-input"
                    name="inventor[no_hp][]"
                    placeholder="Contoh: 081234567890"
                    required readonly>

                  <small class="hp-warning">
                    Nomor HP harus diawali 08 dan minimal 10 digit
                  </small>
                </div>

                <div class="field">
                  <label class="label">Email <span class="req">*</span></label>
                  <input type="email"
                    class="input email-input"
                    name="inventor[email][]"
                    placeholder="nama@email.com"
                    required readonly>

                  <small class="email-warning">
                    Format email tidak valid
                  </small>
                </div>

                <div class="field nidn-wrap" style="display:none;">
                  <label class="label">NIDN <span class="req">*</span></label>
                  <input
                    type="text"
                    class="input nidn-input"
                    name="inventor[nidn][]"
                    placeholder="8 digit NIDN" readonly
                  >
                  <small class="nidn-warning">
                    NIDN harus 8 digit angka
                  </small>
                </div>


                <div class="field">
                  <label class="label">Status <span class="req">*</span></label>
                  <select class="input" name="inventor[status][]" required readonly>
                    <option value="" selected disabled>-- Pilih Status --</option>
                    <option value="Dosen">Dosen</option>
                    <option value="Mahasiswa">Mahasiswa</option>
                  </select>
                </div>
                <input type="hidden" name="inventor[nik][]" value="0000000000000000">
<input type="hidden" name="inventor[alamat][]" value="-">
<input type="hidden" name="inventor[kode_pos][]" value="-">
<input type="hidden" name="inventor[tlp_rumah][]" value="-">
              </div>
            </div>
          </div>
        </template>

        {{-- errors inventor --}}
        @error('inventor') <small style="color:red">{{ $message }}</small> @enderror
        @error('inventor.nama') <small style="color:red">{{ $message }}</small> @enderror
        @error('inventor.nip_nim') <small style="color:red">{{ $message }}</small> @enderror
        @error('inventor.fakultas') <small style="color:red">{{ $message }}</small> @enderror
        @error('inventor.no_hp') <small style="color:red">{{ $message }}</small> @enderror
        @error('inventor.email') <small style="color:red">{{ $message }}</small> @enderror
        @error('inventor.status') <small style="color:red">{{ $message }}</small> @enderror
      </div>

      {{-- SKEMA (PALING BAWAH) --}}
      <div class="field field-full">
        <label class="label">Dihasilkan dari Skema Penelitian? <span class="req">*</span></label>
        @php
        $skemaImgUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url('Skema Penelitian.jpg');
        @endphp

        <img 
          src="{{ $skemaImgUrl }}" 
          class="skema-img" 
          alt="Skema"
        >

        <select class="input input-full" id="skema_penelitian" name="skema_penelitian" required>
          <option value="" disabled {{ old('skema_penelitian', data_get($verifSession, 'skema_penelitian', data_get($data, 'skema_penelitian'))) ? '' : 'selected' }}>
            -- Pilih Skema --
          </option>

          <option value="Penelitian Dasar (TKT 1 - 3)"
  {{ old('skema_penelitian', data_get($verifSession, 'skema_penelitian', data_get($data, 'skema_penelitian'))) == 'Penelitian Dasar (TKT 1 - 3)' ? 'selected' : '' }}>
            Penelitian Dasar (TKT 1 - 3)
          </option>

          <option value="Penelitian Terapan (TKT 4 - 6)"
  {{ old('skema_penelitian', data_get($verifSession, 'skema_penelitian', data_get($data, 'skema_penelitian'))) == 'Penelitian Terapan (TKT 4 - 6)' ? 'selected' : '' }}>
            Penelitian Terapan (TKT 4 - 6)
          </option>

          <option value="Penelitian Pengembangan (TKT 7 - 9)"
  {{ old('skema_penelitian', data_get($verifSession, 'skema_penelitian', data_get($data, 'skema_penelitian'))) == 'Penelitian Pengembangan (TKT 7 - 9)' ? 'selected' : '' }}>
            Penelitian Pengembangan (TKT 7 - 9)
          </option>

          <option value="Bukan dihasilkan dari Skema Penelitian"
  {{ old('skema_penelitian', data_get($verifSession, 'skema_penelitian', data_get($data, 'skema_penelitian'))) == 'Bukan dihasilkan dari Skema Penelitian' ? 'selected' : '' }}>
            Bukan dihasilkan dari Skema Penelitian
          </option>
        </select>

        @error('skema_penelitian')
          <small style="color:red">{{ $message }}</small>
        @enderror
      </div>

      {{-- ACTIONS --}}
      <div class="actions-bar">
        <div class="actions-left">
          <button
            type="button"
            class="btn-prev"
            onclick="window.location.href='{{ route('hakcipta.isiform.formpendaftaran') }}'"
          >
            &laquo; Sebelumnya
          </button>

            <button type="submit" class="btn-next">
                Selanjutnya »
            </button>
        </div>
      </div>
    </form>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const keyPrefix = 'hakcipta_datadiri_draft';

  const fields = {
    nilai_perolehan: document.getElementById('nilai_perolehan'),
    sumber_dana: document.getElementById('sumber_dana'),
    skema_penelitian: document.getElementById('skema_penelitian'),
  };

  Object.entries(fields).forEach(([name, el]) => {
    if (!el) return;

    const key = `${keyPrefix}_${name}`;

    // restore dari sessionStorage kalau value blade kosong
    if (!el.value) {
      const saved = sessionStorage.getItem(key);
      if (saved !== null) {
        el.value = saved;
      }
    }

    // save saat user input/change
    const evt = el.tagName === 'SELECT' ? 'change' : 'input';
    el.addEventListener(evt, () => {
      sessionStorage.setItem(key, el.value);
    });
  });

  // kalau form page 2 berhasil submit, hapus draft browser
  const form = document.getElementById('draftForm');
  if (form) {
    form.addEventListener('submit', () => {
      Object.keys(fields).forEach(name => {
        sessionStorage.removeItem(`${keyPrefix}_${name}`);
      });
    });
  }
});
</script>

<script>
document.addEventListener("input", function (e) {
  if (e.target.matches(".hp-input")) {
    const value = e.target.value.trim();
    const warning = e.target.parentElement.querySelector(".hp-warning");
    const valid = /^08[0-9]{8,13}$/.test(value);

    if (warning) {
      warning.style.display = value === "" || valid ? "none" : "block";
    }
  }

  if (e.target.matches(".email-input")) {
    const value = e.target.value.trim();
    const warning = e.target.parentElement.querySelector(".email-warning");
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

    if (warning) {
      warning.style.display = value === "" || valid ? "none" : "block";
    }
  }

  if (e.target.matches(".nip-input")) {
    const value = e.target.value.trim();
    const warning = e.target.parentElement.querySelector(".nip-warning");
    const valid = /^\d{14}$|^\d{18}$/.test(value);

    if (warning) {
      warning.style.display = value === "" || valid ? "none" : "block";
    }
  }

  if (e.target.matches(".nidn-input")) {
    const value = e.target.value.trim();
    const warning = e.target.parentElement.querySelector(".nidn-warning");
    const valid = /^\d{8}$/.test(value);

    if (warning) {
      warning.style.display = value === "" || valid ? "none" : "block";
    }
  }
});

document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(
    ".nik-warning, .hp-warning, .email-warning, .nip-warning, .nidn-warning"
  ).forEach(el => {
    el.style.display = "none";
  });
});
</script>
@endsection