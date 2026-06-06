<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DuplicateFormPendaftaranCiptaanController extends Controller
{
    private function val($v): string
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? '' : $s;
    }

public function index(Request $request)
{
    
    $ref = $request->filled('ref')
    ? $request->query('ref')
    : session('hakcipta.edit_ref_id');
    if ($ref) {
        session(['hakcipta.edit_ref_id' => $ref]);
    }

    $dbData = DB::table('hak_cipta_verifs')->where('id', $ref)->first();
    if (!$dbData) {
        return "Data tidak ditemukan.";
    }

    $sessionSpecific = session("hakcipta.form.$ref", []);
    $formSession = session('hakcipta.form', []);
    $source = array_merge($formSession, $sessionSpecific);
    $invDb = json_decode($dbData->inventors, true) ?? [];

    $data = [
        'jumlah_inventor' => $source['jumlah_inventor'] ?? (count($invDb) ?: 1),
        'judul_ciptaan'   => $source['judul_ciptaan'] ?? ($dbData->judul_cipta ?? ''),
        'link_ciptaan'    => $source['link_ciptaan'] ?? ($dbData->link_ciptaan ?? ''),
        'berupa'            => $source['berupa'] ?? '',
        'tempat'            => $source['tempat'] ?? '',
        'uraian'            => $source['uraian'] ?? '',
        'tanggal_pengisian' => $source['tanggal_pengisian'] ?? now()->format('Y-m-d'),

        'jenis_cipta' => $source['jenis_cipta'] ?? (in_array($dbData->jenis_cipta, ['Buku', 'Program Komputer', 'Karya Rekaman Video']) ? $dbData->jenis_cipta : 'Lainnya'),

        'inventor' => $source['inventor'] ?? [
            'nama' => array_column($invDb, 'nama'),
            'nik' => array_column($invDb, 'nik'),
            'nip_nim' => array_column($invDb, 'nip_nim'),
            'fakultas' => array_column($invDb, 'fakultas'),
            'status' => array_column($invDb, 'status'),
            'no_hp' => array_column($invDb, 'no_hp'),
            'email' => array_column($invDb, 'email'),
            'alamat' => array_column($invDb, 'alamat'),
            'kode_pos' => array_column($invDb, 'kode_pos'),
            'tlp_rumah' => array_column($invDb, 'tlp_rumah'),
        ],
    ];
    
    return view('isiform.hakcipta.duplicateformpendaftaranciptaan', [
        'data' => $data,
        'ref'  => $ref
    ]);
}

    public function store(Request $request)
    {
        $action = $request->input('action', 'download');
        $refId = $request->input('ref') ?: session('hakcipta.edit_ref_id');
        $sessionKey = "hakcipta.form.$refId";

        $validated = $request->validate([
        'jumlah_inventor'        => ['required', 'integer', 'min:1', 'max:20'],
        'jenis_cipta'            => ['required', 'in:Buku,Program Komputer,Karya Rekaman Video,Lainnya'],
        'jenis_cipta_lainnya'    => ['nullable', 'string', 'max:255'],
        'judul_ciptaan'          => ['required', 'string', 'max:255'],
        'link_ciptaan'           => ['required', 'url'],
        'tanggal_pengisian'      => ['required', 'date'],
        'tempat'                 => ['required', 'string', 'max:100'],
        'uraian'                 => ['required', 'string', 'max:350'],

        'inventor'               => ['required', 'array'],

        'inventor.nama'          => ['required', 'array'],
        'inventor.nama.*'        => ['required', 'string'],

        'inventor.nik'           => ['required', 'array'],
        'inventor.nik.*'         => ['required', 'string'],

        'inventor.nip_nim'       => ['required', 'array'],
        'inventor.nip_nim.*'     => ['required', 'string'],

        'inventor.fakultas'      => ['required', 'array'],
        'inventor.fakultas.*'    => ['required', 'string'],

        'inventor.status'        => ['required', 'array'],
        'inventor.status.*'      => ['required', 'string'],

        'inventor.no_hp'         => ['required', 'array'],
        'inventor.no_hp.*'       => ['required', 'string'],

        'inventor.email'         => ['required', 'array'],
        'inventor.email.*'       => ['required', 'email'],

        'inventor.alamat'        => ['required', 'array'],
        'inventor.alamat.*'      => ['required', 'string'],

        'inventor.kode_pos'      => ['required', 'array'],
        'inventor.kode_pos.*'    => ['required', 'string'],

        'download_format'        => ['nullable', 'in:pdf,docx'],
    ]);

        $jumlah = (int) $request->jumlah_inventor;
        $actual = count($request->inventor['nama'] ?? []);

        if ($actual !== $jumlah) {
            return back()->withErrors([
                'inventor' => 'Jumlah inventor tidak sesuai.'
            ])->withInput();
        }

        $newPayload = [
        'jumlah_inventor'     => (int) $request->jumlah_inventor,
        'jenis_cipta'         => $request->jenis_cipta,
        'jenis_cipta_lainnya' => $request->jenis_cipta_lainnya ?? '',
        'judul_ciptaan'       => $request->judul_ciptaan ?? '',
        'link_ciptaan'        => $request->link_ciptaan ?? '',
        'berupa'              => $request->berupa ?? '',
        'tempat'              => $request->tempat ?? '',
        'uraian'              => $request->uraian ?? '',
        'tanggal_pengisian'   => $request->tanggal_pengisian ?? now()->format('Y-m-d'),
        'inventor'            => $request->input('inventor', []),
    ];

        $existingSpecific = session("hakcipta.form.$refId", []);
        $existingGlobal   = session('hakcipta.form', []);

        session()->put("hakcipta.form.$refId", array_merge(
            $existingSpecific,
            [
                'link_ciptaan'       => $request->link_ciptaan ?? ($existingSpecific['link_ciptaan'] ?? ''),
                'berupa'             => $request->berupa ?? ($existingSpecific['berupa'] ?? ''),
                'tempat'             => $request->tempat ?? ($existingSpecific['tempat'] ?? ''),
                'uraian'             => $request->uraian ?? ($existingSpecific['uraian'] ?? ''),
                'tanggal_pengisian'  => $request->tanggal_pengisian ?? ($existingSpecific['tanggal_pengisian'] ?? now()->format('Y-m-d')),
            ]
        ));

        session()->put('hakcipta.form', array_merge(
            $existingGlobal,
            [
                'link_ciptaan'       => $request->link_ciptaan ?? ($existingGlobal['link_ciptaan'] ?? ''),
                'berupa'             => $request->berupa ?? ($existingGlobal['berupa'] ?? ''),
                'tempat'             => $request->tempat ?? ($existingGlobal['tempat'] ?? ''),
                'uraian'             => $request->uraian ?? ($existingGlobal['uraian'] ?? ''),
                'tanggal_pengisian'  => $request->tanggal_pengisian ?? ($existingGlobal['tanggal_pengisian'] ?? now()->format('Y-m-d')),
            ]
        ));

    session()->put('hakcipta.edit_ref_id', $refId);

        if ($action === 'save' && $refId) {
            $formattedInventors = [];
            for ($i = 0; $i < (int)$request->jumlah_inventor; $i++) {
                $formattedInventors[] = [
                    'nama'      => $request->inventor['nama'][$i] ?? '',
                    'nik'       => $request->inventor['nik'][$i] ?? '',
                    'nip_nim'   => $request->inventor['nip_nim'][$i] ?? '',
                    'fakultas'  => $request->inventor['fakultas'][$i] ?? '',
                    'status'    => $request->inventor['status'][$i] ?? '',
                    'no_hp'     => $request->inventor['no_hp'][$i] ?? '',
                    'email'     => $request->inventor['email'][$i] ?? '',
                    'alamat'    => $request->inventor['alamat'][$i] ?? '',
                    'kode_pos'  => $request->inventor['kode_pos'][$i] ?? '',
                    'tlp_rumah' => $request->inventor['tlp_rumah'][$i] ?? '',
                ];
            }

            DB::table('hak_cipta_verifs')->where('id', $refId)->update([
                'judul_cipta'   => $request->judul_ciptaan,
                'jenis_cipta'   => ($request->jenis_cipta === 'Lainnya') ? $request->jenis_cipta_lainnya : $request->jenis_cipta,
                'link_ciptaan'  => $request->link_ciptaan,
                'inventors'     => json_encode($formattedInventors),
                'updated_at'    => now(),
            ]);

            return response()->json(['ok' => true]);
        }

        return $this->generateDocument($request);
    }

    private function generateDocument($request)
    {
        $templateObjectPath = 'Permohonan Pendaftaran Ciptaan 2021.docx';

        if (!Storage::disk('s3')->exists($templateObjectPath)) {
            abort(500, 'Template DOCX tidak ditemukan di bucket: ' . $templateObjectPath);
        }

        $templatePath = tempnam(sys_get_temp_dir(), 'template_cipta_') . '.docx';

        file_put_contents(
            $templatePath,
            Storage::disk('s3')->get($templateObjectPath)
        );

        $tp = new TemplateProcessor($templatePath);

        $tp->setValue('judul_ciptaan', $this->val($request->judul_ciptaan));
        $tp->setValue('link_ciptaan', $this->val($request->link_ciptaan));
        $tp->setValue('uraian', $this->val($request->uraian));
        $tgl = Carbon::parse($request->tanggal_pertama)
    ->locale('id')
    ->translatedFormat('d F Y');

$tp->setValue('tempatpertama', $this->val($request->tempatpertama));
$tp->setValue('tanggal_pertama', $tgl);

        $names = array_map(fn($n) => $this->val($n), $request->inventor['nama'] ?? []);
        $namaGabung = implode(', ', array_filter($names));

        $tp->setValue('nama_lengkap', $namaGabung);
        $tp->setValue('alamat', $this->val($request->inventor['alamat'][0] ?? ''));
        $tp->setValue('tlp_rumah', $this->val($request->inventor['tlp_rumah'][0] ?? ''));
        $tp->setValue('no_hp', $this->val($request->inventor['no_hp'][0] ?? ''));
        $tp->setValue('email', $this->val($request->inventor['email'][0] ?? ''));

        $out = tempnam(sys_get_temp_dir(), 'cipta_') . '.docx';
        $tp->saveAs($out);

        if ($request->download_format === 'docx') {
            return response()->download($out, 'Permohonan Pendaftaran Ciptaan.docx')->deleteFileAfterSend(true);
        }

        // Convert PDF
        $soffice = 'C:\Program Files\LibreOffice\program\soffice.exe';
        if (!file_exists($soffice)) $soffice = 'C:\Program Files (x86)\LibreOffice\program\soffice.exe';
        
        $outDir = dirname($out);
        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $out);
        $cmd = '"' . $soffice . '" --headless --convert-to pdf --outdir "' . $outDir . '" "' . $out . '"';
        exec($cmd);

        return response()->download($pdfPath, 'Permohonan Pendaftaran Ciptaan.pdf')->deleteFileAfterSend(true);
    }
}