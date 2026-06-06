@extends('layouts.app')

@section('title', 'Login Pemohon')

@vite(['resources/js/pemohon/login.js'])

@php
use Illuminate\Support\Facades\Storage;

$logoUrl = Storage::disk('s3')->url('dirinov26.png');
@endphp

@section('content')
<style>
  .cp-note{
    background:#fff3cd;
    border:1px solid #ffeeba;
    padding:10px;
    border-radius:10px;
    margin:12px 0;
    font-size:13px;
    line-height:1.35;
  }
  .cp-help{
    display:block;
    margin-top:6px;
    font-size:12px;
    opacity:.75;
  }
</style>

<main class="login-page">
  <div class="login-spacer" aria-hidden="true"></div>

  <section class="login-shell">
    <div class="login-panel">
      <div class="login-grid">

        <div class="login-left">
          <div class="logo-badge">
            <img src="{{ $logoUrl }}" alt="Inovasi & Kerja Sama" class="login-logo" />
          </div>
        </div>

        <div class="login-right">
          <div class="login-card">
            <h1 class="login-title">Login</h1>

            @if ($errors->any())
              <div class="login-alert">{{ $errors->first() }}</div>
            @endif

            @if (session('success'))
              <div class="login-alert success">
                {{ session('success') }}
              </div>
            @endif

            @if (session('error'))
              <div class="login-alert">
                {{ session('error') }}
              </div>
            @endif

            <form method="POST" action="{{ route('pemohon.login') }}" class="login-form">
              @csrf

              <div class="field">
                <label class="login-label">Username / Kode Unik</label>
                <input
                  type="text"
                  name="username"
                  class="cp-input"
                  value=""
                  placeholder="Masukkan kode unik"
                  autocomplete="off"
                  required
                >
              </div>

              <div class="field">
                <label class="login-label">Password</label>

                <div class="pw-wrap">
                  <input
                    id="pwInput"
                    type="password"
                    name="password"
                    class="login-input"
                    value=""
                    placeholder="Password"
                    autocomplete="new-password"
                    required
                  />

                  <button type="button" class="pw-eye" id="pwToggle" aria-label="Lihat password">
                    {{-- eye (default) --}}
                    <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" stroke="currentColor" stroke-width="2"/>
                      <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                    </svg>

                    {{-- eye-off --}}
                    <svg class="icon-eyeoff" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M3 3l18 18" stroke="currentColor" stroke-width="2"/>
                      <path d="M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.4" stroke="currentColor" stroke-width="2"/>
                      <path d="M6.3 6.3C3.6 8.2 2 12 2 12s3.5 7 10 7c2.0 0 3.7-.5 5.2-1.3" stroke="currentColor" stroke-width="2"/>
                      <path d="M9.9 4.2C10.6 4.1 11.3 4 12 4c6.5 0 10 8 10 8s-1.1 2.6-3.4 4.7" stroke="currentColor" stroke-width="2"/>
                    </svg>
                  </button>
                </div>
              </div>

              <div class="login-actions">
                <a href="#" class="login-link" id="openChangePw">Ganti Password</a>
              </div>

              <div class="login-btn-row">
                <button type="submit" class="login-btn">Login</button>
              </div>

              <p class="login-hint">
           
              </p>
            </form>
          </div>
        </div>

      </div>
    </div>
  </section>

  <div class="login-spacer" aria-hidden="true"></div>
</main>

{{-- MODAL: GANTI PASSWORD --}}
<div class="cp-backdrop" id="cpBackdrop" hidden></div>

<div class="cp-modal" id="cpModal" hidden role="dialog" aria-modal="true" aria-labelledby="cpTitle">
  <div class="cp-card">
    <div class="cp-head">
      <div>
        <div class="cp-title" id="cpTitle">Ganti Password</div>
        <div class="cp-subtitle">Masukkan kode unik, email pemohon utama, dan password.</div>
      </div>
      <button type="button" class="cp-close" id="closeChangePw" aria-label="Tutup">✕</button>
    </div>

    <form method="POST" action="{{ route('pemohon.prechange.store') }}" class="cp-form">
      @csrf

      <div class="cp-note">
        ⚠️ Hanya <b>pemohon utama</b> yang dapat mengganti password.<br>
        Password hanya bisa diganti <b>1 bulan sekali</b> dan akan dikirim ke semua email pemohon.
      </div>

      <div class="cp-field">
        <label class="cp-label">Username / Kode Unik</label>
        <input
          type="text"
          name="username"
          id="cpUsername"
          class="cp-input"
          value=""
          placeholder="Masukkan kode unik"
          autocomplete="off"
          required
        >
      </div>

      <div class="cp-field">
        <label class="cp-label">Email Pemohon Utama</label>
        <input
          type="email"
          name="owner_email"
          id="cpOwnerEmail"
          class="cp-input"
          value=""
          placeholder="Email pemohon utama"
          readonly
          required
        >
        <small class="cp-help">Email ini otomatis diambil dari Inventor 1 / pemohon utama.</small>
      </div>

      <div class="cp-field">
        <label class="cp-label">Password Lama</label>
        <input
          type="password"
          name="current_password"
          class="cp-input"
          placeholder="Masukkan password lama"
          autocomplete="current-password"
          required
        >
      </div>

      <div class="cp-field">
        <label class="cp-label">Password Baru</label>
        <input type="password" name="new_password" class="cp-input" placeholder="Minimal 8 karakter" minlength="8" required>
      </div>

      <div class="cp-field">
        <label class="cp-label">Konfirmasi Password Baru</label>
        <input type="password" name="new_password_confirmation" class="cp-input" placeholder="Ulangi password baru" minlength="8" required>
      </div>

      <div class="cp-actions">
        <button type="button" class="cp-btn ghost" id="cancelChangePw">Batal</button>
        <button type="submit" class="cp-btn primary" id="saveChangePw">Simpan</button>
      </div>

      <div class="cp-inline-msg" id="cpMsg" aria-live="polite"></div>
    </form>
  </div>
</div>
@endsection