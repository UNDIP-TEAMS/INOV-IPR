<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DuplicateIsiFormController extends Controller
{
   public function store(Request $request)
    {
        $action = $request->input('action', 'download');
        $refId = $request->input('ref') ?? session('edit_ref_id');
        $sessionKey = "hakpaten.isiform.$refId";

        $request->merge([
            'is_pct'         => $request->input('is_pct', 'Tidak'),
            'is_pecahan'     => $request->input('is_pecahan', 'Tidak'),
            'konsultanpaten' => $request->input('konsultanpaten', 'Tidak Melalui'),
            'hak_prioritas'  => $request->input('hak_prioritas', 'Tidak'),
        ]);
       $data = $request->validate([
        'jenis_paten' => ['required','in:Paten,Paten Sederhana'],

        'is_pct' => ['required','in:Ya,Tidak'],
        'nomor_permohonan' => ['required_if:is_pct,Ya','nullable','string','max:100'],

        'judul_invensi' => ['required','string','max:255'],

        'is_pecahan' => ['required','in:Ya,Tidak'],
        'pecahan_paten' => ['required_if:is_pecahan,Ya','nullable','string','max:100'],

        'konsultanpaten' => ['required','in:Melalui,Tidak Melalui'],
        'nama_badan_hukum'       => ['required_if:konsultanpaten,Melalui','nullable','string','max:255'],
        'alamat_badan_hukum'     => ['required_if:konsultanpaten,Melalui','nullable','string','max:255'],
        'nama_konsultan_paten'   => ['required_if:konsultanpaten,Melalui','nullable','string','max:255'],
        'alamat_konsultan_paten' => ['required_if:konsultanpaten,Melalui','nullable','string','max:255'],
        'nomor_konsultan_paten'  => ['required_if:konsultanpaten,Melalui','nullable','string','max:100'],
        'telepon_fax'            => ['required_if:konsultanpaten,Melalui','nullable','string','max:100'],

        'hak_prioritas'   => ['required','in:Ya,Tidak'],
        'negara'          => ['required_if:hak_prioritas,Ya','nullable','string','max:100'],
        'nomor_prioritas' => ['required_if:hak_prioritas,Ya','nullable','string','max:100'],
        'tgl_penerimaan'  => ['required_if:hak_prioritas,Ya','nullable','string','max:20'],

        'jumlah_inventor' => ['required','integer','min:1','max:20'],
        'inventor' => ['required','array'],

        'inventor.nama'            => ['required','array'],
        'inventor.nama.*'          => ['required','string','max:255'],
        'inventor.kewarganegaraan' => ['required','array'],
        'inventor.kewarganegaraan.*' => ['required','string','max:100'],
        'inventor.nip_nim'         => ['required','array'],
        'inventor.nip_nim.*'       => ['required','string','max:30'],
        'inventor.alamat'          => ['required','array'],
        'inventor.alamat.*'        => ['required','string'],
        'inventor.fakultas'        => ['required','array'],
        'inventor.fakultas.*'      => ['required','string','max:255'],
        'inventor.no_hp'           => ['required','array'],
        'inventor.no_hp.*'         => ['required','string','max:30'],
        'inventor.email'           => ['required','array'],
        'inventor.email.*'         => ['required','email','max:255'],
        'inventor.nidn'            => ['nullable','array'],
        'inventor.nidn.*'          => ['nullable','string','max:30'],
        'inventor.status'          => ['required','array'],
        'inventor.status.*'        => ['required','in:Dosen,Mahasiswa'],
        'inventor.kode_pos'        => ['required','array'],
        'inventor.kode_pos.*'      => ['required','string','max:10'],
        'inventor.pekerjaan'       => ['required','array'],
        'inventor.pekerjaan.*'     => ['required','string','max:100'],

        'uraian_halaman'  => ['required','integer','min:1'],
        'klaim_buah'      => ['required','integer','min:1'],
        'abstrak_buah'    => ['required','integer','min:1'],
        'gambar_buah'     => ['required','integer','min:1'],
        'gambar_dari'     => ['required','integer','min:1'],
        'gambar_sampai'   => ['required','integer','gte:gambar_dari'],

        'download_format' => ['required','in:pdf,docx'],
        'lampiran' => ['nullable', 'array'],
        'lampiran.*' => ['in:surat_kuasa,pengalihan,bukti_pemilikan,do_eo,dok_prioritas,dok_pct,jasad_renik,dok_lain'],
        'lampiran_lainnya' => ['nullable', 'string', 'max:1000'],
        ]);

        if (count($data['inventor']['nama'] ?? []) !== (int)$data['jumlah_inventor']) {
        return back()->withErrors([
            'inventor' => 'Jumlah inventor tidak sesuai.'
        ])->withInput();
    }
            if ($refId) {
                session()->put($sessionKey, $data);
            }
            session()->put('hakpaten.isiform', $data);

        if ($action === 'save' && $refId) {
            $formattedInventors = [];
            for ($i = 0; $i < (int)$data['jumlah_inventor']; $i++) {
                $formattedInventors[] = [
                    'nama'            => $data['inventor']['nama'][$i] ?? '',
                    'nip_nim'         => $data['inventor']['nip_nim'][$i] ?? '',
                    'alamat'          => $data['inventor']['alamat'][$i] ?? '',
                    'kode_pos'        => $data['inventor']['kode_pos'][$i] ?? '',
                    'fakultas'        => $data['inventor']['fakultas'][$i] ?? '',
                    'no_hp'           => $data['inventor']['no_hp'][$i] ?? '',
                    'email'           => $data['inventor']['email'][$i] ?? '',
                    'status'          => $data['inventor']['status'][$i] ?? '',
                    'nidn'            => $data['inventor']['nidn'][$i] ?? '',
                    'pekerjaan'       => $data['inventor']['pekerjaan'][$i] ?? '',
                    'kewarganegaraan' => $data['inventor']['kewarganegaraan'][$i] ?? '',
                ];
            }

            DB::table('paten_verifs')->where('id', $refId)->update([
                'jenis_paten' => $data['jenis_paten'],
                'judul_paten' => $data['judul_invensi'],
                'inventors'   => json_encode($formattedInventors),
                'updated_at'  => now(),
            ]);

            return response()->json(['ok' => true]);
        }

        if ($action === 'next') return response()->json(['ok' => true]);

        return $this->generateDocument($data);
    }

    private function generateDocument($data)
    {
        $templateObjectPath = 'Form Daftar Paten (2025).docx';

        if (!Storage::disk('s3')->exists($templateObjectPath)) {
            abort(500, 'Template tidak ditemukan di bucket: ' . $templateObjectPath);
        }

        $templatePath = tempnam(sys_get_temp_dir(), 'template_paten_') . '.docx';

        file_put_contents(
            $templatePath,
            Storage::disk('s3')->get($templateObjectPath)
        );

        $tp = new TemplateProcessor($templatePath);

        // Helper functions
        $coret = function (string $text): string {
            $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
            return implode('', array_map(fn($c) => $c . "\u{0336}", $chars));
        };

        $val = function ($v): string {
            $s = trim((string)($v ?? ''));
            return $s === '' ? '-' : $s;
        };

        // ===== 1) Inven & Konsultan =====
        $isPaten = $data['jenis_paten'] === 'Paten';
        $tp->setValue('paten_normal', $isPaten ? 'Paten' : $coret('Paten'));
        $tp->setValue('paten_strike', $isPaten ? $coret('Paten Sederhana') : 'Paten Sederhana');

        $isMelalui = $data['konsultanpaten'] === 'Melalui';
        $tp->setValue('konsultan_normal', $isMelalui ? 'melalui' : $coret('melalui'));
        $tp->setValue('konsultan_strike', $isMelalui ? $coret('tidak melalui') : 'tidak melalui');

        $hak = ($data['hak_prioritas'] ?? 'Tidak') === 'Ya';
        $tp->setValue('hak_normal', $hak ? 'dengan' : $coret('dengan'));
        $tp->setValue('hak_strike', $hak ? $coret('tidak dengan') : 'tidak dengan');

        // ===== 2) Konsultan Data=====
        $tp->setValue('nama_badan_hukum',       $isMelalui ? $val($data['nama_badan_hukum'] ?? '') : '-');
        $tp->setValue('alamat_badan_hukum',     $isMelalui ? $val($data['alamat_badan_hukum'] ?? '') : '-');
        $tp->setValue('nama_konsultan_paten',   $isMelalui ? $val($data['nama_konsultan_paten'] ?? '') : '-');
        $tp->setValue('alamat_konsultan_paten', $isMelalui ? $val($data['alamat_konsultan_paten'] ?? '') : '-');
        $tp->setValue('nomor_konsultan_paten',  $isMelalui ? $val($data['nomor_konsultan_paten'] ?? '') : '-');
        $tp->setValue('telepon_fax',            $isMelalui ? $val($data['telepon_fax'] ?? '') : '-');

        // ===== 3) Others =====
        $tp->setValue('nomor_permohonan', $val($data['nomor_permohonan'] ?? ''));
        $tp->setValue('judul_invensi',    $val($data['judul_invensi'] ?? ''));
        $tp->setValue('pecahan_paten',    $val($data['pecahan_paten'] ?? ''));
        $tp->setValue('negara',           $hak ? $val($data['negara'] ?? '') : '-');
        $tp->setValue('nomor_prioritas',  $hak ? $val($data['nomor_prioritas'] ?? '') : '-');
        $tp->setValue('tgl_penerimaan',   $hak ? $val($data['tgl_penerimaan'] ?? '') : '-');

        // ===== 4) Inventor list =====
        $inventorInput = '';
        for ($i=0; $i < (int)$data['jumlah_inventor']; $i++) {
            $nama = trim($data['inventor']['nama'][$i] ?? '');
            $wn   = trim($data['inventor']['kewarganegaraan'][$i] ?? 'Indonesia');
            $inventorInput .= ($i+1).". {$nama} ({$wn})\n";
        }
        $tp->setValue('inventor_input', trim($inventorInput) ?: '-');

        // Lampiran invensi
        $tp->setValue('uraian_halaman', (string) $data['uraian_halaman']);
        $tp->setValue('klaim_buah',     (string) $data['klaim_buah']);
        $tp->setValue('abstrak_buah',   (string) $data['abstrak_buah']);
        $tp->setValue('gambar_buah',    (string) $data['gambar_buah']);
        $tp->setValue('gambar_dari',    (string) $data['gambar_dari']);
        $tp->setValue('gambar_sampai',  (string) $data['gambar_sampai']);


        // Output
        $out = tempnam(sys_get_temp_dir(), 'paten_') . '.docx';
        $tp->saveAs($out);

        if ($data['download_format'] === 'docx') {
            return response()->download($out, 'Form Paten.docx')->deleteFileAfterSend(true);
        }

        // === Convert DOCX -> PDF
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
            ->download($pdfPath, 'Form Daftar Paten (2025).pdf')
            ->deleteFileAfterSend(true);

        return redirect()->route('patenverif.datadiri');
    }
}