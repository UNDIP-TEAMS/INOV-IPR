<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PengalihanHakController extends Controller
{
    private function pickTemplate(int $jumlah): string
    {
        if ($jumlah >= 1 && $jumlah <= 7) {
            $templateObjectPath = 'pengalihan hak 1-4.docx';
        } elseif ($jumlah >= 8 && $jumlah <= 14) {
            $templateObjectPath = 'pengalihan hak 9-14.docx';
        } else {
            abort(422, 'Jumlah inventor tidak didukung template.');
        }

        if (!Storage::disk('s3')->exists($templateObjectPath)) {
            abort(500, 'Template DOCX tidak ditemukan di bucket: ' . $templateObjectPath);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'template_pengalihan_') . '.docx';
        file_put_contents($tempPath, Storage::disk('s3')->get($templateObjectPath));

        return $tempPath;
    }

    private function val($v): string
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? '' : $s;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'jumlah_inventor'      => ['required', 'integer', 'min:1', 'max:20'],
            'judul_invensi'        => ['required', 'string', 'max:255'],
            'tanggal_pengisian'    => ['required', 'date'],
            'inventor'             => ['required', 'array'],
            'inventor.nama'        => ['required', 'array'],
            'inventor.nama.*'      => ['required', 'string', 'max:200'],
            'inventor.pekerjaan.*' => ['required', 'string', 'max:100'],
            'inventor.alamat.*'    => ['required', 'string'],
            'inventor.kode_pos.*'  => ['required', 'string', 'max:20'],
            'download_format'      => ['required', 'in:pdf,docx'],
        ]);

        $jumlah = (int) $data['jumlah_inventor'];
        $actual = count($data['inventor']['nama'] ?? []);
        if ($actual !== $jumlah) {
            return back()->withErrors(['inventor' => 'Jumlah inventor tidak sesuai.'])->withInput();
        }

        $templatePath = $this->pickTemplate($jumlah);

        $tp = new TemplateProcessor($templatePath);

        $tp->setValue('judul_paten', $this->val($data['judul_invensi']));
        $tgl = Carbon::parse($data['tanggal_pengisian'])->locale('id');
        $tp->setValue('tanggal_pengisian', $tgl->translatedFormat('d F Y'));

        $tp->cloneBlock('inventor_block', $jumlah, true, true);
        for ($i = 1; $i <= $jumlah; $i++) {
            $idx = $i - 1;
            $tp->setValue("no#{$i}",           $i);
            $tp->setValue("nama_lengkap#{$i}",  $this->val($data['inventor']['nama'][$idx] ?? ''));
            $tp->setValue("pekerjaan#{$i}",     $this->val($data['inventor']['pekerjaan'][$idx] ?? ''));
            $tp->setValue("alamat#{$i}",        $this->val($data['inventor']['alamat'][$idx] ?? ''));
            $tp->setValue("kode_pos#{$i}",      $this->val($data['inventor']['kode_pos'][$idx] ?? ''));
        }

        $tp->cloneBlock('list_inventor', $jumlah, true, true);
        for ($i = 1; $i <= $jumlah; $i++) {
            $idx = $i - 1;
            $tp->setValue("no_list#{$i}",   $i);
            $tp->setValue("nama_list#{$i}", $this->val($data['inventor']['nama'][$idx] ?? ''));
        }

        $out = tempnam(sys_get_temp_dir(), 'invensi_') . '.docx';
        $tp->saveAs($out);
        @unlink($templatePath);

        if ($data['download_format'] === 'docx') {
            return response()
                ->download($out, 'Surat Pernyataan Pengalihan Hak.docx')
                ->deleteFileAfterSend(true);
        }

        // === Convert ke PDF ===
        if (PHP_OS_FAMILY === 'Windows') {
            $soffice = 'D:\\Program Files\\LibreOffice\\program\\soffice.exe';
            if (!file_exists($soffice)) {
                $soffice = 'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe';
            }
            if (!file_exists($soffice)) {
                $soffice = 'D:\\Program Files\\LibreOffice\\program\\soffice.exe';
            }
        } else {
            $soffice = '/usr/bin/soffice';
        }

        if (!file_exists($soffice)) {
            abort(500, "LibreOffice tidak ditemukan: {$soffice}");
        }

        $outDir    = dirname($out);
        $pdfPath   = preg_replace('/\.docx$/i', '.pdf', $out);
        $loProfile = sys_get_temp_dir() . '/lo_profile_paten_' . uniqid();

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
            ->download($pdfPath, 'Surat Pernyataan Pengalihan Hak.pdf')
            ->deleteFileAfterSend(true);
    }
}