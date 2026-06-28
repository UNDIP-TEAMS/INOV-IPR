<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;

class DuplicatePernyataanCiptaController extends Controller
{
    private function val($v): string
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? '' : $s;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul_ciptaan'   => ['required', 'string', 'max:255'],
            'berupa'     => ['required', 'string', 'max:255'],
            'tanggal_pengisian' => ['required', 'date'],

            'download_format' => ['required', 'in:pdf,docx'],
        ]);

        session(['hakcipta.form' => $data]);
        if ($request->input('action') === 'next') {
            return redirect()
                ->route('hakcipta.pengalihanhak') 
                ->with('success', 'Data tersimpan.');
        }

        $templatePath = public_path('templates/Surat Pernyataan Hak Cipta 2021.docx');
        if (!file_exists($templatePath)) {
            abort(500, 'Template DOCX tidak ditemukan: ' . $templatePath);
        }

        $tp = new TemplateProcessor($templatePath);
        $tp->setValue('judul_ciptaan', $this->val($data['judul_ciptaan']));
        $tp->setValue('berupa', $this->val($data['berupa']));
        $tgl = Carbon::parse($data['tanggal_pengisian'])->locale('id');
        $tp->setValue('tanggal_pengisian', $tgl->translatedFormat('d F Y'));

        $out = tempnam(sys_get_temp_dir(), 'cipta_') . '.docx';
    
        $tp->saveAs($out);

        $format = $data['download_format'];

        if ($format === 'docx') {
        return response()
                    ->download($out, 'Surat Pernyataan Hak Cipta.docx')
                    ->deleteFileAfterSend(true);
        }

        // === Convert DOCX 
        $soffice = 'D:\Program Files\LibreOffice\program\soffice.exe';
        if (!file_exists($soffice)) {
            $soffice = 'D:\Program Files (x86)\LibreOffice\program\soffice.exe';
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
            ->download($pdfPath, 'Surat Pernyataan Hak Cipta.pdf')
            ->deleteFileAfterSend(true);
    }
}
