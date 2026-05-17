<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\DB;

class GoogleSheetService
{
    protected $service;
    protected $spreadsheetCiptaId;
    protected $spreadsheetPatenId;

    public function __construct()
    {
        $client = new Client();

        $client->setApplicationName('Laravel Google Sheets');
        $client->setScopes([Sheets::SPREADSHEETS]);

        $credentialPath = base_path(env('GOOGLE_SHEETS_CREDENTIALS'));


        if (!file_exists($credentialPath)) {
            throw new \Exception("File credentials Google Sheets tidak ditemukan: " . $credentialPath);
        }

        $client->setAuthConfig($credentialPath);

        $this->service = new Sheets($client);

$this->spreadsheetCiptaId = env('GOOGLE_SHEET_CIPTA_ID');
$this->spreadsheetPatenId = env('GOOGLE_SHEET_PATEN_ID');
    }

    public function kirim($verif)
    {
        $revisiLatest = DB::table('revisions')
            ->where('type', 'cipta')
            ->where('ref_id', $verif->id)
            ->where('from_role', 'pemohon')
            ->where('state', 'submitted')
            ->orderByDesc('id')
            ->get()
            ->groupBy('doc_key');

        $getRev = function ($key) use ($revisiLatest) {
            return ($revisiLatest[$key] ?? collect())->first();
        };

        $linkCiptaan = $getRev('link_ciptaan');
        $linkCiptaanValue = $linkCiptaan?->pemohon_text ?: $verif->link_ciptaan;

        $suratPermohonanUrl = (string) ($verif->surat_permohonan_drive_url ?? '');
        $suratPernyataanUrl = (string) ($verif->surat_pernyataan_drive_url ?? '');
        $suratPengalihanUrl = (string) ($verif->surat_pengalihan_drive_url ?? '');
        $tandaTerimaUrl     = (string) ($verif->tanda_terima_drive_url ?? '');
        $scanKtpUrl         = (string) ($verif->scan_ktp_drive_url ?? '');
        $hasilCiptaanUrl    = (string) ($verif->hasil_ciptaan_drive_url ?? '');

        $inventors = is_string($verif->inventors)
            ? json_decode($verif->inventors, true) ?? []
            : ($verif->inventors ?? []);

        $nama     = collect($inventors)->pluck('nama')->join(', ');
        $nipNim   = collect($inventors)->pluck('nip_nim')->join(', ');
        $fakultas = collect($inventors)->pluck('fakultas')->join(', ');
        $email    = collect($inventors)->pluck('email')->join(', ');
        $noHp     = collect($inventors)->pluck('no_hp')->join(', ');

        $values = [[
            now()->timezone('Asia/Jakarta')->format('n/j/Y H:i:s'),
            (string) $verif->jenis_cipta,
            (string) $verif->judul_cipta,
            (string) $nama,
            (string) $nipNim,
            (string) $fakultas,
            (string) $noHp,
            (string) $email,
            (string) $verif->nilai_perolehan,
            (string) $verif->sumber_dana,
            (string) $verif->skema_penelitian,
            $suratPermohonanUrl,
            $suratPernyataanUrl,
            $suratPengalihanUrl,
            $tandaTerimaUrl,
            $scanKtpUrl,
            $hasilCiptaanUrl,
            (string) $linkCiptaanValue,
        ]];

        $body = new ValueRange([
            'majorDimension' => 'ROWS',
            'values' => $values,
        ]);

        $sheetName = "Form Responses 1";
        $range = "'$sheetName'!A:R";

        return $this->service->spreadsheets_values->append(
            $this->spreadsheetCiptaId,
            $range,
            $body,
            [
                'valueInputOption' => 'USER_ENTERED',
                'insertDataOption' => 'INSERT_ROWS'
            ]
        );
    }

    public function kirimPaten($verif)
    {
        $revisiLatest = DB::table('revisions')
            ->where('type', 'paten')
            ->where('ref_id', $verif->id)
            ->where('from_role', 'pemohon')
            ->where('state', 'submitted')
            ->orderByDesc('id')
            ->get()
            ->groupBy('doc_key');

        $getRev = function ($key) use ($revisiLatest) {
            return ($revisiLatest[$key] ?? collect())->first();
        };

        $draftPaten       = $getRev('draft_paten');
        $formPermohonan   = $getRev('form_permohonan');
        $suratKepemilikan = $getRev('surat_kepemilikan');
        $suratPengalihan  = $getRev('surat_pengalihan');
        $scanKtp          = $getRev('scan_ktp');
        $gambarPrototipe  = $getRev('gambar_prototipe');

        

        $inventors = is_string($verif->inventors)
            ? json_decode($verif->inventors, true) ?? []
            : ($verif->inventors ?? []);

        $nama = collect($inventors)->pluck('nama')->join(', ') ?: (string) $verif->nama_pencipta;
        $nipNim = collect($inventors)->pluck('nip_nim')->join(', ') ?: (string) $verif->nip_nim;
        $fakultas = collect($inventors)->pluck('fakultas')->join(', ') ?: (string) $verif->fakultas;
        $email = collect($inventors)->pluck('email')->join(', ') ?: (string) $verif->email;
        $noHp = collect($inventors)->pluck('no_hp')->join(', ') ?: (string) $verif->no_hp;

        $draftPatenUrl       = $verif->draft_paten_drive_url ?? '';
$formPermohonanUrl   = $verif->form_permohonan_drive_url ?? '';
$suratKepemilikanUrl = $verif->surat_kepemilikan_drive_url ?? '';
$suratPengalihanUrl  = $verif->surat_pengalihan_drive_url ?? '';
$scanKtpUrl          = $verif->scan_ktp_drive_url ?? '';
$gambarPrototipeUrl  = $verif->gambar_prototipe_drive_url ?? '';

$values = [[
    now()->timezone('Asia/Jakarta')->format('n/j/Y H:i:s'),
    (string) $verif->jenis_paten,
    (string) $verif->judul_paten,
    (string) $nama,
    (string) $nipNim,
    (string) $fakultas,
    (string) $noHp,
    (string) $email,
    (string) $verif->prototipe,
    (string) $verif->nilai_perolehan,
    (string) $verif->sumber_dana,
    (string) $verif->skema_penelitian,

    $draftPatenUrl,
    $formPermohonanUrl,
    $suratKepemilikanUrl,
    $suratPengalihanUrl,
    $scanKtpUrl,

    '',

    $gambarPrototipeUrl,

    (string) $verif->deskripsi_singkat_prototipe,
]];

        $body = new ValueRange([
            'majorDimension' => 'ROWS',
            'values' => $values,
        ]);

        return $this->service->spreadsheets_values->append(
            $this->spreadsheetPatenId,
            "'Form Responses 1'!A:T",
            $body,
            [
                'valueInputOption' => 'USER_ENTERED',
                'insertDataOption' => 'INSERT_ROWS'
            ]
        );
    }
}