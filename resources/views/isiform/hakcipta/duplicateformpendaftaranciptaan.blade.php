@extends('layouts.app')

@section('title','Hak Cipta')

@section('content')

@php 
    $activeStep = 2; 
    $ref = filled(request('ref')) 
        ? request('ref') 
        : session('hakcipta.edit_ref_id');

    $generalForm = session('hakcipta.form', []);
    $refForm = $ref ? session("hakcipta.form.$ref", []) : [];

    $jumlahInventor = old('jumlah_inventor',
        $refForm['jumlah_inventor']
        ?? $generalForm['jumlah_inventor']
        ?? ($data['jumlah_inventor'] ?? 1)
    );

    $jenisOld = old('jenis_cipta',
        $refForm['jenis_cipta']
        ?? $generalForm['jenis_cipta']
        ?? ($data['jenis_cipta'] ?? null)
    );

    $jenisLainnyaOld = old('jenis_cipta_lainnya',
        $refForm['jenis_cipta_lainnya']
        ?? $generalForm['jenis_cipta_lainnya']
        ?? ($data['jenis_cipta_lainnya'] ?? null)
    );

    $judulData = old('judul_ciptaan',
        $refForm['judul_ciptaan']
        ?? $generalForm['judul_ciptaan']
        ?? ($data['judul_ciptaan'] ?? '')
    );

    $linkData = old('link_ciptaan',
        $refForm['link_ciptaan']
        ?? $generalForm['link_ciptaan']
        ?? ($data['link_ciptaan'] ?? '')
    );

    $berupaData = old('berupa',
    $refForm['berupa']
    ?? $generalForm['berupa']
    ?? ''
);

$tempatData = old('tempat',
    $refForm['tempat']
    ?? $generalForm['tempat']
    ?? ''
);

$uraianData = old('uraian',
    $refForm['uraian']
    ?? $generalForm['uraian']
    ?? ''
);

    $tanggalData = old('tanggal_pengisian',
        !empty($refForm['tanggal_pengisian']) ? $refForm['tanggal_pengisian'] :
        (!empty($generalForm['tanggal_pengisian']) ? $generalForm['tanggal_pengisian'] : now()->format('Y-m-d'))
    );

    $inventorData = old('inventor',
        $refForm['inventor']
        ?? $generalForm['inventor']
        ?? ($data['inventor'] ?? [])
    );
@endphp

<script type="application/json" id="prefill-inventor-data">
{!! json_encode($inventorData ?? []) !!}
</script>

<script type="application/json" id="prefill-count">
{!! json_encode($jumlahInventor ?? 1) !!}
</script>

<div class="paten-form-page">
  <div class="judul">
    <h2>Formulir Pendaftaran Ciptaan</h2>
    <p>Isi formulir ini untuk mendapatkan dokumen formulir pendaftaran hak cipta</p>
  </div>

  <form id="dupCiptaForm" class="form" method="POST" action="{{ route('dup.isiformCipta.store') }}">
    @csrf
    <input type="hidden" name="ref" value="{{ $ref }}" form="dupCiptaForm">

    <div class="grid-2">
    
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
              form="dupCiptaForm"
              required
          >

          <button type="button" id="invPlus" class="btn-plus" aria-label="Tambah inventor">+</button>
        </div>
        @error('jumlah_inventor')
          <small class="err">{{ $message }}</small>
        @enderror
      </div>

      <div class="field">
        <label class="label">Jenis Hak Cipta <span class="req">*</span></label>

        <div class="jenis-radio">
          <label class="radio-item">
            <input type="radio" name="jenis_cipta" value="Buku" form="dupCiptaForm" {{ $jenisOld === 'Buku' ? 'checked' : '' }} required>
            Buku
          </label>

          <label class="radio-item">
            <input type="radio" name="jenis_cipta" value="Program Komputer" form="dupCiptaForm" {{ $jenisOld === 'Program Komputer' ? 'checked' : '' }} required>
            Program Komputer
          </label>

          <label class="radio-item">
            <input type="radio" name="jenis_cipta" value="Karya Rekaman Video" form="dupCiptaForm" {{ $jenisOld === 'Karya Rekaman Video' ? 'checked' : '' }} required>
            Karya Rekaman Video
          </label>

          <label class="radio-item">
            <input type="radio" name="jenis_cipta" value="Lainnya" form="dupCiptaForm" {{ $jenisOld === 'Lainnya' ? 'checked' : '' }} required>
            Lainnya
          </label>
        </div>

        <div id="jenis-lainnya-wrap" class="mt-8" style="display:none;">
          <input
              type="text"
              class="input"
              name="jenis_cipta_lainnya"
              value="{{ $jenisLainnyaOld }}"
              placeholder="Sebutkan jenis ciptaan lainnya"
              form="dupCiptaForm"
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
              value="{{ $linkData }}"
              form="dupCiptaForm"
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
            value="{{ $judulData }}"
            form="dupCiptaForm"
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
        value="{{ old('tempatpertama',
            $refForm['tempatpertama']
            ?? $generalForm['tempatpertama']
            ?? ($data['tempatpertama'] ?? '')
        ) }}"
        form="dupCiptaForm"
        required
    >

    @error('tempatpertama')
        <small class="err">{{ $message }}</small>
    @enderror
</div>

<div class="field">
    <label class="label">Tanggal Ciptaan Diumumkan <span class="req">*</span></label>

    <p class="hint">
        Tanggal dimana ciptaan pertama kali diumumkan
    </p>

    <input
        type="date"
        class="input"
        name="tanggal_pertama"
        value="{{ old('tanggal_pertama',
            $refForm['tanggal_pertama']
            ?? $generalForm['tanggal_pertama']
            ?? now()->format('Y-m-d')
        ) }}"
        form="dupCiptaForm"
        required
    >

    @error('tanggal_pertama')
        <small class="err">{{ $message }}</small>
    @enderror
</div>

      <div class="field">
        <label class="label">Tanggal Pengisian <span class="req">*</span></label>
        <input
          type="date"
          class="input"
          id="tanggal_pengisian"
          name="tanggal_pengisian"
          value="{{ $tanggalData }}"
          form="dupCiptaForm"
          required
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
            value="{{ $tempatData }}"
            form="dupCiptaForm"
            required
        >
        @error('tempat') <small class="err">{{ $message }}</small> @enderror
      </div>
    </div>

    <div class="nama mt-16">
      <div class="field">
        <label class="label">Data Pencipta <span class="req">*</span></label>
        <div id="inventor-container"></div>
        @error('inventor') <small class="err">{{ $message }}</small> @enderror
        @error('inventor.*') <small class="err">{{ $message }}</small> @enderror
      </div>
    </div>

    <div class="field mt-16">
      <label class="label">Ulasan Ciptaan <span class="req">*</span></label>
      <p class="hint">Tulis singkat (± 2–3 kalimat).</p>
      <textarea
          class="input input-full"
          name="uraian"
          rows="4"
          maxlength="350"
          placeholder="Masukkan uraian produk ciptaan"
          form="dupCiptaForm"
          required
      >{{ $uraianData }}</textarea>
      @error('uraian') <small class="err">{{ $message }}</small> @enderror
    </div>

    <div class="actions-bar">
      <div class="actions-left">
        <button
          type="button" 
          id="nextLinkIsiform"
          class="btn-next"
          data-save-url="{{ route('dup.isiformCipta.store') }}"
          data-next-url="{{ route('pemohon.dashboard') }}"
        >
          Simpan Perubahan
        </button>
      </div>

      <div class="actions-download">
        <select id="doc_type" class="input" style="width:220px;">
          <option value="" selected disabled>-- Pilih Dokumen --</option>
          <option value="{{ route('dup.isiformCipta.store') }}">Formulir Permohonan Pendaftaran Ciptaan</option>
          <option value="{{ route('dup.pernyataanCipta.store') }}">Surat Pernyataan</option>
          <option value="{{ route('dup.pengalihanhakCipta.store') }}">Surat Pengalihan Hak Cipta</option>
        </select>

        <select name="download_format" class="input" style="width:160px;" form="dupCiptaForm">
          <option value="docx">DOCX</option>
          <option value="pdf">PDF</option>
        </select>

        <button type="submit" class="unduh" id="btnDownload">
          ⬇ Download
        </button>
      </div>
    </div>
  </form>

  <template id="inventor-template">
    <div class="inventor-card">
      <p class="inventor-head">
        Pencipta <span class="inv-no"></span>
      </p>

      <div class="grid-2">
        <div class="field">
          <label class="label">Nama Pencipta <span class="req">*</span></label>
          <input type="text" class="input" name="inventor[nama][]" placeholder="Nama lengkap" form="dupCiptaForm" required>
        </div>

        <div class="field">
          <label class="label">NIK <span class="req">*</span></label>
          <input type="text" class="input nik-input" name="inventor[nik][]" placeholder="Masukkan NIK (16 digit)" form="dupCiptaForm" required>
<small class="nik-warning">NIK harus terdiri dari 16 digit angka</small>
        </div>

        <div class="field">
          <label class="label">NIP/NIM <span class="req">*</span></label>
          <input type="text" class="input nip-input" name="inventor[nip_nim][]" placeholder="Masukkan NIP/NIM Anda" form="dupCiptaForm" required>
          <small class="nip-warning">NIP/NIM harus terdiri dari 14 atau 18 digit angka</small>
        </div>

        <div class="field">
          <label class="label">Fakultas <span class="req">*</span></label>
          <select class="input" name="inventor[fakultas][]" form="dupCiptaForm" required>
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

        <div class="field nidn-wrap" style="display:none;">
          <label class="label">NIDN <span class="req">*</span></label>
          <input type="text" class="input nidn-input" name="inventor[nidn][]" placeholder="8 digit NIDN" form="dupCiptaForm">
          <small class="nidn-warning">NIDN harus 8 digit angka</small>
        </div>

        <div class="field">
          <label class="label">Status <span class="req">*</span></label>
          <select class="input status-select" name="inventor[status][]" form="dupCiptaForm" required>
            <option value="" disabled selected>-- Pilih Status --</option>
            <option value="Dosen">Dosen</option>
            <option value="Mahasiswa">Mahasiswa</option>
          </select>
        </div>

        <div class="field">
          <label class="label">No. HP <span class="req">*</span></label>
          <input type="text" class="input hp-input" name="inventor[no_hp][]" placeholder="Contoh: 081234567890" form="dupCiptaForm" required>
<small class="hp-warning">Nomor HP harus diawali 08 dan minimal 10 digit</small>
        </div>

        <div class="field">
          <label class="label">Telp Rumah</label>
          <input type="text" class="input" name="inventor[tlp_rumah][]" placeholder="Contoh: 021-1234567" form="dupCiptaForm">
        </div>

        <div class="field">
          <label class="label">Email <span class="req">*</span></label>
          <input type="email" class="input email-input" name="inventor[email][]" placeholder="nama@email.com" form="dupCiptaForm" required>
<small class="email-warning">Format email tidak valid</small>
        </div>

        <div class="field span-2">
          <label class="label">Alamat Lengkap (sesuai KTP) <span class="req">*</span></label>
          <textarea class="input" name="inventor[alamat][]" rows="3" placeholder="Alamat lengkap" form="dupCiptaForm" required></textarea>
        </div>

        <div class="field">
          <label class="label">Kode Pos <span class="req">*</span></label>
          <input type="text" class="input" name="inventor[kode_pos][]" placeholder="Contoh: 50275" form="dupCiptaForm" required>
        </div>
      </div>
    </div>
  </template>

  <script type="application/json" id="old-inventor-data">
    {!! json_encode($inventorData ?? []) !!}
  </script>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const nextBtn = document.getElementById('nextLinkIsiform');
    const form = document.getElementById('dupCiptaForm');
    if (!nextBtn || !form) return;

    nextBtn.addEventListener('click', async (e) => {
      e.preventDefault();

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const saveUrl = nextBtn.dataset.saveUrl;
      const nextUrl = nextBtn.dataset.nextUrl;
      const fd = new FormData(form);
      fd.set('action', 'save');

      console.log([...fd.entries()]);

      try {
        Swal.fire({
          title: 'Memproses Data...',
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });

        const res = await fetch(saveUrl, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: fd
        });

        if (res.ok) {
          const result = await Swal.fire({
            title: 'Data Disimpan Sementara',
            html: `
              <p>Data perubahan Anda sudah tersimpan di sistem (Sesi).</p>
            <hr style="margin:15px 0;">
            <p><b>Penting:</b> Pastikan Anda mendownload ulang dokumen dengan data terbaru sebelum melanjutkan ke tahap berikutnya:</p>
            <ul style="text-align:left; margin-top:10px; list-style:none; font-size:17px;">
                <li>• Formulir Permohonan Pendaftaran Cipta</li>
                <li>• Surat Pernyataan</li>
                <li>• Surat Pengalihan Hak Cipta</li>
              </ul>
            `,
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Sudah',
            cancelButtonText: 'Belum',
            confirmButtonColor: '#2F5C9E',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
          });

          if (result.isConfirmed) {
            Swal.fire({
              title: 'Berhasil!',
              text: 'Data disimpan. Kembali ke Dashboard...',
              icon: 'success',
              timer: 1500,
              showConfirmButton: false
            });

            setTimeout(() => {
              window.location.href = nextUrl;
            }, 1500);
          } else {
            Swal.fire({
              title: 'Silakan Download',
              text: 'Gunakan tombol download di bawah sebelum kembali ke dashboard.',
              icon: 'info'
            });
          }
        } else {
          Swal.fire('Gagal', 'Terjadi kesalahan saat menyimpan ke session.', 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
      }
    });

    const select = document.getElementById('doc_type');
    if (select) {
      select.addEventListener('change', function () {
        form.action = this.value;
      });
    }
  });
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