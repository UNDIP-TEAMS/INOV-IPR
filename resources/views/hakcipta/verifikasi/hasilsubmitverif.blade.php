@extends('layouts.app')

@section('title','Pengajuan Berhasil')

@section('content')

<section class="section-full section-content success-page">
  <div class="section-inner">
    <div class="submit-result">

      <h2>Pengajuan Verifikasi Cipta Berhasil</h2>

      <p class="desc">
        Terima kasih. Data dan dokumen Anda telah berhasil dikirim.
        Silakan simpan kode pengajuan berikut untuk keperluan tracking.
      </p>

      <div class="kode-box">
        <span>Kode Pengajuan</span>
        <strong>{{ $verif->no_pendaftaran }}</strong>
      </div>

      <div class="submit-actions">
        <a href="{{ route('home') }}" class="btn-primary">Kembali ke Beranda</a>
        <a href="{{ route('pemohon.claim', ['kode' => $verif->no_pendaftaran]) }}" class="btn-secondary">Login</a>
      </div>
    </div>
  </div>
</section>
@endsection