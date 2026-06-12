<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paten;
use App\Models\HakCipta;
use App\Models\VerifikasiDokumen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\StatusUpdateMail;
use App\Models\PatenVerif;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Process;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\PatenInventorExport;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Illuminate\Support\Facades\Auth;
use App\Mail\DiterimaMail;
use Carbon\Carbon;


class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->session()->get('admin_logged_in')) {
            return redirect()->route('admin.login.form');
        }

        $name = $request->session()->get('admin_name', 'Admin');
        $tab  = $request->query('tab', 'stats');
        $sub  = $request->query('sub', 'all'); // all | revisi
        $year  = $request->query('year');  
        $month = $request->query('month');

        $totalPaten = 0;
        $totalCipta = 0;
        $totalAll   = 0;

        $patenJenis = [];
        $ciptaJenis = [];

        $patenMahasiswa = 0;
        $patenDosen = 0;
        $ciptaMahasiswa = 0;
        $ciptaDosen = 0;

        $allFakultasMap = [];
        $patenFakultasMap = [];
        $ciptaFakultasMap = [];

        // =========================
        // STATISTIK (PAKAI DATA PENGAJUAN)
        // =========================
        $totalPaten = DB::table('paten_verifs')->count();       //pengajuan paten
        $totalCipta = DB::table('hak_cipta_verifs')->count();   //pengajuan cipta
        $totalAll   = $totalPaten + $totalCipta;

        $patenJenis = DB::table('paten_verifs')
            ->select('jenis_paten', DB::raw('count(*) as total'))
            ->groupBy('jenis_paten')
            ->pluck('total', 'jenis_paten')
            ->map(fn ($v) => (int)$v)
            ->toArray();

        $ciptaJenis = DB::table('hak_cipta_verifs')
            ->select('jenis_cipta', DB::raw('count(*) as total'))
            ->groupBy('jenis_cipta')
            ->pluck('total', 'jenis_cipta')
            ->map(fn ($v) => (int)$v)
            ->toArray();

        // =========================
        // STATISTIK TAMBAHAN (MAHASISWA/DOSEN + FAKULTAS)
        // =========================

        //PATEN
        $patenRows = DB::table('paten_verifs')->select('inventors')->get();

        $patenMahasiswa = 0;
        $patenDosen = 0;
        $patenFakultasMap = [];

        foreach ($patenRows as $r) {
            $arr = [];
            if (is_string($r->inventors) && trim($r->inventors) !== '') {
                $decoded = json_decode($r->inventors, true);
                $arr = is_array($decoded) ? $decoded : [];
            } elseif (is_array($r->inventors)) {
                $arr = $r->inventors;
            }

            foreach ($arr as $inv) {
                $st = strtolower(trim((string)($inv['status'] ?? '')));
                if ($st === 'mahasiswa') $patenMahasiswa++;
                if ($st === 'dosen') $patenDosen++;

                $fk = trim((string)($inv['fakultas'] ?? ''));
                if ($fk !== '') {
                    $patenFakultasMap[$fk] = ($patenFakultasMap[$fk] ?? 0) + 1;
                }
            }
        }

        //CIPTA
        $ciptaMahasiswa = 0;
        $ciptaDosen = 0;
        $ciptaFakultasMap = [];
        $ciptaRows = DB::table('hak_cipta_verifs')->select('inventors', 'fakultas')->get();

        $ciptaMahasiswa = 0;
        $ciptaDosen = 0;
        $ciptaFakultasMap = [];

        foreach ($ciptaRows as $r) {
            // fakultas
            $fk = trim((string)($r->fakultas ?? ''));
            if ($fk !== '') {
                $ciptaFakultasMap[$fk] = ($ciptaFakultasMap[$fk] ?? 0) + 1;
            }

            // inventors json
            $arr = [];
            if (is_string($r->inventors) && trim($r->inventors) !== '') {
                $decoded = json_decode($r->inventors, true);
                $arr = is_array($decoded) ? $decoded : [];
            } elseif (is_array($r->inventors)) {
                $arr = $r->inventors;
            }

            foreach ($arr as $inv) {
                $st = strtolower(trim((string)($inv['status'] ?? '')));
                if ($st === 'mahasiswa') $ciptaMahasiswa++;
                if ($st === 'dosen') $ciptaDosen++;

                $fk2 = trim((string)($inv['fakultas'] ?? ''));
                if ($fk2 !== '') {
                    $ciptaFakultasMap[$fk2] = ($ciptaFakultasMap[$fk2] ?? 0) + 1;
                }
            }
        }

        // ===== TOTAL HKI (paten + cipta)
        $totalMahasiswaHKI = $patenMahasiswa + $ciptaMahasiswa;
        $totalDosenHKI     = $patenDosen + $ciptaDosen;

        // ===== FAKULTAS TOTAL (gabung)
        $allFakultasMap = $patenFakultasMap;
        foreach ($ciptaFakultasMap as $fk => $cnt) {
            $allFakultasMap[$fk] = ($allFakultasMap[$fk] ?? 0) + $cnt;
        }

        // urutkan fakultas descending & ambil top N
        arsort($allFakultasMap);
        arsort($patenFakultasMap);
        arsort($ciptaFakultasMap);

        $TOP = 12;
        $TOP = 12;

        // ambil TOP dari TOTAL (jadi label utama)
        arsort($allFakultasMap);
        $topLabels = array_slice(array_keys($allFakultasMap), 0, $TOP);

        // bikin map top total (buat “Total HKI”)
        $allFakultasMap = [];
        foreach ($topLabels as $fk) {
            $allFakultasMap[$fk] = ($patenFakultasMap[$fk] ?? 0) + ($ciptaFakultasMap[$fk] ?? 0);
        }

        $patenFakultasMap = array_intersect_key($patenFakultasMap, $allFakultasMap);
        $ciptaFakultasMap = array_intersect_key($ciptaFakultasMap, $allFakultasMap);

        $dataPaten   = collect();
        $dataCipta   = collect();
        $dataStatus  = collect();
        $revisiItems = collect();
        $notifCount  = 0;

        // NOTIF REVISI
        $notifCount = DB::table('revisions as r')
        ->whereIn('r.type', ['paten', 'cipta'])
        ->where('r.from_role', 'pemohon')
        ->where('r.state', 'submitted')
        ->whereNotNull('r.pemohon_file_path')
        ->where('r.is_read_admin', 0)
        ->distinct()
        ->count(DB::raw("CONCAT(r.type,'#',r.ref_id,'#',r.doc_key)"));

        // keys dokumen per tipe
        $keysByType = [
            'paten' => [
                'skema_tkt',
                'draft_paten',
                'form_permohonan',
                'surat_kepemilikan',
                'surat_pengalihan',
                'scan_ktp',
                'tanda_terima',
                'gambar_prototipe',
                'deskripsi_singkat_prototipe',
            ],
            'cipta' => [
                'surat_permohonan',
                'surat_pernyataan',
                'surat_pengalihan',
                'tanda_terima',
                'scan_ktp',
                'hasil_ciptaan',
            ],
        ];

        // =========================
        // TAB PATEN
        // =========================
        if ($tab === 'paten') {

        $from = $request->query('from');
        $to = $request->query('to');
        $status = $request->query('status');
        $jenis = $request->query('jenis');


            $q = DB::table('paten_verifs as p')
                ->leftJoin('status_verifikasi as sv', function ($join) {
                    $join->on('sv.ref_id', '=', 'p.id')
                        ->where('sv.ref_type', '=', 'paten');
                })
                ->select([
                    'p.*',
                    DB::raw("COALESCE(sv.status,'terkirim') as status"),
                    'sv.sertifikat_path',
                    'sv.emailed_at',
                ]);

            // filter tahun
            $q->when(!empty($year), function($qq) use ($year){
                $qq->whereYear('p.created_at', (int)$year);
            });

            // filter bulan
            $q->when(!empty($month), function($qq) use ($month){
                $qq->whereMonth('p.created_at', (int)$month);
            });

            $q->when(!empty($from), function($qq) use ($from){
            $qq->whereDate('p.created_at', '>=', $from);
            });

            $q->when(!empty($to), function($qq) use ($to){
            $qq->whereDate('p.created_at', '<=', $to);
            });

            $q->when(!empty($status), function($qq) use ($status){
            $qq->whereRaw("COALESCE(sv.status,'terkirim') = ?", [$status]);
            });

            $q->when(!empty($jenis), function($qq) use ($jenis){
            $qq->where('p.jenis_paten', $jenis);
            });


            $dataPaten = $q->orderByDesc('p.id')->get();

            $dataPaten = $dataPaten->map(function ($r) {
                $raw = $r->inventors ?? null;

                $arr = [];
                if (is_string($raw) && trim($raw) !== '') {
                    $decoded = json_decode($raw, true);
                    $arr = is_array($decoded) ? $decoded : [];
                } elseif (is_array($raw)) {
                    $arr = $raw;
                }

                $r->inventors_arr = collect($arr)->map(function ($i) {
                    return [
                        'nama'    => $i['nama'] ?? '-',
                        'status'  => $i['status'] ?? '-',
                        'email'   => $i['email'] ?? '-',
                        'no_hp'   => $i['no_hp'] ?? ($i['no hp'] ?? ($i['hp'] ?? '-')),
                        'nip_nim' => $i['nip_nim'] ?? ($i['nipnim'] ?? ($i['nip'] ?? '-')),
                        'fakultas'=> $i['fakultas'] ?? '-',
                    ];
                })->values()->all();

                return $r;
            });

            $dataPaten = $this->attachDocsToRows($dataPaten, 'paten', $keysByType['paten']);
        }

        // =========================
        // TAB CIPTA
        // =========================
        if ($tab === 'cipta') {

            $from   = $request->query('from');
            $to     = $request->query('to');
            $status = $request->query('status');
            $jenis  = $request->query('jenis');

            $q = DB::table('hak_cipta_verifs as c')
                ->leftJoin('status_verifikasi as sv', function ($join) {
                $join->on('sv.ref_id', '=', 'c.id')
                    ->where('sv.ref_type', '=', 'cipta');
                })
                ->select([
                'c.*',
                DB::raw("COALESCE(sv.status,'terkirim') as status"),
                'sv.sertifikat_path',
                'sv.emailed_at',
                ]);

            $q->when(!empty($year), fn($qq) => $qq->whereYear('c.created_at', (int)$year));
            $q->when(!empty($month), fn($qq) => $qq->whereMonth('c.created_at', (int)$month));

            $q->when(!empty($from), fn($qq) => $qq->whereDate('c.created_at', '>=', $from));
            $q->when(!empty($to),   fn($qq) => $qq->whereDate('c.created_at', '<=', $to));

            $q->when(!empty($status), fn($qq) => $qq->whereRaw("COALESCE(sv.status,'terkirim') = ?", [$status]));

            // filter jenis cipta
            $q->when(!empty($jenis), fn($qq) => $qq->where('c.jenis_cipta', $jenis));

            $dataCipta = $q->orderByDesc('c.id')->get();
            $dataCipta = collect($dataCipta);

            $dataCipta = $this->attachDocsToRows($dataCipta, 'cipta', $keysByType['cipta']);
        }



        // =========================
        // TAB STATUS (gabung paten + cipta)
        // =========================
        if ($tab === 'status') {
            // ---- PATEN
            $paten = DB::table('paten_verifs as p')
            ->leftJoin('status_verifikasi as sv', function ($join) {
                $join->on('sv.ref_id', '=', 'p.id')
                    ->where('sv.ref_type', '=', 'paten');
            })
            ->select([
                'p.id',
                'p.no_pendaftaran',
                DB::raw('p.judul_paten as judul'),
                DB::raw('p.jenis_paten as jenis'),
                'p.email',
                'p.inventors',

                'p.draft_paten',
                'p.form_permohonan',
                'p.surat_kepemilikan',
                'p.surat_pengalihan',
                'p.scan_ktp',
                'p.gambar_prototipe',

                DB::raw("'paten' as type"),
                DB::raw("COALESCE(sv.status,'terkirim') as status"),
                'sv.sertifikat_path',
                'sv.emailed_at',
            ])
            ->orderByDesc('p.id')
            ->get();

            // ---- CIPTA
          $cipta = DB::table('hak_cipta_verifs')
            ->leftJoin('status_verifikasi as sv', function ($join) {
                $join->on('sv.ref_id', '=', 'hak_cipta_verifs.id')
                    ->where('sv.ref_type', '=', 'cipta');
            })
            ->select([
                'hak_cipta_verifs.id',
                'hak_cipta_verifs.no_pendaftaran',
                DB::raw('hak_cipta_verifs.judul_cipta as judul'),
                DB::raw('hak_cipta_verifs.jenis_cipta as jenis'),

                'hak_cipta_verifs.email',

                'hak_cipta_verifs.surat_permohonan',
                'hak_cipta_verifs.surat_pernyataan',
                'hak_cipta_verifs.surat_pengalihan',
                'hak_cipta_verifs.tanda_terima',
                'hak_cipta_verifs.scan_ktp',
                'hak_cipta_verifs.hasil_ciptaan',

                DB::raw("'cipta' as type"),
                DB::raw("COALESCE(sv.status,'terkirim') as status"),
                'sv.sertifikat_path',
                'sv.emailed_at',
            ])
            ->orderByDesc('hak_cipta_verifs.id')
            ->get()
            ->map(function ($r) {
                // jenis lainnya
                if (strtolower((string)$r->jenis) === 'lainnya') {
                    $r->jenis = 'Lainnya';
                }
                return $r;
            });

            $dataStatus = $paten->concat($cipta)->values();

            $dataStatus = $dataStatus->map(function ($r) use ($keysByType) {
                $docKeys = $keysByType[$r->type] ?? [];

                $existing = VerifikasiDokumen::where([
                    'ref_type' => $r->type,
                    'ref_id'   => $r->id,
                ])->get()->keyBy('doc_key');

                $docsArr = [];
                foreach ($docKeys as $k) {
                    $docsArr[$k] = $existing->get($k) ?? (object)[
                        'doc_key' => $k,
                        'status'  => 'pending',
                        'note'    => null,
                        'admin_attachment_path' => null,
                        'pemohon_file_path' => null,
                    ];
                }

                $r->docs = $docsArr;
                return $r;
            });

              $revType   = $request->query('rev_type');  
                $revState  = $request->query('rev_state');  
                $revDoc    = $request->query('rev_doc');    
                $revUnread = $request->query('rev_unread'); 
                $from      = $request->query('from');
                $to        = $request->query('to');

            $incomingQ = DB::table('revisions as r')
                ->whereIn('r.type', ['paten', 'cipta'])
                ->where('r.from_role', 'pemohon')
                ->where('r.state', 'submitted')
                ->whereNotNull('r.pemohon_file_path')
                ->where('r.is_read_admin', 0) 
                ->select('r.*');

            // filter type
            if (!empty($revType)) {
                $incomingQ->where('r.type', $revType);
            }

            // filter doc_key
            if (!empty($revDoc)) {
                $incomingQ->where('r.doc_key', $revDoc);
            }

            // filter unread
            if ($revUnread === '1') {
                $incomingQ->where('r.is_read_admin', 0);
            }

            // filter tanggal
            if (!empty($from)) $incomingQ->whereDate('r.updated_at', '>=', $from);
            if (!empty($to))   $incomingQ->whereDate('r.updated_at', '<=', $to);

            $incoming = $incomingQ->orderByDesc('r.updated_at')->get();

            $mapStatus = $dataStatus->keyBy(fn($r) => trim(strtolower($r->type)).':' . $r->id);

            $revisiItems = $incoming
            ->groupBy(fn($sub) => trim(strtolower($sub->type)).':' . $sub->ref_id)
            ->map(function ($subs, $key) use ($mapStatus) {
                $base = $mapStatus->get($key);
                if (!$base) return null;


            $perDoc = $subs->groupBy('doc_key')->map(function ($docSubs) {
                $sub = $docSubs->sortByDesc('id')->first();
                if (!$sub) return null;

                $req = DB::table('revisions')
                    ->where('type', $sub->type)
                    ->where('ref_id', $sub->ref_id)
                    ->where('doc_key', $sub->doc_key)
                    ->where('from_role', 'admin')
                    ->whereIn('state', ['requested','closed'])
                    ->where('id', '<', $sub->id)
                    ->orderByDesc('id')
                    ->first();

                return (object)[
                    'id' => $sub->id,
                    'doc_key' => $sub->doc_key,
                    'note' => $req->note ?? null,
                    'file_path' => $req->file_path ?? null,

                    'pemohon_file_path' => $sub->pemohon_file_path ?? null,
                    'pemohon_file_name_display' => $sub->pemohon_file_name
                        ?: ($sub->pemohon_file_path ? basename($sub->pemohon_file_path) : null),

                    'updated_at' => $sub->pemohon_uploaded_at ?? $sub->created_at ?? $sub->updated_at,
                    'state' => 'submitted',
                    'is_read_admin' => $sub->is_read_admin ?? 0,
                ];
            })->filter()->values();

            $base->revisi_masuk = $perDoc;
            return $base;
        })
        ->filter()
        ->values();

        }

            $years = collect();
            $jenisList = collect();

            if ($tab === 'paten') {
                $years = DB::table('paten_verifs')
                    ->selectRaw('YEAR(created_at) as y')
                    ->whereNotNull('created_at')
                    ->distinct()
                    ->orderByDesc('y')
                    ->pluck('y');

                $jenisList = DB::table('paten_verifs')
                    ->whereNotNull('jenis_paten')
                    ->select('jenis_paten')
                    ->distinct()
                    ->orderBy('jenis_paten')
                    ->pluck('jenis_paten');
            }

            if ($tab === 'cipta') {
                $years = DB::table('hak_cipta_verifs')
                    ->selectRaw('YEAR(created_at) as y')
                    ->whereNotNull('created_at')
                    ->distinct()
                    ->orderByDesc('y')
                    ->pluck('y');

                $jenisList = DB::table('hak_cipta_verifs')
                    ->whereNotNull('jenis_cipta')
                    ->select('jenis_cipta')
                    ->distinct()
                    ->orderBy('jenis_cipta')
                    ->pluck('jenis_cipta');
            }

            $revDocKeys = DB::table('revisions')
                ->select('doc_key')
                ->whereIn('type', ['paten','cipta'])
                ->whereNotNull('doc_key')
                ->distinct()
                ->orderBy('doc_key')
                ->pluck('doc_key');


            return view('admin.dashboard', compact(
                'name',
                'tab',
                'sub',
                'dataPaten',
                'dataCipta',
                'dataStatus',
                'revisiItems',
                'notifCount',
                'totalAll',
                'totalPaten',
                'totalCipta',
                'patenJenis',
                'ciptaJenis',

                'totalMahasiswaHKI',
                'totalDosenHKI',
                'patenMahasiswa',
                'patenDosen',
                'ciptaMahasiswa',
                'ciptaDosen',
                'allFakultasMap',
                'patenFakultasMap',
                'ciptaFakultasMap',
                'years', 
                'year', 
                'month',
                'jenisList',
                'revDocKeys'
            ));
        }

    private function attachDocsToRows(\Illuminate\Support\Collection $rows, string $type, array $docKeys): \Illuminate\Support\Collection
    {
        if ($rows->count() === 0) return $rows;

        $ids = $rows->pluck('id')->filter()->values()->all();
        if (count($ids) === 0) return $rows;

        $allDocs = VerifikasiDokumen::where('ref_type', $type)
            ->whereIn('ref_id', $ids)
            ->get()
            ->groupBy('ref_id');

        return $rows->map(function ($r) use ($allDocs, $docKeys) {
            $existing = collect($allDocs->get($r->id, collect()))->keyBy('doc_key');

            $docsArr = [];
            foreach ($docKeys as $k) {
                $docsArr[$k] = $existing->get($k) ?? (object)[
                    'doc_key' => $k,
                    'status'  => 'pending',
                    'note'    => null,
                    'admin_attachment_path' => null,
                ];
            }

            $r->docs = $docsArr;

            return $r;
        });
    }


    // ========= helper email list =========
    private function parseEmails(?string $raw): array
    {
        if (!$raw) return [];
        $parts = preg_split('/[,\s;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $emails = [];
        foreach ($parts as $e) {
            $e = strtolower(trim($e));
            if (filter_var($e, FILTER_VALIDATE_EMAIL)) $emails[] = $e;
        }
        return array_values(array_unique($emails));
    }

    // ========= helper WA =========
    private function normalizeWaNumber(?string $raw): ?string
    {
        if (!$raw) return null;
        $num = preg_replace('/\D+/', '', $raw);

        if (!$num) return null;

        // kalau mulai 0 maka ganti jadi 62
        if (str_starts_with($num, '0')) {
            $num = '62' . substr($num, 1);
        }

        // kalau mulai 8 (user ngetik 812...) tambah 62
        if (str_starts_with($num, '8')) {
            $num = '62' . $num;
        }

        // basic validation panjang
        if (strlen($num) < 10 || strlen($num) > 16) return null;

        return $num;
    }

    private function makeWaLink(?string $phone, string $message): ?string
    {
        $phone = $this->normalizeWaNumber($phone);
        if (!$phone) return null;

        $text = rawurlencode($message);

        return "https://wa.me/{$phone}?text={$text}";
    }

    private function getPemohonPhone($row): ?string
    {
        return $row->nomor_hp ?? $row->no_hp ?? null;
    }

    private function getPemohonName($row): string
    {
        return $row->nama_pencipta
            ?? $row->nama
            ?? $row->username
            ?? 'Pemohon';
    }

    private function buildWaStatusMessage(
        string $kategori,
        string $judul,
        string $no,
        string $status
        ): string {
            $statusUpper = strtoupper($status);

            $body = match ($status) {
                'terkirim' =>
                    "Pengajuan {$kategori} telah KAMI TERIMA dan berhasil tercatat dalam sistem.\n" .
                    "Saat ini status pengajuan: {$statusUpper}.",

                'proses' =>
                    "Pengajuan {$kategori} sedang dalam tahap PEMERIKSAAN / VERIFIKASI oleh petugas.\n" .
                    "Saat ini status pengajuan: {$statusUpper}.",

                'revisi' =>
                    "Pengajuan {$kategori} MEMERLUKAN PERBAIKAN (REVISI) pada dokumen yang diajukan.\n" .
                    "Mohon meninjau catatan revisi pada sistem, kemudian melakukan unggah ulang dokumen sesuai arahan.\n" .
                    "Saat ini status pengajuan: {$statusUpper}.",

                'approve' =>
                    "Pengajuan {$kategori} telah DISETUJUI (APPROVE).\n" .
                    "Silakan mengunduh Tanda Terima melalui sistem dan melanjutkan proses sesuai ketentuan yang berlaku.\n" .
                    "Saat ini status pengajuan: {$statusUpper}.",

                default =>
                    "Status pengajuan {$kategori} telah diperbarui.\n" .
                    "Saat ini status pengajuan: {$statusUpper}.",
            };

            return
                "Yth. Bapak/Ibu\n\n" .
                "Dengan hormat,\n" .
                "{$body}\n\n" .
                "Rincian Pengajuan:\n" .
                "• Kategori : {$kategori}\n" .
                "• No. Pendaftaran : {$no}\n" .
                "• Judul : {$judul}\n\n" .
                "Untuk memantau perkembangan status pengajuan, silakan mengakses halaman berikut secara berkala:\n" .
                "http://127.0.0.1:8000/pemohon/login\n\n" .
                "Apabila diperlukan informasi lebih lanjut, silakan menghubungi admin atau layanan terkait.\n\n" .
                "Hormat kami,\n" .
                "Admin HKI";
        }

    // ========= STATUS VERIFIKASI (tabel status_verifikasi) =========
    public function updateStatusVerifikasi(Request $request, string $type, int $id)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return $request->expectsJson()
                    ? response()->json(['ok'=>false,'message'=>'Unauthorized'], 401)
                    : redirect()->route('admin.login.form');
            }

            if (!in_array($type, ['paten', 'cipta'], true)) abort(404);

            $request->validate([
                'status' => 'required|in:terkirim,proses,revisi,approve',
            ]);

            $newStatus = strtolower($request->input('status'));
            if ($newStatus === 'approve') {
    $docKeysByType = [
        'paten' => [
            'skema_tkt',
            'draft_paten',
            'form_permohonan',
            'surat_kepemilikan',
            'surat_pengalihan',
            'scan_ktp',
            'gambar_prototipe',
            'deskripsi_singkat_prototipe',
        ],
        'cipta' => [
            'surat_permohonan',
            'surat_pernyataan',
            'surat_pengalihan',
            'scan_ktp',
            'hasil_ciptaan',
        ],
    ];

    $docKeys = $docKeysByType[$type] ?? [];

    $docs = VerifikasiDokumen::where('ref_type', $type)
        ->where('ref_id', $id)
        ->get()
        ->keyBy('doc_key');

    foreach ($docKeys as $docKey) {
        $statusDoc = strtolower((string) optional($docs->get($docKey))->status ?? 'pending');

        if ($statusDoc !== 'ok') {
            $msg = 'Approve ditolak. Semua dokumen harus berstatus OK terlebih dahulu.';
            return $request->expectsJson()
                ? response()->json(['ok'=>false, 'message'=>$msg], 422)
                : redirect()->back()->with('admin_success', $msg);
        }
    }
}

        $old = DB::table('status_verifikasi')
            ->where('ref_type', $type)
            ->where('ref_id', $id)
            ->select(
                'id',
                'status',
                'created_at',
                'tanda_terima_pdf',
                'terkirim_at',
                'proses_at',
                'revisi_at',
                'approve_at'
            )
            ->first();

            $oldStatus = $old->status ?? null;
            $createdAt = $old->created_at ?? now();

            $payload = [
                'status'     => $newStatus,
                'updated_at' => now(),
                'created_at' => $createdAt,
            ];

            if ($newStatus === 'terkirim' && empty($old->terkirim_at)) {
                $payload['terkirim_at'] = now();
            }

            if ($newStatus === 'proses' && empty($old->proses_at)) {
                $payload['proses_at'] = now();
            }

            if ($newStatus === 'revisi') {
                $payload['revisi_at'] = now();
            }

            if ($newStatus === 'approve' && empty($old->approve_at)) {
                $payload['approve_at'] = now();
            }

            DB::table('status_verifikasi')->updateOrInsert(
                ['ref_type' => $type, 'ref_id' => $id],
                $payload
            );

            if ($newStatus === 'approve') {
                $path = $this->generateTandaTerimaPdf($type, $id);

                DB::table('status_verifikasi')
                    ->where(['ref_type' => $type, 'ref_id' => $id])
                    ->update([
                        'tanda_terima_pdf' => $path,
                        'updated_at' => now(),
                        ]);

            }
            // ✅ ambil row pengajuan untuk email/wa
            if ($type === 'paten') {
                $row = PatenVerif::findOrFail($id);
                $kategori = 'PATEN';
                $judul = $row->judul_paten ?? '-';
            } else {
                $row = DB::table('hak_cipta_verifs')->where('id', $id)->first();
                if (!$row) abort(404);
                $kategori = 'HAK CIPTA';
                $judul = $row->judul_cipta ?? '-';
            }

            $no = $row->no_pendaftaran ?? '-';

            if ($newStatus === 'approve' && empty($old?->tanda_terima_pdf)) {
                $path = $this->generateTandaTerimaPdf($type, $id);

                DB::table('status_verifikasi')
                    ->where(['ref_type' => $type, 'ref_id' => $id])
                    ->update([
                    'tanda_terima_pdf' => $path,
                    'updated_at' => now(),
                    ]);
                $old->tanda_terima_pdf = $path;
            }

            if ($newStatus === $oldStatus) {
                $msg = 'Status tidak berubah.';
                return $request->expectsJson()
                    ? response()->json(['ok'=>true,'message'=>$msg,'status'=>$newStatus,'no_change'=>true])
                    : redirect()->route('admin.dashboard', ['tab'=>'status'])->with('admin_success', $msg);
            }

            $autoStatuses = ['terkirim','proses'];

            $waStatuses = ['terkirim','proses','revisi','approve'];

            $emails = $this->parseEmails($row->email);
            $emailSent = false;

            if (in_array($newStatus, $autoStatuses, true) && count($emails) > 0) {
                try {
                    foreach ($emails as $to) {
                        Mail::to($to)->send(new StatusUpdateMail($kategori, $judul, $no, $newStatus));
                    }
                    $emailSent = true;
                } catch (\Throwable $e) {
                    Log::error('Gagal kirim email status update', [
                        'ref_type'=>$type,'ref_id'=>$id,'status'=>$newStatus,'err'=>$e->getMessage()
                    ]);
                }
            }

            $waLink = null;
            if (in_array($newStatus, $waStatuses, true)) {
                $phones = $this->getPemohonPhones($row);
                $nama  = $this->getPemohonName($row);

                $waMsg = $this->buildWaStatusMessage($kategori, $judul, $no, $newStatus, $nama);

                $waLinks = $this->makeWaLinks($phones, $waMsg);
                $waLink = $waLinks[0] ?? null;
            }

            $msg = 'Status berhasil diupdate.'
                . (count($emails) ? ($emailSent ? ' | Email terkirim.' : ' | Email gagal/skip.') : ' | Email kosong.')
                . ($waLink ? ' | WA siap (klik).' : ' | WA tidak valid.');

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => $msg,
                    'data' => [
                        'type' => $type,
                        'id' => $id,
                        'status' => $newStatus,
                        'kategori' => $kategori,
                        'judul' => $judul,
                        'no' => $no,
                    ],
                    'wa_link' => $waLink,
                    'wa_links' => $waLinks,
                ]);
            }
            if (in_array($newStatus, ['proses','approve'])) {

                $sv = DB::table('status_verifikasi')
                    ->where(['ref_type'=>$type,'ref_id'=>$id])
                    ->first();

                if (empty($sv->tanda_terima_pdf)) {
                    $pdfPath = $this->generateTandaTerimaPdf($type, $id);

                    DB::table('status_verifikasi')
                        ->where(['ref_type'=>$type,'ref_id'=>$id])
                        ->update([
                            'tanda_terima_pdf' => $pdfPath,
                            'tanda_terima_generated_at' => now(),
                        ]);
                }
            }

            if ($newStatus === 'approve') {
                $path = $this->generateTandaTerimaPdf($type, $id);

                DB::table('status_verifikasi')
                    ->where(['ref_type' => $type, 'ref_id' => $id])
                    ->update([
                        'tanda_terima_pdf' => $path,
                        'updated_at' => now(),
                    ]);
            }

            return redirect()->route('admin.dashboard', ['tab' => 'status'])
                ->with('admin_success', $msg)
                ->with('wa_link', $waLink)
                ->with('wa_label', 'Kirim WA (Status Update)');
        }

        // ========= upload sertifikat DJKI + kirim email diterima =========
        public function uploadSertifikatVerifikasi(Request $request, string $type, int $id)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return $request->expectsJson()
                    ? response()->json(['ok'=>false,'message'=>'Unauthorized'], 401)
                    : redirect()->route('admin.login.form');
            }

            if (!in_array($type, ['paten', 'cipta'])) abort(404);

            $request->validate([
                'sertifikat' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            $path = $request->file('sertifikat')->store('sertifikat_djki', 'public');

            $old = DB::table('status_verifikasi')->where(['ref_type'=>$type,'ref_id'=>$id])->first();
            DB::table('status_verifikasi')->updateOrInsert(
                ['ref_type' => $type, 'ref_id' => $id],
                [
                    'sertifikat_path' => $path,
                    'updated_at' => now(),
                    'created_at' => $old ? $old->created_at : now(),
                ]
            );

            $sv = DB::table('status_verifikasi')->where(['ref_type' => $type, 'ref_id' => $id])->first();

            if (!$sv || $sv->status !== 'diterima') {
                return redirect()->route('admin.dashboard', ['tab' => 'status'])
                    ->with('success', 'Sertifikat berhasil diupload. Email akan dikirim otomatis setelah status = diterima.');
            }

            if (!empty($sv->emailed_at)) {
                return redirect()->route('admin.dashboard', ['tab' => 'status'])
                    ->with('success', 'Sertifikat diupload. (Email sudah pernah dikirim, gunakan tombol Kirim Ulang Email jika perlu).');
            }

            $meta = $this->getRowByType($type, $id);
            $row = $meta['row'];
            $kategori = $meta['kategori'];
            $judul = $meta['judul'];
            $no = $meta['no'];

            $emails = $this->parseEmails($meta['email']);

            $no = $row->no_pendaftaran ?? '-';
            $emails = $this->parseEmails($row->email);

            if (count($emails) === 0) {
                return redirect()->route('admin.dashboard', ['tab' => 'status'])
                    ->with('success', 'Sertifikat diupload, tapi email pemohon kosong/tidak valid.');
            }

            $fullPath = storage_path('app/public/' . $path);

            try {
                foreach ($emails as $to) {
                    Mail::to($to)->send(new DiterimaMail($kategori, $judul, $no, $fullPath, basename($path)));
                }

                DB::table('status_verifikasi')
                    ->where(['ref_type' => $type, 'ref_id' => $id])
                    ->update(['emailed_at' => Carbon::now(), 'updated_at' => now()]);

                return redirect()->route('admin.dashboard', ['tab' => 'status'])
                    ->with('success', 'Sertifikat diupload & email otomatis berhasil dikirim.');
            } catch (\Throwable $e) {
                Log::error('Gagal kirim email diterima', ['err' => $e->getMessage()]);
                return redirect()->route('admin.dashboard', ['tab' => 'status'])
                    ->with('success', 'Gagal mengirim email. Cek storage/logs/laravel.log');
            }
        }

        // ========= resend email sertifikat =========
        public function resendEmail(Request $request, string $type, int $id)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return redirect()->route('admin.login.form');
            }
            if (!in_array($type, ['paten', 'cipta'])) abort(404);

            $sv = DB::table('status_verifikasi')->where(['ref_type' => $type, 'ref_id' => $id])->first();
            if (!$sv || empty($sv->sertifikat_path)) {
                return redirect()->route('admin.dashboard', ['tab'=>'status'])
                    ->with('success', 'Tidak bisa kirim ulang: sertifikat belum ada.');
            }

            $meta = $this->getRowByType($type, $id);
            $row = $meta['row'];
            $kategori = $meta['kategori'];
            $judul = $meta['judul'];

            $emails = $this->parseEmails($meta['email']);
            if (count($emails) === 0) {
                return redirect()->route('admin.dashboard', ['tab'=>'status'])
                    ->with('success', 'Email pemohon kosong/tidak valid.');
            }

            $fullPath = storage_path('app/public/' . $sv->sertifikat_path);

            try {
                foreach ($emails as $to) {
                    Mail::to($to)->send(new DiterimaMail($kategori, $judul, $row->no_pendaftaran ?? '-', $fullPath, basename($sv->sertifikat_path)));
                }

                DB::table('status_verifikasi')->where(['ref_type'=>$type,'ref_id'=>$id])
                    ->update(['emailed_at'=>Carbon::now(),'updated_at'=>now()]);

                return redirect()->route('admin.dashboard', ['tab'=>'status'])
                    ->with('success', 'Email berhasil dikirim ulang.');
            } catch (\Throwable $e) {
                Log::error('Gagal resend email', ['err' => $e->getMessage()]);
                return redirect()->route('admin.dashboard', ['tab'=>'status'])
                    ->with('success', 'Gagal kirim ulang email. Cek storage/logs/laravel.log');
            }
        }

        // ========= verifikasi dokumen per-file (OK / REVISI) =========
        public function setVerifikasiDokumen(Request $request, string $type, int $id)
    {
        if (!$request->session()->get('admin_logged_in')) {
            return $request->expectsJson()
                ? response()->json(['ok'=>false,'message'=>'Unauthorized'], 401)
                : redirect()->route('admin.login.form');
        }
        if (!in_array($type, ['paten', 'cipta'], true)) abort(404);

        $request->validate([
            'doc_key' => 'required|string|max:100',
            'action'  => 'required|in:ok,revisi,pending',
            'note'    => 'nullable|string',
            'admin_attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $docKey = $request->input('doc_key');
        $action = $request->input('action');

        $existing = VerifikasiDokumen::where([
            'ref_type' => $type,
            'ref_id'   => $id,
            'doc_key'  => $docKey,
        ])->first();

        $currentStatus = strtolower((string)($existing->status ?? 'pending'));

        if ($action === 'revisi') {
            $meta = $this->getRowByType($type, $id);
            $row  = $meta['row'];

            $isText = ($docKey === 'deskripsi_singkat_prototipe');

            if ($isText) {
                $sourceValue = trim((string) data_get($row, $docKey, ''));
            } else {
                $sourceValue = null;

                if ($type === 'paten' && $docKey === 'skema_tkt') {
                    $sourceValue = data_get($row, 'skema_tkt_template_path');
                } else {
                    $sourceValue = data_get($row, $docKey);
                }
            }

            $hasSourceValue = $isText
                ? ($sourceValue !== '')
                : !empty($sourceValue);

            if (!$hasSourceValue) {
                $msg = 'Dokumen opsional yang belum diisi / diupload tidak bisa direvisi. Silakan pilih OK jika memang tidak dilampirkan.';
                return $request->expectsJson()
                    ? response()->json(['ok' => false, 'message' => $msg], 422)
                    : redirect()->back()->with('admin_success', $msg);
            }
        }
   
        if ($currentStatus === 'revisi' && $action === 'pending') {
            $action = 'revisi';
        }

        if ($currentStatus === 'ok' && $action === 'revisi') {
            return $request->expectsJson()
                ? response()->json(['ok'=>false,'message'=>'Dokumen yang sudah OK tidak bisa direvisi lagi.'], 422)
                : redirect()->back()->with('success', 'Dokumen yang sudah OK tidak bisa direvisi lagi.');
        }

            $data = [
                'status' => in_array($action, ['ok', 'revisi', 'pending'], true) ? $action : 'pending',
            ];

        if ($action === 'revisi') {
            if (!trim((string)$request->input('note'))) {
                if ($request->expectsJson()) {
                    return response()->json(['ok'=>false,'message'=>'Catatan revisi wajib diisi.'], 422);
                }
                return redirect()->back()->with('success', 'Catatan revisi wajib diisi.');
            }

            $data['note'] = $request->input('note');
            $data['requested_at'] = now();
            $data['updated_at'] = now();
        } elseif ($action === 'ok') {
            $data['updated_at'] = now();
        } else { 
            $data['updated_at'] = now();
        }

    if ($request->hasFile('admin_attachment')) {
        $file = $request->file('admin_attachment');

        $original = $file->getClientOriginalName();
        $safeName = preg_replace('/[^a-zA-Z0-9.\-_ ()]/', '_', $original);

        $dir = "admin_revisi/{$type}/{$id}/{$docKey}";

        $finalName = $safeName;
        $counter = 1;

        $base = pathinfo($finalName, PATHINFO_FILENAME);
        $ext  = pathinfo($finalName, PATHINFO_EXTENSION);

        while (Storage::disk('public')->exists($dir.'/'.$finalName)) {
            $finalName = "{$base}_{$counter}" . ($ext ? ".{$ext}" : "");
            $counter++;
        }

        $path = $file->storeAs($dir, $finalName, 'public');

        $data['admin_attachment_path'] = $path;
        $data['admin_attachment_name'] = $finalName; 

        if ($action === 'revisi' && empty($data['requested_at'])) {
            $data['requested_at'] = now();
        }
    }

    VerifikasiDokumen::updateOrCreate(
        ['ref_type' => $type, 'ref_id' => $id, 'doc_key' => $docKey],
        $data
    );

        if ($request->expectsJson()) {
            $doc = VerifikasiDokumen::where([
                'ref_type' => $type,
                'ref_id'   => $id,
                'doc_key'  => $docKey,
            ])->first();

            return response()->json([
                'ok'      => true,
                'message' => 'Status dokumen berhasil disimpan.',
                'doc'     => [
                    'doc_key'               => $docKey,
                    'status'                => $doc->status ?? 'pending',
                    'note'                  => $doc->note,
                    'updated_at'            => $doc->updated_at,
                    'requested_at'          => $doc->requested_at,
                    'admin_attachment_path' => $doc->admin_attachment_path,
                    'admin_attachment_url'  => $doc->admin_attachment_path
                        ? asset('storage/' . $doc->admin_attachment_path)
                        : null,
                ],
                
            ]);
        }

        return redirect()->back()->with('success', 'Status dokumen berhasil disimpan.');
    }

        // ========= kirim email revisi  =========
    public function sendRevisiEmail(Request $request, string $type, int $id)
    {
        if (!$request->session()->get('admin_logged_in')) {
            return redirect()->route('admin.login.form');
        }
        if (!in_array($type, ['paten', 'cipta'], true)) abort(404);

        // ambil meta pengajuan
        $meta = $this->getRowByType($type, $id);
        $row = $meta['row'];
        $kategori = $meta['kategori'];
        $judul = $meta['judul'];
        $no = $meta['no'];

        $emails = $this->parseEmails($meta['email']);
        if (count($emails) === 0) {
            if ($request->expectsJson()) {
                return response()->json(['ok'=>false,'message'=>'Gagal kirim revisi: email pemohon kosong/tidak valid.'], 422);
            }
            return redirect()->back()->with('success', 'Gagal kirim revisi: email pemohon kosong/tidak valid.');
        }

        $docKeysOnly = $request->input('doc_keys', []);
        if (!is_array($docKeysOnly)) $docKeysOnly = [];
        $docKeysOnly = array_values(array_unique(array_filter($docKeysOnly)));

        // ambil dokumen yang statusnya revisi
        $qDocs = VerifikasiDokumen::where(['ref_type'=>$type,'ref_id'=>$id])
            ->where('status','revisi');

            if (count($docKeysOnly) > 0) {
            $qDocs->whereIn('doc_key', $docKeysOnly); 
            }

            $docs = $qDocs->orderBy('doc_key')->get();


        $docsToSend = $docs->filter(function($d) use ($type, $id){

            $lastPemohon = DB::table('revisions')
                ->where('type', $type)
                ->where('ref_id', $id)
                ->where('doc_key', $d->doc_key)
                ->where('from_role', 'pemohon')
                ->where('state', 'submitted')
                ->whereNotNull('pemohon_file_path')
                ->orderByDesc('id')
                ->first();

            if (!$lastPemohon) return true;
            if (empty($d->requested_at)) return false;
            return \Carbon\Carbon::parse($lastPemohon->created_at)
                ->lt(\Carbon\Carbon::parse($d->requested_at));

        })->values();

        if ($docsToSend->count() === 0) {
            if ($request->expectsJson()) {
                return response()->json(['ok'=>false,'message'=>'Tidak ada dokumen revisi baru untuk dikirim (pemohon sudah upload).'], 422);
            }
            return redirect()->back()->with('success', 'Tidak ada dokumen revisi baru untuk dikirim (pemohon sudah upload).');
        }

        if ($docs->count() === 0) {
            if ($request->expectsJson()) {
                return response()->json(['ok'=>false,'message'=>'Tidak ada dokumen revisi untuk dikirim.'], 422);
            }
            return redirect()->back()->with('success', 'Tidak ada dokumen revisi untuk dikirim.');
        }

        // label dokumen
        $labels = [
            // paten
            'draft_paten' => 'Draft Paten',
            'form_permohonan' => 'Form Permohonan',
            'surat_kepemilikan' => 'Surat Kepemilikan',
            'surat_pengalihan' => 'Surat Pengalihan',
            'scan_ktp' => 'Scan KTP',
            'tanda_terima' => 'Tanda Terima',
            'gambar_prototipe' => 'Gambar Prototipe',

            // cipta
            'surat_permohonan' => 'Surat Permohonan',
            'surat_pernyataan' => 'Surat Pernyataan',
            'surat_pengalihan' => 'Surat Pengalihan',
            'tanda_terima' => 'Tanda Terima',
            'scan_ktp' => 'Scan KTP',
            'hasil_ciptaan' => 'Hasil Ciptaan',
        ];

        $items = [];
        foreach ($docsToSend as $d) {
            $adminFull = null;
            if ($d->admin_attachment_path) {
                $adminFull = storage_path('app/public/' . $d->admin_attachment_path);
            }

            VerifikasiDokumen::where([
            'ref_type' => $type,
            'ref_id'   => $id,
        ])
        ->whereIn('doc_key', $docsToSend->pluck('doc_key')->all())
        ->update([
            'admin_attachment_path' => null,
        ]);

            $items[] = [
                'label' => $labels[$d->doc_key] ?? $d->doc_key,
                'note' => $d->note,
                'has_attachment' => !empty($d->admin_attachment_path),
                'admin_attachment_fullpath' => $adminFull,
                'admin_attachment_name' => $d->admin_attachment_path ? basename($d->admin_attachment_path) : null,
            ];
            }

            $oldSv = DB::table('status_verifikasi')
                ->where(['ref_type' => $type, 'ref_id' => $id])
                ->first();

            DB::table('status_verifikasi')->updateOrInsert(
                ['ref_type' => $type, 'ref_id' => $id],
                [
                    'status'     => 'revisi',
                    'revisi_at'  => now(),
                    'updated_at' => now(),
                    'created_at' => $oldSv?->created_at ?? now(),
                ]
            );
        
        foreach ($docsToSend as $d) {
            if (empty($d->requested_at)) {
                continue;
            }

            $hasOpen = DB::table('revisions')
                ->where('type', $type)
                ->where('ref_id', $id)
                ->where('doc_key', $d->doc_key)
                ->where('from_role', 'admin')
                ->where('state', 'requested')
                ->exists();

            if ($hasOpen) {
                continue;
            }

            $lastAdmin = DB::table('revisions')
                ->where('type', $type)
                ->where('ref_id', $id)
                ->where('doc_key', $d->doc_key)
                ->where('from_role', 'admin')
                ->whereIn('state', ['requested', 'closed'])
                ->orderByDesc('id')
                ->first();

            if ($lastAdmin) {
                $lastReqTime = Carbon::parse($lastAdmin->created_at);
                $newReqTime  = Carbon::parse($d->requested_at);

                if ($lastReqTime->gte($newReqTime)) {
                    continue;
                }
            }

        DB::table('revisions')->insert([
            'type'              => $type,
            'ref_id'            => $id,
            'doc_key'           => $d->doc_key,
            'from_role'         => 'admin',
            'state'             => 'requested',
            'note'              => $d->note,
            'file_path'         => $d->admin_attachment_path,
            'pemohon_file_path' => null,
            'pemohon_file_name' => null,
            'pemohon_uploaded_at' => null,
            'is_read_admin'     => 1,
            'is_read_pemohon'   => 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }


        // kirim email revisi (hold)
        try {
            $kategoriUpper = strtoupper($type === 'paten' ? 'PATEN' : 'HAK CIPTA');
            $loginUrl = url('/pemohon/login');

            $waMsg =
                "Yth. Bapak/Ibu Pemohon,\n\n"
            . "Dengan hormat,\n\n"
            . "Bersama ini kami sampaikan bahwa pengajuan {$kategoriUpper} membutuhkan *REVISI*.\n"
            . "Mohon melakukan perbaikan sesuai catatan revisi dan mengunggah kembali dokumen melalui sistem.\n\n"
            . "Rincian Pengajuan:\n"
            . "• Kategori : {$kategoriUpper}\n"
            . "• No. Pendaftaran : {$no}\n"
            . "• Judul : {$judul}\n\n"
            . "Silakan login melalui tautan berikut untuk melihat detail revisi:\n"
            . "{$loginUrl}\n\n"
            . "Terima kasih atas perhatian dan kerja samanya.\n\n"
            . "Hormat kami,\n"
            . "Admin KIHub";

            $phones  = $this->getPemohonPhones($row);      
            $waLinks = $this->makeWaLinks($phones, $waMsg);  

            $waLink = $waLinks[0] ?? null;

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Revisi berhasil dikirim.',
                    'emails' => $emails,
                    'wa_links' => $waLinks, 
                    'wa_link'  => $waLink,   
                    'wa_label' => 'Kirim WA (Announce Revisi)',
                ]);
            }

            return redirect()->back()
                ->with('success', 'Email revisi terkirim ke: '.implode(', ', $emails))
                ->with('wa_links', $waLinks)
                ->with('wa_label', 'Kirim WA (Announce Revisi)');


        } catch (\Throwable $e) {
            Log::error('Gagal kirim email revisi', [
                'ref_type'=>$type,'ref_id'=>$id,'emails'=>$emails,'err'=>$e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['ok'=>false,'message'=>'Gagal kirim email revisi.'], 500);
            }

            return redirect()->back()->with('success', 'Gagal kirim email revisi. Cek storage/logs/laravel.log');
        }
    }

        public function getWaLinks(Request $request, string $type, int $id)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
            }

            if (!in_array($type, ['paten', 'cipta'], true)) {
                abort(404);
            }

            $meta = $this->getRowByType($type, $id);
            $row = $meta['row'];
            $kategori = strtoupper($type === 'paten' ? 'PATEN' : 'HAK CIPTA');
            $judul = $meta['judul'] ?? '-';
            $no = $meta['no'] ?? '-';

            $phones = $this->getPemohonPhones($row);
            if (count($phones) === 0) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Nomor WhatsApp pemohon tidak tersedia.',
                ], 422);
            }

            $sv = DB::table('status_verifikasi')
                ->where(['ref_type' => $type, 'ref_id' => $id])
                ->first();

            $status = strtolower((string)($sv->status ?? ''));

            if ($status === 'approve') {
                $nama = $this->getPemohonName($row);

                $waMsg = $this->buildWaStatusMessage(
                    $kategori,
                    $judul,
                    $no,
                    'approve'
                );

                $waLinks = $this->makeWaLinks($phones, $waMsg);

                return response()->json([
                    'ok' => true,
                    'mode' => 'approve',
                    'title' => 'Kirim WA Approve',
                    'wa_links' => $waLinks,
                ]);
            }

            if ($status === 'revisi') {
                $loginUrl = url('/pemohon/login');

                $waMsg =
                    "Yth. Bapak/Ibu Pemohon,\n\n"
                    . "Dengan hormat,\n\n"
                    . "Bersama ini kami sampaikan bahwa pengajuan {$kategori} membutuhkan *REVISI*.\n"
                    . "Mohon melakukan perbaikan sesuai catatan revisi dan mengunggah kembali dokumen melalui sistem.\n\n"
                    . "Rincian Pengajuan:\n"
                    . "• Kategori : {$kategori}\n"
                    . "• No. Pendaftaran : {$no}\n"
                    . "• Judul : {$judul}\n\n"
                    . "Silakan login melalui tautan berikut untuk melihat detail revisi:\n"
                    . "{$loginUrl}\n\n"
                    . "Terima kasih atas perhatian dan kerja samanya.\n\n"
                    . "Hormat kami,\n"
                    . "Admin KIHub";

                $waLinks = $this->makeWaLinks($phones, $waMsg);

                return response()->json([
                    'ok' => true,
                    'mode' => 'revisi',
                    'title' => 'Kirim WA Revisi',
                    'wa_links' => $waLinks,
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'WA hanya tersedia untuk status revisi atau approve.',
            ], 422);
        }
        public function destroyPaten($id)
        {
            $row = PatenVerif::findOrFail($id);

            DB::table('status_verifikasi')
                ->where(['ref_type' => 'paten', 'ref_id' => $id])
                ->delete();

            VerifikasiDokumen::where(['ref_type' => 'paten', 'ref_id' => $id])->delete();

            DB::table('revisions')
                ->where(['type' => 'paten', 'ref_id' => $id])
                ->delete();

            $paths = [
                $row->draft_paten,
                $row->form_permohonan,
                $row->surat_kepemilikan,
                $row->surat_pengalihan,
                $row->scan_ktp,
                $row->tanda_terima,
                $row->gambar_prototipe,
            ];

            foreach ($paths as $p) {
                if ($p) Storage::disk('public')->delete($p);
            }

            $row->delete();

            return back()->with('success', 'Data paten berhasil dihapus.');
        }

        public function destroyCipta($id)
        {
            $row = DB::table('hak_cipta_verifs')->where('id', $id)->first();
            if (!$row) abort(404);

            DB::table('status_verifikasi')
                ->where(['ref_type' => 'cipta', 'ref_id' => $id])
                ->delete();

            VerifikasiDokumen::where(['ref_type' => 'cipta', 'ref_id' => $id])->delete();

            DB::table('revisions')
                ->where(['type' => 'cipta', 'ref_id' => $id])
                ->delete();

            $paths = [
                $row->surat_permohonan ?? null,
                $row->surat_pernyataan ?? null,
                $row->surat_pengalihan ?? null,
                $row->tanda_terima ?? null,
                $row->scan_ktp ?? null,
                $row->hasil_ciptaan ?? null,
            ];

            foreach ($paths as $p) {
                if ($p) Storage::disk('public')->delete($p);
            }

            DB::table('hak_cipta_verifs')->where('id', $id)->delete(); // ✅ ini penggantinya

            return back()->with('success', 'Data hak cipta berhasil dihapus.');
        }

        public function uploadRevisi(Request $request, int $revisionId)
    {
        $pemohon = Auth::guard('pemohon')->user();
        if (!$pemohon) return redirect()->route('pemohon.login.form');

        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $reqRow = DB::table('revisions')->where('id', $revisionId)->first();
        if (!$reqRow) abort(404, 'Revisi tidak ditemukan.');

        if (($reqRow->from_role ?? null) !== 'admin' || ($reqRow->state ?? null) !== 'requested') {
            return back()->with('success', 'Revisi ini sudah ditutup / tidak valid untuk upload.');
        }

        DB::beginTransaction();
        try {
        $file = $request->file('file');

        $tableName = ($reqRow->type === 'paten') ? 'paten_verifs' : 'hak_cipta_verifs';

        $source = DB::table($tableName)
            ->where('id', $reqRow->ref_id)
            ->first();

        $noPendaftaran = trim((string)($source->no_pendaftaran ?? ''));
        $safeNo = preg_replace('/[^A-Za-z0-9_-]/', '', $noPendaftaran);

        $dir = "revisi/{$reqRow->type}/{$reqRow->ref_id}/{$reqRow->doc_key}";

        $originalExt = $file->getClientOriginalExtension();

        if ($reqRow->doc_key === 'skema_tkt') {
            $finalName = $safeNo . '_skema_tkt_7-9.' . $originalExt;
        } else {
            $original = $file->getClientOriginalName();
            $safeName = preg_replace('/[^a-zA-Z0-9.\-_ ()]/', '_', $original);
            $finalName = $safeName;
        }

        $counter = 1;
        $base = pathinfo($finalName, PATHINFO_FILENAME);
        $ext  = pathinfo($finalName, PATHINFO_EXTENSION);

        while (Storage::disk('public')->exists($dir . '/' . $finalName)) {
            $finalName = "{$base}_{$counter}" . ($ext ? ".{$ext}" : "");
            $counter++;
        }

        $path = $file->storeAs($dir, $finalName, 'public');

            DB::table('revisions')->where('id', $revisionId)->update([
                'state'         => 'closed',
                'is_read_admin' => 1,
                'is_read_pemohon' => 1,
                'updated_at'    => now(),
            ]);

            DB::table('revisions')->insert([
                'type'              => $reqRow->type,
                'ref_id'            => $reqRow->ref_id,
                'doc_key'           => $reqRow->doc_key,
                'from_role'         => 'pemohon',
                'state'             => 'submitted',
                'note'              => $reqRow->note,

                'file_path'         => null,
                'pemohon_file_path' => $path,
                'pemohon_file_name' => $finalName,
                'pemohon_uploaded_at' => now(),

                'is_read_admin'     => 0, 
                'is_read_pemohon'   => 1,

                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            DB::commit();
            return back()->with('success', 'File revisi berhasil diupload.');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

        private function getRowByType(string $type, int $id)
        {
            if ($type === 'paten') {
                $row = PatenVerif::findOrFail($id);

                return [
                    'row'      => $row,
                    'kategori' => 'PATEN',
                    'judul'    => $row->judul_paten ?? '-',
                    'jenis'    => $row->jenis_paten ?? '-',  
                    'email'    => $row->email,
                    'no'       => $row->no_pendaftaran ?? '-',
                ];
            }

            $row = DB::table('hak_cipta_verifs')->where('id', $id)->first();
            if (!$row) abort(404);

            return [
                'row'           => $row,
                'kategori'      => 'HAK CIPTA',
                'judul'         => $row->judul_cipta ?? '-',
                'email'         => $row->email,
                'jenis'         => $row->jenis_cipta ?? '-',     
                'jenis_lainnya' => $row->jenis_lainnya ?? null,  
                'no'            => $row->no_pendaftaran ?? '-',
            ];
        }

        public function markRevisionRead(Request $request, int $id)
        {
        if (!$request->session()->get('admin_logged_in')) {
            return redirect()->route('admin.login.form');
        }

        DB::table('revisions')->where('id', $id)->update([
            'is_read_admin' => 1,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Notifikasi dibaca.');
        }
        
        private function generateTandaTerimaPdf(string $type, int $id): string
    {
        $meta = $this->getRowByType($type, $id);

       $template = $type === 'paten'
    ? storage_path('app/templates/tanda_terima_paten.docx')
    : storage_path('app/templates/tanda_terima_hakcipta.docx');

    if (!file_exists($template)) {
        throw new \Exception("Template tanda terima tidak ditemukan: {$template}");
    }

        $doc = new TemplateProcessor($template);
        $judul = trim((string)($meta['judul'] ?? '-')) ?: '-';
        $jenis = trim((string)($meta['jenis'] ?? '-')) ?: '-';

        if (strtolower($type) === 'cipta') {
            if (strtolower(trim((string)($meta['jenis'] ?? ''))) === 'lainnya') {
                $jenis = trim((string)($meta['jenis_lainnya'] ?? 'Lainnya')) ?: 'Lainnya';
            }
        }

        $doc->setValue('jenis', $jenis);
        $doc->setValue('judul', $judul);
        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

        $tmpDocx = $tmpDir . DIRECTORY_SEPARATOR . "tanda_terima_{$type}_{$id}.docx";
        $doc->saveAs($tmpDocx);

        // LibreOffice path
        $soffice = 'D:\\Program Files\\LibreOffice\\program\\soffice.exe';;
        if (!file_exists($soffice)) {
            @unlink($tmpDocx);
            throw new \Exception("LibreOffice (soffice.exe) tidak ditemukan: {$soffice}");
        }

        $convertOut = storage_path('app/lo_out');
        if (!is_dir($convertOut)) mkdir($convertOut, 0777, true);

        // profile khusus
        $loProfile = storage_path('app/lo_profile');
        if (!is_dir($loProfile)) mkdir($loProfile, 0777, true);

        $sofficeArg = $soffice;
        $tmpDocxArg = str_replace('\\', '/', $tmpDocx);
        $outDirArg  = str_replace('\\', '/', $convertOut);
        $profileUri = 'file:///' . str_replace('\\', '/', $loProfile);
        $profileArg = '-env:UserInstallation=' . $profileUri;

        $process = new \Symfony\Component\Process\Process([
            $sofficeArg,
            '--headless',
            '--nologo',
            '--nofirststartwizard',
            '--nodefault',
            '--norestore',
            $profileArg,
            '--convert-to', 'pdf:writer_pdf_Export',
            '--outdir', $outDirArg,
            $tmpDocxArg,
        ]);

        $process->setEnv([
            'USERPROFILE' => $loProfile,
            'APPDATA'     => $loProfile,
            'TEMP'        => $loProfile,
            'TMP'         => $loProfile,
        ]);

        $process->setTimeout(120);
        $process->run();
        $expectedPdf = $convertOut . DIRECTORY_SEPARATOR . pathinfo($tmpDocx, PATHINFO_FILENAME) . '.pdf';

        $pdfPath = null;

        if (file_exists($expectedPdf)) {
            $pdfPath = $expectedPdf;
        } else {
            $pdfs = glob($convertOut . DIRECTORY_SEPARATOR . '*.pdf');
            if ($pdfs) {
                usort($pdfs, fn($a, $b) => filemtime($b) <=> filemtime($a));
                $pdfPath = $pdfs[0];
            }
        }

        if (!$pdfPath || !file_exists($pdfPath)) {
            $outList = @scandir($convertOut) ?: [];
            $tmpList = @scandir($tmpDir) ?: [];

            @unlink($tmpDocx);

            throw new \Exception(
                "PDF tidak ditemukan setelah convert.\n" .
                "ExitCode: " . $process->getExitCode() . "\n" .
                "ErrorOutput: " . $process->getErrorOutput() . "\n" .
                "Output: " . $process->getOutput() . "\n" .
                "Isi convertOut: " . implode(', ', $outList) . "\n" .
                "Isi tmpDir: " . implode(', ', $tmpList) . "\n"
            );
        }

        $finalDir = storage_path('app/public/tanda_terima');
        if (!is_dir($finalDir)) mkdir($finalDir, 0777, true);

        $safeNo = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($meta['no'] ?? ''));
            $safeId = (int) $id;

            $finalName = $type === 'paten'
                ? "Tanda_Terima_Paten_{$safeNo}_{$safeId}.pdf"
                : "Tanda_Terima_Hak_Cipta_{$safeNo}_{$safeId}.pdf";
        $finalPath = $finalDir . DIRECTORY_SEPARATOR . $finalName;

        if (!@rename($pdfPath, $finalPath)) {
            if (!@copy($pdfPath, $finalPath)) {
                @unlink($tmpDocx);
                throw new \Exception("Gagal memindahkan PDF ke: {$finalPath}");
            }
            @unlink($pdfPath);
        }

        @unlink($tmpDocx);

        return "tanda_terima/" . $finalName;
    }

        public function exportPatenExcel(Request $request)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return redirect()->route('admin.login.form');
            }

            $file = 'data_paten_' . now()->format('Ymd_His') . '.xlsx';
            return Excel::download(new PatenInventorExport(), $file);
        }

        public function exportPatenPdf(Request $request)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return redirect()->route('admin.login.form');
            }

            $items = PatenVerif::orderByDesc('id')->get()->map(function ($p) {

                $raw = $p->inventors ?? null;

                if (is_string($raw) && trim($raw) !== '') {
                    $inventors = json_decode($raw, true);
                    $inventors = is_array($inventors) ? $inventors : [];
                } elseif (is_array($raw)) {
                    $inventors = $raw;
                } else {
                    $inventors = [];
                }

                if (count($inventors) === 0) {
                    $inventors = [[
                        'nama' => $p->nama_pencipta ?? '-',
                        'status' => '-',
                        'nip_nim' => $p->nip_nim ?? '-',
                        'fakultas' => $p->fakultas ?? '-',
                        'no_hp' => $p->no_hp ?? '-',
                        'email' => $p->email ?? '-',
                    ]];
                }

                $inventors = collect($inventors)->map(function ($i) {
                    return [
                        'nama' => $i['nama'] ?? '-',
                        'status' => $i['status'] ?? '-',
                        'nip_nim' => $i['nip_nim'] ?? '-',
                        'fakultas' => $i['fakultas'] ?? '-',
                        'no_hp' => $i['no_hp'] ?? '-',
                        'email' => $i['email'] ?? '-',
                    ];
                })->values()->all();

                return (object)[
                    'no_pendaftaran' => $p->no_pendaftaran ?? '-',
                    'judul_paten' => $p->judul_paten ?? '-',
                    'jenis_paten' => $p->jenis_paten ?? '-',
                    'inventors' => $inventors,
                ];
            });

            $pdf = Pdf::loadView('export.pateninventorpdf', [
                'items' => $items
            ])->setPaper('a4', 'landscape');

            $file = 'data_paten_' . now()->format('Ymd_His') . '.pdf';
            return $pdf->download($file);
        }

        public function exportPatenCsv(Request $request)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return redirect()->route('admin.login.form');
            }

            $file = 'data_paten_' . now()->format('Ymd_His') . '.csv';

            return Excel::download(
                new PatenInventorExport(),
                $file,
                ExcelExcel::CSV
            );
        }

        public function exportCiptaExcel(Request $request)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return redirect()->route('admin.login.form');
            }

            $file = 'data_hak_cipta_' . now()->format('Ymd_His') . '.xlsx';
            return Excel::download(new \App\Http\Controllers\HakCiptaInventorExport(), $file);
        }

        public function exportCiptaCsv(Request $request)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return redirect()->route('admin.login.form');
            }

            $file = 'data_hak_cipta_' . now()->format('Ymd_His') . '.csv';
            return Excel::download(new \App\Http\Controllers\HakCiptaInventorExport(), $file, ExcelExcel::CSV);
        }


        public function exportCiptaPdf(Request $request)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return redirect()->route('admin.login.form');
            }

            $items = DB::table('hak_cipta_verifs')
                ->orderByDesc('id')
                ->get()
                ->flatMap(function ($c) {

                    $raw = $c->inventors ?? null;

                    if (is_string($raw) && trim($raw) !== '') {
                        $inventors = json_decode($raw, true);
                        $inventors = is_array($inventors) ? $inventors : [];
                    } elseif (is_array($raw)) {
                        $inventors = $raw;
                    } else {
                        $inventors = [];
                    }

                    if (count($inventors) === 0) {
                        $inventors = [[
                            'urut'     => 1,
                            'nama'     => $c->nama_pencipta ?? '-',
                            'status'   => $c->status_pencipta ?? ($c->status_inventor ?? ($c->role ?? '-')),
                            'nip_nim'  => $c->nip_nim ?? '-',
                            'fakultas' => $c->fakultas ?? '-',
                            'no_hp'    => $c->nomor_hp ?? ($c->no_hp ?? '-'),
                            'email'    => $c->email ?? '-',
                        ]];
                    }

                    return collect($inventors)->map(function ($i, $idx) use ($c) {
                        return (object)[
                            'no_pendaftaran' => $c->no_pendaftaran ?? '-',
                            'judul'          => $c->judul_cipta ?? '-',
                            'jenis'          => $c->jenis_cipta ?? '-',
                            'inventor_ke'    => $i['urut'] ?? ($idx + 1),
                            'nama'           => $i['nama'] ?? '-',
                            'status'         => $i['status'] ?? '-',
                            'nip_nim'        => $i['nip_nim'] ?? ($i['nip'] ?? ($i['nim'] ?? '-')),
                            'fakultas'       => $i['fakultas'] ?? '-',
                            'no_hp'          => $i['no_hp'] ?? ($i['nomor_hp'] ?? ($i['hp'] ?? '-')),
                            'email'          => $i['email'] ?? '-',
                        ];
                    });
                })
                ->values();

            $pdf = Pdf::loadView('export.ciptainventorpdf', [
                'items' => $items
            ])->setPaper('a4', 'landscape');

            $file = 'data_hak_cipta_' . now()->format('Ymd_His') . '.pdf';
            return $pdf->download($file);
        }

        public function detailPaten(Request $request, int $id)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return redirect()->route('admin.login.form');
            }

            $this->markAsProsesWhenViewed('paten', $id);

            // notifCount
            $notifCount = DB::table('revisions as r')
            ->whereIn('r.type', ['paten', 'cipta'])
            ->where('r.from_role', 'pemohon')
            ->where('r.state', 'submitted')
            ->whereNotNull('r.pemohon_file_path')
            ->where('r.is_read_admin', 0)
            ->distinct()
            ->count(DB::raw("CONCAT(r.type,'#',r.ref_id,'#',r.doc_key)"));

            $row = DB::table('paten_verifs as p')
                ->leftJoin('status_verifikasi as sv', function ($join) {
                    $join->on('sv.ref_id', '=', 'p.id')
                        ->where('sv.ref_type', '=', 'paten');
                })
                ->select([
                    'p.*',
                    DB::raw("COALESCE(sv.status,'terkirim') as status"),
                ])
                ->where('p.id', $id)
                ->first();

            if (!$row) abort(404);

            // inventors array
            $invArr = [];
            if (!empty($row->inventors)) {
                $decoded = json_decode($row->inventors, true);
                $invArr = is_array($decoded) ? $decoded : [];
            }
            $row->inventors_arr = collect($invArr)->map(function ($i) {
                return [
                    'nama'     => $i['nama'] ?? '-',
                    'status'   => $i['status'] ?? '-',
                    'email'    => $i['email'] ?? '-',
                    'no_hp'    => $i['no_hp'] ?? ($i['no hp'] ?? ($i['hp'] ?? '-')),
                    'nip_nim'  => $i['nip_nim'] ?? ($i['nipnim'] ?? ($i['nip'] ?? '-')),
                    'fakultas' => $i['fakultas'] ?? '-',
                ];
            })->values()->all();

            // attach docs
            $docKeys = [
                'skema_tkt',
                'draft_paten',
                'form_permohonan',
                'surat_kepemilikan',
                'surat_pengalihan',
                'scan_ktp',
                'tanda_terima',
                'gambar_prototipe',
                'deskripsi_singkat_prototipe',
            ];

            $rowCol = collect([$row]);
            $rowCol = $this->attachDocsToRows($rowCol, 'paten', $docKeys);
            $row = $rowCol->first();

            // revisi masuk dari pemohon (tabel revisions)
            $incomingByDoc = DB::table('revisions')
                ->where('type', 'paten')
                ->where('ref_id', $id)
                ->whereIn('state', ['requested','submitted'])
                ->orderByDesc('id')
                ->get()
                ->map(function($r) use ($id) {

            if (($r->from_role ?? null) === 'pemohon'
                && ($r->state ?? null) === 'submitted'
                && empty(trim((string)($r->note ?? '')))) {

                $adminNote = DB::table('revisions')
                    ->where('type', $r->type)
                    ->where('ref_id', $r->ref_id)
                    ->where('doc_key', $r->doc_key)
                    ->where('from_role', 'admin')
                    ->whereIn('state', ['requested','closed'])
                    ->where('id', '<', $r->id)
                    ->orderByDesc('id')
                    ->value('note');

                $r->note = $adminNote ?: null;
            }

            if (($r->from_role ?? null) === 'pemohon' && ($r->state ?? null) === 'submitted') {
                $r->pemohon_file_name_display =
                    $r->pemohon_file_name ?: ($r->pemohon_file_path ? basename($r->pemohon_file_path) : null);
            } else {
                $r->pemohon_file_name_display = null;
            }

            return $r;
        })
                ->groupBy('doc_key');


        return view('admin.patendetail', [
            'name' => $request->session()->get('admin_name', 'Admin'),
            'row' => $row,
            'incomingByDoc' => $incomingByDoc,
            'tab' => 'paten',
            'notifCount' => $notifCount ?? 0,
            ]);
        }

        public function detailCipta(Request $request, int $id)
        {
            if (!$request->session()->get('admin_logged_in')) {
                return redirect()->route('admin.login.form');
            }

            $this->markAsProsesWhenViewed('cipta', $id);

            // notifCount
            $notifCount = DB::table('revisions as r')
            ->whereIn('r.type', ['paten', 'cipta'])
            ->where('r.from_role', 'pemohon')
            ->where('r.state', 'submitted')
            ->whereNotNull('r.pemohon_file_path')
            ->where('r.is_read_admin', 0)
            ->distinct()
            ->count(DB::raw("CONCAT(r.type,'#',r.ref_id,'#',r.doc_key)"));
            
            $row = DB::table('hak_cipta_verifs as c')
                ->leftJoin('status_verifikasi as sv', function ($join) {
                    $join->on('sv.ref_id', '=', 'c.id')
                        ->where('sv.ref_type', '=', 'cipta');
                })
                ->select([
                    'c.*',
                    DB::raw("COALESCE(sv.status,'terkirim') as status"),
                ])
                ->where('c.id', $id)
                ->first();

            if (!$row) abort(404);

            $invArr = [];
            if (!empty($row->inventors)) {
                $decoded = json_decode($row->inventors, true);
                $invArr = is_array($decoded) ? $decoded : [];
            }

            $row->inventors_arr = collect($invArr)->map(function ($i) {
                return [
                    'nama'     => $i['nama'] ?? '-',
                    'status'   => $i['status'] ?? '-',
                    'email'    => $i['email'] ?? '-',
                    'no_hp'    => $i['no_hp'] ?? ($i['no hp'] ?? ($i['hp'] ?? '-')),
                    'nip_nim'  => $i['nip_nim'] ?? ($i['nipnim'] ?? ($i['nip'] ?? '-')),
                    'fakultas' => $i['fakultas'] ?? '-',
                ];
            })->values()->all();

            // attach docs
            $docKeys = [
                'surat_permohonan',
                'surat_pernyataan',
                'surat_pengalihan',
                'tanda_terima',
                'scan_ktp',
                'hasil_ciptaan',
            ];

            $rowCol = collect([$row]);
            $rowCol = $this->attachDocsToRows($rowCol, 'cipta', $docKeys);
            $row = $rowCol->first();

            $incomingByDoc = DB::table('revisions')
                ->where('type', 'cipta')
                ->where('ref_id', $id)
                ->whereIn('state', ['requested','submitted'])
                ->orderByDesc('id')
                ->get()
                ->map(function($r) use ($id) {

            if (($r->from_role ?? null) === 'pemohon'
                && ($r->state ?? null) === 'submitted'
                && empty(trim((string)($r->note ?? '')))) {

                $adminNote = DB::table('revisions')
                    ->where('type', $r->type)
                    ->where('ref_id', $r->ref_id)
                    ->where('doc_key', $r->doc_key)
                    ->where('from_role', 'admin')
                    ->whereIn('state', ['requested','closed'])
                    ->where('id', '<', $r->id)
                    ->orderByDesc('id')
                    ->value('note');

                $r->note = $adminNote ?: null;
            }

            if (($r->from_role ?? null) === 'pemohon' && ($r->state ?? null) === 'submitted') {
                $r->pemohon_file_name_display =
                    $r->pemohon_file_name ?: ($r->pemohon_file_path ? basename($r->pemohon_file_path) : null);
            } else {
                $r->pemohon_file_name_display = null;
            }

            return $r;
        })
                ->groupBy('doc_key');


            return view('admin.ciptadetail', [
                'name' => $request->session()->get('admin_name', 'Admin'),
                'row' => $row,
                'incomingByDoc' =>  $incomingByDoc,
                'tab' => 'cipta',
                'notifCount' => $notifCount ?? 0,
            ]);
        }

        // =========================
        // APPROVE VIA AJAX (DETAIL)
        // =========================
        public function approveAjax(Request $request, $type, $id)
        {
            DB::beginTransaction();

            try {
                // 1) Ambil data pengajuan
                if ($type === 'paten') {
                    $row = PatenVerif::findOrFail($id);
                } else {
                    $row = DB::table('hak_cipta_verifs')->where('id', $id)->first();
                    if (!$row) abort(404);
                }

                // 2) Ambil status_verifikasi terbaru
                $sv = DB::table('status_verifikasi')
                    ->where('ref_type', $type)
                    ->where('ref_id', $row->id)
                    ->orderByDesc('id')
                    ->first();

                if (!$sv) {
                    throw new \Exception('Status verifikasi tidak ditemukan');
                }

                // 3) Update status -> approve
                DB::table('status_verifikasi')
                    ->where('id', $sv->id)
                    ->update([
                        'status'     => 'approve',
                        'approve_at' => $sv->approve_at ?: now(),
                        'updated_at' => now(),
                    ]);

                // 4) GENERATE TANDA TERIMA PDF kalau belum ada
                $needGenerate = empty($sv->tanda_terima_pdf);

                if (!$needGenerate && !Storage::disk('public')->exists($sv->tanda_terima_pdf)) {
                    $needGenerate = true;
                }

                if ($needGenerate) {
                    $path = $this->generateTandaTerimaPdf($type, $id);

                    DB::table('status_verifikasi')
                        ->where('id', $sv->id)
                        ->update([
                            'tanda_terima_pdf' => $path,
                            'updated_at'       => now(),
                        ]);

                    $sv->tanda_terima_pdf = $path;
                }

                // 5) WA message 
                $kategori = strtoupper($type === 'paten' ? 'PATEN' : 'HAK CIPTA');
                $noPendaftaran = $row->no_pendaftaran ?? '-';

                $judul = $type === 'paten'
                    ? ($row->judul_paten ?? '-')
                    : ($row->judul_cipta ?? ($row->judul ?? '-'));

                $loginUrl = url('/pemohon/login');

                $text =
                    "Yth. Bapak/Ibu\n\n"
                    ."Dengan hormat,\n\n"
                    ."Pengajuan {$kategori} telah DISETUJUI (APPROVE).\n"
                    ."Silakan mengunduh Tanda Terima melalui sistem dan melanjutkan proses sesuai ketentuan yang berlaku.\n"
                    ."Saat ini status pengajuan: APPROVE.\n\n"
                    ."Rincian Pengajuan:\n"
                    ."• Kategori : {$kategori}\n"
                    ."• No. Pendaftaran : {$noPendaftaran}\n"
                    ."• Judul : {$judul}\n\n"
                    ."Untuk memantau perkembangan status pengajuan, silakan mengakses halaman berikut secara berkala:\n"
                    ."{$loginUrl}\n\n"
                    ."Hormat kami,\n"
                    ."Admin KIHub";

                $phones  = $this->getPemohonPhones($row);
                $waLinks = $this->makeWaLinks($phones, $text);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Pengajuan berhasil di-approve',

                    'status'  => 'approve',

                    // MULTI WA
                    'wa_links' => $waLinks,

                    'tanda_terima_pdf' => $sv->tanda_terima_pdf,
                    'tanda_terima_url' => !empty($sv->tanda_terima_pdf) ? asset('storage/'.$sv->tanda_terima_pdf) : null,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
        }


        private function markAsProsesWhenViewed(string $type, int $id): void
        {
            if (!in_array($type, ['paten', 'cipta'], true)) {
                return;
            }

            $sv = DB::table('status_verifikasi')
                ->where('ref_type', $type)
                ->where('ref_id', $id)
                ->orderByDesc('id')
                ->first();

            if (!$sv) {
                DB::table('status_verifikasi')->insert([
                    'ref_type'    => $type,
                    'ref_id'      => $id,
                    'status'      => 'proses',
                    'proses_at'   => now(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
                return;
            }

            $current = strtolower((string)($sv->status ?? 'terkirim'));

            if ($current === 'terkirim') {
                DB::table('status_verifikasi')
                    ->where('id', $sv->id)
                    ->update([
                        'status'     => 'proses',
                        'proses_at'  => $sv->proses_at ?: now(),
                        'updated_at' => now(),
                    ]);
            }
        }

        private function getPemohonPhones($row): array
    {
        $phones = [];

        $pushPhones = function ($val) use (&$phones) {
            if (!$val) return;
            if (is_array($val)) {
                foreach ($val as $v) $phones[] = $v;
                return;
            }
            $val = (string)$val;

            $parts = preg_split('/[,\n;\r]+/', $val, -1, PREG_SPLIT_NO_EMPTY);
            if (!$parts) $parts = [$val];

            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') $phones[] = $p;
            }
        };

        foreach (['nomor_hp', 'no_hp', 'no hp', 'hp', 'phone'] as $col) {
            if (isset($row->{$col}) && !empty($row->{$col})) {
                $pushPhones($row->{$col});
            }
        }

        $invArr = $row->inventors_arr ?? null;
        if (is_array($invArr)) {
            foreach ($invArr as $inv) {
                $hp = $inv['no_hp'] ?? $inv['nomor_hp'] ?? $inv['no hp'] ?? $inv['nomor hp'] ?? $inv['hp'] ?? null;
                $pushPhones($hp);
            }
        }

        $invRaw = $row->inventors ?? null;
        if (!empty($invRaw)) {
            $arr = is_string($invRaw) ? json_decode($invRaw, true) : $invRaw;
            if (is_array($arr)) {
                foreach ($arr as $inv) {
                    if (!is_array($inv)) continue;
                    $hp = $inv['no_hp'] ?? $inv['nomor_hp'] ?? $inv['no hp'] ?? $inv['nomor hp'] ?? $inv['hp'] ?? null;
                    $pushPhones($hp);
                }
            }
        }

        $invArr = [];
        if (!empty($row->inventors)) {
            $decoded = is_string($row->inventors) ? json_decode($row->inventors, true) : $row->inventors;
            $invArr = is_array($decoded) ? $decoded : [];
        }

        $row->inventors_arr = collect($invArr)->map(function ($i) {
            return [
                'no_hp' => $i['no_hp'] ?? ($i['nomor_hp'] ?? ($i['hp'] ?? ($i['no hp'] ?? null))),
            ];
        })->values()->all();

        $normalized = [];
        foreach ($phones as $p) {
            $n = $this->normalizeWaNumber($p);
            if ($n) $normalized[] = $n;
        }

        return array_values(array_unique($normalized));
    }

        private function makeWaLinks(array $phones, string $message): array
        {
            $links = [];
            $text = rawurlencode($message);

            foreach ($phones as $phone) {

                if (!$phone) continue;

                $links[] = "https://wa.me/{$phone}?text={$text}";
            }

            return $links;
        }

    public function downloadRevisi(int $id)
    {
        $rev = DB::table('revisions')->where('id', $id)->first();
        if (!$rev) abort(404, 'Revisi tidak ditemukan.');

        $path = $rev->pemohon_file_path;
        if (!$path) abort(404, 'File revisi belum ada.');

        $path = ltrim((string)$path, '/');
        $path = preg_replace('#^storage/#', '', $path);

        if (!Storage::disk('public')->exists($path)) {
            abort(404, "File tidak ada di storage.");
        }

        $downloadName = $rev->pemohon_file_name ?: basename($path);

        $downloadName = preg_replace('/[^a-zA-Z0-9.\-_ ()]/', '_', $downloadName);

        return response()->download(
            Storage::disk('public')->path($path),
            $downloadName
        );
    }
        public function adminDownloadDocPaten(int $id, string $doc_key)
        {
            if (!session('admin_logged_in')) {
                return redirect()->route('admin.login.form');
            }

            $row = PatenVerif::findOrFail($id);

            $allowedDocs = [
                'skema_tkt',
                'draft_paten',
                'form_permohonan',
                'surat_kepemilikan',
                'surat_pengalihan',
                'scan_ktp',
                'tanda_terima',
                'gambar_prototipe',
            ];

            if (!in_array($doc_key, $allowedDocs, true)) {
                abort(404, 'Jenis dokumen tidak valid.');
            }

            if ($doc_key === 'skema_tkt') {
                $path = $row->skema_tkt_template_path ?? null;
            } else {
                $path = $row->{$doc_key} ?? null;
            }

            if (!$path) {
                abort(404, 'File tidak ditemukan di database.');
            }

            $path = ltrim((string) $path, '/');
            $path = preg_replace('#^storage/#', '', $path);

            if (!Storage::disk('public')->exists($path)) {
                abort(404, 'File tidak ditemukan di storage.');
            }

            return response()->download(
                Storage::disk('public')->path($path),
                basename($path)
            );
        }

        public function adminDownloadDocCipta(int $id, string $doc_key)
        {
            if (!session('admin_logged_in')) {
                return redirect()->route('admin.login.form');
            }

            $row = DB::table('hak_cipta_verifs')->where('id', $id)->first();
            if (!$row) {
                abort(404);
            }

            $allowedDocs = [
                'surat_permohonan',
                'surat_pernyataan',
                'surat_pengalihan',
                'tanda_terima',
                'scan_ktp',
                'hasil_ciptaan',
            ];

            if (!in_array($doc_key, $allowedDocs, true)) {
                abort(404, 'Jenis dokumen tidak valid.');
            }

            $path = $row->{$doc_key} ?? null;

            if (!$path) {
                abort(404, 'File tidak ditemukan di database.');
            }

            $path = ltrim((string) $path, '/');
            $path = preg_replace('#^storage/#', '', $path);

            if (!Storage::disk('public')->exists($path)) {
                abort(404, 'File tidak ditemukan di storage.');
            }

            return response()->download(
                Storage::disk('public')->path($path),
                basename($path)
            );
        }
    }
    