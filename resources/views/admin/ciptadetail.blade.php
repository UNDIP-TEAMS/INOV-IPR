{{-- resources/views/admin/cipta/detail.blade.php --}}

@php
  $tab = 'cipta';
  $name = $name ?? 'Admin';
  $notifCount = $notifCount ?? 0;

  // lonceng
  $notifUrl = route('admin.dashboard', ['tab' => 'status', 'sub' => 'revisi']);

  $docLabels = [
    'surat_permohonan' => 'Surat Permohonan',
    'surat_pernyataan' => 'Surat Pernyataan',
    'surat_pengalihan' => 'Surat Pengalihan',
    'scan_ktp'         => 'Scan KTP',
    'hasil_ciptaan'    => 'Hasil Ciptaan',
  ];
  $docKeys = array_keys($docLabels);
  $editableDocKeysByType = [
    'cipta' => ['surat_permohonan','surat_pernyataan','surat_pengalihan'],
    'paten' => ['form_permohonan','surat_kepemilikan','surat_pengalihan'],
  ];

  $editableDocKeys = $editableDocKeysByType['cipta'];
  // pencipta
  $inventors = $row->inventors_arr ?? [];
  $incomingByDoc = collect($incomingByDoc ?? []);

  $isKeyedByDoc = $incomingByDoc->keys()->contains(fn($kk) => in_array($kk, $docKeys, true));

  if (!$isKeyedByDoc) {
    $incomingByDoc = $incomingByDoc->groupBy(function ($x) {
      return data_get($x, 'doc_key'); // aman buat array/object/collection
    });
  }


    $allDocStatuses = collect($docKeys)->map(
      fn($k) => strtolower((string) data_get(data_get($row,'docs'), "$k.status", 'pending'))
    );

    $hasAnyRevisi = $allDocStatuses->contains(fn($st) => $st === 'revisi');
    $allCheckedOk = $allDocStatuses->every(fn($st) => $st === 'ok');

    $canSend = $hasAnyRevisi;
    $canApprove = $allCheckedOk && !$hasAnyRevisi;

@endphp

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Detail Hak Cipta</title>

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

<body class="admin-page cipta-detail-page">

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

    <div class="cipta-wrap" data-cipta-detail>

      <div class="page-head page-head-left">
        <button type="button" class="btn-back-modern" onclick="history.back()">
          <span class="icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M15 18l-6-6 6-6"
                    stroke="white" stroke-width="2.8"
                    stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <span>Kembali</span>
        </button>
      </div>

      <div class="card card-paten">
        <div class="paten-head">
          <h2 class="paten-title">Detail Hak Cipta</h2>
          <div class="paten-sub">
            {{ $row->no_pendaftaran ?? '-' }} • {{ $row->judul_cipta ?? '-' }}
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
                <div class="p-value">
                  @php
                    $jenis = $row->jenis_cipta ?? '-';
                    if (strtolower($jenis) === 'lainnya') $jenis = $row->jenis_lainnya ?? 'Lainnya';
                  @endphp
                  {{ $jenis }}
                </div>
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
                <div class="p-value">{{ $row->judul_cipta ?? '-' }}</div>
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

              <div class="paten-row">
                <div class="p-label">Link Ciptaan</div><div class="p-colon">:</div>
                <div class="p-value">
                  @if(!empty($row->link_ciptaan))
                    <a class="doc-link doc-link-full" href="{{ $row->link_ciptaan }}" target="_blank">
                      {{ $row->link_ciptaan }}
                    </a>
                  @else
                    <span class="muted">-</span>
                  @endif
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      {{-- ACCORDION --}}
      <div class="acc-wrap">

        {{-- DOKUMEN --}}
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

              @foreach($docKeys as $k)
                @php
                    $filePath  = $row->$k ?? null;
                    $doc       = $row->docs[$k] ?? null;
                    $statusDoc = optional($doc)->status ?? 'pending';
                    $note      = optional($doc)->note;

                    $hasSourceValue = !empty($filePath);
                    $canRevisi = $hasSourceValue;

                    $bucket = null;

                    if ($incomingByDoc instanceof \Illuminate\Support\Collection && $incomingByDoc->has($k)) {
                        $bucket = $incomingByDoc->get($k);
                    } elseif (is_array($incomingByDoc) && array_key_exists($k, $incomingByDoc)) {
                        $bucket = $incomingByDoc[$k];
                    }

                    $cycles = $bucket !== null
                        ? collect($bucket)
                        : collect($incomingByDoc ?? [])->flatten(1)->filter(fn($x) => data_get($x, 'doc_key') === $k);

                    $cycles = $cycles->sortByDesc(fn($x) => data_get($x,'id') ?? 0)->values();

                    $cyclesSorted = $cycles;

                    $hasPemohonUpload = $cyclesSorted->contains(function($x){
                      return data_get($x,'from_role') === 'pemohon'
                        && in_array(data_get($x,'state'), ['submitted','uploaded'], true)
                        && !empty(data_get($x,'pemohon_file_path'));
                    });

                    // admin revisi = status dokumen revisi ATAU note admin ada
                    $hasAdminRevisi = ($statusDoc === 'revisi') || (trim((string)$note) !== '');

                    $lastAdminReq = $cycles->first(function($x){
                      return data_get($x,'from_role') === 'admin'
                        && in_array(data_get($x,'state'), ['requested','closed'], true);
                    });

                    $lastPemohonSub = null;
                    if ($lastAdminReq) {
                      $lastPemohonSub = $cycles->first(function($x) use ($lastAdminReq){
                        return data_get($x,'from_role') === 'pemohon'
                          && data_get($x,'state') === 'submitted'
                          && !empty(data_get($x,'pemohon_file_path'))
                          && (data_get($x,'id') > data_get($lastAdminReq,'id'));
                      });
                    }

                    $displayNote = trim((string) data_get($lastAdminReq,'note','')) ?: '-';

                    $displayFilePath = data_get($lastPemohonSub,'pemohon_file_path');
                    $displayFileName = data_get($lastPemohonSub,'pemohon_file_name')
                        ?: ($displayFilePath ? basename($displayFilePath) : '-');

                    $displayTimeRaw = data_get($lastPemohonSub,'pemohon_uploaded_at')
                        ?: data_get($lastPemohonSub,'created_at');
                    // =======================
                    // DOT STATUS
                    // merah = admin sudah minta revisi tapi pemohon belum upload
                    // hijau = pemohon sudah pernah upload revisi
                    // tetap hijau walaupun setelah itu admin klik OK
                    // =======================
                    $showDot  = false;
                    $dotClass = null;

                    // kalau status sudah OK → pasti hijau
                    if ($statusDoc === 'ok') {
                      $showDot = true;
                      $dotClass = 'green';
                    }

                    // ✅ kalau belum OK, lihat histori revisi
                    elseif ($hasAdminRevisi) {
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
                        {{ $docLabels[$k] }}
                      </div>

                      @if($filePath)
                        @php
                          $displayName = optional($doc)->pemohon_file_name
                            ?: (optional($doc)->pemohon_file_name_display ?: basename($filePath));
                        @endphp

                        <a class="doc-link doc-link-full"
                           href="{{ route('admin.cipta.doc.download', ['id' => $row->id, 'doc_key' => $k]) }}"
                          target="_blank"
                          title="{{ $displayName }}">
                          {{ $displayName }}
                        </a>
                      @else
                        <div class="muted">-</div>
                      @endif
                    </div>

                    <span class="badge badge-{{ $statusDoc }}" data-doc-badge data-doc-key="{{ $k }}">
                      {{ strtoupper($statusDoc) }}
                    </span>
                  </div>

                  <div class="doc-actions">
                    <form class="js-doc-form" method="POST"
                          action="{{ route('admin.verifikasi_dokumen.set',['type'=>'cipta','id'=>$row->id]) }}">
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

                    <div class="rev-dd" data-rev>
                      <button
                        type="button"
                        class="btn-mini rev-btn"
                        data-rev-btn
                        data-doc-revisi-btn
                        data-doc-key="{{ $k }}"
                        data-can-revisi="{{ $canRevisi ? '1' : '0' }}"
                        {{ (strtolower($statusDoc) === 'ok' || !$canRevisi) ? 'disabled' : '' }}
                        title="{{ $canRevisi ? 'Revisi dokumen' : 'Tidak ada dokumen' }}"
                      >
                        Revisi
                      </button>

                      <div class="rev-pop" data-rev-pop hidden>
                        <form class="js-doc-form" method="POST" enctype="multipart/form-data"
                              action="{{ route('admin.verifikasi_dokumen.set',['type'=>'cipta','id'=>$row->id]) }}">
                          @csrf
                          <input type="hidden" name="doc_key" value="{{ $k }}">
                          <input type="hidden" name="action" value="revisi">

                          <textarea name="note" rows="3" class="input" placeholder="Catatan revisi (wajib)" required>{{ $note }}</textarea>

                          <div style="margin-top:6px;">
                            <label style="font-size:12px;">Upload file revisi admin (opsional)</label>
                            <input type="file" name="admin_attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div style="margin-top:4px; font-size:11px; color:#6b7280;">
                              Format file: PDF, DOC, DOCX, JPG, JPEG, PNG • Maksimal 10 MB
                            </div>
                          </div>

                          <button type="submit" class="btn-mini" style="margin-top:6px;">Simpan Revisi</button>
                        </form>

                        @if(!empty(optional($doc)->admin_attachment_path))
                          <div style="margin-top:6px; font-size:12px;">
                            Lampiran admin:
                            <a href="{{ Storage::disk('s3')->url(optional($doc)->admin_attachment_path) }}" target="_blank">
                              {{ basename(optional($doc)->admin_attachment_path) }}
                            </a>
                          </div>
                        @endif
                      </div>
                    </div>
                  </div>

              <div class="incoming-wrap" data-incoming-wrap {{ ($hasAdminRevisi || $hasPemohonUpload) ? '' : 'hidden' }}>
                <div class="incoming-title">Revisi</div>

              <div class="rev-admin-box" data-admin-note-wrap {{ !empty($note) ? '' : 'hidden' }}>
                <div class="meta">
                  Update (Admin):
                  <span data-admin-note-date>
                    {{ optional($doc)->requested_at
                      ? \Carbon\Carbon::parse(optional($doc)->requested_at)->timezone('Asia/Jakarta')->format('d M Y H:i')
                      : '-' }}
                  </span>
                </div>
              </div>

              {{-- Status pemohon --}}
              <div class="muted" data-pemohon-empty {{ $hasPemohonUpload ? 'hidden' : '' }} style="font-size:12px; margin-top:10px;">
                Belum ada upload revisi dari pemohon.
              </div>

                @php
                  // cycles hasil buildRevisionCycles()
                  $adminNote = trim((string)($note ?? ''));

                  // jumlah row yang tampil di "Detail Revisi"
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
                  <div>File Pemohon</div>
                  <div>Update Pemohon</div>
                </div>

            {{-- fallback: kalau belum ada cycle (admin belum kirim), tapi ada note --}}
            @if($cycles->count() === 0 && $adminNote !== '')
              @php
                $raw = trim((string) $adminNote);
              @endphp

              <div class="incoming-row" data-empty-cycle-row="1">
              {{-- CATATAN --}}
              <div class="incoming-cell">
            @php
              $full = trim((string) $raw);
              $preview = \Illuminate\Support\Str::limit(
                preg_replace("/\r\n|\r|\n/", " ", $full),
                80
              );

              $more = mb_strlen(preg_replace("/\r\n|\r|\n/", " ", $full)) > 80
                    || preg_match("/\r\n|\r|\n/", $full);
            @endphp

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

              <div class="incoming-cell muted">Pemohon belum upload file revisi.</div>
              <div class="incoming-cell muted">-</div>
            </div>
            @endif


            @foreach($cycles as $cy)
              @php
                $hasFile = !empty(data_get($cy,'pemohon_file_path'));

                $noteTextRaw = trim((string) data_get($cy, 'note', ''));
                if ($noteTextRaw === '' && $hasFile) {

                  // admin request terdekat SEBELUM pemohon upload ini
                  $pairedAdmin = $cycles->first(function($x) use ($cy) {
                    return data_get($x,'from_role') === 'admin'
                      && in_array(data_get($x,'state'), ['requested','closed'], true)
                      && (data_get($x,'id') < data_get($cy,'id'));
                  });

                  $noteTextRaw = trim((string) data_get($pairedAdmin, 'note', ''));
                  if ($noteTextRaw === '' && $lastAdminReq && (data_get($lastAdminReq,'id') < data_get($cy,'id'))) {
                    $noteTextRaw = trim((string) data_get($lastAdminReq, 'note', ''));
                  }

                  // terakhir: kalau masih kosong, baru '-'
                  if ($noteTextRaw === '') {
                    $noteTextRaw = '-';
                  }
                }

                $noteText = ($noteTextRaw !== '') ? $noteTextRaw : '-';
                $timeRaw = $hasFile
                  ? (data_get($cy,'pemohon_uploaded_at') ?? data_get($cy,'created_at') ?? null)
                  : null;
              @endphp

                <div class="incoming-row">
                {{-- CATATAN --}}
                <div class="incoming-cell">
                  @php
              $full = trim((string) $noteText);                
              $preview = \Illuminate\Support\Str::limit(
                preg_replace("/\r\n|\r|\n/", " ", $full),
                80
              );

              $more = mb_strlen(preg_replace("/\r\n|\r|\n/", " ", $full)) > 80
                    || preg_match("/\r\n|\r|\n/", $full);
            @endphp

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

                {{-- FILE PEMOHON --}}
                <div class="incoming-cell">
                  @if($hasFile)
                    <a target="_blank" href="{{ route('revisi.download', $cy->id) }}">
                      {{ $cy->pemohon_file_name ?: ($cy->pemohon_file_name_display ?: basename($cy->pemohon_file_path)) }}
                    </a>
                  @else
                    <span class="muted">Pemohon belum upload file revisi.</span>
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
            </div> {{-- .incoming-wrap --}}
            </div> {{-- .doc-item --}}
            @endforeach

            </div> {{-- .docs-list --}}

            {{-- FOOTER BUTTONS (samain paten) --}}
            <div class="docs-footer-actions">

              <form id="sendRevisiForm"
                    class="js-send-revisi-form"
                    method="POST"
                    action="{{ route('admin.verifikasi_dokumen.sendRevisi', ['type'=>'cipta','id'=>$row->id]) }}">
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
                data-url="{{ route('admin.verifikasi_dokumen.waLinks', ['type'=>'cipta','id'=>$row->id]) }}"
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
                data-url="{{ route('admin.verifikasi_dokumen.approve', ['type'=>'cipta','id'=>$row->id]) }}"
                data-approved="{{ $isApproved ? '1' : '0' }}"
                {{ ($canApprove && !$isApproved) ? '' : 'disabled' }}
              >
                {{ $isApproved ? 'Sudah Approve' : 'Approve' }}
              </button>
            </div>
          </div>
        </div>

        {{-- PENCIPTA --}}
        <div class="acc-card" data-acc-card="inv">
          <button type="button" class="acc-head" data-acc-toggle="inv" aria-expanded="false">
            <div class="acc-left">
              <div class="acc-title">Detail Pencipta</div>
              <div class="acc-sub">Klik untuk melihat data pencipta</div>
            </div>
            <span class="acc-toggle" aria-hidden="true">
              <span class="acc-chevron"></span>
            </span>
          </button>

          <div class="acc-body" data-acc-body="inv" hidden>
            @if(empty($inventors))
              <div class="muted">Tidak ada data pencipta.</div>
            @else
              <div class="inv-grid">
                @foreach($inventors as $idx => $inv)
                  <div class="inv-card">
                    <div class="inv-name">
                      {{ $idx+1 }}. {{ $inv['nama'] ?? '-' }}
                      <span class="muted">({{ $inv['status'] ?? '-' }})</span>
                    </div>

                    <div class="inv-info">
                      <div class="inv-row"><div class="inv-k">NIP/NIM</div><div class="inv-colon">:</div><div class="inv-v">{{ $inv['nip_nim'] ?? '-' }}</div></div>
                      <div class="inv-row"><div class="inv-k">Fakultas</div><div class="inv-colon">:</div><div class="inv-v">{{ $inv['fakultas'] ?? '-' }}</div></div>
                      <div class="inv-row"><div class="inv-k">Email</div><div class="inv-colon">:</div><div class="inv-v">{{ $inv['email'] ?? '-' }}</div></div>
                      <div class="inv-row"><div class="inv-k">No HP</div><div class="inv-colon">:</div><div class="inv-v">{{ $inv['no_hp'] ?? '-' }}</div></div>
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
