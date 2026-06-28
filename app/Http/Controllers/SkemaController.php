<?php

namespace App\Http\Controllers;

use App\Models\Paten;
use App\Models\PatenVerif;
use App\Models\HakCiptaVerif;
use App\Models\HakCipta;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class SkemaController extends Controller
{
    // ===== PATEN VERIF =====
    public function showVerif(PatenVerif $verif)
    {
        $draft = session("patenverif.{$verif->id}.skema", []);
        return view('hakpaten.verifikasidokumen.skema.skemapengembangan', compact('verif','draft'));
    }

    public function downloadVerif(Request $request, PatenVerif $verif)
{
    $sessionKey = "patenverif.{$verif->id}.skema";
    $action = $request->input('action');

    $this->saveSkemaDraft($request, $sessionKey);

    if ($action === 'prev') {
        return redirect()->route('patenverif.datadiri', $verif->id);
    }

    $validated = $request->validate([
        'nama_lengkap'      => ['required', 'string', 'max:255'],
        'program_studi'     => ['required', 'string', 'max:255'],
        'judul_paten'       => ['required', 'string', 'max:255'],
        'nidn_nip'          => ['required', 'regex:/^(\d{8}|\d{18})$/'],
        'fakultas'          => ['required', 'string', 'max:255'],
        'tanggal_pengisian' => ['required', 'date'],
        'download_format'   => ['nullable', 'in:pdf,docx'],
    ], [
        'nidn_nip.regex' => 'NIDN/NIP harus terdiri dari 8 atau 18 digit angka.',
    ]);

    session()->put($sessionKey, array_merge(session($sessionKey, []), $validated));

    if ($action === 'next') {
        if (empty($verif->skema_tkt_template_path)) {
            return back()
                ->withErrors(['file' => 'Upload surat pernyataan skema pengembangan terlebih dahulu.'])
                ->withInput();
        }

        return redirect()->route('patenverif.all', $verif->id);
    }

    return $this->downloadDocx($request);
}
    public function uploadVerif(Request $request, PatenVerif $verif)
    {
        return $this->handleUpload(
            request: $request,
            noPendaftaran: $verif->no_pendaftaran ?? ('VP_'.$verif->id),
            storeDir: 'paten-verif/skema',
            updateModel: function(string $path) use ($verif) {
                $docs = $verif->docs ?? [];
                $docs['skema_tkt'] = [
                    'status' => 'pending', 
                    'path'   => $path,
                    'note'   => null,
                    'updated_at' => now(),
                ];

                $verif->update([
                    'skema_tkt_template_path' => $path, 
                    'docs' => $docs 
                ]);
            },
            fallbackRedirect: route('patenverif.skema.form', ['verif' => $verif->id]),
            sessionKey: "patenverif.{$verif->id}.skema",
        );
    }

        // ===== PATEN =====
        public function showPaten(Paten $paten)
        {
            $draft = session("paten.{$paten->id}.skema", []);
            return view('hakpaten.skemapengembanganpaten', compact('paten','draft'));
        }

        public function downloadPaten(Request $request, Paten $paten)
    {
        $this->saveSkemaDraft($request, "paten.{$paten->id}.skema");

        if ($request->input('action') === 'prev') {
            return redirect()->route('hakpaten.datadiri');
        }

        if ($request->input('action') === 'next') {
            return redirect()->route('hakpaten.all', $paten->id);
        }

        return $this->downloadDocx($request);
    }

        public function uploadPaten(Request $request, Paten $paten)
    {
        return $this->handleUpload(
            request: $request,
            noPendaftaran: $paten->no_pendaftaran ?? ('P_'.$paten->id),
            storeDir: 'hak-paten/skema',
            updateModel: fn(string $path) => $paten->update([
                'skema_tkt_template_path' => $path,
            ]),
            fallbackRedirect: route('hakpaten.skema.form', ['paten' => $paten->id]),
            sessionKey: "paten.{$paten->id}.skema",
        );
    }


    // ===== Shared helpers =====
    private function downloadDocx(Request $request)
    {
        $data = $request->validate([
            'nama_lengkap'      => ['required', 'string', 'max:255'],
            'program_studi'     => ['required', 'string', 'max:255'],
            'judul_paten'       => ['required', 'string', 'max:255'],
            'nidn_nip'          => ['required', 'regex:/^(\d{8}|\d{18})$/'],
            'fakultas'          => ['required', 'string', 'max:255'],
            'tanggal_pengisian' => ['required', 'date'],
            'download_format'   => ['required', 'in:pdf,docx'],   // <--- baru
        ]);

        $templateObjectPath = 'Surat Pernyatan TKT 7-9.docx';

        if (!Storage::disk('s3')->exists($templateObjectPath)) {
            abort(500, 'Template DOCX tidak ditemukan di bucket: ' . $templateObjectPath);
        }

        $templatePath = tempnam(sys_get_temp_dir(), 'template_tkt_') . '.docx';

        file_put_contents(
            $templatePath,
            Storage::disk('s3')->get($templateObjectPath)
        );

        $tp = new TemplateProcessor($templatePath);

        foreach (['nama_lengkap','program_studi','judul_paten','nidn_nip','fakultas'] as $k) {
            $tp->setValue($k, $data[$k]);
        }

        $tgl = Carbon::parse($data['tanggal_pengisian'])->locale('id')->translatedFormat('d F Y');
        $tp->setValue('tanggal_pengisian', $tgl);

        // generate DOCX temp
        $out = tempnam(sys_get_temp_dir(), 'tkt_') . '.docx';
        $tp->saveAs($out);

        // ===== DOCX =====
        if ($data['download_format'] === 'docx') {
            return response()
                ->download($out, 'Surat Pernyataan TKT 7-9.docx')
                ->deleteFileAfterSend(true);
        }

        // ===== PDF (convert dari DOCX template via LibreOffice) =====
        $soffice = 'D:\Program Files\LibreOffice\program\soffice.exe';
        if (!file_exists($soffice)) {
            $soffice = 'D:\Program Files (x86)\LibreOffice\program\soffice.exe';
        }
        if (!file_exists($soffice)) {
            abort(500, 'soffice.exe tidak ditemukan. Cek instalasi LibreOffice.');
        }

        $outDir  = dirname($out);
        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $out);

        $cmd = '"' . $soffice . '" --headless --nologo --nofirststartwizard '
            . '--convert-to pdf --outdir "' . $outDir . '" "' . $out . '" 2>&1';

        $output = [];
        $code = 0;
        exec($cmd, $output, $code);

        if ($code !== 0 || !file_exists($pdfPath)) {
            @unlink($out);
            abort(500, "Gagal convert PDF. ExitCode=$code\n" . implode("\n", $output));
        }

        @unlink($out); 

        return response()
            ->download($pdfPath, 'Surat Pernyataan TKT 7-9.pdf')
            ->deleteFileAfterSend(true);
    }


    private function handleUpload(
    Request $request,
    string $noPendaftaran,
    string $storeDir,
    callable $updateModel,
    string $fallbackRedirect,
    ?string $sessionKey = null,
) {
    $request->validate([
        'file' => ['required', 'file', 'mimes:doc,docx,pdf', 'max:10240'],
    ]);

    $file = $request->file('file');
    $uploadSignature = md5(
        $noPendaftaran . '|' .
        $file->getClientOriginalName() . '|' .
        $file->getSize()
    );

    $lastSignature = session('skema_upload_signature');
    $lastTime = (int) session('skema_upload_time', 0);

    if ($lastSignature === $uploadSignature && (time() - $lastTime) <= 3) {
        return redirect($fallbackRedirect)
            ->with('success', 'File skema berhasil diupload');
    }

    session([
        'skema_upload_signature' => $uploadSignature,
        'skema_upload_time' => time(),
    ]);

    $ext  = $file->getClientOriginalExtension();
    $filename = $noPendaftaran . '_skema_tkt_' . uniqid() . '.' . $ext;

    $path = $file->storeAs($storeDir, $filename, 's3');

    $updateModel($path);

    if ($sessionKey) {
    $textDraft = $request->only([
        'nama_lengkap',
        'program_studi',
        'judul_paten',
        'nidn_nip',
        'fakultas',
        'tanggal_pengisian',
        'download_format',
    ]);

    foreach ($textDraft as $k => $v) {
        if (is_string($v)) {
            $textDraft[$k] = trim($v);
        }
    }

    session()->put($sessionKey, array_merge(session($sessionKey, []), $textDraft, [
        'file_path' => $path,
        'file_name' => $file->getClientOriginalName(),
        'uploaded'  => true,
    ]));
}

    $to = $request->input('redirect');
    $redirectTo = $to ?: $fallbackRedirect;

    return redirect($redirectTo)
        ->with('success', 'File skema berhasil diupload')
        ->withInput();
}

private function saveSkemaDraft(Request $request, string $sessionKey): void
{
    $data = $request->only([
        'nama_lengkap',
        'program_studi',
        'judul_paten',
        'nidn_nip',
        'fakultas',
        'tanggal_pengisian',
        'download_format',
    ]);

    foreach ($data as $k => $v) {
        if (is_string($v)) {
            $data[$k] = trim($v);
        }
    }

    session()->put($sessionKey, array_merge(session($sessionKey, []), $data));
}
}
