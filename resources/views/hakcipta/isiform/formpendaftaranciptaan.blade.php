@extends('layouts.app')

@section('title','Hak Cipta')

@section('content')


@php $activeStep = 2; @endphp
@include('hakcipta.isiform.menuformcipta')

@php
    $data = session('hakcipta.form', []);
@endphp

<script type="application/json" id="prefill-inventor-data">
{!! json_encode($data['inventor'] ?? []) !!}
</script>

<script type="application/json" id="prefill-count">
{!! json_encode($data['jumlah_inventor'] ?? 1) !!}
</script>

<div class="paten-form-page">
  <div class="judul">
    <h2>Formulir Pendaftaran Ciptaan</h2>
    <p>Isi formulir ini untuk mendapatkan dokumen formulir pendaftaran hak cipta</p>
  </div>

  <form class="form" method="POST" action="{{ route('isiformCipta.store') }}" autocomplete="off">
    
    @csrf

    {{-- GRID UTAMA --}}
    <div class="grid-2">
      @php
          $jumlahInventor = old('jumlah_inventor', $data['jumlah_inventor'] ?? 1);
      @endphp

      <div class="field">
          <label class="label">Jumlah pencipta <span class="req">*</span></label>
          <div class="jumlah-inventor-wrap" style="display:flex; gap:10px; align-items:center;">
            <button type="button" id="invMinus" class="btn-minus" aria-label="Kurangi inventor">-</button>

          <input
              type="number"
              class="input"
              id="jumlah_inventor"
              name="jumlah_inventor"
              min="1"
              max="20"
              value="{{ $jumlahInventor }}"
              required
          >
           <button type="button" id="invPlus" class="btn-plus" aria-label="Tambah inventor">+</button>
          </div>
          @error('jumlah_inventor')
              <small class="err">{{ $message }}</small>
          @enderror
      </div>

      <div class="field">
    @php
        $jenisOld = old('jenis_cipta', session('hakcipta.form.jenis_cipta'));
        $jenisLainnyaOld = old('jenis_cipta_lainnya', session('hakcipta.form.jenis_cipta_lainnya'));
    @endphp

    <label class="label">Jenis Hak Cipta <span class="req">*</span></label>

    <div class="jenis-radio">
        <label class="radio-item">
            <input type="radio" name="jenis_cipta" value="Buku"
                {{ $jenisOld === 'Buku' ? 'checked' : '' }} required>
            Buku
        </label>

        <label class="radio-item">
            <input type="radio" name="jenis_cipta" value="Program Komputer"
                {{ $jenisOld === 'Program Komputer' ? 'checked' : '' }} required>
            Program Komputer
        </label>

        <label class="radio-item">
            <input type="radio" name="jenis_cipta" value="Karya Rekaman Video"
                {{ $jenisOld === 'Karya Rekaman Video' ? 'checked' : '' }} required>
            Karya Rekaman Video
        </label>

        <label class="radio-item">
            <input type="radio" name="jenis_cipta" value="Lainnya"
                {{ $jenisOld === 'Lainnya' ? 'checked' : '' }} required>
            Lainnya
        </label>
    </div>

    <div id="jenis-lainnya-wrap" class="mt-8" style="display:none;">
        <input
            type="text"
            class="input"
            name="jenis_cipta_lainnya"
            value="{{ old('jenis_cipta_lainnya', $data['jenis_cipta_lainnya'] ?? '') }}"
            placeholder="Sebutkan jenis ciptaan lainnya"
        >
        <small class="hint">Isi jika memilih “Lainnya”.</small>
    </div>

      @error('jenis_cipta') <small class="err">{{ $message }}</small> @enderror
      @error('jenis_cipta_lainnya') <small class="err">{{ $message }}</small> @enderror
  </div>

      <div class="field">
        <label class="label">Link Ciptaan </label>
        <p class="hint">Isi jika memilih jenis cipta <strong>karya rekaman video</strong></p>
        <input
          type="url"
          class="input"
          name="link_ciptaan"
          placeholder="Contoh: https://drive.google.com/..."
          value="{{ old('link_ciptaan', $data['link_ciptaan'] ?? '') }}"
        >
        @error('link_ciptaan') <small class="err">{{ $message }}</small> @enderror
      </div>

      <div class="field span-2">
        <label class="label">Judul Ciptaan <span class="req">*</span></label>
        <input
          type="text"
          class="input"
          name="judul_ciptaan"
          placeholder="Masukkan judul ciptaan"
          value="{{ old('judul_ciptaan', $data['judul_ciptaan'] ?? '') }}"
          required
        >
        @error('judul_ciptaan') <small class="err">{{ $message }}</small> @enderror
      </div>
      <div class="field span-2">
        <label class="label">Tempat Diumumkan Ciptaan <span class="req">*</span></label>
        <p class="hint">Tempat dimana ciptaan pertama kali diumumkan</p>
        <input
          type="text"
          class="input"
          name="tempatpertama"
          placeholder="Contoh: Jakarta"
          value="{{ old('tempatpertama', $data['tempatpertama'] ?? '') }}"
          required
        >
        @error('tempatpertama') <small class="err">{{ $message }}</small> @enderror
      </div>

      <div class="field">
        <label class="label">Tanggal Ciptaan Diumumkan <span class="req">*</span></label>
        <p class="hint">Tanggal dimana ciptaan pertama kali diumumkan</p>
        <input
          type="date"
          class="input"
          id="tanggal_pertama"
          name="tanggal_pertama"
          value="{{ old('tanggal_pertama', $data['tanggal_pertama'] ?? now()->format('Y-m-d')) }}"
        >
        @error('tanggal_pertama') <small class="err">{{ $message }}</small> @enderror
      </div>

      <div class="field">
        <label class="label">Tanggal Pengisian <span class="req">*</span></label>
        <input
          type="date"
          class="input"
          id="tanggal_pengisian"
          name="tanggal_pengisian"
          value="{{ old('tanggal_pengisian', $data['tanggal_pengisian'] ?? now()->format('Y-m-d')) }}"
        >
        @error('tanggal_pengisian') <small class="err">{{ $message }}</small> @enderror
      </div>

      <div class="field span-2">
        <label class="label">Tempat Pengisian <span class="req">*</span></label>
        <p class="hint">Tempat saat anda mengisi form ini.</p>
        <input
          type="text"
          class="input"
          name="tempat"
          placeholder="Contoh: Semarang"
          value="{{ old('tempat', $data['tempat'] ?? '') }}"
          required
        >
        @error('tempat') <small class="err">{{ $message }}</small> @enderror
      </div>
    </div>

    {{-- DATA PENCIPTA --}}
    <div class="nama mt-16">
      <div class="field">
        <label class="label">Data Pencipta <span class="req">*</span></label>
        <div id="inventor-container"></div>
        @error('inventor') <small class="err">{{ $message }}</small> @enderror
        @error('inventor.*') <small class="err">{{ $message }}</small> @enderror
      </div>
    </div>

    {{-- ULASAN (DI ATAS BUTTON) --}}
    <div class="field mt-16">
      <label class="label">Ulasan Ciptaan <span class="req">*</span></label>
      <p class="hint">Tulis singkat (± 2–3 kalimat).</p>
      <textarea
    class="input input-full"
    name="uraian"
    rows="4"
    maxlength="350"
    placeholder="Masukkan uraian produk ciptaan"
    required
>{{ old('uraian', $data['uraian'] ?? '') }}</textarea>

      @error('uraian') <small class="err">{{ $message }}</small> @enderror
    </div>

    {{-- ACTIONS BAR --}}
    <div class="actions-bar">
      <div class="actions-left">
        <button
          type="button"
          class="btn-prev"
          data-fallback="{{ route('hakcipta.isiform.peralihanverifcipta') }}"
          onclick="(history.length > 1) ? history.back() : (window.location.href=this.dataset.fallback)"
        >
          &laquo; Sebelumnya
        </button>

        <a
          id="nextLinkIsiform"
          href="#"
          class="btn-next"
          data-save-url="{{ route('isiformCipta.store') }}"
          data-next-url="{{ route('datadiricipta') }}"
        >
          Selanjutnya &raquo;
        </a>

        <script>
          document.addEventListener('DOMContentLoaded', () => {
            const nextBtn = document.getElementById('nextLinkIsiform');
            if (!nextBtn) return;

            nextBtn.addEventListener('click', async (e) => {
              e.preventDefault();

              const form = nextBtn.closest('form');
              if (!form) { console.error('Form tidak ketemu'); return; }

              // optional: validasi HTML5
              if (!form.checkValidity()) {
                form.reportValidity();
                return;
              }

              // SWEET ALERT 
              const result = await Swal.fire({
                title: 'Konfirmasi Download',
                html: `
                  <p>Apakah Anda sudah mendownload 3 file berikut?</p>
                  <ul style="text-align:left; margin-top:10px;">
                    <li>• Formulir Permohonan Pendaftaran Ciptaan</li>
                    <li>• Surat Pengalihan Hak Cipta</li>
                    <li>• Surat Pernyataan</li>
                  </ul>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sudah',
                cancelButtonText: 'Belum',
                confirmButtonColor: '#2F5C9E',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
              });

              if (!result.isConfirmed) {
                return; // kalau klik "Belum" tetap di halaman
              }

              const saveUrl = nextBtn.dataset.saveUrl;
              const nextUrl = nextBtn.dataset.nextUrl;

              const fd = new FormData(form);
              fd.set('action', 'next'); 

              try {
                const res = await fetch(saveUrl, {
                  method: 'POST',
                  headers: { 'X-Requested-With': 'XMLHttpRequest' },
                  body: fd
                });

                if (!res.ok) {
                  console.error('Save gagal', res.status);
                  return;
                }

                window.location.href = nextUrl;
              } catch (err) {
                console.error(err);
              }
            });
          });
          </script>


      </div>

      <div class="actions-download">
        <select id="doc_type" class="input" style="width:220px;">
          <option value="" selected disabled>-- Pilih Dokumen --</option>
          <option value="{{ route('isiformCipta.store') }}">Formulir Permohonan Pendaftaran Ciptaan</option>
          <option value="{{ route('pernyataanCipta.store') }}">Surat Pernyataan</option>
          <option value="{{ route('pengalihanhakCipta.store') }}">Surat Pengalihan Hak Cipta</option>
        </select>

        <select name="download_format" class="input" style="width:160px;">
          <option value="docx">DOCX</option>  
          <option value="pdf">PDF</option>
        </select>

        <button type="submit" class="unduh" id="btnDownload">
          ⬇ Download
        </button>
      </div>

      <script>
        document.addEventListener('DOMContentLoaded', function () {
            const select = document.getElementById('doc_type');
            const form = document.querySelector('form');

            select.addEventListener('change', function () {
                form.action = this.value;
            });
        });
        </script>
    </div>

  </form>

  {{-- TEMPLATE PENCIPTA --}}
  <template id="inventor-template">
    <div class="inventor-card">
      
      <p class="inventor-head">
        Pencipta <span class="inv-no"></span>
      </p>

      <div class="grid-2">

        {{-- Nama --}}
        <div class="field">
          <label class="label">Nama Pencipta <span class="req">*</span></label>
          <input type="text"
                class="input"
                name="inventor[nama][]"
                placeholder="Nama lengkap"
                required>
        </div>

        {{-- NIK --}}
        <div class="field">
          <label class="label">NIK <span class="req">*</span></label>
          <input type="text"
            class="input nik-input"
            name="inventor[nik][]"
            placeholder="Masukkan NIK (16 digit)"
            required>

          <small class="nik-warning">
            NIK harus terdiri dari 16 digit angka
          </small>
        </div>

        {{-- NIP/NIM --}}
        <div class="field">
          <label class="label">NIP/NIM <span class="req">*</span></label>
          <input type="text"
                class="input nip-input"
                name="inventor[nip_nim][]"
                placeholder="Masukkan NIP/NIM Anda"
                required>
          <small class="nip-warning">
            NIP/NIM harus terdiri dari 14 atau 18 digit angka
          </small>
        </div>

        {{-- Fakultas --}}
        <div class="field">
          <label class="label">Fakultas <span class="req">*</span></label>
          <select class="input"
                  name="inventor[fakultas][]"
                  required>
            <option value="" disabled selected>-- Pilih Fakultas --</option>
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

        {{-- NIDN (khusus dosen) --}}
        <div class="field nidn-wrap" style="display:none;">
          <label class="label">NIDN <span class="req">*</span></label>
          <input type="text"
                class="input nidn-input"
                name="inventor[nidn][]"
                placeholder="8 digit NIDN">
          <small class="nidn-warning">
            NIDN harus 8 digit angka
          </small>
        </div>

        {{-- Status --}}
        <div class="field">
          <label class="label">Status <span class="req">*</span></label>
          <select class="input status-select"
                  name="inventor[status][]"
                  required>
            <option value="" disabled selected>-- Pilih Status --</option>
            <option value="Dosen">Dosen</option>
            <option value="Mahasiswa">Mahasiswa</option>
          </select>
        </div>

        {{-- No HP --}}
        <div class="field">
          <label class="label">No. HP <span class="req">*</span></label>
          <input type="text"
            class="input hp-input"
            name="inventor[no_hp][]"
            placeholder="Contoh: 081234567890"
            required>

          <small class="hp-warning">
            Nomor HP harus diawali 08 dan minimal 10 digit
          </small>
        </div>

        {{-- Telp Rumah --}}
        <div class="field">
          <label class="label">Telp Rumah</label>
          <input type="text"
                class="input"
                name="inventor[tlp_rumah][]"
                placeholder="Contoh: 021-1234567">
        </div>

        {{-- Email --}}
        <div class="field">
          <label class="label">Email <span class="req">*</span></label>
          <input type="email"
              class="input email-input"
              name="inventor[email][]"
              placeholder="nama@email.com"
              required>

            <small class="email-warning">
              Format email tidak valid
            </small>
        </div>

        {{-- Alamat --}}
        <div class="field span-2">
          <label class="label">Alamat Lengkap (sesuai KTP) <span class="req">*</span></label>
          <textarea class="input"
                    name="inventor[alamat][]"
                    rows="3"
                    placeholder="Alamat lengkap"
                    required></textarea>
        </div>

        {{-- Kode Pos --}}
        <div class="field">
          <label class="label">Kode Pos <span class="req">*</span></label>
          <input type="text"
                class="input"
                name="inventor[kode_pos][]"
                placeholder="Masukkan Kode Pos"
                required>
        </div>
      </div>
    </div>
  </template>

  <script type="application/json" id="old-inventor-data">
    {!! json_encode(old('inventor', $data['inventor'] ?? [])) !!}
</script>
<script>
document.addEventListener("input", function (e) {
  if (e.target.matches(".nik-input")) {
    const value = e.target.value.trim();
    const warning = e.target.parentElement.querySelector(".nik-warning");
    const valid = /^\d{16}$/.test(value);

    if (warning) {
      warning.style.display = value === "" || valid ? "none" : "block";
    }
  }

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
  
</div>
@endsection