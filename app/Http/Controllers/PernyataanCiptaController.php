<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PernyataanCiptaController extends Controller
{
    private function val($v): string
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? '' : $s;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul_ciptaan'       => ['required', 'string', 'max:255'],
            'jenis_cipta'         => ['required', 'string', 'max:255'],
            'jenis_cipta_lainnya' => ['nullable', 'string', 'max:255'],
            'tanggal_pengisian'   => ['required', 'date'],
            'download_format'     => ['required', 'in:pdf,docx'],
        ]);

        session(['hakcipta.form' => $data]);

        if ($request->input('action') === 'next') {
            return redirect()
                ->route('hakcipta.pengalihanhak')
                ->with('success', 'Data tersimpan.');
        }

        $templateObjectPath = 'Surat Pernyataan Hak Cipta 2021.docx';

        if (!Storage::disk('s3')->exists($templateObjectPath)) {
            abort(500, 'Template DOCX tidak ditemukan di bucket: ' . $templateObjectPath);
        }

        $templatePath = tempnam(sys_get_temp_dir(), 'template_cipta_') . '.docx';
        file_put_contents($templatePath, Storage::disk('s3')->get($templateObjectPath));

        $tp = new TemplateProcessor($templatePath);

        $tp->setValue('judul_ciptaan', $this->val($data['judul_ciptaan']));

        $berupa = $data['jenis_cipta'] === 'Lainnya'
            ? $this->val($data['jenis_cipta_lainnya'] ?? '')
            : $this->val($data['jenis_cipta']);
        $tp->setValue('berupa', $berupa);

        $tgl = Carbon::parse($data['tanggal_pengisian'])->locale('id');
        $tp->setValue('tanggal_pengisian', $tgl->translatedFormat('d F Y'));

        $out = tempnam(sys_get_temp_dir(), 'cipta_') . '.docx';
        $tp->saveAs($out);
        @unlink($templatePath);

        if ($data['download_format'] === 'docx') {
            return response()
                ->download($out, 'Surat Pernyataan Hak Cipta.docx')
                ->deleteFileAfterSend(true);
        }

        // === Convert ke PDF ===
        if (PHP_OS_FAMILY === 'Windows') {
            $soffice = 'D:\\Program Files\\LibreOffice\\program\\soffice.exe';
            if (!file_exists($soffice)) {
                $soffice = 'D:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe';
            }
        } else {
            $soffice = '/usr/bin/soffice';
        }

        if (!file_exists($soffice)) {
            abort(500, "LibreOffice tidak ditemukan: {$soffice}");
        }

        $outDir     = dirname($out);
        $pdfPath    = preg_replace('/\.docx$/i', '.pdf', $out);
        $loProfile  = sys_get_temp_dir() . '/lo_profile_cipta_' . uniqid();

        if (!is_dir($loProfile)) {
            mkdir($loProfile, 0777, true);
        }

        $profileArg = '-env:UserInstallation=file:///' . str_replace('\\', '/', $loProfile);

        $process = new \Symfony\Component\Process\Process([
            $soffice,
            '--headless',
            '--nologo',
            '--nofirststartwizard',
            '--nodefault',
            '--norestore',
            $profileArg,
            '--convert-to', 'pdf:writer_pdf_Export',
            '--outdir', str_replace('\\', '/', $outDir),
            str_replace('\\', '/', $out),
        ]);

        $process->setEnv([
            'USERPROFILE' => $loProfile,
            'APPDATA'     => $loProfile,
            'TEMP'        => $loProfile,
            'TMP'         => $loProfile,
        ]);

        $process->setTimeout(120);
        $process->run();

        clearstatcache();

        if (!file_exists($pdfPath)) {
            $pdfs = glob($outDir . DIRECTORY_SEPARATOR . '*.pdf');
            if ($pdfs) {
                usort($pdfs, fn($a, $b) => filemtime($b) <=> filemtime($a));
                $pdfPath = $pdfs[0];
            }
        }

        @array_map('unlink', glob($loProfile . '/*'));
        @rmdir($loProfile);
        @unlink($out);

        if (!$pdfPath || !file_exists($pdfPath)) {
            abort(
                500,
                "Gagal convert PDF.\n" .
                "ExitCode: " . $process->getExitCode() . "\n" .
                "ErrorOutput: " . $process->getErrorOutput() . "\n" .
                "Output: " . $process->getOutput()
            );
        }

        return response()
            ->download($pdfPath, 'Surat Pernyataan Hak Cipta.pdf')
            ->deleteFileAfterSend(true);
    }
}