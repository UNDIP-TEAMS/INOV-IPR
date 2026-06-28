<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class FormPendaftaranCiptaanController extends Controller
{
    private function val($v): string
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? '' : $s;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
    'jumlah_inventor'        => ['required', 'integer', 'min:1', 'max:20'],
    'jenis_cipta'            => ['required', 'in:Buku,Program Komputer,Karya Rekaman Video,Lainnya'],
    'jenis_cipta_lainnya'    => ['nullable', 'string', 'max:255'],
    'judul_ciptaan'          => ['required', 'string', 'max:255'],
    'link_ciptaan'           => ['nullable', 'url'],
    'berupa'                 => ['nulabel', 'string', 'max:255'],
    'tanggal_pengisian'      => ['required', 'date'],
    'tempat'                 => ['required', 'string', 'max:100'],
    'uraian'                 => ['required', 'string', 'max:350'],

    'tempatpertama' => ['required', 'string', 'max:100'],
    'tanggal_pertama' => ['required', 'date'],

    'inventor'               => ['required', 'array'],

    'inventor.nama'          => ['required', 'array'],
    'inventor.nama.*'        => ['required', 'string', 'max:200'],

    'inventor.nik'           => ['required', 'array'],
    'inventor.nik.*'         => ['required', 'string', 'max:50'],

    'inventor.nip_nim'       => ['required', 'array'],
    'inventor.nip_nim.*'     => ['required', 'string', 'max:50'],

    'inventor.fakultas'      => ['required', 'array'],
    'inventor.fakultas.*'    => ['required', 'string', 'max:255'],

    'inventor.nidn'          => ['nullable', 'array'],
    'inventor.nidn.*'        => ['nullable', 'string', 'max:20'],

    'inventor.status'        => ['required', 'array'],
    'inventor.status.*'      => ['required', 'string', 'max:50'],

    'inventor.no_hp'         => ['required', 'array'],
    'inventor.no_hp.*'       => ['required', 'string', 'max:50'],

    'inventor.tlp_rumah'     => ['nullable', 'array'],
    'inventor.tlp_rumah.*'   => ['nullable', 'string', 'max:50'],

    'inventor.email'         => ['required', 'array'],
    'inventor.email.*'       => ['required', 'email', 'max:100'],

    'inventor.alamat'        => ['required', 'array'],
    'inventor.alamat.*'      => ['required', 'string'],

    'inventor.kode_pos'      => ['required', 'array'],
    'inventor.kode_pos.*'    => ['required', 'string', 'max:20'],

        'download_format'        => ['nullable', 'in:pdf,docx'],
    ]);

        $jumlah = (int) $data['jumlah_inventor'];
        $actual = count($data['inventor']['nama'] ?? []);
        if ($actual !== $jumlah) {
            return back()->withErrors(['inventor' => 'Jumlah inventor tidak sesuai.'])->withInput();
        }

        $existingForm = session('hakcipta.form', []);

        session()->put('hakcipta.form', array_merge($existingForm, [
            'jumlah_inventor'      => $request->jumlah_inventor,
            'jenis_cipta'          => $request->jenis_cipta,
            'jenis_cipta_lainnya'  => $request->jenis_cipta_lainnya,
            'link_ciptaan'         => $request->link_ciptaan ?? ($existingForm['link_ciptaan'] ?? null),
            'judul_ciptaan'        => $request->judul_ciptaan,
            'tempatpertama'        => $request->tempatpertama ?? ($existingForm['tempatpertama'] ?? null),
            'tanggal_pertama'      => $request->tanggal_pertama ?? ($existingForm['tanggal_pertama'] ?? null),
            'tanggal_pengisian'    => $request->tanggal_pengisian ?? ($existingForm['tanggal_pengisian'] ?? null),
            'tempat'               => $request->tempat ?? ($existingForm['tempat'] ?? null),
            'uraian'               => $request->uraian ?? ($existingForm['uraian'] ?? null),
            'inventor'             => $request->inventor,
        ]));

        if ($request->input('action') === 'next') {
            return response()->json(['ok' => true]);
        }

        $templateObjectPath = 'Permohonan Pendaftaran Ciptaan 2021.docx';

        if (!Storage::disk('s3')->exists($templateObjectPath)) {
            abort(500, 'Template DOCX tidak ditemukan di bucket: ' . $templateObjectPath);
        }

        $templatePath = tempnam(sys_get_temp_dir(), 'template_permohonan_cipta_') . '.docx';

        file_put_contents(
            $templatePath,
            Storage::disk('s3')->get($templateObjectPath)
        );

        $tp = new TemplateProcessor($templatePath);

        $tp->setValue('judul_ciptaan', $this->val($data['judul_ciptaan']));
        $tp->setValue('link_ciptaan', $this->val($data['link_ciptaan']));

       // tanggal + tempat pengisian
        $tgl = Carbon::parse($data['tanggal_pertama'])
            ->locale('id')
            ->translatedFormat('d F Y');

        $tp->setValue('tempatpertama', $this->val($data['tempatpertama']));
        $tp->setValue('tanggal_pertama', $tgl);

        // === inventor rows
        $names = array_map(
            fn($n) => $this->val($n),
            $data['inventor']['nama'] ?? []
        );
        $names = array_values(array_filter($names, fn($n) => $n !== ''));
        $namaGabung = implode(', ', $names);

        $alamat1 = $this->val($data['inventor']['alamat'][0] ?? '');
        $telp1   = $this->val($data['inventor']['tlp_rumah'][0] ?? ''); // sesuai input: tlp_rumah
        $hp1     = $this->val($data['inventor']['no_hp'][0] ?? '');
        $email1  = $this->val($data['inventor']['email'][0] ?? '');

        $tp->setValue('nama_lengkap', $namaGabung);
        $tp->setValue('alamat', $alamat1);
        $tp->setValue('tlp_rumah', $telp1);
        $tp->setValue('no_hp', $hp1);
        $tp->setValue('email', $email1);

        $tp->setValue('uraian', $this->val($request->input('uraian')));

        $out = tempnam(sys_get_temp_dir(), 'cipta_') . '.docx';
        $tp->saveAs($out);

        $format = $data['download_format'];

        if ($format === 'docx') {
        return response()
                    ->download($out, 'Permohonan Pendaftaran Ciptaan.docx')
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
            ->download($pdfPath, 'Permohonan Pendaftaran Ciptaan.pdf')
            ->deleteFileAfterSend(true);
    }
}