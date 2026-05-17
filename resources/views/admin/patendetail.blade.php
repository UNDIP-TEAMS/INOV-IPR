{{-- resources/views/admin/paten/detail.blade.php --}}


@php
  $tab = 'paten';
  $name = $name ?? 'Admin';
  $notifCount = $notifCount ?? 0;
  $notifUrl = route('admin.dashboard', ['tab' => 'status', 'sub' => 'revisi']);
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Detail Paten</title>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  @vite(['resources/css/admin.css', 'resources/js/app.js'])

  @vite([
    'resources/css/lihatdetail.css',
    'resources/js/admin/lihatdetail.js'
  ])

  <style>
    .doc-dot{
      display:inline-block;width:8px;height:8px;border-radius:999px;
      margin-right:8px;vertical-align:middle;
      background:#e74c3c;
      box-shadow:0 0 0 3px rgba(231,76,60,.12);
    }
    .doc-dot.green{
      background:#22c55e;
      box-shadow:0 0 0 3px rgba(34,197,94,.14);
    }

    .rev-admin-box{
      background:#f7f9fc;border:1px solid #e6edf7;border-radius:12px;
      padding:12px 14px;margin-top:10px;
    }
    .rev-admin-box .ttl{font-weight:700;margin-bottom:6px}
    .rev-admin-box .meta{font-size:12px;color:#6b7280;margin-top:6px}

    .incoming-table{margin-top:10px}
    .incoming-row > div{font-size:13px}

    .truncate-1{
      max-width: 420px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    @media (max-width: 900px){
      .truncate-1{max-width: 220px;}
    }

    /* tombol approve */
    .btn-approve-right{
      background:#2563eb;
      color:#fff;
      border:none;
      padding:12px 18px;
      border-radius:12px;
      font-weight:800;
      font-size:14px;
      cursor:pointer;
      min-width:180px;
      margin-left:10px;
    }

    .docs-footer-actions{
      display:flex;
      justify-content:flex-end;
      gap:10px;
      margin-top:14px;
      align-items:center;
      flex-wrap:wrap;
    }
  </style>
</head>

<body class="admin-page paten-detail-page">

<header class="admin-header">
  <div class="brand">
    <img src="{{ Storage::disk('s3')->url('logo.jpg') }}" alt="Logo">
  </div>

  <div class="header-actions">
    <a href="{{ $notifUrl }}" class="notif-icon-btn" title="Notif Revisi">
      <img src="{{ Storage::disk('s3')->url('notif.png') }}" alt="Notif" class="notif-ic">
      @if($notifCount > 0)
        <span class="notif-badge">{{ $notifCount }}</span>
      @endif
    </a>

    <div class="user-dd" id="userDD">
      <button type="button" class="user-icon" id="userBtn" aria-haspopup="true" aria-expanded="false">
        <img src="{{ Storage::disk('s3')->url('user.png') }}" alt="User">
      </button>

      <div class="user-menu" id="userMenu" hidden>
        <div class="user-menu-head">
          <div class="user-menu-name">{{ $name }}</div>
          <div class="user-menu-sub">Admin</div>
        </div>
        <div class="user-menu-actions">
          <button type="button" class="user-menu-item" id="openChangePass">Ubah Password</button>
        </div>
      </div>
    </div>

    <button type="button" class="logout-btn" id="openLogoutModal" aria-label="Logout">
      <img src="{{ Storage::disk('s3')->url('logout.png') }}" alt="Logout">
    </button>
  </div>
</header>

<section class="dash-hero">
  <div class="dash-hero-overlay"></div>
  <h1 class="dash-hero-title">Halo, {{ $name }}!</h1>
</section>

<div class="dash-layout">

  <aside class="dash-sidebar">
    <a class="side-link {{ $tab==='stats' ? 'active' : '' }}"
       href="{{ route('admin.dashboard', ['tab'=>'stats']) }}">
      <img class="side-ic-img" src="{{ Storage::disk('s3')->url('statistik.png') }}" alt="">
      Statistik Analisis
    </a>

    <a class="side-link {{ $tab==='cipta' ? 'active' : '' }}"
       href="{{ route('admin.dashboard', ['tab'=>'cipta']) }}">
      <img class="side-ic-img" src="{{ Storage::disk('s3')->url('dokumen.png') }}" alt="">
      Data Hak Cipta
    </a>

    <a class="side-link {{ $tab==='paten' ? 'active' : '' }}"
       href="{{ route('admin.dashboard', ['tab'=>'paten']) }}">
      <img class="side-ic-img" src="{{ Storage::disk('s3')->url('dokumen.png') }}" alt="">
      Data Paten
    </a>
  </aside>

  <main class="dash-content">
@php
  use Illuminate\Support\Str;

  $notePreview = function($text, $limit = 80){
    $t = (string) $text;
    $oneLine = preg_replace("/\r\n|\r|\n/", " ", $t);
    return Str::limit($oneLine, $limit);
  };

  $hasMore = function($text, $limit = 80){
    $t = (string) $text;
    $oneLine = preg_replace("/\r\n|\r|\n/", " ", $t);
    return mb_strlen($oneLine) > $limit;
  };
@endphp

    @php
      $docLabels = [
        'skema_tkt'         => 'Dokumen TKT 7-9',
        'draft_paten'       => 'Draft Paten',
        'form_permohonan'   => 'Form Permohonan',
        'surat_kepemilikan' => 'Surat Kepemilikan',
        'surat_pengalihan'  => 'Surat Pengalihan',
        'scan_ktp'          => 'Scan KTP',
        'gambar_prototipe'  => 'Gambar Prototipe',
        'deskripsi_singkat_prototipe' => 'Deskripsi Singkat Prototipe',
      ];
      $docKeys = array_keys($docLabels);

      $inventors = $row->inventors_arr ?? [];
      $incomingByDoc = collect($incomingByDoc ?? []);

      $isKeyedByDoc = $incomingByDoc->keys()->contains(fn($kk) => in_array($kk, $docKeys, true));

      if (!$isKeyedByDoc) {
        $incomingByDoc = $incomingByDoc->groupBy(function ($x) {
          return data_get($x, 'doc_key');
        });
      }

      /**
      * RULE tombol:
      * - canSend: aktif kalau ada minimal 1 dokumen status revisi
      * - canApprove: aktif kalau semua dokumen sudah OK dan tidak ada yang revisi
      */
      $allDocStatuses = collect($docKeys)->map(fn($k) => strtolower((string) data_get(data_get($row,'docs'), "$k.status", 'pending')));

      $hasAnyRevisi = $allDocStatuses->contains(fn($st) => $st === 'revisi');
      $allCheckedOk = $allDocStatuses->every(fn($st) => $st === 'ok');

      $canSend = $hasAnyRevisi;
      $canApprove = $allCheckedOk && !$hasAnyRevisi;

    @endphp

    <div class="paten-wrap" data-paten-detail>

      <div class="page-head page-head-left">
       <button type="button" class="btn-back-modern" onclick="history.back()">
          <span class="icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path
                d="M15 18l-6-6 6-6"
                stroke="white"
                stroke-width="2.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </span>
          <span>Kembali</span>
        </button>

      </div>

      <div class="card card-paten">
        <div class="paten-head">
          <h2 class="paten-title">Detail Paten</h2>
          <div class="paten-sub">
            {{ $row->no_pendaftaran ?? '-' }} • {{ $row->judul_paten ?? '-' }}
          </div>
        </div>

        <div class="paten-grid">
          <div class="paten-col">
            <div class="paten-info">
              <div class="paten-row">
                <div class="p-label">No Pendaftaran</div><div class="p-colon">:</div>
                <div class="p-value">{{ $row->no_pendaftaran ?? '-' }}</div>
              </div>

              <div class="paten-row">
                <div class="p-label">Jenis</div><div class="p-colon">:</div>
                <div class="p-value">{{ $row->jenis_paten ?? '-' }}</div>
              </div>

              <div class="paten-row">
                <div class="p-label">Nilai Perolehan</div><div class="p-colon">:</div>
                <div class="p-value">{{ $row->nilai_perolehan ?? '-' }}</div>
              </div>

              <div class="paten-row">
                <div class="p-label">Skema Penelitian</div><div class="p-colon">:</div>
                <div class="p-value">{{ $row->skema_penelitian ?? '-' }}</div>
              </div>
            </div>
          </div>

          <div class="paten-col">
            <div class="paten-info">
              <div class="paten-row">
                <div class="p-label">Judul</div><div class="p-colon">:</div>
                <div class="p-value">{{ $row->judul_paten ?? '-' }}</div>
              </div>

              <div class="paten-row">
                <div class="p-label">Prototipe</div><div class="p-colon">:</div>
                <div class="p-value">{{ $row->prototipe ?? '-' }}</div>
              </div>

              <div class="paten-row">
                <div class="p-label">Sumber Dana</div><div class="p-colon">:</div>
                <div class="p-value">{{ $row->sumber_dana ?? '-' }}</div>
              </div>

              <div class="paten-row">
                <div class="p-label">Status Pengajuan</div><div class="p-colon">:</div>
                <div class="p-value">
                 <span
                    id="statusPengajuanBadge"
                    class="status-badge s-{{ strtolower($row->status ?? 'pending') }}"
                  >
                    {{ strtoupper($row->status ?? '-') }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="acc-wrap">

        <div class="acc-card" data-acc-card="docs">
          <button type="button" class="acc-head" data-acc-toggle="docs" aria-expanded="false">
            <div class="acc-left">
              <div class="acc-title">Detail Dokumen</div>
              <div class="acc-sub">Klik untuk melihat & verifikasi dokumen</div>
            </div>
            <span class="acc-toggle" aria-hidden="true">
              <span class="acc-chevron"></span>
            </span>

          </button>

         <div class="acc-body" data-acc-body="docs" hidden>
  <div class="docs-list">

  @php
  $prettyName = function($path, $fallbackLabel){
    if(!$path) return null;
    $base = basename($path);

    if (preg_match('/^[0-9a-f]{40}\.[A-Za-z0-9]+$/', $base)) {
      $ext = pathinfo($base, PATHINFO_EXTENSION);
      return $fallbackLabel . ($ext ? '.'.$ext : '');
    }

    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.[A-Za-z0-9]+$/i', $base)) {
      $ext = pathinfo($base, PATHINFO_EXTENSION);
      return $fallbackLabel . ($ext ? '.'.$ext : '');
    }

    return $base;
  };
@endphp

   @foreach($docKeys as $k)
  @php
    $isText = ($k === 'deskripsi_singkat_prototipe');

    $doc       = data_get($row->docs, $k);
    $statusDoc = data_get($doc, 'status', 'pending');
    $note      = data_get($doc, 'note');

    if ($isText) {
        $textValue = trim((string) data_get($row, $k, ''));
        $filePath  = null;
        $shownName = null;
    } else {
        if ($k === 'skema_tkt') {
            $filePath = $row->skema_tkt_template_path ?? null;
        } else {
            $filePath = data_get($row, $k);
        }

        $nameField    = $k . '_name';
        $labelForName = $docLabels[$k] ?? $k;

        if ($k === 'skema_tkt' && $filePath) {
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);

            $shownName = ($row->no_pendaftaran ?? 'file') . '_skema_tkt_7-9'
                . ($ext ? '.' . $ext : '');
        } else {
            $shownName = data_get($row, $nameField)
                ?: ($filePath ? $prettyName($filePath, $labelForName) : null);
        }
        $textValue = '';
    }

    $hasSourceValue = $isText
      ? ($textValue !== '')
      : !empty($filePath);
    $canRevisi = $hasSourceValue;

    // ==========================
    // CIPTA STYLE
    // ==========================
    $bucket = null;

    if ($incomingByDoc instanceof \Illuminate\Support\Collection && $incomingByDoc->has($k)) {
        $bucket = $incomingByDoc->get($k);
    } elseif (is_array($incomingByDoc) && array_key_exists($k, $incomingByDoc)) {
        $bucket = $incomingByDoc[$k];
    }

    $cycles = $bucket !== null
        ? collect($bucket)
        : collect($incomingByDoc ?? [])->flatten(1)->filter(fn($x) => data_get($x, 'doc_key') === $k);

    $cycles = $cycles->map(fn($x) => is_array($x) ? (object)$x : $x)
                     ->sortByDesc(fn($x) => data_get($x,'id') ?? 0)
                     ->values();

    $cyclesSorted = $cycles;

    $hasPemohonUpload = $cyclesSorted->contains(function($x) use ($isText){
      if($isText){
        $t = data_get($x,'pemohon_text')
          ?? data_get($x,'text')
          ?? data_get($x,'deskripsi')
          ?? data_get($x,'value');
        return trim((string)$t) !== '';
      }

      return data_get($x,'from_role') === 'pemohon'
        && in_array(data_get($x,'state'), ['submitted','uploaded'], true)
        && !empty(data_get($x,'pemohon_file_path'));
    });

    $hasAdminRevisi =
      ($statusDoc === 'revisi') ||
      (trim((string)$note) !== '') ||
      ($cycles->count() > 0);

    // =======================
    // DOT STATUS FINAL
    // =======================
    $showDot  = false;
    $dotClass = null;

    if ($statusDoc === 'ok') {
      $showDot = true;
      $dotClass = 'green';
    } elseif ($hasAdminRevisi) {
      $showDot = true;
      $dotClass = $hasPemohonUpload ? 'green' : 'red';
    }

    $dotTitle = ($dotClass === 'green')
      ? 'Pemohon sudah upload revisi'
      : 'Menunggu upload revisi pemohon';
  @endphp

      <div class="doc-item"
        data-doc-wrap
        data-doc-key="{{ $k }}"
        data-doc-status="{{ strtolower($statusDoc) }}">
        <div class="doc-top">
          <div>
            <div class="doc-name">
              @if($showDot)
                <span class="doc-dot {{ $dotClass }}" title="{{ $dotTitle }}"></span>
              @endif
              {{ $docLabels[$k] ?? $k }}
            </div>

            {{--  OUTPUT UTAMA --}}
            @if($isText)
              @if($textValue !== '')
                <div class="doc-text-block">
                  {{ $textValue }}
                </div>
              @else
                <div class="muted doc-text-block">-</div>
              @endif
            @else
              @if($filePath)
                <a class="doc-link"
                  href="{{ route('admin.paten.doc.download', ['id'=>$row->id, 'doc_key'=>$k]) }}">
                  {{ $shownName }}
                </a>
              @else
                <div class="muted">-</div>
              @endif
            @endif

          </div>
          <span class="badge badge-{{ $statusDoc }}" data-doc-badge data-doc-key="{{ $k }}">
            {{ strtoupper($statusDoc) }}
          </span>
        </div>

        <div class="doc-actions">
          <form class="js-doc-form" method="POST"
                action="{{ route('admin.verifikasi_dokumen.set',['type'=>'paten','id'=>$row->id]) }}">
            @csrf
            <input type="hidden" name="doc_key" value="{{ $k }}">
            <input type="hidden" name="action" value="ok">
            <button
              class="btn-mini"
              type="submit"
              data-doc-ok-btn
              data-doc-key="{{ $k }}"
            >
              OK
            </button>
          </form>

          <div data-rev>
            <button
            type="button"
            class="btn-mini rev-btn"
            data-rev-btn
            data-doc-revisi-btn
            data-doc-key="{{ $k }}"
            data-can-revisi="{{ $canRevisi ? '1' : '0' }}"
            {{ $canRevisi ? '' : 'disabled' }}
            title="{{ $canRevisi ? 'Revisi dokumen' : 'Tidak ada dokumen' }}"
          >
            Revisi
          </button>

      <div class="rev-pop" data-rev-pop hidden>
        <form class="js-doc-form" method="POST" enctype="multipart/form-data"
              action="{{ route('admin.verifikasi_dokumen.set',['type'=>'paten','id'=>$row->id]) }}">
          @csrf
          <input type="hidden" name="doc_key" value="{{ $k }}">
          <input type="hidden" name="action" value="revisi">

          <textarea name="note" rows="3" class="input" placeholder="Catatan revisi (wajib)" required>{{ $note }}</textarea>

          @if(!$isText)
            <div style="margin-top:6px;">
              <label style="font-size:12px;">Upload file revisi admin (opsional)</label>
              <input type="file" name="admin_attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
              <div style="margin-top:4px; font-size:11px; color:#6b7280;">
                Format file: PDF, DOC, DOCX, JPG, JPEG, PNG • Maksimal 10 MB
              </div>
            </div>
          @endif

          <button type="submit" class="btn-mini" style="margin-top:6px;">Simpan Revisi</button>
        </form>

        @if(!$isText && !empty(data_get($doc,'admin_attachment_path')))
          <div style="margin-top:6px; font-size:12px;">
            Lampiran admin:
            <a href="{{ asset('storage/'.data_get($doc,'admin_attachment_path')) }}" target="_blank">
              {{ basename(data_get($doc,'admin_attachment_path')) }}
            </a>
          </div>
        @endif
      </div>
    </div>
</div>

        {{-- REVISI box --}}
        <div class="incoming-wrap" data-incoming-wrap {{ ($hasAdminRevisi || $hasPemohonUpload) ? '' : 'hidden' }}>
          <div class="incoming-title">Revisi</div>

          <div class="rev-admin-box" data-admin-note-wrap {{ !empty($note) ? '' : 'hidden' }}>
            <div class="meta">
              Update (Admin):
              <span data-admin-note-date>
                {{ data_get($doc,'updated_at') ? \Carbon\Carbon::parse(data_get($doc,'updated_at'))->format('d M Y') : '-' }}
              </span>
            </div>
          </div>

          <div class="muted" data-pemohon-empty {{ $hasPemohonUpload ? 'hidden' : '' }} style="font-size:12px; margin-top:10px;">
            Belum ada upload revisi dari pemohon.
          </div>
@php
  $adminNote = trim((string)($note ?? ''));
  $displayCount = $cycles->count();
  if ($displayCount === 0 && $adminNote !== '') {
    $displayCount = 1;
  }
@endphp

          <details class="incoming-details" style="margin-top:10px;" {{ ($hasAdminRevisi || $hasPemohonUpload) ? '' : 'hidden' }}>
            <summary class="incoming-summary">
              Detail Revisi ({{ $displayCount }})
            </summary>

            <div class="incoming-table">
              <div class="incoming-row incoming-head">
                <div>Catatan</div>
                <div>{{ $isText ? 'Teks Pemohon' : 'File Pemohon' }}</div>
                <div>Update Pemohon</div>
              </div>

    {{-- fallback: kalau belum ada cycle tapi ada note admin --}}
    @if($cycles->count() === 0 && $adminNote !== '')
      @php
        $raw = trim((string) $adminNote);
        $full = $raw !== '' ? $raw : '-';
        $preview = \Illuminate\Support\Str::limit(
          preg_replace("/\r\n|\r|\n/", " ", $full),
          80
        );
        $more = mb_strlen(preg_replace("/\r\n|\r|\n/", " ", $full)) > 80
              || preg_match("/\r\n|\r|\n/", $full);
      @endphp

      <div class="incoming-row" data-empty-cycle-row="1">
        {{-- CATATAN --}}
        <div class="incoming-cell">
          @if($more && $full !== '-')
            <details class="note-detail">
              <summary class="note-sum" title="{{ $preview }}">
                <span class="note-short">{{ $preview }}</span>
                <span class="note-action">Selengkapnya</span>
              </summary>
              <div class="note-long">{{ $full }}</div>
              <button type="button" class="note-close">Tutup</button>
            </details>
          @else
            <div class="note-plain" title="{{ $preview }}">{{ $preview }}</div>
          @endif
        </div>

        <div class="incoming-cell muted">
          Pemohon belum upload {{ $isText ? 'teks revisi' : 'file revisi' }}.
        </div>
        <div class="incoming-cell muted">-</div>
      </div>
    @endif

    {{-- LIST CYCLES --}}
    @foreach($cycles as $cy)
      @php
        $pemohonText = null;
        $pemohonFilePath = null;

        if ($isText) {
          $pemohonText = data_get($cy,'pemohon_text')
            ?? data_get($cy,'text')
            ?? data_get($cy,'deskripsi')
            ?? data_get($cy,'value');
          $pemohonText = trim((string)$pemohonText);
        } else {
          $pemohonFilePath = data_get($cy,'pemohon_file_path');
        }

        $hasPemohonValue = $isText
          ? ($pemohonText !== '')
          : (!empty($pemohonFilePath));
        $noteTextRaw = trim((string) data_get($cy, 'note', ''));

        if ($noteTextRaw === '' && $hasPemohonValue) {
          $pairedAdmin = $cycles->first(function($x) use ($cy) {
            return data_get($x,'from_role') === 'admin'
              && in_array(data_get($x,'state'), ['requested','closed'], true)
              && (data_get($x,'id') < data_get($cy,'id'));
          });

          $noteTextRaw = trim((string) data_get($pairedAdmin, 'note', ''));

          if ($noteTextRaw === '') {
            $noteTextRaw = '-';
          }
        }

        $noteText = ($noteTextRaw !== '') ? $noteTextRaw : '-';
        $timeRaw = $hasPemohonValue
          ? (data_get($cy,'pemohon_uploaded_at') ?? data_get($cy,'created_at') ?? null)
          : null;

        // preview note
        $full = trim((string) $noteText);
        $preview = \Illuminate\Support\Str::limit(
          preg_replace("/\r\n|\r|\n/", " ", $full),
          80
        );
        $more = mb_strlen(preg_replace("/\r\n|\r|\n/", " ", $full)) > 80
              || preg_match("/\r\n|\r|\n/", $full);
      @endphp

      <div class="incoming-row">
        {{-- CATATAN --}}
        <div class="incoming-cell">
          @if($more && $full !== '-')
            <details class="note-detail">
              <summary class="note-sum" title="{{ $preview }}">
                <span class="note-short">{{ $preview }}</span>
                <span class="note-action">Selengkapnya</span>
              </summary>
              <div class="note-long">{{ $full }}</div>
              <button type="button" class="note-close">Tutup</button>
            </details>
          @else
            <div class="note-plain" title="{{ $preview }}">{{ $preview }}</div>
          @endif
        </div>

        {{-- FILE/TEXT PEMOHON --}}
        <div class="incoming-cell">
          @if($isText)
            @if($pemohonText !== '')
              <div class="truncate-1" title="{{ $pemohonText }}">{{ $pemohonText }}</div>
            @else
              <span class="muted">Pemohon belum upload teks revisi.</span>
            @endif
          @else
            @if(!empty($pemohonFilePath))
              <a target="_blank" href="{{ route('revisi.download', $cy->id) }}">
                {{ data_get($cy,'pemohon_file_name') ?: (data_get($cy,'pemohon_file_name_display') ?: basename($pemohonFilePath)) }}
              </a>
            @else
              <span class="muted">Pemohon belum upload file revisi.</span>
            @endif
          @endif
        </div>

        {{-- UPDATE PEMOHON --}}
        <div class="incoming-cell">
          @if(!empty($timeRaw))
            {{ \Carbon\Carbon::parse($timeRaw)->timezone('Asia/Jakarta')->format('d M Y H:i') }}
          @else
            -
          @endif
        </div>
      </div>
    @endforeach
  </div>
</details>
        </div>
      </div>
    @endforeach
</div>


            {{-- FOOTER BUTTONS --}}
            <div class="docs-footer-actions">
            <form id="sendRevisiForm"
                  class="js-send-revisi-form"
                  method="POST"
                  action="{{ route('admin.verifikasi_dokumen.sendRevisi', ['type'=>'paten','id'=>$row->id]) }}">
              @csrf
              <button id="btnSendRevisi"
                      type="submit"
                      class="btn-send-right"
                      {{ $canSend ? '' : 'disabled' }}>
                Simpan & Kirim ke Pemohon
              </button>
              </form>

              <button
                type="button"
                id="btnSendWA"
                class="btn-wa-right"
                data-url="{{ route('admin.verifikasi_dokumen.waLinks', ['type'=>'paten','id'=>$row->id]) }}"
              >
                Kirim WA
              </button>

              @php
              $isApproved = strtolower((string)($row->status ?? 'pending')) === 'approve';
            @endphp

            <button
              type="button"
              id="btnApprove"
              class="btn-approve-right {{ $isApproved ? 'is-approved' : '' }}"
              data-url="{{ route('admin.verifikasi_dokumen.approve', ['type'=>'paten','id'=>$row->id]) }}"
              data-approved="{{ $isApproved ? '1' : '0' }}"
              {{ ($canApprove && !$isApproved) ? '' : 'disabled' }}
            >
              {{ $isApproved ? 'Sudah Approve' : 'Approve' }}
            </button>
            </div>
          </div>
        </div>


        {{-- INVENTOR --}}
        <div class="acc-card" data-acc-card="inv">
          <button type="button" class="acc-head" data-acc-toggle="inv" aria-expanded="false">
            <div class="acc-left">
              <div class="acc-title">Detail Inventor</div>
              <div class="acc-sub">Klik untuk melihat data inventor</div>
            </div>
            <span class="acc-toggle" aria-hidden="true">
              <span class="acc-chevron"></span>
            </span>
          </button>

          <div class="acc-body" data-acc-body="inv" hidden>
            @if(empty($inventors))
              <div class="muted">Tidak ada data inventor.</div>
            @else
              <div class="inv-grid">
                @foreach($inventors as $idx => $inv)
                  <div class="inv-card">
                    <div class="inv-name">
                      {{ $idx+1 }}. {{ $inv['nama'] ?? '-' }}
                      <span class="muted">({{ $inv['status'] ?? '-' }})</span>
                    </div>

                    <div class="inv-info">
                      <div class="inv-row">
                        <div class="inv-k">NIP/NIM</div><div class="inv-colon">:</div><div class="inv-v">{{ $inv['nip_nim'] ?? '-' }}</div>
                      </div>
                      <div class="inv-row">
                        <div class="inv-k">Fakultas</div><div class="inv-colon">:</div><div class="inv-v">{{ $inv['fakultas'] ?? '-' }}</div>
                      </div>
                      <div class="inv-row">
                        <div class="inv-k">Email</div><div class="inv-colon">:</div><div class="inv-v">{{ $inv['email'] ?? '-' }}</div>
                      </div>
                      <div class="inv-row">
                        <div class="inv-k">No HP</div><div class="inv-colon">:</div><div class="inv-v">{{ $inv['no_hp'] ?? '-' }}</div>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>

      </div>
    </div>

    {{-- MODAL LOGOUT --}}
    <div class="modal-backdrop" id="logoutBackdrop" hidden></div>
    <div class="modal" id="logoutModal" hidden role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
      <div class="modal-card">
        <h3 id="logoutTitle" class="modal-title">Konfirmasi Logout</h3>
        <p class="modal-text">Kamu yakin mau logout?</p>

        <div class="modal-actions">
          <button type="button" class="btn-ghost" id="cancelLogout">Batal</button>

          <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn-danger">Ya, Logout</button>
          </form>
        </div>
      </div>
    </div>

    {{-- MODAL UBAH PASSWORD --}}
    <div class="modal-backdrop" id="passBackdrop" hidden></div>
    <div class="modal" id="passModal" hidden role="dialog" aria-modal="true" aria-labelledby="passTitle">
      <div class="modal-card">
        <h3 id="passTitle" class="modal-title">Ubah Password</h3>

        <form method="POST" action="{{ route('admin.password.update') }}">
          @csrf

          <label style="display:block; font-size:12px; margin-top:10px;">Password Lama</label>
          <input class="input" type="password" name="old_password" required>

          <label style="display:block; font-size:12px; margin-top:10px;">Password Baru</label>
          <input class="input" type="password" name="new_password" minlength="6" required>

          <label style="display:block; font-size:12px; margin-top:10px;">Konfirmasi Password Baru</label>
          <input class="input" type="password" name="new_password_confirmation" minlength="6" required>

          <div class="modal-actions" style="margin-top:14px;">
            <button type="button" class="btn-ghost" id="cancelPass">Batal</button>
            <button type="submit" class="btn-danger">Simpan</button>
          </div>
        </form>
      </div>
    </div>

  </main>
</div>

@if(session('wa_links'))
  <div id="waPayload"
       data-was='@json(session("wa_links"))'
       data-label="{{ session('wa_label') ?? 'Kirim WhatsApp' }}"
       hidden></div>
@endif

</body>
</html>
