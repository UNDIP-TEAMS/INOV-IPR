<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;

class DuplicatePengalihanHakCiptaController extends Controller
{
    private function pickTemplate(int $jumlah): string
    {
        if ($jumlah >= 1 && $jumlah <= 7)  return public_path('templates/pengalihan hak CIPTA 1-8.docx');
        if ($jumlah >= 8 && $jumlah <= 14) return public_path('templates/pengalihan hak CIPTA 9-14.docx');

        abort(422, 'Jumlah inventor tidak didukung template.');
    }

    private function val($v): string
    {
        return trim((string)($v ?? ''));
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'jumlah_inventor' => ['required', 'integer', 'min:1', 'max:14'],
            'judul_ciptaan' => ['required', 'string', 'max:255'],
            'tanggal_pengisian' => ['required', 'date'],
            'jenis_cipta' => ['required', 'string', 'max:100'],
            'jenis_cipta_lainnya' => ['nullable', 'string', 'max:100'],

            'inventor' => ['required', 'array'],
            'inventor.nama' => ['required', 'array'],
            'inventor.nama.*' => ['required', 'string', 'max:200'],
            'inventor.NIK' => ['nullable', 'array'],
            'inventor.NIK.*' => ['nullable', 'string', 'max:100'],
            'inventor.alamat' => ['required', 'array'],
            'inventor.alamat.*' => ['required', 'string'],
            'inventor.kode_pos' => ['nullable', 'array'],
            'inventor.kode_pos.*' => ['nullable', 'string', 'max:20'],
            'inventor.email' => ['required', 'array'],
            'inventor.email.*' => ['required', 'email', 'max:255'],
            'inventor.no_hp' => ['required', 'array'],
            'inventor.no_hp.*' => ['required', 'string', 'max:30'],

            'download_format' => ['required', 'in:pdf,docx'],
        ]);

        $jumlah = (int) $data['jumlah_inventor'];
        if (count($data['inventor']['nama']) !== $jumlah) {
            return back()->withErrors(['inventor' => 'Jumlah inventor tidak sesuai.'])->withInput();
        }

        $templatePath = $this->pickTemplate($jumlah);
        if (!file_exists($templatePath)) {
            abort(500, 'Template DOCX tidak ditemukan: ' . $templatePath);
        }

        $tp = new TemplateProcessor($templatePath);
        $tp->setValue('judul_hak_cipta', $this->val($data['judul_ciptaan']));
        $jenis = $data['jenis_cipta'] === 'Lainnya'
            ? $this->val($data['jenis_cipta_lainnya'] ?? 'Lainnya')
            : $this->val($data['jenis_cipta']);

        $tp->setValue('jenis', $jenis);

        $tgl = Carbon::parse($data['tanggal_pengisian'])->locale('id');
        $tp->setValue('tanggal_pengisian', $tgl->translatedFormat('d F Y'));

        // =========================
        // Clone table inventor_block
        // =========================
        $tp->cloneBlock('inventor_block', $jumlah, true, true);
            for ($i = 1; $i <= $jumlah; $i++) {
                $idx = $i - 1;
                $tp->setValue("no#{$i}", (string)$i);
                $tp->setValue("NIK#{$i}", $this->val($data['inventor']['NIK'][$idx] ?? ''));
                $tp->setValue("nama#{$i}", $this->val($data['inventor']['nama'][$idx] ?? ''));
                $tp->setValue("alamat#{$i}", $this->val($data['inventor']['alamat'][$idx] ?? ''));
                $tp->setValue("kode_pos#{$i}", $this->val($data['inventor']['kode_pos'][$idx] ?? ''));
                $tp->setValue("email#{$i}", $this->val($data['inventor']['email'][$idx] ?? ''));
                $tp->setValue("no_hp#{$i}", $this->val($data['inventor']['no_hp'][$idx] ?? ''));
            }

        // =========================
        // Clone list_inventor
        // =========================
        $tp->cloneBlock('list_inventor', $jumlah, true, true);
            for ($i = 1; $i <= $jumlah; $i++) {
                $idx = $i - 1;
                $tp->setValue("no_list#{$i}", (string)$i);
                $tp->setValue("nama_list#{$i}", $this->val($data['inventor']['nama'][$idx] ?? ''));
            }

            $out = sys_get_temp_dir() . '/hakcipta_' . uniqid() . '.docx';
            $tp->saveAs($out);
            $format = $data['download_format'];

            if ($format === 'docx') {
            return response()
                        ->download($out, 'Surat Pengalihan Hak Cipta.docx')
                        ->deleteFileAfterSend(true);
            }

        // === Convert DOCX 
        $soffice = 'C:\Program Files\LibreOffice\program\soffice.exe';
        if (!file_exists($soffice)) {
            $soffice = 'C:\Program Files (x86)\LibreOffice\program\soffice.exe';
        }
        if (!file_exists($soffice)) {
            abort(500, 'soffice.exe tidak ditemukan. Cek instalasi LibreOffice.');
        }

        $outDir  = dirname($out);
        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $out);

        // command
        $cmd = '"' . $soffice . '" --headless --nologo --nofirststartwizard '
            . '--convert-to pdf --outdir "' . $outDir . '" "' . $out . '" 2>&1';

        $output = [];
        $code = 0;
        exec($cmd, $output, $code);

        if ($code !== 0 || !file_exists($pdfPath)) {
            abort(500, "Gagal convert PDF. ExitCode=$code\n" . implode("\n", $output));
        }

        return response()
            ->download($pdfPath, 'Surat Pengalihan Hak Cipta.pdf')
            ->deleteFileAfterSend(true);
    }
}
