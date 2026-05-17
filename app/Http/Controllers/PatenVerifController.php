<?php

namespace App\Http\Controllers;

use App\Models\PatenVerif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class PatenVerifController extends Controller
{

    public function start(Request $request)
    {
        $enumFakultas   = $this->getEnumValues('paten_verifs', 'fakultas');
        $enumSumberDana = $this->getEnumValues('paten_verifs', 'sumber_dana');

        $jumlah = (int) $request->input('jumlah_inventor', 1);
        $jumlah = max(1, min(20, $jumlah));

        $action = $request->input('action', 'next');

        if ($action === 'prev') {
            return redirect()->route('hakpaten.isiformulir');
        }

        $messages = [
            'inventor.nip_nim.*.regex' => 'NIP/NIM harus terdiri dari 14 atau 18 karakter',
            'inventor.no_hp.*.regex'   => 'Nomor HP harus diawali 08 dan hanya angka',
            'inventor.email.*.email'   => 'Format email tidak valid',
        ];

        $validated = $request->validate([
            'jumlah_inventor'  => ['required', 'integer', 'min:1', 'max:20'],
            'jenis_paten'      => ['required', 'in:Paten,Paten Sederhana'],
            'judul_paten'      => ['required', 'string', 'max:255'],

            'inventor'               => ['required', 'array'],
            'inventor.nama'          => ['required', 'array', "size:$jumlah"],
            'inventor.nip_nim'       => ['required', 'array', "size:$jumlah"],
            'inventor.fakultas'      => ['required', 'array', "size:$jumlah"],
            'inventor.no_hp'         => ['required', 'array', "size:$jumlah"],
            'inventor.email'         => ['required', 'array', "size:$jumlah"],
            'inventor.status'        => ['required', 'array', "size:$jumlah"],

            'inventor.nama.*'        => ['required', 'string', 'max:255'],
            'inventor.nip_nim.*'     => ['required', 'regex:/^.{14}$|^.{18}$/'],
            'inventor.fakultas.*'    => empty($enumFakultas) ? ['required','string'] : ['required', Rule::in($enumFakultas)],
            'inventor.no_hp.*'  => ['required', 'regex:/^08[0-9]{8,13}$/'],
            'inventor.email.*'  => ['required', 'email', 'max:255'],
            'inventor.status.*'      => ['required', 'in:Dosen,Mahasiswa'],
            'inventor.nidn'   => ['required', 'array', "size:$jumlah"],
            'inventor.nidn.*' => ['nullable', 'string', 'max:255'],


            'prototipe'        => ['required', 'in:Sudah,Belum'],
            'nilai_perolehan'  => ['required', 'string', 'max:255'],
            'sumber_dana'      => empty($enumSumberDana) ? ['required','string'] : ['required', Rule::in($enumSumberDana)],
            'skema_penelitian' => ['required', 'string', 'max:255'],
        ], $messages);

        session([
            'hakpaten.verif' => [
                'jumlah_inventor'  => $validated['jumlah_inventor'],
                'jenis_paten'      => $validated['jenis_paten'],
                'judul_paten'      => $validated['judul_paten'],
                'inventor'         => $validated['inventor'],
                'prototipe'        => $validated['prototipe'],
                'nilai_perolehan'  => $validated['nilai_perolehan'],
                'sumber_dana'      => $validated['sumber_dana'],
                'skema_penelitian' => $validated['skema_penelitian'],
            ]
        ]);

        $action = $request->input('action', 'next');

        if (($validated['inventor']['status'][0] ?? null) !== 'Dosen') {
            return back()->withErrors([
                'inventor.status.0' => 'Inventor pertama wajib berstatus Dosen'
            ])->withInput();
        }

        if (empty($validated['inventor']['nidn'][0])) {
            return back()->withErrors([
                'inventor.nidn.0' => 'NIDN wajib diisi untuk inventor pertama'
            ])->withInput();
        }

        for ($i = 1; $i < $jumlah; $i++) {
            if (
                ($validated['inventor']['status'][$i] ?? null) === 'Dosen'
                && empty($validated['inventor']['nidn'][$i])
            ) {
                return back()->withErrors([
                    "inventor.nidn.$i" => "NIDN wajib diisi untuk inventor ke-" . ($i + 1)
                ])->withInput();
            }

            if (
                ($validated['inventor']['status'][$i] ?? null) === 'Mahasiswa'
                && !empty($validated['inventor']['nidn'][$i])
            ) {
                return back()->withErrors([
                    "inventor.nidn.$i" => "Mahasiswa tidak boleh memiliki NIDN"
                ])->withInput();
            }
        }

        $validated['inventor']['status'][0] = 'Dosen';

        $inventors = [];
        for ($i = 0; $i < $jumlah; $i++) {
            $inventors[] = [
                'urut'     => $i + 1,
                'nama'     => trim((string) $validated['inventor']['nama'][$i]),
                'nip_nim'  => trim((string) $validated['inventor']['nip_nim'][$i]),
                'fakultas' => trim((string) $validated['inventor']['fakultas'][$i]),
                'no_hp'    => trim((string) $validated['inventor']['no_hp'][$i]),
                'email'    => trim((string) $validated['inventor']['email'][$i]),
                'status'   => trim((string) $validated['inventor']['status'][$i]),
                'nidn' => trim((string) ($validated['inventor']['nidn'][$i] ?? '')),

            ];
        }

        $fakultas = $inventors[0]['fakultas'] ?? null;
        

        $payload = [
            'no_pendaftaran' => $this->generateNoPendaftaranVerif(),
            'jenis_paten'      => $validated['jenis_paten'],
            'judul_paten'      => $validated['judul_paten'],

            'inventors'        => $inventors,

            'nama_pencipta'    => $inventors[0]['nama'] ?? '',
            'nip_nim'          => $inventors[0]['nip_nim'] ?? '',
            'fakultas'         => $fakultas,
            'no_hp'            => $inventors[0]['no_hp'] ?? '',
            'email'            => $inventors[0]['email'] ?? '',

            'prototipe'        => $validated['prototipe'],
            'nilai_perolehan'  => $validated['nilai_perolehan'],
            'sumber_dana'      => $validated['sumber_dana'],
            'skema_penelitian' => $validated['skema_penelitian'],

            'status_verif'     => 'Terkirim',
        ];

        if (!session()->has('verif_id')) {

    foreach ([
        'draft_paten',
        'form_permohonan',
        'surat_kepemilikan',
        'surat_pengalihan',
        'scan_ktp',
        'gambar_prototipe', 
        'deskripsi_singkat_prototipe',
    ] as $field) {
        $payload[$field] = '';
    }

    $verif = PatenVerif::create($payload);
    session(['verif_id' => $verif->id]);

} else {

    $verif = PatenVerif::findOrFail(session('verif_id'));
    $verif->update($payload);

}

        // tentukan next route berdasarkan skema
        if ($verif->skema_penelitian === 'Penelitian Pengembangan (TKT 7 - 9)') {
            $nextRoute = route('patenverif.skema.form', $verif->id);
        } else {
            $nextRoute = route('patenverif.all', $verif->id);
        }

        // kalau AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $verif->id,
                'redirect' => $nextRoute,
            ]);
        }
        return redirect($nextRoute);

    }

    // =========================
    // API FLOW 
    // =========================
    public function store(Request $request)
    {
        $enumFakultas   = $this->getEnumValues('paten_verifs', 'fakultas');
        $enumSumberDana = $this->getEnumValues('paten_verifs', 'sumber_dana');

        $jumlah = (int) $request->input('jumlah_inventor', 1);
        $jumlah = max(1, min(20, $jumlah));
        $messages = [
            'inventor.nip_nim.*.regex' => 'NIP/NIM harus terdiri dari 14 atau 18 karakter',
            'inventor.no_hp.*.regex'   => 'Nomor HP harus diawali 08 dan hanya angka',
            'inventor.email.*.email'   => 'Format email tidak valid',
        ];

        $validated = $request->validate([
            'jumlah_inventor'  => ['required', 'integer', 'min:1', 'max:20'],
            'jenis_paten'      => ['required', 'in:Paten,Paten Sederhana'],
            'judul_paten'      => ['required', 'string', 'max:255'],

            'inventor'               => ['required', 'array'],
            'inventor.nama'          => ['required', 'array', "size:$jumlah"],
            'inventor.nip_nim'       => ['required', 'array', "size:$jumlah"],
            'inventor.fakultas'      => ['required', 'array', "size:$jumlah"],
            'inventor.no_hp'         => ['required', 'array', "size:$jumlah"],
            'inventor.email'         => ['required', 'array', "size:$jumlah"],
            'inventor.status'        => ['required', 'array', "size:$jumlah"],

            'inventor.nama.*'        => ['required', 'string', 'max:255'],
            'inventor.nip_nim.*'     => ['required', 'regex:/^.{14}$|^.{18}$/'],
            'inventor.fakultas.*'    => empty($enumFakultas) ? ['required','string'] : ['required', Rule::in($enumFakultas)],
            'inventor.no_hp.*' => ['required', 'regex:/^08[0-9]{8,13}$/'],
            'inventor.email.*' => ['required', 'email', 'max:255'],
            'inventor.status.*'      => ['required', 'in:Dosen,Mahasiswa'],
            'inventor.nidn'   => ['required', 'array', "size:$jumlah"],
            'inventor.nidn.*' => ['nullable', 'string', 'max:255'],


            'prototipe'        => ['required', 'in:Sudah,Belum'],
            'nilai_perolehan'  => ['required', 'string', 'max:255'],
            'sumber_dana'      => empty($enumSumberDana) ? ['required','string'] : ['required', Rule::in($enumSumberDana)],
            'skema_penelitian' => ['required', 'string', 'max:255'],
        ], $messages);


        // Inventor pertama wajib DOSEN
        if (($validated['inventor']['status'][0] ?? null) !== 'Dosen') {
            return back()
                ->withErrors(['inventor.status.0' => 'Inventor pertama wajib berstatus Dosen'])
                ->withInput();
        }

        // Inventor pertama wajib NIDN
        if (empty($validated['inventor']['nidn'][0])) {
            return back()
                ->withErrors(['inventor.nidn.0' => 'NIDN wajib diisi untuk inventor pertama'])
                ->withInput();
        }

        // Inventor ke-2 dan seterusnya
        for ($i = 1; $i < $jumlah; $i++) {
            if (
                ($validated['inventor']['status'][$i] ?? null) === 'Dosen'
                && empty($validated['inventor']['nidn'][$i])
            ) {
                return back()
                    ->withErrors([
                        "inventor.nidn.$i" => "NIDN wajib diisi untuk inventor ke-" . ($i + 1)
                    ])
                    ->withInput();
            }
        }

        $validated['inventor']['status'][0] = 'Dosen';


        $inventors = [];
        for ($i = 0; $i < $jumlah; $i++) {

            if (($validated['inventor']['status'][$i] ?? null) === 'Mahasiswa') {
                $validated['inventor']['nidn'][$i] = '';
            }

            $inventors[] = [
                'urut'     => $i + 1,
                'nama'     => trim((string) $validated['inventor']['nama'][$i]),
                'nip_nim'  => trim((string) $validated['inventor']['nip_nim'][$i]),
                'fakultas' => trim((string) $validated['inventor']['fakultas'][$i]),
                'no_hp'    => trim((string) $validated['inventor']['no_hp'][$i]),
                'email'    => trim((string) $validated['inventor']['email'][$i]),
                'status'   => trim((string) $validated['inventor']['status'][$i]),
                'nidn' => trim((string) ($validated['inventor']['nidn'][$i] ?? '')),

            ];
        }

        $payload = [
            'no_pendaftaran'   => $this->generateNoPendaftaranVerif(),
            'jenis_paten'      => $validated['jenis_paten'],
            'judul_paten'      => $validated['judul_paten'],
            'inventors'        => $inventors,

            'nama_pencipta'    => $inventors[0]['nama'] ?? '',
            'nip_nim'          => $inventors[0]['nip_nim'] ?? '',
            'fakultas'         => $inventors[0]['fakultas'] ?? '',
            'no_hp'            => $inventors[0]['no_hp'] ?? '',
            'email'            => $inventors[0]['email'] ?? '',

            'prototipe'        => $validated['prototipe'],
            'nilai_perolehan'  => $validated['nilai_perolehan'],
            'sumber_dana'      => $validated['sumber_dana'],
            'skema_penelitian' => $validated['skema_penelitian'],
            'status_verif'     => 'Terkirim',
        ];

        foreach ([
            'draft_paten',
            'form_permohonan',
            'surat_kepemilikan',
            'surat_pengalihan',
            'scan_ktp',
            'tanda_terima',
            'gambar_prototipe',
            'deskripsi_singkat_prototipe',
        ] as $field) {
            $payload[$field] = $payload[$field] ?? '';
        }

        unset($payload['tanda_terima']);

        $verif = PatenVerif::create($payload);
        if (!session()->has('verif_id')) {
            $verif = PatenVerif::create($payload);
            session(['verif_id' => $verif->id]);
        } else {
            $verif = PatenVerif::findOrFail(session('verif_id'));
            $verif->update($payload);
        }

        return response()->json([
            'message'       => 'Pengajuan verifikasi paten berhasil',
            'verif_id'      => $verif->id,
            'no_pendaftaran'=> $verif->no_pendaftaran,
        ]);
    }

    public function uploadSemua(PatenVerif $verif)
{
    return view('hakpaten.verifikasidokumen.semuaverif', compact('verif'));
}

private function getPrevRouteForUpload(PatenVerif $verif): string
{
    if ($verif->skema_penelitian === 'Penelitian Pengembangan (TKT 7 - 9)') {
        return route('patenverif.skema.form', $verif->id);
    }

    return route('patenverif.datadiri', $verif->id);
}
    public function all($id)
{
    $verif = PatenVerif::findOrFail($id);
    $prevRoute = $this->getPrevRouteForUpload($verif);

    return view('hakpaten.verifikasidokumen.semuaverif', compact('verif', 'prevRoute'));
}


    private function storeUploadedOriginalName(Request $request, string $dir): string
    {
        $file = $request->file('file');

        $original = $file->getClientOriginalName();
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $original);
        return $file->storeAs($dir, $safeName, 'public');
    }

    public function uploadDraft(Request $request, PatenVerif $verif)
{
    $request->validate([
        'file' => ['required', 'file', 'mimes:doc,docx,pdf', 'max:5120'],
    ]);

    $path = $this->storeUploadedOriginalName($request, 'verif/draft');
    $driveService = new \App\Services\GoogleDriveOAuthService('paten');
    $absolutePath = Storage::disk('public')->path($path);
    $driveUrl = $driveService->uploadFile($absolutePath, basename($path));

    $verif->update([
        'draft_paten' => $path,
        'draft_paten_drive_url' => $driveUrl,
    ]);

    return redirect()
->route('patenverif.all', $verif->id)
->with('success', 'Draft Paten berhasil diupload');
}


    public function submitDeskripsi(Request $request, PatenVerif $verif)
    {
        $request->validate([
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ]);

        $verif->update([
            'deskripsi_singkat_prototipe' => $request->filled('deskripsi') ? $request->deskripsi : null,
        ]);

        return redirect()
->route('patenverif.all', $verif->id)
->with('success', 'Deskripsi Paten berhasil diupload');
    }

    private function generateNoPendaftaranVerif(): string
    {
        $year   = now()->format('Y');
        $prefix = 'VP0' . $year;

        $last = DB::table('paten_verifs')
            ->where('no_pendaftaran', 'like', $prefix . '%')
            ->orderByDesc('no_pendaftaran')
            ->value('no_pendaftaran');

        $next = 1;
        if ($last) {
            $next = ((int) substr($last, -5)) + 1;
        }

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function getEnumValues(string $table, string $field): array
    {
        $column = DB::selectOne("SHOW COLUMNS FROM {$table} WHERE Field = '{$field}'");
        if (!$column || !isset($column->Type)) return [];

        if (preg_match("/^enum\((.*)\)$/", $column->Type, $matches)) {
            return str_getcsv($matches[1], ',', "'");
        }

        return [];
    }

public function draft(PatenVerif $verif){
    $draft = $this->getDraft($verif, 'draft');
    return view('hakpaten.verifikasidokumen.draftpatenverif', compact('verif','draft'));
}

public function formpermohonan(PatenVerif $verif){
    $draft = $this->getDraft($verif, 'formpermohonan');
    return view('hakpaten.verifikasidokumen.formpermohonanverif', compact('verif','draft'));
}

public function invensi(PatenVerif $verif){
    $draft = $this->getDraft($verif, 'invensi');
    return view('hakpaten.verifikasidokumen.invensiverif', compact('verif','draft'));
}

public function pengalihanhak(PatenVerif $verif){
    $draft = $this->getDraft($verif, 'pengalihanhak');
    return view('hakpaten.verifikasidokumen.pengalihanhakverif', compact('verif','draft'));
}

public function scanktp(PatenVerif $verif){
    $draft = $this->getDraft($verif, 'scanktp');
    return view('hakpaten.verifikasidokumen.scanktpverif', compact('verif','draft'));
}

public function uploadgambar(PatenVerif $verif){
    $draft = $this->getDraft($verif, 'uploadgambar');
    return view('hakpaten.verifikasidokumen.uploadgambarverif', compact('verif','draft'));
}

public function deskripsiprodukverif(PatenVerif $verif){
    $draft = $this->getDraft($verif, 'deskripsi');
    return view('hakpaten.verifikasidokumen.deskripsiprodukverif', compact('verif','draft'));
}

    public function uploadForm(Request $request, PatenVerif $verif)
{
    $request->validate([
        'file' => ['required', 'file', 'mimes:doc,docx,pdf', 'max:10240'],
    ]);

    $path = $this->storeUploadedOriginalName($request, 'verif/form_permohonan');

    $driveService = new \App\Services\GoogleDriveOAuthService('paten');
    $absolutePath = Storage::disk('public')->path($path);
    $driveUrl = $driveService->uploadFile($absolutePath, basename($path));

    $verif->update([
        'form_permohonan' => $path,
        'form_permohonan_drive_url' => $driveUrl,
    ]);

   return redirect()
->route('patenverif.all', $verif->id)
->with('success', 'Form Permohonan Paten berhasil diupload');
}


    public function uploadInvensi(Request $request, PatenVerif $verif)
{
    $request->validate([
        'file' => ['required', 'file', 'mimes:doc,docx,pdf', 'max:10240'],
    ]);

    $path = $this->storeUploadedOriginalName($request, 'verif/surat_kepemilikan');

    $driveService = new \App\Services\GoogleDriveOAuthService('paten');
    $absolutePath = Storage::disk('public')->path($path);
    $driveUrl = $driveService->uploadFile($absolutePath, basename($path));

    $verif->update([
        'surat_kepemilikan' => $path,
        'surat_kepemilikan_drive_url' => $driveUrl,
    ]);

    return redirect()
->route('patenverif.all', $verif->id)
->with('success', 'Surat Kepemilikan Invensi Paten berhasil diupload');
}

    public function uploadPengalihan(Request $request, PatenVerif $verif)
{
    $request->validate([
        'file' => ['required', 'file', 'mimes:doc,docx,pdf', 'max:10240'],
    ]);

    $path = $this->storeUploadedOriginalName($request, 'verif/surat_pengalihan');

    $driveService = new \App\Services\GoogleDriveOAuthService('paten');
    $absolutePath = Storage::disk('public')->path($path);
    $driveUrl = $driveService->uploadFile($absolutePath, basename($path));

    $verif->update([
        'surat_pengalihan' => $path,
        'surat_pengalihan_drive_url' => $driveUrl,
    ]);

    return redirect()
->route('patenverif.all', $verif->id)
->with('success', 'Surat Pengalihan Hak Paten berhasil diupload');
}


    public function uploadKTP(Request $request, PatenVerif $verif)
{
    $request->validate([
        'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
    ]);

    $file = $request->file('file');
    $original = $file->getClientOriginalName();
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $original);

    $path = $this->storeUploadedOriginalName($request, 'verif/scan_ktp');

    $driveService = new \App\Services\GoogleDriveOAuthService('paten');
    $absolutePath = Storage::disk('public')->path($path);
    $driveUrl = $driveService->uploadFile($absolutePath, basename($path));

    $verif->update([
        'scan_ktp' => $path,
        'scan_ktp_drive_url' => $driveUrl,
    ]);

    return redirect()
->route('patenverif.all', $verif->id)
->with('success', 'Scan KTP berhasil diupload');
}


    public function uploadGambarr(Request $request, PatenVerif $verif)
{
    $request->validate([
        'file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,pdf', 'max:10240'],
    ]);

    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $original = $file->getClientOriginalName();
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $original);

        $path = $this->storeUploadedOriginalName($request, 'verif/gambar_prototipe');

        $driveService = new \App\Services\GoogleDriveOAuthService('paten');
        $absolutePath = Storage::disk('public')->path($path);
        $driveUrl = $driveService->uploadFile($absolutePath, basename($path));

        $verif->update([
            'gambar_prototipe' => $path,
            'gambar_prototipe_drive_url' => $driveUrl,
        ]);
    }

    return redirect()
->route('patenverif.all', $verif->id)
->with('success', 'Gambar Prototipe Paten berhasil diupload');
}


    // =========================
    // SUBMIT FINAL
    // =========================
    public function submitFinal(Request $request, PatenVerif $verif)
{
    $request->validate([
       'deskripsi' => ['nullable', 'string', 'max:255'],
       'file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,pdf', 'max:10240'],
    ]);

    $wajib = [
        'draft_paten' => 'Draft Paten',
        'form_permohonan' => 'Formulir Permohonan Paten',
        'surat_kepemilikan' => 'Surat Kepemilikan Invensi',
        'surat_pengalihan' => 'Surat Pengalihan Hak',
        'scan_ktp'         => 'Scan KTP',
    ];

    $kurang = [];

    foreach ($wajib as $field => $label) {
        if (!$verif->$field) {
            $kurang[] = $label;
        }
    }

    if (!empty($kurang)) {
        return back()->with('submit_error', $kurang);
    }
    $verif->update([
        'deskripsi_singkat_prototipe' => $request->filled('deskripsi')
            ? $request->deskripsi
            : $verif->deskripsi_singkat_prototipe,
        'status_verif' => 'Terkirim',
        'submitted_at' => now(),
    ]);

    session()->forget('verif_id');

    $kode = $verif->no_pendaftaran;
    $authController = app(\App\Http\Controllers\PemohonAuthController::class);
    $result = $authController->sendCredentialAfterSubmit($kode);
    if (!$result['ok']) {
        return back()->with('error', $result['message']);
    }

    return redirect()
        ->route('patenverif.hasil', ['verif' => $verif->id])
        ->with('success','Verifikasi berhasil dikirim');
}

    // =========================
    // HALAMAN HASIL SUBMIT
    // =========================
    public function hasilSubmit(PatenVerif $verif)
    {
        return view('hakpaten.verifikasidokumen.hasilsubmit', compact('verif'));
    }

    private function saveDraft(Request $request, PatenVerif $verif, string $step, array $onlyKeys)
{
    $data = $request->only($onlyKeys);

    // bersihin spasi biar rapi (opsional)
    foreach ($data as $k => $v) {
        if (is_string($v)) $data[$k] = trim($v);
    }

    session()->put("draft.{$verif->id}.{$step}", array_merge(
        session("draft.{$verif->id}.{$step}", []),
        $data
    ));
}

private function getDraft(PatenVerif $verif, string $step): array
{
    return session("draft.{$verif->id}.{$step}", []);
}

}