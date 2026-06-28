@extends('layouts.app')

@section('title','Test Header')

@section('content')

@php $activeStep = 2; @endphp
@include('hakpaten.isiformulir.menuformulir')

@php
  $prefill = session('hakpaten.isiform', []);
@endphp

<script type="application/json" id="prefill-inventor-data">
{!! json_encode($prefill['inventor'] ?? []) !!}
</script>

<script type="application/json" id="prefill-count">
{!! json_encode($prefill['jumlah_inventor'] ?? 1) !!}
</script>


<div class="paten-form-page">
  <div class="judul">
    <h2>Formulir Pendaftaran Paten</h2>
    <p>Isi formulir ini untuk mendapatkan dokumen form paten</p>
  </div>

  <form class="form" method="POST" action="{{ route('isiform.store') }}">
    @csrf

    {{-- ===============================
    1) JENIS PATEN + PCT
    =============================== --}}
    <div class="row-2">
      <div class="col">
        <div class="field">
          <label class="label">Jenis Pengajuan Paten <span class="req">*</span></label>
          <select class="input" name="jenis_paten" required>
            <option value="" disabled {{ old('jenis_paten') ? '' : 'selected' }}>-- Jenis Pengajuan Paten --</option>
            <option value="Paten" {{ old('jenis_paten', $prefill['jenis_paten'] ?? '')=='Paten' ? 'selected' : '' }}>Paten</option>
            <option value="Paten Sederhana" {{ old('jenis_paten', $prefill['jenis_paten'] ?? '')=='Paten Sederhana' ? 'selected' : '' }}>Paten Sederhana</option>

          </select>
          @error('jenis_paten') <small style="color:red">{{ $message }}</small> @enderror
        </div>
      </div>

      <div class="col">
        <div class="field">
          <label class="label">Apakah menggunakan Nomor PCT? <span class="req">*</span></label>

          <select class="input" id="is_pct_display" disabled>
            <option value="Tidak" selected>Tidak</option>
          </select>
          <input type="hidden" name="is_pct" value="Tidak">

          @error('is_pct') <small style="color:red">{{ $message }}</small> @enderror
        </div>

        <div class="field" id="pct-followup" @if(old('is_pct', $prefill['is_pct'] ?? '')!=='Ya') style="display:none;" @endif>
          <label class="label">Nomor Permohonan Paten Internasional (PCT) <span class="req">*</span></label>
          <input type="text" class="input" name="nomor_permohonan"
                 placeholder="Masukkan nomor permohonan paten internasional"
                 value="{{ old('nomor_permohonan', $prefill['nomor_permohonan'] ?? '') }}"
                 @if(old('is_pct', $prefill['is_pct'] ?? '')==='Ya') required @endif>
          @error('nomor_permohonan') <small style="color:red">{{ $message }}</small> @enderror
        </div>
      </div>
    </div>

    {{-- ===============================
    2) JUDUL + PECAHAN
    =============================== --}}
    <div class="row-2">
      <div class="col">
        <div class="field">
          <label class="label">Judul Invensi <span class="req">*</span></label>
          <input type="text" class="input" name="judul_invensi" placeholder="Masukkan judul draft paten"
                 value="{{ old('judul_invensi', $prefill['judul_invensi'] ?? '') }}" required>
          @error('judul_invensi') <small style="color:red">{{ $message }}</small> @enderror
        </div>
      </div>

      <div class="col">
        <div class="field">
          <label class="label">Apakah merupakan pecahan paten? <span class="req">*</span></label>

          <select class="input" id="is_pecahan_display" disabled>
            <option value="Tidak" selected>Tidak</option>
          </select>
          <input type="hidden" name="is_pecahan" value="Tidak">

          @error('is_pecahan')
            <small style="color:red">{{ $message }}</small>
          @enderror
        </div>

        <div class="field" id="pecahan-followup"
          @if(old('is_pecahan', $prefill['is_pecahan'] ?? '')!=='Ya')
            style="display:none;"
          @endif>

          <label class="label">
            Nomor Permohonan Paten Induk <span class="req">*</span>
          </label>

          <input type="text"
            class="input"
            name="pecahan_paten"
            placeholder="Masukkan nomor permohonan paten induk"
            value="{{ old('pecahan_paten', $prefill['pecahan_paten'] ?? '') }}"
            @if(old('is_pecahan', $prefill['is_pecahan'] ?? '')==='Ya')
              required
            @endif>

          @error('pecahan_paten')
            <small style="color:red">{{ $message }}</small>
          @enderror
        </div>
      </div>
    </div>

    {{-- ===============================
    3) KONSULTAN PATEN
    =============================== --}}
    <div class="row-2">
      <div class="col">
        <div class="field">
          <label class="label">Apakah Melalui Konsultan Paten? <span class="req">*</span></label>

          <select class="input" id="konsultanpaten" disabled>
            <option value="Tidak Melalui" selected>Tidak Melalui</option>
          </select>
          <input type="hidden" name="konsultanpaten" value="Tidak Melalui">

          @error('konsultanpaten') <small style="color:red">{{ $message }}</small> @enderror
        </div>
      </div>
    </div>

    <div class="field" id="konsultan-followup" @if(old('konsultanpaten', $prefill['konsultanpaten'] ?? '')!=='Melalui') style="display:none;" @endif>
      <div class="row-2">
        <div class="col">
          <div class="field">
            <label class="label">Nama Badan Hukum <span class="req">*</span></label>
            <input type="text" class="input" id="nama_badan_hukum" name="nama_badan_hukum"
                   value="{{ old('nama_badan_hukum', $prefill['nama_badan_hukum'] ?? '') }}" placeholder="Masukkan nama badan hukum"
                   @if(old('konsultanpaten', $prefill['konsultanpaten'] ?? '')==='Melalui') required @endif>
            @error('nama_badan_hukum') <small style="color:red">{{ $message }}</small> @enderror
          </div>

          <div class="field">
            <label class="label">Nama Konsultan Paten <span class="req">*</span></label>
            <input type="text" class="input" id="nama_konsultan_paten" name="nama_konsultan_paten"
                   value="{{ old('nama_konsultan_paten', $prefill['nama_konsultan_paten'] ?? '') }}" placeholder="Masukkan nama konsultan paten"
                   @if(old('konsultanpaten', $prefill['konsultanpaten'] ?? '')==='Melalui') required @endif>
            @error('nama_konsultan_paten') <small style="color:red">{{ $message }}</small> @enderror
          </div>

          <div class="field">
            <label class="label">Nomor Konsultan Paten <span class="req">*</span></label>
            <input type="text" class="input" id="nomor_konsultan_paten" name="nomor_konsultan_paten"
                  value="{{ old('nomor_konsultan_paten', $prefill['nomor_konsultan_paten'] ?? '') }}" placeholder="Masukkan nomor konsultan paten"
                   @if(old('konsultanpaten', $prefill['konsultanpaten'] ?? '')==='Melalui') required @endif>
            @error('nomor_konsultan_paten') <small style="color:red">{{ $message }}</small> @enderror
          </div>
        </div>

        <div class="col">
          <div class="field">
            <label class="label">Alamat Badan Hukum <span class="req">*</span></label>
            <input type="text" class="input" id="alamat_badan_hukum" name="alamat_badan_hukum"
                   value="{{ old('alamat_badan_hukum', $prefill['alamat_badan_hukum'] ?? '') }}" placeholder="Masukkan alamat badan hukum"
                   @if(old('konsultanpaten', $prefill['konsultanpaten'] ?? '')==='Melalui') required @endif>
            @error('alamat_badan_hukum') <small style="color:red">{{ $message }}</small> @enderror
          </div>

          <div class="field">
            <label class="label">Alamat Konsultan Paten <span class="req">*</span></label>
            <input type="text" class="input" id="alamat_konsultan_paten" name="alamat_konsultan_paten"
                   value="{{ old('alamat_konsultan_paten', $prefill['alamat_konsultan_paten'] ?? '') }}" placeholder="Masukkan alamat konsultan paten"
                   @if(old('konsultanpaten', $prefill['konsultanpaten'] ?? '')==='Melalui') required @endif>
            @error('alamat_konsultan_paten') <small style="color:red">{{ $message }}</small> @enderror
          </div>

          <div class="field">
            <label class="label">Telepon/Fax <span class="req">*</span></label>
            <input type="text" class="input" id="telepon_fax" name="telepon_fax"
                   value="{{ old('telepon_fax', $prefill['telepon_fax'] ?? '') }}" placeholder="Masukkan telepon/fax"
                   @if(old('konsultanpaten', $prefill['konsultanpaten'] ?? '')==='Melalui') required @endif>
            @error('telepon_fax') <small style="color:red">{{ $message }}</small> @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- ===============================
    4) HAK PRIORITAS
    =============================== --}}
    <div class="row-2">
      <div class="col">
        <div class="field">
          <label class="label">Permohonan paten ini diajukan dengan/tidak dengan Hak prioritas? <span class="req">*</span></label>

          <select class="input" id="hak_prioritas" disabled>
            <option value="Tidak" selected>Tidak</option>
          </select>
          <input type="hidden" name="hak_prioritas" value="Tidak">

          @error('hak_prioritas') <small style="color:red">{{ $message }}</small> @enderror
        </div>
      </div>
    </div>

    <div class="field" id="hak-prioritas-followup" @if(old('hak_prioritas', $prefill['hak_prioritas'] ?? '') !== 'Ya') style="display:none;" @endif>
      <div class="row-2">
        <div class="col">
          <div class="field">
            <label class="label">Negara <span class="req">*</span></label>
            <input type="text" class="input" id="negara" name="negara"
                   value="{{ old('negara', $prefill['negara'] ?? '') }}" placeholder="Masukkan negara"
                   @if(old('hak_prioritas', $prefill['hak_prioritas'] ?? '') !== 'Ya') required @endif>
            @error('negara') <small style="color:red">{{ $message }}</small> @enderror
          </div>
        </div>

        <div class="col">
          <div class="field">
            <label class="label">Nomor Prioritas <span class="req">*</span></label>
            <input type="text" class="input" id="nomor_prioritas" name="nomor_prioritas"
                   value="{{ old('nomor_prioritas', $prefill['nomor_prioritas'] ?? '') }}" placeholder="Masukkan nomor prioritas"
                   @if(old('hak_prioritas', $prefill['hak_prioritas'] ?? '') !== 'Ya') required @endif>
            @error('nomor_prioritas') <small style="color:red">{{ $message }}</small> @enderror
          </div>
        </div>
      </div>

      <div class="field">
        <label class="label">Tanggal Penerimaan Permohonan <span class="req">*</span></label>
        <p>Format: Tanggal/Bulan/Tahun<br>Contoh: 13/05/2026</p>
        <input type="text" class="input" id="tgl_penerimaan" name="tgl_penerimaan"
               value="{{ old('tgl_penerimaan', $prefill['tgl_penerimaan'] ?? '') }}" placeholder="Masukkan Tanggal Penerimaan Permohonan"
               @if(old('hak_prioritas', $prefill['hak_prioritas'] ?? '') !== 'Ya') required @endif>
        @error('tgl_penerimaan') <small style="color:red">{{ $message }}</small> @enderror
      </div>
    </div>


    {{-- ===============================
    5) URAIAN + TANGGAL
    =============================== --}}
    <div class="row-2">
      <div class="col">
        <div class="field">
          <label class="label">Uraian (jumlah halaman) <span class="req">*</span></label>
          <input type="number" class="input" name="uraian_halaman" min="1"
                 value="{{ old('uraian_halaman', $prefill['uraian_halaman'] ?? '') }}" placeholder="Masukkan jumlah halaman" required>
          @error('uraian_halaman') <small style="color:red">{{ $message }}</small> @enderror
        </div>

        <div class="field">
          <label class="label">Abstrak (jumlah buah) <span class="req">*</span></label>
          <input type="number" class="input" name="abstrak_buah" min="1"
                 value="{{ old('abstrak_buah', $prefill['abstrak_buah'] ?? '') }}" placeholder="Masukkan jumlah buah" required>
          @error('abstrak_buah') <small style="color:red">{{ $message }}</small> @enderror
        </div>
      </div>

      <div class="col">
        <div class="field">
          <label class="label">Klaim (jumlah buah) <span class="req">*</span></label>
          <input type="number" class="input" name="klaim_buah" min="1"
                 value="{{ old('klaim_buah', $prefill['klaim_buah'] ?? '') }}" placeholder="Masukkan jumlah buah" required>
          @error('klaim_buah') <small style="color:red">{{ $message }}</small> @enderror
        </div>

        <div class="field">
          <label class="label">Gambar (jumlah buah) <span class="req">*</span></label>
          <input type="number" class="input" name="gambar_buah" min="1"
                 value="{{ old('gambar_buah', $prefill['gambar_buah'] ?? '') }}" placeholder="Masukkan jumlah buah" required>
          @error('gambar_buah') <small style="color:red">{{ $message }}</small> @enderror
        </div>
      </div>
    </div>

    <div class="col">
      <div class="field">
        <label class="label">Gambar yang menyertai abstrak (dari–sampai) <span class="req">*</span></label>
        <div class="row-2 inner-row">
          <div class="col">
            <input type="number" class="input" name="gambar_dari" placeholder="Dari (contoh: 1)"
                   value="{{ old('gambar_dari', $prefill['gambar_dari'] ?? '') }}" required>
            @error('gambar_dari') <small style="color:red">{{ $message }}</small> @enderror
          </div>
          <div class="col">
            <input type="number" class="input" name="gambar_sampai" placeholder="Sampai (contoh: 3)"
                   value="{{ old('gambar_sampai', $prefill['gambar_sampai'] ?? '') }}" required>
            @error('gambar_sampai') <small style="color:red">{{ $message }}</small> @enderror
          </div>
        </div>
      </div>
    </div>

    <div class="field">
      <label class="label">Tanggal Pengisian</label>
      <input type="date" class="input" name="tanggal_pengisian"
             value="{{ old('tanggal_pengisian', $prefill['tanggal_pengisian'] ?? now()->format('Y-m-d')) }}">
      @error('tanggal_pengisian') <small style="color:red">{{ $message }}</small> @enderror
    </div>

    {{-- ===============================
    6) JUMLAH INVENTOR (PALING BAWAH)
    =============================== --}}
    <div class="row-2">
      <div class="col">
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
              value="{{ old('jumlah_inventor', $prefill['jumlah_inventor'] ?? 1) }}"
              required
            >


            <button type="button" id="invPlus" class="btn-plus" aria-label="Tambah inventor">+</button>
          </div>

          @error('jumlah_inventor') <small style="color:red">{{ $message }}</small> @enderror
        </div>
      </div>
    </div>

    {{-- ===============================
    7) DATA INVENTOR (PALING BAWAH)
    =============================== --}}
    <div class="hr"></div>

    <div class="field field-full">
      <div class="field">
        <label class="label">Data Inventor <span class="req">*</span></label>

        <div id="inventor-container-verif"></div>

        @error('inventor') <small style="color:red">{{ $message }}</small> @enderror
        @error('inventor.*') <small style="color:red">{{ $message }}</small> @enderror

        {{-- TEMPLATE INVENTOR 1 (WAJIB DOSEN) --}}
        <template id="inventor-template-first-verif">
          <div class="inventor-card">
            <p class="inventor-head">Inventor <span class="inv-no"></span></p>
            <p>Catatan: Data Inventor 1 HARUS diisi dengan data Dosen</p>

            <div class="inventor-grid">
              <div class="inventor-col">
                <div class="field">
                  <label class="label">Nama Lengkap Inventor <span class="req">*</span></label>
                  <input type="text" class="input" name="inventor[nama][]" placeholder="Nama lengkap" required>
                </div>

                <div class="field">
                  <label class="label">Kewarganegaraan <span class="req">*</span></label>
                  <input type="text" class="input" name="inventor[kewarganegaraan][]" placeholder="Contoh: Indonesia" required>
                </div>

                <div class="field">
                  <label class="label">NIP/NIM <span class="req">*</span></label>
                  <input type="text" class="input nip-input" name="inventor[nip_nim][]" placeholder="Masukkan NIP/NIM Anda" required>
                  <small class="nip-warning">NIP/NIM harus terdiri dari 14 atau 18 digit angka</small>
                </div>

                <div class="field">
                  <label class="label">Alamat Lengkap (sesuai KTP) <span class="req">*</span></label>
                  <textarea class="input" name="inventor[alamat][]" rows="3" placeholder="Alamat lengkap" required></textarea>
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
                  <input type="text" class="input hp-input" name="inventor[no_hp][]" placeholder="08xxxxxxxxxx" required>
                  <small class="hp-warning">Nomor HP tidak valid (contoh: 081234567890)</small>
                </div>

                <div class="field">
                  <label class="label">Email <span class="req">*</span></label>
                  <input type="email" class="input email-input" name="inventor[email][]" placeholder="nama@email.com" required>
                  <small class="email-warning">Format email tidak valid</small>
                </div>

                <div class="field nidn-field">
                  <label class="label">NIDN <span class="req">*</span></label>
                  <input type="text" class="input nidn-input" name="inventor[nidn][]" placeholder="NIDN" required>
                  <small class="nidn-warning">NIDN harus 8 karakter</small>
                </div>

                <div class="field">
                  <label class="label">Status Inventor <span class="req">*</span></label>
                  <input type="text" class="input" value="Dosen" disabled>
                  <input type="hidden" name="inventor[status][]" value="Dosen">
                </div>

                <div class="field">
                  <label class="label">Kode Pos <span class="req">*</span></label>
                  <input type="text" class="input" name="inventor[kode_pos][]" placeholder="Contoh: XXXXX" required>
                </div>

                <div class="field">
                  <label class="label">Pekerjaan <span class="req">*</span></label>
                  <input type="text" class="input" name="inventor[pekerjaan][]" placeholder="Contoh: Pegawai" required>
                </div>
              </div>
            </div>
          </div>
        </template>

        {{-- TEMPLATE INVENTOR 2+ (STATUS PILIH) --}}
        <template id="inventor-template-verif">
          <div class="inventor-card">
            <p class="inventor-head">Inventor <span class="inv-no"></span></p>

            <div class="inventor-grid">
              <div class="inventor-col">
                <div class="field">
                  <label class="label">Nama Lengkap Inventor <span class="req">*</span></label>
                  <input type="text" class="input" name="inventor[nama][]" placeholder="Nama lengkap" required>
                </div>

                <div class="field">
                  <label class="label">Kewarganegaraan <span class="req">*</span></label>
                  <input type="text" class="input" name="inventor[kewarganegaraan][]" placeholder="Contoh: Indonesia" required>
                </div>

                <div class="field">
                  <label class="label">NIP/NIM <span class="req">*</span></label>
                  <input type="text" class="input nip-input" name="inventor[nip_nim][]" placeholder="Masukkan NIP/NIM Anda" required>
                  <small class="nip-warning">NIP/NIM harus terdiri dari 14 atau 18 digit angka</small>
                </div>

                <div class="field">
                  <label class="label">Alamat Lengkap (sesuai KTP) <span class="req">*</span></label>
                  <textarea class="input" name="inventor[alamat][]" rows="3" placeholder="Alamat lengkap" required></textarea>
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
                  <input type="text" class="input hp-input" name="inventor[no_hp][]" placeholder="08xxxxxxxxxx" required>
                  <small class="hp-warning">Nomor HP tidak valid (contoh: 081234567890)</small>
                </div>

                <div class="field">
                  <label class="label">Email <span class="req">*</span></label>
                  <input type="email" class="input email-input" name="inventor[email][]" placeholder="nama@email.com" required>
                  <small class="email-warning">Format email tidak valid</small>
                </div>

                <div class="field nidn-field" style="display:none;">
                  <label class="label">NIDN <span class="req">*</span></label>
                  <input type="text" class="input nidn-input" name="inventor[nidn][]" placeholder="Masukkan NIDN">
                  <small class="nidn-warning">NIDN harus 8 karakter</small>
                </div>

                <div class="field">
                  <label class="label">Status Inventor <span class="req">*</span></label>
                  <select class="input status-select" name="inventor[status][]" required>
                    <option value="" selected disabled>-- Status Inventor --</option>
                    <option value="Dosen">Dosen</option>
                    <option value="Mahasiswa">Mahasiswa</option>
                  </select>
                </div>

                <div class="field">
                  <label class="label">Kode Pos <span class="req">*</span></label>
                  <input type="text" class="input" name="inventor[kode_pos][]" placeholder="Contoh: XXXXX" required>
                </div>

                <div class="field">
                  <label class="label">Pekerjaan <span class="req">*</span></label>
                  <input type="text" class="input" name="inventor[pekerjaan][]" placeholder="Contoh: Pegawai" required>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    {{-- ACTIONS --}}
    <div class="actions-bar">
      <div class="actions-left">
        <button
          type="button"
          class="btn-prev"
          data-url="{{ route('hakpaten.draftpatenisiformulir') }}"
          onclick="window.location.href=this.dataset.url"
        >
          &laquo; Sebelumnya
        </button>

        <a
          id="nextLinkIsiform"
          href="#"
          class="btn-next"
          data-save-url="{{ route('isiform.store') }}"
          data-next-url="{{ route('patenverif.datadiri') }}"
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
              if (!form) return;

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
                    <li>• Form Paten</li>
                    <li>• Surat Pengalihan Hak</li>
                    <li>• Kepemilikan Invensi</li>
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
                return;
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
          <option value="{{ route('isiform.store') }}">Form Paten</option>
          <option value="{{ route('pengalihanhak.store') }}">Surat Pengalihan Hak</option>
          <option value="{{ route('invensi.store') }}">Kepemilikan Invensi</option>
        </select>

        <select name="download_format" class="input" style="width:160px;">
          <option value="docx">DOCX</option>
          <option value="pdf">PDF</option>
        </select>

        <button type="submit" class="unduh" id="btnDownload">
          ⬇ Download
        </button>
      </div>
    </div>
  </form>

  <script type="application/json" id="old-inventor-data">
    {!! json_encode(old('inventor', $prefill['inventor'] ?? [])) !!}
  </script>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const jumlahInput = document.getElementById("jumlah_inventor_verif");
      const btnMinus = document.getElementById("invMinus");
      const btnPlus  = document.getElementById("invPlus");

      const container = document.getElementById("inventor-container-verif");
      const tplFirst  = document.getElementById("inventor-template-first-verif");
      const tplOther  = document.getElementById("inventor-template-verif");

      const oldEl = document.getElementById("old-inventor-data");
      let oldInventor = {};
      try { oldInventor = JSON.parse(oldEl?.textContent || "{}"); } catch(e) { oldInventor = {}; }

      const clamp = (n, min, max) => Math.max(min, Math.min(max, n));

      const getCount = () => {
        const n = parseInt(jumlahInput?.value || "1", 10);
        return clamp(isNaN(n) ? 1 : n, 1, 20);
      };

      const snapshotCurrent = () => {
        const snap = {};
        if (!container) return snap;

        container.querySelectorAll("[name^='inventor[']").forEach((el) => {
          const name = el.getAttribute("name");
          if (!snap[name]) snap[name] = [];
          snap[name].push(el.value);
        });

        return snap;
      };

      const fillFromOld = (root, idx) => {
        const keys = [
          "nama","kewarganegaraan","nip_nim","alamat","fakultas",
          "no_hp","email","nidn","status","kode_pos","pekerjaan"
        ];

        keys.forEach((k) => {
          const el = root.querySelector(`[name="inventor[${k}][]"]`);
          if (!el) return;

          const arr = oldInventor?.[k];
          const val = Array.isArray(arr) ? (arr[idx] ?? "") : "";
          if (val) el.value = val;
        });
      };

      const applyStatusLogic = (card, isFirst) => {
        const statusSelect = card.querySelector(".status-select");
        const nidnField = card.querySelector(".nidn-field");
        const nidnInput = card.querySelector('[name="inventor[nidn][]"]');

        if (isFirst) {
          if (nidnField) nidnField.style.display = "";
          if (nidnInput) nidnInput.setAttribute("required", "required");
          return;
        }

        const update = () => {
          const isDosen = statusSelect?.value === "Dosen";
          if (nidnField) nidnField.style.display = isDosen ? "" : "none";
          if (nidnInput) {
            if (isDosen) nidnInput.setAttribute("required", "required");
            else {
              nidnInput.removeAttribute("required");
              nidnInput.value = "";
            }
          }
        };

        if (statusSelect) {
          statusSelect.addEventListener("change", update);
          update();
        }
      };

      function renderInventors(count) {
        if (!container || !tplFirst || !tplOther) return;

        const snap = snapshotCurrent();
        container.innerHTML = "";

        for (let i = 0; i < count; i++) {
          const isFirst = (i === 0);
          const tpl = isFirst ? tplFirst : tplOther;

          const node = tpl.content.cloneNode(true);
          const card = node.querySelector(".inventor-card");

          const no = node.querySelector(".inv-no");
          if (no) no.textContent = (i + 1);

          node.querySelectorAll("[name^='inventor[']").forEach((el) => {
            const name = el.getAttribute("name");
            const arr = snap[name] || [];
            const v = arr[i] ?? "";
            if (v) el.value = v;
          });

          fillFromOld(node, i);

          if (card) applyStatusLogic(card, isFirst);

          container.appendChild(node);
        }
      }

      const setCount = (n) => {
        const v = clamp(n, 1, 20);
        if (jumlahInput) jumlahInput.value = v;
        renderInventors(v);
      };

      const countEl = document.getElementById("prefill-count");
      let prefillCount = 1;
      try { prefillCount = parseInt(JSON.parse(countEl?.textContent || "1"), 10) || 1; } catch(e) {}
      setCount(prefillCount);


      if (btnMinus) btnMinus.addEventListener("click", () => setCount(getCount() - 1));
      if (btnPlus)  btnPlus.addEventListener("click", () => setCount(getCount() + 1));

      // Konsultan show/hide + required toggle
      const konsultan = document.getElementById("konsultanpaten");
      const follow = document.getElementById("konsultan-followup");
      const konsultanReqIds = [
        "nama_badan_hukum","nama_konsultan_paten","nomor_konsultan_paten",
        "alamat_badan_hukum","alamat_konsultan_paten","telepon_fax"
      ];

      const setKonsultanRequired = (on) => {
        konsultanReqIds.forEach(id => {
          const el = document.getElementById(id);
          if (!el) return;
          if (on) el.setAttribute("required", "required");
          else el.removeAttribute("required");
        });
      };

      const updateKonsultanUI = () => {
        const isMelalui = konsultan?.value === "Melalui";
        if (follow) follow.style.display = isMelalui ? "" : "none";
        setKonsultanRequired(!!isMelalui);
      };

      if (konsultan) {
        konsultan.addEventListener("change", updateKonsultanUI);
        updateKonsultanUI();
      }

      const hak = document.getElementById("hak_prioritas");
      const hakFollow = document.getElementById("hak-prioritas-followup");
      const hakReqIds = ["negara","nomor_prioritas","tgl_penerimaan"];

      const setHakRequired = (on) => {
        hakReqIds.forEach(id => {
          const el = document.getElementById(id);
          if (!el) return;
          if (on) el.setAttribute("required", "required");
          else el.removeAttribute("required");
        });
      };

      const updateHakUI = () => {
        const isYa = hak?.value === "Ya";
        if (hakFollow) hakFollow.style.display = isYa ? "" : "none";
        setHakRequired(!!isYa);
      };

      if (hak) {
        hak.addEventListener("change", updateHakUI);
        updateHakUI();
      }
      document.querySelector("form").addEventListener("submit", () => {
        const cards = document.querySelectorAll("#inventor-container-verif .inventor-card");
        document.getElementById("jumlah_inventor_verif").value = cards.length || 1;
      });

    });
  </script>

  <script>
  document.addEventListener("input", function (e) {
    if (e.target.matches(".nip-input")) {
      const value = e.target.value.trim();
      const warning = e.target.parentElement.querySelector(".nip-warning");
      const valid = /^\d{14}$|^\d{18}$/.test(value);

      e.target.setCustomValidity(value === "" || valid ? "" : "NIP/NIM harus terdiri dari 14 atau 18 digit angka");

      if (warning) {
        warning.style.display = value === "" || valid ? "none" : "block";
        warning.style.color = "red";
      }
    }

    if (e.target.matches(".nidn-input")) {
      const value = e.target.value.trim();
      const warning = e.target.parentElement.querySelector(".nidn-warning");
      const valid = /^\d{8}$/.test(value);

      e.target.setCustomValidity(value === "" || valid ? "" : "NIDN harus 8 digit angka");

      if (warning) {
        warning.style.display = value === "" || valid ? "none" : "block";
        warning.style.color = "red";
      }
    }

    if (e.target.matches(".hp-input")) {
      const value = e.target.value.trim();
      const warning = e.target.parentElement.querySelector(".hp-warning");
      const valid = /^08[0-9]{8,13}$/.test(value);

      e.target.setCustomValidity(value === "" || valid ? "" : "Nomor HP harus diawali 08 dan berisi 10-15 digit");

      if (warning) {
        warning.style.display = value === "" || valid ? "none" : "block";
        warning.style.color = "red";
      }
    }

    if (e.target.matches(".email-input")) {
      const value = e.target.value.trim();
      const warning = e.target.parentElement.querySelector(".email-warning");
      const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

      e.target.setCustomValidity(value === "" || valid ? "" : "Format email tidak valid");

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
@endsection
