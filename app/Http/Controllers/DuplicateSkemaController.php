<?php

namespace App\Http\Controllers;

use App\Models\Paten;
use App\Models\PatenVerif;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class DuplicateSkemaController extends Controller
{
    // FUNGSI UNTUK TAMPILIN HALAMAN EDIT
    public function showDuplicate(PatenVerif $verif)
    {
        $sessionKey = "patenverif.{$verif->id}.skema";
        $draft = session($sessionKey, [
            'nama_lengkap' => $verif->inventors_arr[0]['nama'] ?? '',
            'program_studi' => '',
            'judul_paten'  => $verif->judul_paten,
            'nidn_nip'     => $verif->inventors_arr[0]['nip_nim'] ?? '',
            'fakultas'     => $verif->inventors_arr[0]['fakultas'] ?? '',
        ]);

        return view('duplicateskemarevisi', compact('verif', 'draft'));
    }

    // FUNGSI UNTUK DOWNLOAD & SIMPAN SESSION 
    public function downloadDuplicate(Request $request, PatenVerif $verif)
    {
        $sessionKey = "patenverif.{$verif->id}.skema";
        $this->saveSkemaDraft($request, $sessionKey);

        if ($request->input('action') === 'save') {
            return back()->with('success', 'Data berhasil disimpan ke sistem.');
        }

        return $this->downloadDocx($request);
    }

    //FUNGSI UNTUK UPLOAD REVISI 
    public function uploadDuplicate(Request $request, PatenVerif $verif)
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
                    'updated_at' => now(),
                ];

                $verif->update([
                    'skema_tkt_template_path' => $path,
                    'docs' => $docs
                ]);
            },
            fallbackRedirect: route('dup.skema.form', ['verif' => $verif->id]),
            sessionKey: "patenverif.{$verif->id}.skema",
        );
    }

    private function downloadDocx(Request $request)
    {
        $data = $request->validate([
            'nama_lengkap'      => ['required', 'string', 'max:255'],
            'program_studi'     => ['required', 'string', 'max:255'],
            'judul_paten'       => ['required', 'string', 'max:255'],
            'nidn_nip'          => ['required', 'regex:/^(\d{8}|\d{18})$/'],
            'fakultas'          => ['required', 'string', 'max:255'],
            'tanggal_pengisian' => ['required', 'date'],
            'download_format'   => ['required', 'in:pdf,docx'],
        ]);

        $templatePath = public_path('templates/Surat Pernyatan TKT 7-9.docx');
        if (!file_exists($templatePath)) abort(500, 'Template tidak ditemukan.');

        $tp = new TemplateProcessor($templatePath);
        foreach (['nama_lengkap','program_studi','judul_paten','nidn_nip','fakultas'] as $k) {
            $tp->setValue($k, $data[$k]);
        }

        $tgl = Carbon::parse($data['tanggal_pengisian'])->locale('id')->translatedFormat('d F Y');
        $tp->setValue('tanggal_pengisian', $tgl);

        $out = tempnam(sys_get_temp_dir(), 'tkt_') . '.docx';
        $tp->saveAs($out);

        if ($data['download_format'] === 'docx') {
            return response()->download($out, 'Surat Pernyataan TKT 7-9.docx')->deleteFileAfterSend(true);
        }

        $soffice = 'C:\Program Files\LibreOffice\program\soffice.exe';
        if (!file_exists($soffice)) $soffice = 'C:\Program Files\LibreOffice\program\soffice.exe';
        
        $outDir = dirname($out);
        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $out);
        $cmd = '"' . $soffice . '" --headless --nologo --nofirststartwizard --convert-to pdf --outdir "' . $outDir . '" "' . $out . '" 2>&1';
        exec($cmd);

        return response()->download($pdfPath, 'Surat Pernyataan TKT 7-9.pdf')->deleteFileAfterSend(true);
    }

    private function handleUpload(Request $request, string $noPendaftaran, string $storeDir, callable $updateModel, string $fallbackRedirect, ?string $sessionKey = null)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:doc,docx,pdf', 'max:10240']]);
        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $filename = $noPendaftaran . '_skema_tkt_rev_' . uniqid() . '.' . $ext;
        $path = $file->storeAs($storeDir, $filename, 'public');

        $updateModel($path);

        if ($sessionKey) {
            $data = $request->only(['nama_lengkap','program_studi','judul_paten','nidn_nip','fakultas','tanggal_pengisian','download_format']);
            session()->put($sessionKey, array_merge(session($sessionKey, []), $data, [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'uploaded'  => true,
            ]));
        }

        return redirect($fallbackRedirect)->with('success', 'File skema berhasil direvisi & diupload');
    }

    private function saveSkemaDraft(Request $request, string $sessionKey): void
    {
        $data = $request->only(['nama_lengkap','program_studi','judul_paten','nidn_nip','fakultas','tanggal_pengisian','download_format']);
        session()->put($sessionKey, array_merge(session($sessionKey, []), $data));
    }
}