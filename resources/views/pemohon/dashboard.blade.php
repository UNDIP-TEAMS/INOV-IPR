@extends('layouts.app')

@section('title', 'Dashboard Pemohon')

@vite([
  'resources/css/dashboardpemohon.css',
  'resources/js/app.js',
  
])

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('content')

  @php
  use Illuminate\Support\Str;

  $notePreview = function($text, $limit = 90){
    $t = (string) $text;

    $oneLine = preg_replace("/\r\n|\r|\n/", " ", $t);
    $oneLine = trim($oneLine);

    return Str::limit($oneLine, $limit);
  };

  $hasMore = function($text, $limit = 90){
    $t = (string) $text;
    $oneLine = preg_replace("/\r\n|\r|\n/", " ", $t);
    $oneLine = trim($oneLine);

    return mb_strlen($oneLine) > $limit;
  };
@endphp
<main class="pd-main">

  <div class="pd-container">

    <div class="pd-topbar">
      <div>
        <h2 class="pd-title">Pemantauan Status</h2>
        <div class="pd-sub">Pantau Pengajuan Kekayaan Intelektual di Sini.</div>
      </div>

      <div class="pd-actions">
        <button type="button" class="pd-user-btn" id="openAccount">
          @php
            $namaAkun = trim($akun->nama ?? 'PEMOHON');
            $parts = preg_split('/\s+/', $namaAkun, -1, PREG_SPLIT_NO_EMPTY);
            $inisial = count($parts) >= 2
              ? strtoupper(substr($parts[0],0,1).substr($parts[1],0,1))
              : strtoupper(substr($namaAkun,0,2));
          @endphp

          <span class="pd-avatar">{{ $inisial }}</span>

          <span class="pd-user-text">
            <span class="pd-user-name">{{ $akun->nama ?? '-' }}</span>
            <span class="pd-user-role">{{ $akun->kategori ?? '-' }}</span>
          </span>

          <span class="pd-caret">▾</span>
        </button>

        <form method="POST"
            action="{{ route('pemohon.logout') }}"
            id="logoutForm">
        @csrf
        <button class="pd-logout" type="button" id="btnLogout">Logout</button>
      </form>
      </div>
    </div>

    <section class="pd-card pd-card--full">
      <div class="pd-card-head">
        <div class="pd-card-title">Pemantauan Status</div>
        <div class="pd-note">Status pengajuan saat ini</div>
      </div>

      @php
        $rank = ['terkirim'=>1,'proses'=>2,'revisi'=>3,'approve'=>4];
        $current = $rank[$status] ?? 1;
      @endphp

      <div class="pd-tracker" data-active="{{ $activeStatus }}">
        @foreach($steps as $s)
          @php
            $stepRank = $rank[$s['key']] ?? 1;

            if ($stepRank < $current) {
              $cls = 'is-done';
            } elseif ($stepRank === $current) {
              $cls = 'is-run';
            } else {
              $cls = 'is-todo';
            }
          @endphp

          <div class="pd-step {{ $cls }}" data-step="{{ $s['key'] }}">
            <div class="pd-dot"></div>

            <div class="pd-step-body">
              <div class="pd-step-title">{{ $s['label'] }}</div>
              @php
                $timeMap = [
                  'terkirim' => $sv->terkirim_at ?? null,
                  'proses'   => $sv->proses_at ?? null,
                  'revisi'   => $sv->revisi_at ?? null,
                  'approve'  => $sv->approve_at ?? null,
                ];

                $time = $timeMap[$s['key']] ?? null;
              @endphp

              <div class="pd-step-sub">
                Terakhir diperbarui:
                {{ $time 
                    ? \Carbon\Carbon::parse($time)->timezone('Asia/Jakarta')->format('d M Y') 
                      . ' • ' . 
                      \Carbon\Carbon::parse($time)->timezone('Asia/Jakarta')->format('H:i') 
                    : '-' 
                }}
              </div>
              @if($s['key'] === 'approve' && $status === 'approve')
                <div style="
                  margin-top:12px;
                  padding:12px 14px;
                  background:#eef4ff;
                  border-left:4px solid #2563eb;
                  color:#1e3a8a;
                  font-size:14px;
                  border-radius:6px;
                ">
                  ℹ️ Silakan unduh <b>Tanda Terima</b>, kemudian cetak(print), dan bawa ke <b>Gedung UPT Perpustakaan Lantai 5 </b> sebagai bukti untuk melanjutkan proses selanjutnya.
                </div>
              @endif

              @if($s['key'] === 'terkirim')
                @php
                  $labelsTerkirim = $pengajuan->type === 'paten'
                    ? [
                        'skema_tkt'         => 'Dokumen TKT 7-9',
                        'draft_paten'       => 'Draft Paten',
                        'form_permohonan'   => 'Formulir Permohonan Paten',
                        'surat_kepemilikan' => 'Surat Pernyataan Kepemilikan Invensi',
                        'surat_pengalihan'  => 'Surat Pernyataan Pengalihan Hak Atas Invensi',
                        'scan_ktp'          => 'Scan KTP',
                        'gambar_prototipe'  => 'Gambar Prototipe',
                        'deskripsi_singkat_prototipe' => 'Deskripsi Singkat Prototipe', //TEKS
                      ]
                    : [
                        'surat_permohonan' => 'Surat Permohonan Pendaftaraan Ciptaan',
                        'surat_pernyataan' => 'Surat Pernyataan',
                        'surat_pengalihan' => 'Surat Pengalihan Hak Cipta',
                        'scan_ktp'         => 'Scan KTP',
                        'hasil_ciptaan'    => 'Hasil Ciptaan',
                        'link_ciptaan'     => 'Link Ciptaan'
                      ];

                  $sentDocs = collect($labelsTerkirim)->map(function($label, $key) use ($source){
                    $isText = in_array($key, ['deskripsi_singkat_prototipe'], true);

                    $val = $isText ? data_get($source, $key) : data_get($source, $key);

                    return (object)[
                      'key'     => $key,
                      'label'   => $label,
                      'is_text' => $isText,
                      'value'   => $val,
                    ];
                  })->values();

                  $prettyName = function($path, $fallbackLabel){
                    if(!$path) return null;
                    $base = basename($path);

                    $isHash = preg_match('/^[0-9a-f]{40}\.[A-Za-z0-9]+$/', $base);
                    $isUuid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.[A-Za-z0-9]+$/i', $base);

                    if($isHash || $isUuid){
                      $ext = pathinfo($base, PATHINFO_EXTENSION);
                      return $fallbackLabel . ($ext ? '.'.$ext : '');
                    }
                    return $base;
                  };
                @endphp

  <button type="button" class="pd-mini-btn btnDetailDok" style="margin-top:10px;">
    Detail Dokumen
  </button>

  <div class="pd-revisi-box boxDetailDok" style="display:none; margin-top:12px;">
    <div class="pd-revisi-title">Dokumen yang Dikirim Pemohon</div>

    <div class="pd-table-shell">
      <table class="pd-dok-table">
        <thead>
          <tr>
            <th style="width:60px;">No</th>
            <th style="width:40%;">Nama Dokumen</th>
            <th>File</th>
          </tr>
        </thead>

        <tbody>
          @foreach($sentDocs as $i => $d)
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $d->label }}</td>
              <td>
               @if($d->is_text)
                  {{-- TEKS --}}
                  @php $txt = trim((string)($d->value ?? '')); @endphp
                  @if($txt !== '')
                    <div style="white-space:pre-wrap;">{{ $txt }}</div>
                  @else
                    <span class="pd-muted">-</span>
                  @endif

                @elseif($d->key === 'link_ciptaan')
                  {{-- LINK (bukan file) --}}
                  @php
                    $url = trim((string)($d->value ?? ''));
                    // kalau user isi "gdrive.com/..." tanpa http, tambahin biar valid
                    if ($url !== '' && !preg_match('~^https?://~i', $url)) {
                      $url = 'https://' . $url;
                    }
                  @endphp

                  @if($url !== '')
                    <a href="{{ $url }}"
                      class="pd-file-link"
                      target="_blank"
                      rel="noopener noreferrer">
                      {{ $d->value }}
                    </a>
                  @else
                    <span class="pd-muted">-</span>
                  @endif

                @else
                {{-- FILE --}}
                @php
                $realValue = $d->value;
                $shownName = null;

                if ($d->key === 'skema_tkt') {
                    $realValue = $source->skema_tkt_template_path;

                    if ($realValue) {
                        $ext = pathinfo($realValue, PATHINFO_EXTENSION);

                        $shownName = ($source->no_pendaftaran ?? 'file') . '_skema_tkt_7-9'
                            . ($ext ? '.' . $ext : '');
                    }
                }
              @endphp

              @if(!empty($realValue))
                @php
                  $shownName = $shownName ?: $prettyName($realValue, $d->label);
                @endphp

                <a href="{{ route('pemohon.dokumen.download', ['type'=>$pengajuan->type, 'ref'=>$pengajuan->id, 'key'=>$d->key]) }}"
                  class="pd-file-link">
                  {{ $shownName }}
                </a>
              @else
                <span class="pd-muted">Belum diupload</span>
              @endif
            @endif
              </td>
            </tr>
          @endforeach
        </tbody>

      </table>
    </div>
  </div>
@endif


              {{-- =========================
                   REVISI (TETAP - TIDAK DIUBAH)
                   ========================= --}}
              @if($s['key'] === 'revisi' && in_array(($status ?? ''), ['revisi','approve']))
                @php
                  $labels = [
                    'skema_tkt'         => 'Dokumen TKT 7-9',
                    'draft_paten'=>'Draft Paten',
                    'form_permohonan'=>'Form Permohonan',
                    'surat_kepemilikan'=>'Surat Kepemilikan Invensi',
                    'surat_pengalihan'=>'Surat Pengalihan Hak',
                    'scan_ktp'=>'Scan KTP',
                    'gambar_prototipe'=>'Gambar Prototipe',
                    'deskripsi_singkat_prototipe' => 'Deskripsi Singkat Prototipe',
                    'surat_permohonan'=>'Surat Permohonan',
                    'surat_pernyataan'=>'Surat Pernyataan',
                    'hasil_ciptaan'=>'Hasil Ciptaan',
                  ];

                  $editableDocKeysByType = [
                    'cipta' => ['surat_permohonan','surat_pernyataan','surat_pengalihan'],
                    'paten' => ['form_permohonan','surat_kepemilikan','surat_pengalihan', 'skema_tkt','deskripsi_singkat_prototipe',],
                  ];

                  $editableDocKeys = $editableDocKeysByType[$pengajuan->type] ?? [];

                  $hist = collect($revHistory ?? [])
                    ->filter(function($h){
                      return !empty(data_get($h, 'pemohon_file_path'))
                          || !empty(data_get($h, 'pemohon_text'));
                    })
                    ->values();
                @endphp

            <button type="button" class="pd-mini-btn btnRevisi" style="margin-top:10px;">
            Detail Revisi
          </button>

          <div id="boxRevisi" class="pd-revisi-box" style="display:none; margin-top:10px;">

            @if(($status ?? '') === 'revisi')
              <div class="pd-revisi-title">Dokumen yang Perlu Direvisi</div>

              <p style="
            margin:10px 0 16px 0;
            padding:12px 14px;
            background:#eef4ff;
            border-left:4px solid #2563eb;
            color:#1e3a8a;
            font-size:15px;
            font-weight:500;
            border-radius:6px;
        ">
          ℹ️ Jika tersedia tombol <b>Edit</b>, silakan perbaiki data pada formulir terlebih dahulu, 
          kemudian <b>download ulang dokumen</b> dan <b>upload kembali</b> file revisi pada kolom 
          <b>Upload Revisi</b>.
        </p>
              <div class="pd-table-shell">
                <table class="pd-revisi-table revisi">
                <thead>
          <tr>
            <th>Dokumen</th>
            <th>Catatan</th>
            <th>File Resivi</th>
            <th>Status</th>
            <th>Aksi</th>
            <th>Upload Revisi</th>
          </tr>
        </thead>

        <tbody>
          @forelse(($revActiveByDoc ?? []) as $req)
            @php
              $docKey = $req->doc_key ?? '-';
              $docLabel = $labels[$docKey] ?? $docKey;

              $pemohonUploaded = !empty($req->pemohon_uploaded);
              $adminNote = $req->admin_note ?? '-';
              $adminFile = $req->admin_file_path ?? null;

              $revId = $req->id ?? null;
              $isTextDoc = in_array($docKey, ['deskripsi_singkat_prototipe'], true);
              $currentText = $isTextDoc ? trim((string) ($req->pemohon_text ?? '')) : '';
            @endphp

            <tr>
              <td>{{ $docLabel }}</td>

              <td>
                @php
                  $noteText = trim((string)($adminNote ?? ''));
                  $noteText = ($noteText === '' ? '-' : $noteText);
                  $noteClamp = trim(preg_replace("/\r\n|\r|\n/", " ", (string)$noteText));
                @endphp

                <div class="pd-note-cell">
                  @if($noteText === '-')
                    <span class="pd-dash">-</span>
                  @else
                    <div class="pd-note-clamp">{{ $noteClamp }}</div>
                    <div class="pd-note-full" hidden>{{ $noteText }}</div>
                    <button type="button" class="pd-note-toggle jsNoteToggle" hidden>Selengkapnya</button>
                  @endif
                </div>
              </td>

              <td class="pd-td-center">
                @if(!empty($adminFile))
                  <a href="{{ Storage::disk('s3')->url($adminFile) }}" target="_blank">
                    Download
                  </a>
                @else
                  <span class="pd-dash">-</span>
                @endif
              </td>

        <td class="pd-td-center">
          @if($isTextDoc)
            @if($currentText !== '')
              <span class="pd-pill done">
                Sudah<br>diperbarui
              </span>
            @else
              <span class="pd-pill todo">
                Belum<br>diperbarui
              </span>
            @endif
          @else
            @if($pemohonUploaded)
              <span class="pd-pill done">Sudah upload</span>
            @else
              <span class="pd-pill todo">Belum upload</span>
            @endif
          @endif
        </td>

              {{-- EDIT --}}
            {{-- REVISI TOMBOL EDIT DI DASHBOARD --}}
        <td class="pd-td-center">
        @if(in_array($docKey, $editableDocKeys))
          @php
            if ($docKey === 'skema_tkt') {
                $urlEdit = route('dup.skema.form', ['verif' => $pengajuan->id]);
            } elseif ($docKey === 'deskripsi_singkat_prototipe') {
                $urlEdit = route('pemohon.paten.edit_deskripsi', ['ref' => $pengajuan->id]);
            } else {
                $urlEdit = route('pemohon.revisi.edit', [
                    'type' => $pengajuan->type,
                    'ref'  => $pengajuan->id,
                    'doc'  => $docKey,
                ]);
            }
          @endphp

          <a href="{{ $urlEdit }}" class="pd-mini-btn">
            Edit
          </a>
          @else
            <span class="pd-muted">-</span>
          @endif
        </td>

              {{-- UPLOAD REVISI --}}
              <td>
                @if($isTextDoc)
                  <div style="display:flex; flex-direction:column; gap:8px;">
                    <div class="pd-muted" style="font-size:13px;">
                      Perbaiki melalui tombol <b>Edit</b>.
                    </div>

                    <div style="padding:10px 12px; background:#f8fafc; border:1px solid #dbe5f0; border-radius:10px;">
                      @if($currentText !== '')
                        <div style="white-space:pre-wrap;">{{ $currentText }}</div>
                      @else
                        <span class="pd-muted">Teks belum diisi.</span>
                      @endif
                    </div>
                  </div>
                @else
                  @if($revId)
                    @if(!$pemohonUploaded)
                      <form method="POST"
                        action="{{ route('pemohon.uploadRevisi', ['id' => $revId]) }}"
                        enctype="multipart/form-data"
                        class="pd-upload-form">
                    @csrf

                    <div class="upload-revisi-box">
                      <input
                        type="file"
                        name="file"
                        required
                        class="js-file-input hidden-input"
                        id="file-{{ $revId }}"
                        accept=".pdf,.doc,.docx"
                      >

                      <button type="button" class="btn-choose-file">
                        Pilih File
                      </button>

                      <div class="pd-file-name">Belum pilih file</div>
                      <div class="upload-hint">
                      <span class="required">*</span> Format: PDF, DOC, DOCX • Maks. 10 MB
                    </div>

                      <button type="submit" class="pd-mini-btn upload-btn">
                        Upload
                      </button>
                      @error('file')
                        <div class="error-text">{{ $message }}</div>
                      @enderror
                    </div>

                    </div>
                    </form>
                    @else
                      <span class="pd-muted">Sudah diupload untuk revisi ini.</span>
                    @endif
                  @else
                    <span class="pd-muted">Data revisi belum valid (id kosong).</span>
                  @endif
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6">Belum ada dokumen revisi.</td></tr>
          @endforelse
        </tbody>
        </table>
      </div>
    @endif

    <div class="pd-revisi-title" style="margin-top:16px;">
      Riwayat Revisi (Sudah Diupload)
    </div>

    <div class="pd-table-shell" style="margin-top:10px;">
      <table class="pd-revisi-table riwayat">
        <thead>
          <tr>
            <th>Dokumen</th>
            <th>Catatan</th>
            <th>File Revisi</th>
            <th>Status</th>
            <th>File Pemohon</th>
          </tr>
        </thead>
        <tbody>
          @forelse($hist as $h)
            @php
              $docKey2 = $h->doc_key ?? '-';
              $docLabel2 = $labels[$docKey2] ?? $docKey2;

              $adminNote2 = $h->admin_note ?? ($h->note ?? '-');
              $adminFile2 = $h->admin_file_path ?? ($h->file_path ?? null);
              $pemohonFile2 = $h->pemohon_file_path ?? null;
            @endphp

            <tr>
              <td>{{ $docLabel2 }}</td>

             <td>
                @php
                  $noteText = trim((string)($adminNote2 ?? ''));
                  $noteText = ($noteText === '' ? '-' : $noteText);
                @endphp

                <div class="pd-note-cell">
                  @if($noteText === '-')
                    <span class="pd-dash">-</span>
                  @else
                    @php
                      $noteClamp = trim(preg_replace("/\r\n|\r|\n/", " ", (string)$noteText));
                    @endphp
                    <div class="pd-note-clamp">{{ $noteClamp }}</div>
                    <div class="pd-note-full" hidden style="white-space:pre-wrap;">{{ $noteText }}</div>
                    <button type="button" class="pd-note-toggle jsNoteToggle" hidden>Selengkapnya</button>
                  @endif
                </div>
              </td>

              <td class="pd-td-center">
                @if(!empty($adminFile))
                  <a href="{{ Storage::disk('s3')->url($adminFile) }}" target="_blank">
                    Download
                  </a>
                @else
                  <span class="pd-dash">-</span>
                @endif
              </td>

              <td class="pd-td-center">
                @php
                  $isTextHistory = in_array($docKey2, ['deskripsi_singkat_prototipe'], true);
                @endphp

                @if($isTextHistory)
                  <span class="pd-pill done">Sudah<br>diperbarui</span>
                @else
                  <span class="pd-pill done">Sudah upload</span>
                @endif
              </td>

              <td class="pd-td-center">
                @php
                  $isTextHistory = in_array($docKey2, ['deskripsi_singkat_prototipe'], true);
                  $pemohonText2 = trim((string)($h->pemohon_text ?? ''));
                @endphp

                @if($isTextHistory)
                  @if($pemohonText2 !== '')
                    <div style="white-space:pre-wrap; text-align:left;">{{ $pemohonText2 }}</div>
                  @else
                    <span class="pd-dash">-</span>
                  @endif
                @else
                  @if($pemohonFile2)
                    <a href="{{ Storage::disk('s3')->url($pemohonFile2) }}" class="pd-action-link">
                      {{ $h->pemohon_file_name_display ?? $h->pemohon_file_name ?? 'Lihat File' }}
                    </a>
                  @else
                    <span class="pd-dash">-</span>
                  @endif
                @endif
              </td>
              
            </tr>
          @empty
            <tr><td colspan="5">Belum ada riwayat revisi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
@endif


              {{-- =========================
                   APPROVE
                   ========================= --}}
              @if($s['key'] === 'approve')
                @php
                  $isApprove = ($status === 'approve');
                  $tt = $sv->tanda_terima_pdf ?? null; 
                @endphp

                <div class="pd-approve-actions" style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
                  @if($isApprove && $tt)
                    <a class="pd-mini-btn primary"
                      target="_blank"
                      href="{{ route('pemohon.tanda_terima.download', ['type' => $pengajuan->type, 'ref' => $pengajuan->id]) }}">
                      Download Tanda Terima
                    </a>
                  @else
                    <button class="pd-mini-btn" disabled>Download Tanda Terima</button>
                  @endif

                  {{-- PENDAFTARAN --}}
                  @if($isApprove)
                    <a
                      href="{{ route('pemohon.pendaftaran.kirim', ['type' => $pengajuan->type, 'ref' => $pengajuan->id]) }}"
                      class="pd-mini-btn outline btnPendaftaran"
                    >
                      Pendaftaran
                    </a>
                  @else
                    <button class="pd-mini-btn outline" disabled>
                      Pendaftaran
                    </button>
                  @endif
                </div> {{-- nutup .pd-approve-actions --}}
              @endif  {{-- nutup @if($s['key'] === 'approve') --}}

            </div> {{-- nutup .pd-step-body --}}
          </div>   {{-- nutup .pd-step --}}
        @endforeach

      <div class="pd-legend">
        <div class="pd-leg"><span class="pd-leg-dot done"></span> Selesai</div>
        <div class="pd-leg"><span class="pd-leg-dot run"></span> Sedang Berlangsung</div>
        <div class="pd-leg"><span class="pd-leg-dot todo"></span> Belum Diproses</div>
      </div>
    </section>

  </div>
</main>

{{--  MODAL AKUN (klik user -> muncul tengah) --}}
<div class="pa-backdrop" id="paBackdrop" hidden></div>

<div class="pa-modal" id="paModal" hidden role="dialog" aria-modal="true" aria-labelledby="paTitle">
  <div class="pa-card">
    <div class="pa-head">
      <div>
        <div class="pa-title" id="paTitle">Akun Pemohon</div>
        <div class="pa-subtitle">Ringkasan data pemohon.</div>
      </div>
      <button type="button" class="pa-close" id="closeAccount" aria-label="Tutup">✕</button>
    </div>

    <div class="pa-body">
      <div class="pa-chip">{{ $akun->kategori ?? '-' }}</div>

      <div class="pa-kv">

      <div class="pa-wide">
        <div class="pa-label">Judul</div>
        <div class="pa-value">{{ $akun->judul ?? '-' }}</div>
      </div>

      <div>
        <div class="pa-label">Kategori Pengajuan</div>
        <div class="pa-value">{{ $akun->kategori ?? '-' }}</div>
      </div>

      <div>
        <div class="pa-label">Jenis</div>
        <div class="pa-value">{{ $akun->jenis ?? '-' }}</div>
      </div>

      <div class="pa-wide" style="margin-top:10px;">
        <div class="pa-label">Inventor</div>

        @php
          $inv = $akun->inventors_arr ?? [];
        @endphp

        @if(is_array($inv) && count($inv))
          <div style="display:flex; flex-direction:column; gap:10px; margin-top:6px;">
            @foreach($inv as $idx => $i)
              <div style="border:1px solid #e8eef7; border-radius:12px; padding:10px;">
                <div style="font-weight:600;">
                  {{ ($idx+1) }}. {{ $i['nama'] ?? '-' }}
                  <span style="font-weight:500;">({{ $i['status'] ?? '-' }})</span>
                </div>
                <div style="font-size:14px; margin-top:4px;">
                  <div><b>Email:</b> {{ $i['email'] ?? '-' }}</div>
                  <div><b>No. HP:</b> {{ $i['no_hp'] ?? '-' }}</div>
                  <div><b>Fakultas:</b> {{ $i['fakultas'] ?? '-' }}</div>
                </div>
              </div>
            @endforeach
          </div>
        @else
          <div class="pa-value">{{ $akun->inventor_list ?? '-' }}</div>
        @endif
      </div>

    </div>

  </div>
</div>

<div id="pdNoteModal" class="pd-modal" hidden>
  <div class="pd-modal-card">
    <div class="pd-modal-head">
      <div>
        <div id="pdNoteTitle" class="pd-modal-title">Detail</div>
        <div class="pd-modal-sub">Isi lengkap</div>
      </div>
      <button type="button" class="pd-modal-close" id="pdNoteClose">✕</button>
    </div>

    <div class="pd-modal-body">
      <pre id="pdNoteBody" class="pd-modal-pre"></pre>
    </div>
  </div>
</div>

<div id="pdNoteBackdrop" class="pd-backdrop" hidden></div>

@endsection

