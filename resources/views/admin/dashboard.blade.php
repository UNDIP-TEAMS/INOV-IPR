@php
  $tab = request()->get('tab', 'stats');
  $sub = request()->get('sub', 'all'); // all | revisi
@endphp


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Admin</title>

    {{-- CSS + JS lewat Vite --}}
    @vite(['resources/css/admin.css', 'resources/js/app.js', 'resources/js/admin/dashboard.js'])

    {{-- daterangepicker --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1/daterangepicker.min.js"></script>


</head>
 <body class="admin-page">
    <header class="admin-header">
    <div class="brand">
        <img src="{{ Storage::disk('s3')->url('logo.jpg') }}" alt="Logo">
    </div>

    <div class="header-actions">

       <a href="{{ route('admin.dashboard', ['tab'=>'status','sub'=>'revisi']) }}"
          class="notif-icon-btn"
          title="Notif Revisi">
            <img src="{{ Storage::disk('s3')->url('notif.png') }}" alt="Notif" class="notif-ic">

            @if(($notifCount ?? 0) > 0)
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
                <button type="button" class="user-menu-item" id="openChangePass">
                    Ubah Password
                </button>
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

@php
    $tab = $tab ?? 'stats';

    $docLabels = [
        // PATEN
        'draft_paten' => 'Draft Paten',
        'form_permohonan' => 'Form Permohonan',
        'surat_kepemilikan' => 'Surat Kepemilikan',
        'surat_pengalihan' => 'Surat Pengalihan',
        'scan_ktp' => 'Scan KTP',
        'tanda_terima' => 'Tanda Terima',
        'gambar_prototipe' => 'Gambar Prototipe',

        // CIPTA
        'surat_permohonan' => 'Surat Permohonan',
        'surat_pernyataan' => 'Surat Pernyataan',
        'surat_pengalihan' => 'Surat Pengalihan',
        'tanda_terima' => 'Tanda Terima',
        'scan_ktp' => 'Scan KTP',
        'hasil_ciptaan' => 'Hasil Ciptaan',
    ];
@endphp

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

        @php
            $tab = request('tab', 'stats');
            $sub = request('sub', 'all'); // all | revisi
        @endphp

         <div class="side-group">

      </div>
    </aside>

    <main class="dash-content">

        {{-- ================= TAB: STATISTIK ================= --}}
        @if($tab === 'stats')
            <section class="stats-wrap">

                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-title">Jumlah Keseluruhan</div>
                            <div class="stat-badge">TOTAL</div>
                        </div>
                        <div class="stat-value">{{ $totalAll }}</div>
                        <div class="stat-sub">Total pendaftaran/pengajuan</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-title">Jumlah Paten</div>
                            <div class="stat-badge">PATEN</div>
                        </div>
                        <div class="stat-value">{{ $totalPaten }}</div>
                        <div class="stat-sub">Semua jenis hak paten</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-title">Jumlah Hak Cipta</div>
                            <div class="stat-badge">CIPTA</div>
                        </div>
                        <div class="stat-value">{{ $totalCipta }}</div>
                        <div class="stat-sub">Semua jenis hak cipta</div>
                    </div>
                </div>

                <div class="stats-charts">
                    <div class="chart-card">
                        <div class="chart-title">Jumlah Jenis Hak Paten</div>
                        <canvas id="chartPaten"></canvas>
                    </div>

                    <div class="chart-card">
                        <div class="chart-title">Jumlah Jenis Hak Cipta</div>
                        <canvas id="chartCipta"></canvas>
                    </div>
                </div>

                 <div class="stats-charts">
                  <div class="chart-card">
                    <div class="chart-title">Total HKI Mahasiswa / Dosen</div>
                    <canvas id="chartRoleAll"></canvas>
                  </div>

                  <div class="chart-card">
                    <div class="chart-title">Jumlah Mahasiswa atau Dosen (Paten / Cipta)</div>
                    <canvas id="chartRoleByType"></canvas>
                  </div>
                </div>

                <div class="stats-charts">
                  <div class="chart-card" style="grid-column: 1 / -1;">
                    <div class="chart-title">Fakultas Pengajuan (Total / Paten / Cipta)</div>
                    <canvas id="chartFakultas"></canvas>
                  </div>
                </div>
            </section>
        @endif

        {{-- ================= TAB: DATA HAK CIPTA (STYLE = PATEN) ================= --}}
        @if($tab === 'cipta')
          <div class="page-head page-head--cipta">
            <h2 class="page-title">Data Hak Cipta</h2>

            <div class="page-actions page-actions--cipta">

              {{-- ===== FILTER BAR (sama kayak PATEN) ===== --}}
              <form method="GET" action="{{ url()->current() }}" class="filters-bar" id="ciptaFilters">
                <input type="hidden" name="tab" value="{{ request('tab','cipta') }}">
                <input type="hidden" name="sub" value="{{ request('sub') }}">

                <div class="filters-label">FILTERS</div>

                {{-- STATUS --}}
                <div class="filter-item">
                  <label>Status</label>
                  <select name="status" class="input">
                    <option value="">Semua</option>
                    @foreach(['terkirim','proses','revisi','approve'] as $st)
                      <option value="{{ $st }}" {{ request('status')===$st ? 'selected' : '' }}>
                        {{ strtoupper($st) }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="filter-item">
                  <label>Jenis</label>
                  <select name="jenis" class="input">
                    <option value="">Semua</option>
                    @foreach(($jenisList ?? []) as $j)
                      <option value="{{ $j }}" {{ request('jenis')===$j ? 'selected' : '' }}>
                        {{ $j }}
                      </option>
                    @endforeach
                  </select>
                </div>

                {{-- DATE RANGE --}}
                <div class="filter-item">
                  <label>Tanggal</label>

                  <div class="date-range-wrap">
                    <span class="date-ic">📅</span>
                    <input type="text"
                      id="dateRangeCipta"
                      class="input date-range-input"
                      placeholder="YYYY-MM-DD - YYYY-MM-DD"
                      value="{{ request('from') && request('to') ? request('from').' - '.request('to') : '' }}"
                      autocomplete="off"
                    >
                    <button type="button" class="date-clear" id="clearDateRangeCipta" title="Clear">×</button>
                  </div>

                  <input type="hidden" name="from" id="fromDateCipta" value="{{ request('from') }}">
                  <input type="hidden" name="to" id="toDateCipta" value="{{ request('to') }}">
                </div>

                <div class="filter-actions">
                  <button type="submit" class="btn-apply">Apply</button>
                  <a href="{{ route('admin.dashboard',['tab'=>'cipta']) }}" class="btn-remove">Remove</a>
                </div>
              </form>

              {{-- ===== EXPORT BUTTONS ===== --}}
              <a class="btn-mini" href="{{ route('admin.cipta.export_excel') }}" title="Download Excel">
                <img src="{{ Storage::disk('s3')->url('excel.png') }}" alt="PDF" style="width:32px;height:32px;">
                Excel
              </a>

              <a class="btn-mini" href="{{ route('admin.cipta.export_pdf') }}" title="Download PDF">
                <img src="{{ Storage::disk('s3')->url('pdf.png') }}" alt="PDF" style="width:32px;height:32px;">
                PDF
              </a>

              <a class="btn-mini" href="{{ route('admin.cipta.export_csv') }}" title="Download CSV">
                <img src="{{ Storage::disk('s3')->url('csv.png') }}" alt="PDF" style="width:32px;height:32px;">
                CSV
              </a>

              <input id="searchCipta" class="search-input search-input--cipta" type="text" placeholder="Cari..." />
            </div>
          </div>

          {{-- SUCCESS ALERT --}}
          @if(session('admin_success'))
            <div class="alert-success">
              <div>{{ session('success') }}</div>

              @if(session('wa_link'))
                <div style="margin-top:10px;">
                  <a class="btn-mini" target="_blank" href="{{ session('wa_link') }}">
                    {{ session('wa_label') ?? 'Kirim WhatsApp' }}
                  </a>
                </div>
              @endif
            </div>
          @endif

          {{-- TABLE --}}
          <div class="table-card table-scroll">
            <table class="data-table table-wide" id="ciptaTable">
              <thead>
                <tr>
                  <th style="width:60px;">No</th>
                    <th style="width:110px;">Tanggal</th>
                    <th style="width:160px;">No Pendaftaran</th>
                    <th>Judul</th>
                    <th style="width:170px;">Jenis</th>
                    <th style="width:130px;">Status</th>
                    <th style="width:140px;">Aksi</th>
                  </tr>
                </thead>

              <tbody>
          @forelse($dataCipta as $i => $row)
            @php
              $ciptaKey = strtolower(implode(' ', array_filter([
                $row->no_pendaftaran ?? '',
                $row->judul_cipta ?? '',
                $row->jenis_cipta ?? '',
                $row->status ?? '',
              ])));
            @endphp

            <tr data-key="{{ $ciptaKey }}" data-nop="{{ strtolower($row->no_pendaftaran ?? '') }}">
              <td>{{ $i+1 }}</td>

              {{-- TANGGAL --}}
              <td style="white-space:nowrap;">
                {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}
              </td>

              <td>{{ $row->no_pendaftaran ?? '-' }}</td>

              {{-- JUDUL --}}
              <td>
                <div class="title-main">{{ $row->judul_cipta ?? '-' }}</div>
              </td>

              {{-- JENIS --}}
              <td>
                @php
                  $jenis = $row->jenis_cipta ?? '-';
                  if (strtolower($jenis) === 'lainnya') $jenis = $row->jenis_lainnya ?? 'Lainnya';
                @endphp
                {{ $jenis }}
              </td>

              {{-- STATUS --}}
              <td>
                <span class="status-pill s-{{ strtolower($row->status) }}">
                  {{ strtoupper($row->status) }}
                </span>
              </td>

              {{-- AKSI --}}
              <td class="cell-actions">
                <a class="btn-mini" href="{{ route('admin.cipta.detail', $row->id) }}">Lihat Detail</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="center muted">Belum ada data cipta</td></tr>
          @endforelse
        </tbody>

            </table>
          </div>

          <div class="table-footer" data-pager="cipta">
            <div class="table-info" data-info>Showing 0 to 0 of 0 entries</div>

            <div class="table-controls">
              <label class="entries-wrap">
                Show
                <select class="entries-select" data-entries>
                  <option value="10">10</option>
                  <option value="20" selected>20</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                  <option value="200">200</option>
                </select>
                entries
              </label>

              <div class="pagination" data-pagination></div>
            </div>
          </div>
        @endif

       {{-- ================= TAB: DATA PATEN ================= --}}
        @if($tab === 'paten')
       <div class="page-head page-head--paten">
        <h2 class="page-title">Data Paten</h2>

        <div class="page-actions page-actions--paten">

          {{-- ===== FILTER BAR (rapi seperti contoh) ===== --}}
        <form method="GET" action="{{ url()->current() }}" class="filters-bar" id="patenFilters">
        <input type="hidden" name="tab" value="{{ request('tab','paten') }}">
        <input type="hidden" name="sub" value="{{ request('sub') }}">

        <div class="filters-label">FILTERS</div>

        {{-- STATUS --}}
        <div class="filter-item">
          <label>Status</label>
          <select name="status" class="input">
            <option value="">Semua</option>
            @foreach(['terkirim','proses','revisi','approve'] as $st)
              <option value="{{ $st }}" {{ request('status')===$st ? 'selected' : '' }}>
                {{ strtoupper($st) }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- JENIS --}}
        <div class="filter-item">
          <label>Jenis</label>
          <select name="jenis" class="input">
            <option value="">Semua</option>
            @foreach(($jenisList ?? []) as $j)
              <option value="{{ $j }}" {{ request('jenis')===$j ? 'selected' : '' }}>
                {{ $j }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- DATE RANGE --}}
        <div class="filter-item">
          <label>Tanggal</label>

          <div class="date-range-wrap">
            <span class="date-ic">📅</span>
            <input type="text"
              id="dateRangePaten"
              class="input date-range-input"
              placeholder="YYYY-MM-DD - YYYY-MM-DD"
              value="{{ request('from') && request('to') ? request('from').' - '.request('to') : '' }}"
              autocomplete="off"
            >
            <button type="button" class="date-clear" id="clearDateRange" title="Clear">×</button>
          </div>

          <input type="hidden" name="from" id="fromDate" value="{{ request('from') }}">
          <input type="hidden" name="to" id="toDate" value="{{ request('to') }}">
        </div>

        <div class="filter-actions">
          <button type="submit" class="btn-apply">Apply</button>
          <a href="{{ route('admin.dashboard',['tab'=>'paten']) }}" class="btn-remove">Remove</a>
        </div>
      </form>  

      {{-- ===== EXPORT BUTTONS (tetep pakai gambar) ===== --}}
      <a class="btn-mini" href="{{ route('admin.paten.export_excel') }}" title="Download Excel">
        <img src="{{ asset('images/excel.png') }}" alt="Excel" style="width:32px;height:32px;vertical-align:middle;">
        Excel
      </a>

      <a class="btn-mini" href="{{ route('admin.paten.export_pdf') }}" title="Download PDF">
        <img src="{{ asset('images/pdf.png') }}" alt="PDF" style="width:32px;height:32px;">
        PDF
      </a>

      <a class="btn-mini" href="{{ route('admin.paten.export_csv') }}" title="Download CSV">
        <img src="{{ asset('images/csv.png') }}" alt="CSV" style="width:32px;height:32px;">
        CSV
      </a>

      <input id="searchPaten" class="search-input search-input--paten" type="text" placeholder="Cari..." />
    </div>
  </div>


        @if(session('success'))
            <div class="alert-success">
            <div>{{ session('success') }}</div>

            @if(session('wa_link'))
                <div style="margin-top:10px;">
                <a class="btn-mini" target="_blank" href="{{ session('wa_link') }}">
                    {{ session('wa_label') ?? 'Kirim WhatsApp' }}
                </a>
                </div>
            @endif
            </div>
        @endif

        <div class="table-card table-scroll">
            <table class="data-table table-wide" id="patenTable">
            <thead>
              <tr>
                <th style="width:60px;">No</th>
                <th style="width:110px;">Tanggal</th>
                <th style="width:160px;">No Pendaftaran</th>
                <th>Judul</th>
                <th style="width:170px;">Jenis</th>
                <th style="width:130px;">Status</th>
                <th style="width:140px;">Aksi</th>
              </tr>
            </thead>

            <tbody>
            @forelse($dataPaten as $i => $row)
              @php
                $patenKey = strtolower(implode(' ', array_filter([
                  $row->no_pendaftaran ?? '',
                  $row->judul_paten ?? '',
                  $row->jenis_paten ?? '',
                  $row->status ?? '',
                ])));
              @endphp

              <tr data-key="{{ $patenKey }}" data-nop="{{ strtolower($row->no_pendaftaran ?? '') }}">
                <td>{{ $i+1 }}</td>

                {{-- TANGGAL --}}
                <td style="white-space:nowrap;">
                  {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}
                </td>

                <td>{{ $row->no_pendaftaran ?? '-' }}</td>

                {{-- JUDUL --}}
                <td>
                  <div class="title-main">{{ $row->judul_paten ?? '-' }}</div>
                </td>

                <td>{{ $row->jenis_paten ?? '-' }}</td>

                <td>
                  <span class="status-pill s-{{ strtolower($row->status) }}">
                    {{ strtoupper($row->status) }}
                  </span>
                </td>

                <td class="cell-actions">
                  <a class="btn-mini" href="{{ route('admin.paten.detail', $row->id) }}">Lihat Detail</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="center muted">Belum ada data paten</td></tr>
            @endforelse
            </tbody>

            </table>
        </div>
        <div class="table-footer" data-pager="paten">
            <div class="table-info" data-info>Showing 0 to 0 of 0 entries</div>

            <div class="table-controls">
                <label class="entries-wrap">
                Show
                <select class="entries-select" data-entries>
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
                entries
                </label>

                <div class="pagination" data-pagination></div>
            </div>
        </div>
        @endif
{{-- ================= TAB: STATUS VERIFIKASI ================= --}}
@if($tab === 'status' && $sub === 'all')
  <div class="page-head">
    <h2 class="page-title">Status Verifikasi</h2>

   <div class="page-actions">

  <div class="dd" data-dd>
    <button type="button" class="dd-btn" data-dd-btn>
      <span data-dd-label id="filterTypeLabel">Semua</span>
      <span class="dd-caret"></span>
    </button>

    <div class="dd-menu" data-dd-menu hidden>
      <button type="button" class="dd-item active" data-value="all">Semua</button>
      <button type="button" class="dd-item" data-value="paten">Paten</button>
      <button type="button" class="dd-item" data-value="cipta">Hak Cipta</button>
    </div>

    <input type="hidden" id="filterType" value="all" data-dd-input>
  </div>

  <input id="searchStatus" class="search-input" type="text" placeholder="Cari..." />

</div>


  <div class="table-card">
    <table class="data-table" id="statusTable">
      <thead>
      <tr>
        <th style="width:70px;">No</th>
        <th style="width:125px;">Kategori</th>
        <th style="width:150px;">Jenis</th>
        <th style="min-width:160px;">No Pendaftaran</th>
        <th style="min-width:240px;">Judul</th>
        <th style="width:280px;">Update Status</th>
      </tr>
      </thead>

      <tbody>
      @forelse($dataStatus as $i => $row)
        @php
          $statusKey = strtolower(implode(' ', array_filter([
            $row->type ?? '',              // paten/cipta (kategori)
            $row->no_pendaftaran ?? '',
            $row->status ?? '',
            $row->jenis ?? '',
            $row->judul ?? '',
            $row->email ?? '',             
          ])));
        @endphp

        <tr
          data-type="{{ strtolower($row->type) }}"
          data-nop="{{ strtolower($row->no_pendaftaran ?? '') }}"
          data-key="{{ $statusKey }}"
        >
          <td class="center">{{ $i + 1 }}</td>
          <td>{{ strtoupper($row->type) }}</td>
          <td>{{ $row->jenis ?? '-' }}</td>
          <td>{{ $row->no_pendaftaran ?? '-' }}</td>
          <td>{{ $row->judul ?? '-' }}</td>

          <td>
            <form class="inline-form js-status-form" method="POST"
              action="{{ route('admin.status.update', ['type' => $row->type, 'id' => $row->id]) }}">
              @csrf
              @method('PUT')

              <div class="dd dd-status" data-dd>
                <button type="button" class="dd-btn" data-dd-btn>
                  <span data-dd-label>{{ $row->status }}</span>
                  <span class="dd-caret"></span>
                </button>

                <div class="dd-menu" data-dd-menu hidden>
                  @foreach(['terkirim','proses','revisi','approve'] as $st)
                    <button type="button"
                      class="dd-item {{ $row->status===$st ? 'active' : '' }}"
                      data-value="{{ $st }}">{{ $st }}</button>
                  @endforeach
                </div>

                <input type="hidden" name="status" value="{{ $row->status }}" data-dd-input>
              </div>

              <button class="btn-mini btn-save" type="submit">Simpan</button>
              <div class="inline-msg" data-inline-msg style="margin-top:6px;font-size:12px;"></div>
            </form>

            <div class="wa-slot" data-wa-slot style="margin-top:8px;"></div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="center muted">Belum ada data status verifikasi</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <div class="table-footer" data-pager="status">
    <div class="table-info" data-info>Showing 0 to 0 of 0 entries</div>
    <div class="table-controls">
      <label class="entries-wrap">
        Show
        <select class="entries-select" data-entries>
          <option value="10">10</option>
          <option value="20" selected>20</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
          <option value="200">200</option>
        </select>
        entries
      </label>
      <div class="pagination" data-pagination></div>
    </div>
  </div>
@endif

   {{-- ================= SUB: REVISI (UPLOAD PEMOHON) ================= --}}
@if($tab === 'status' && $sub === 'revisi')
  <div class="revisi-head">

    {{-- ROW 1: TITLE --}}
    <div class="revisi-head__row1">
      <div class="revisi-head__title">
        <h2 class="page-title">Revisi Masuk (Upload Pemohon)</h2>
        <p class="muted page-desc">
          Daftar dokumen revisi yang sudah diupload pemohon (siap dicek admin).
        </p>
      </div> 
    </div>

    {{-- ROW 2: FILTER + SEARCH --}}
    <div class="revisi-head__row2">
      <form method="GET" action="{{ url()->current() }}"
            class="filters-bar filters-bar--revisi" id="revisiFilters">
        <input type="hidden" name="tab" value="status">
        <input type="hidden" name="sub" value="revisi">

        <div class="filters-label">FILTERS</div>

        <div class="filter-item">
          <label>Kategori</label>
          <select name="rev_type" class="input">
            <option value="">Semua</option>
            <option value="paten" {{ request('rev_type')==='paten' ? 'selected' : '' }}>PATEN</option>
            <option value="cipta" {{ request('rev_type')==='cipta' ? 'selected' : '' }}>HAK CIPTA</option>
          </select>
        </div>

        <div class="filter-item">
          <label>Dokumen</label>
          <select name="rev_doc" class="input">
            <option value="">Semua</option>
            @foreach(($revDocKeys ?? []) as $k)
              <option value="{{ $k }}" {{ request('rev_doc')===$k ? 'selected' : '' }}>
                {{ $docLabels[$k] ?? $k }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="filter-item">
          <label>Tanggal</label>
          <div class="date-range-wrap">
            <span class="date-ic">📅</span>
            <input type="text" id="dateRangeRevisi" class="input date-range-input"
                   placeholder="YYYY-MM-DD - YYYY-MM-DD"
                   value="{{ request('from') && request('to') ? request('from').' - '.request('to') : '' }}"
                   autocomplete="off">
            <button type="button" class="date-clear" id="clearDateRangeRevisi" title="Clear">×</button>
          </div>
          <input type="hidden" name="from" id="fromDateRevisi" value="{{ request('from') }}">
          <input type="hidden" name="to" id="toDateRevisi" value="{{ request('to') }}">
        </div>

        <div class="filter-actions">
          <button type="submit" class="btn-apply">Apply</button>
          <a href="{{ route('admin.dashboard',['tab'=>'status','sub'=>'revisi']) }}"
             class="btn-remove btn-remove--danger">Remove</a>
        </div>
      </form>

      <input id="searchRevisi" class="search-input search-input--revisi"
             type="text" placeholder="Cari..." />
    </div>

  </div>

      <div class="table-card">
        <table class="data-table" id="revisiTable">
          <thead>
            <tr>
              <th style="width:70px;">No</th>
              <th style="width:125px;">Kategori</th>
              <th style="width:150px;">Jenis</th>
              <th style="min-width:160px;">No Pendaftaran</th>
              <th style="min-width:240px;">Judul</th>
              <th style="min-width:360px;">Dokumen Revisi Masuk</th>
            </tr>
          </thead>

          <tbody>
            @php $n=1; @endphp

          @forelse($revisiItems as $row)
            @php
              // ini dari controller: $row->revisi_masuk (rows dari table revisions)
              $items = collect($row->revisi_masuk ?? []);

              $itemsBlob = $items->map(function($rv) use ($docLabels) {
                return implode(' ', array_filter([
                  $rv->doc_key ?? '',
                  $docLabels[$rv->doc_key] ?? '',
                  $rv->note ?? '',
                  basename($rv->pemohon_file_path ?? ''),
                  basename($rv->file_path ?? ''), // lampiran admin
                  $rv->updated_at ?? '',
                ]));
              })->implode(' ');

              $revisiKey = strtolower(implode(' ', array_filter([
                $row->type ?? '',                 // paten/cipta
                $row->jenis ?? '',
                $row->no_pendaftaran ?? '',
                $row->judul ?? '',
                $itemsBlob,
              ])));
            @endphp

            <tr
              data-key="{{ $revisiKey }}"
              data-nop="{{ strtolower($row->no_pendaftaran ?? '') }}"
            >
                <td class="center">{{ $n++ }}</td>
                <td>{{ strtoupper($row->type) }}</td>
                <td>{{ $row->jenis ?? '-' }}</td>
                <td>{{ $row->no_pendaftaran ?? '-' }}</td>
                <td>{{ $row->judul ?? '-' }}</td>

                <td>
                  @if($items->count() === 0)
                    <span class="badge badge-warn">Belum ada upload revisi dari pemohon</span>
                  @else
                    <div style="display:flex; flex-direction:column; gap:10px;">
                      @foreach($items as $rv)
                        <div style="padding:10px;border:1px solid #e6ebf5;border-radius:12px;background:#fff;">
                          <div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">
                            <div style="font-weight:800;color:#0b2c5f;">
                              {{ $docLabels[$rv->doc_key] ?? ($rv->doc_key ?? 'Dokumen') }}
                            </div>

                            {{-- optional: tandai notif sudah dibaca --}}
                            <form method="POST" action="{{ route('admin.revisi.read', $rv->id) }}">
                              @csrf
                              <button type="submit" class="btn-mini">Tandai dibaca</button>
                            </form>
                          </div>

                          @if(!empty($rv->note))
                            <div class="muted" style="margin-top:4px;font-size:12px;">
                              Catatan admin: {{ $rv->note }}
                            </div>
                          @endif

                          <div style="margin-top:8px;font-size:12px;">
                            <b>File Pemohon:</b>
                            @if(!empty($rv->pemohon_file_path))
                              <a href="{{ Storage::disk('s3')->url($rv->pemohon_file_path) }}" target="_blank">
                                {{ basename($rv->pemohon_file_path) }}
                              </a>
                            @else
                              <span class="muted">-</span>
                            @endif
                          </div>

                          <div style="margin-top:6px;font-size:12px;">
                            <b>Lampiran Admin:</b>
                            @if(!empty($rv->file_path))
                              <a href="{{ Storage::disk('s3')->url($rv->file_path) }}" target="_blank">
                                {{ basename($rv->file_path) }}
                              </a>
                            @else
                              <span class="muted">-</span>
                            @endif
                          </div>

                          @if(!empty($rv->updated_at))
                            <div class="muted" style="margin-top:6px;font-size:12px;">
                              Updated: {{ \Carbon\Carbon::parse($rv->updated_at)->format('d M Y H:i') }}
                            </div>
                          @endif
                        </div>
                      @endforeach
                    </div>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="center muted">Belum ada upload revisi dari pemohon</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="table-footer" data-pager="revisi">
        <div class="table-info" data-info>Showing 0 to 0 of 0 entries</div>
        <div class="table-controls">
          <label class="entries-wrap">
            Show
            <select class="entries-select" data-entries>
              <option value="10">10</option>
              <option value="20" selected>20</option>
              <option value="25">25</option>
              <option value="50">50</option>

              <option value="100">100</option>
              <option value="200">200</option>
            </select>
            entries
          </label>
          <div class="pagination" data-pagination></div>
        </div>
      </div>
    @endif


<!-- MODAL KONFIRMASI LOGOUT -->
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

{{-- MODAL KONFIRMASI HAPUS (PAKAI UNTUK PATEN & CIPTA) --}}
<div class="modal-backdrop" id="deleteBackdrop" hidden></div>

<div class="modal" id="deleteModal" hidden>
  <div class="modal-card modal-lg">
    <div class="modal-icon">!</div>

    <h3 class="modal-title" id="deleteTitle">Konfirmasi Hapus</h3>

    <p class="modal-text" id="deleteText">
      Apakah yakin ingin menghapus data ini?
      <br>
      <span class="modal-warning">Tindakan ini bersifat permanen dan tidak dapat dibatalkan.</span>
    </p>

    <div class="modal-actions">
      <button type="button" class="btn-ghost" id="cancelDelete">Batal</button>

      <form method="POST" id="deleteForm">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-danger">Hapus</button>
      </form>
    </div>
  </div>
</div>

@if($tab === 'stats')
  @php
    $fLabels = array_keys($allFakultasMap);

    $fAll   = array_map(fn($k) => (int)($allFakultasMap[$k] ?? 0), $fLabels);
    $fPaten = array_map(fn($k) => (int)($patenFakultasMap[$k] ?? 0), $fLabels);
    $fCipta = array_map(fn($k) => (int)($ciptaFakultasMap[$k] ?? 0), $fLabels);
  @endphp

  <script id="chart-data" type="application/json">
    {!! json_encode([
      'patenLabels' => array_keys($patenJenis),
      'patenData'   => array_values($patenJenis),
      'ciptaLabels' => array_keys($ciptaJenis),
      'ciptaData'   => array_values($ciptaJenis),

      'roleAll' => [
        'labels' => ['Mahasiswa','Dosen'],
        'data'   => [(int)$totalMahasiswaHKI, (int)$totalDosenHKI],
      ],
      'roleByType' => [
        'labels' => ['Paten','Hak Cipta'],
        'mahasiswa' => [(int)$patenMahasiswa, (int)$ciptaMahasiswa],
        'dosen'     => [(int)$patenDosen, (int)$ciptaDosen],
      ],

      'fakultas' => [
        'labels' => $fLabels,
        'all'    => $fAll,
        'paten'  => $fPaten,
        'cipta'  => $fCipta,
      ],
    ]) !!}
  </script>
@endif

{{-- DRAWER DETAIL PEMOHON --}}
<div class="modal-backdrop" id="detailBackdrop" hidden></div>

<aside class="detail-drawer" id="detailDrawer" hidden aria-hidden="true">
  <div class="detail-drawer-head">
    <div>
      <div class="detail-title" id="detailTitle">Detail</div>
      <div class="detail-sub" id="detailSub">-</div>
    </div>

    <button type="button" class="btn-ghost" id="closeDetail">Tutup</button>
  </div>

  <div class="detail-drawer-body" id="detailBody">
    {{-- diisi via JS --}}
  </div>
</aside>

</body>
</html>