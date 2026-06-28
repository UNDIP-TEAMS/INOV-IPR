<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatenVerif extends Model
{
    protected $table = 'paten_verifs';

    protected $fillable = [
        // utama
        'no_pendaftaran',
        'jenis_paten',
        'judul_paten',

        // inventors json
        'inventors',
         'skema_tkt_template_path',

        // ringkasan inventor pertama 
        'nama_pencipta',
        'nip_nim',
        'fakultas',
        'no_hp',
        'email',

        // data tambahan
        'prototipe',
        'nilai_perolehan',
        'sumber_dana',
        'skema_penelitian',

        // draft & dokumen (path file)
        'draft_paten',
        'form_permohonan',
        'surat_kepemilikan',
        'surat_pengalihan',
        'scan_ktp',
        'tanda_terima',
        'gambar_prototipe',
        'deskripsi_singkat_prototipe',
        'link_ciptaan',

        // verifikasi
        'status_verif',
        'catatan_verif',
        'draft_paten_drive_url',
        
        'form_permohonan_drive_url',
        'surat_kepemilikan_drive_url',
        'surat_pengalihan_drive_url',
        'scan_ktp_drive_url',
        'gambar_prototipe_drive_url',
    ];

    protected $casts = [
        'inventors' => 'array',
    ];
}