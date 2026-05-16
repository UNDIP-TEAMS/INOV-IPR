@extends('layouts.app')

@section('title','Landing Page')

@section('content')

<div class="content-y">
  <div class="hero">
  @php
use Illuminate\Support\Facades\Storage;

$bgUrl = Storage::disk('s3')->url('bg2.jpg');
@endphp

<img src="{{ $bgUrl }}" class="hero-img" alt="gedung">

  <div class="hero-text">
    <h1>Selamat Datang</h1>
    <h2>di IPRHub Universitas Diponegoro</h2>
  </div>
</div>

  <div class="hero-daftar">
    <h3>Ayo daftarkan KI Anda</h3>
    <div class="hero-buttons-center icon-menu">
      <div class="hero-buttons-center icon-menu">
  <a href="{{ route('menuhakpaten') }}" class="icon-btn paten">
    <span class="icon"><i class="fa-solid fa-lightbulb"></i></span>
    <span class="label">Daftar Paten</span>
  </a>

  <a href="{{ route('menucipta') }}" class="icon-btn cipta">
    <span class="icon"><i class="fa-regular fa-copyright"></i></span>
    <span class="label">Daftar Hak Cipta</span>
  </a>

  <a href="{{ route('daftar') }}" class="icon-btn industri">
    <span class="icon"><i class="fa-solid fa-pen-ruler"></i></span>
    <span class="label">Daftar Desain Industri</span>
  </a>

  <a href="{{ route('daftar') }}" class="icon-btn dtlst">
    <span class="icon"><i class="fa-solid fa-microchip"></i></span>
    <span class="label">Daftar Desain Tata Letak<br/>Sirkuit Terpadu</span>
  </a>

  <a href="{{ route('daftar') }}" class="icon-btn rahasia">
  <span class="icon"><i class="fa-solid fa-lock"></i></span>
  <span class="label">Daftar Rahasia Dagang</span>
</a>

  <a href="{{ route('daftar') }}" class="icon-btn merek">
    <span class="icon"><i class="fa-solid fa-tag"></i></span>
    <span class="label">Daftar Merek</span>
  </a>

</div>

    <!-- CARD PUTIH DI TENGAH GAMBAR -->
  <div class="hero-card">
    <h1>Tentang IPRHub</h1>
    <p>
       IPRHub adalah pusat layanan terpadu pengelolaan Kekayaan Intelektual yang dirancang untuk mendukung dosen dan mahasiswa dalam melindungi serta mengelola hasil inovasi dan riset.<br>
      Melalui IPRHub, proses pengajuan kekayaan intelektual menjadi lebih terstruktur, transparan, dan terdokumentasi dengan baik. <br>
      Website ini hadir sebagai jembatan antara inovator dan sistem perlindungan hukum, sehingga setiap karya memiliki nilai, kepastian, dan dampak berkelanjutan.
    </p>
  </div>

  <div class="hero-search">
    <form action="{{ route('tracking') }}" method="GET" class="search-form">
      <input type="text" name="q" class="search-input" placeholder="Masukkan No Pengajuan (contoh: EC00XXXXXXXXX)" value="{{ request('q') }}" required>
      <button type="submit" class="search-btn">Cek Status Verifikasi</button>
    </form>
  </div>
  </div>
  <div class="container-langkah">
    <div class="container-langkah-langkah">
        <h3>Langkah-Langkah Pendaftaran Paten / Hak Cipta</h3>
        <p>1. Pemohon mengakses website IPRHub dan mengisi formulir pengajuan Paten atau Hak Cipta sesuai dengan persyaratan yang tertera pada menu yang tersedia.</p>
        <p>2. Berkas pengajuan akan diverifikasi oleh Admin. Pemohon dimohon untuk menunggu hingga status pengajuan dinyatakan disetujui (approved).</p>
        <p>3. Setelah pengajuan disetujui oleh Admin, pemohon mencetak (print) draft Paten/Hak Cipta yang telah diisi (tanpa meterai dan tanpa tanda tangan), kemudian mengajukannya ke Bagian Innovation Hub, Gedung UPT Perpustakaan Lantai 5 untuk dilakukan verifikasi kelengkapan berkas.
<br>(mohon membawa laptop untuk koreksi di tempat apabila diperlukan).</p>
        <p>4. Draft berkas Paten/Hak Cipta yang telah diverifikasi selanjutnya dilengkapi dengan tanda tangan seluruh inventor/pencipta serta meterai, kemudian dikumpulkan ke Bagian Inovasi, Gedung UPT Perpustakaan Lantai 5 pada jam kerja berikut:<br>
              <ul class="jam-layanan">
                <li><strong>Senin – Kamis</strong>: 09.00 – 12.00 WIB &amp; 13.00 – 15.00 WIB.</li>
                <li><strong>Jumat</strong>: 09.00 – 11.30 WIB &amp; 13.30 – 15.30 WIB.</li>
              </ul>
      </p>
        <p>5. Setelah memperoleh nomor urut, pemohon dimohon untuk melengkapi data dan mengunggah (upload) softfile pengajuan (tanpa meterai dan tanpa tanda tangan) melalui menu yang tersedia pada website KI Hub.</p>
        <p>6. Surat Catatan Ciptaan Kekayaan Intelektual akan dikirimkan melalui email masing-masing inventor/pencipta.</p>
    </div>
  </div>
  <div class="container-catatan-awal">
    @php

$lampuUrl = Storage::disk('s3')->url('lampu.svg');
@endphp

<img src="{{ $lampuUrl }}" class="lampu-img" alt="lampu">
    <div class="container-catatan">
        <h3>Catatan: </h3>
        <p>1. Semua dokumen yang dibutuhkan dapat diunduh dan diisi sesuai dengan data yang benar pada menu yang tersedia di atas.</p>
        <p>2. Nama Inventor/Pencipta DOSEN disesuaikan dengan gelar di E-Duk.</p>
        <p>3. Nama Inventor/Pencipta MAHASISWA disesuaikan dengan SIAP.</p>
        <p>4. Kolom NIP/NIM pada semua Inventor WAJIB diisi.</p>
        <p>5. Dibuat Rangkap 1 bermaterai.</p>
        <p>6. Di Print dengan Kertas A4 80 gram.</p>
        <p>7. Data dan Tanda Tangan pada Surat Pernyataan dan Surat Pengalihan Hak dalam 1 halaman.</p>
        <p>8. Informasi lebih lanjut dapat menghubungi melalui Call Center 0811-3848-555 pada hari-jam kerja 07.30-16.00 WIB.</p>
    </div>
  </div>
</div>
@endsection

