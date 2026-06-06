<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('paten_verifs', function (Blueprint $table) {
            $table->text('draft_paten_drive_url')->nullable();
            $table->text('form_permohonan_drive_url')->nullable();
            $table->text('surat_kepemilikan_drive_url')->nullable();
            $table->text('surat_pengalihan_drive_url')->nullable();
            $table->text('scan_ktp_drive_url')->nullable();
            $table->text('gambar_prototipe_drive_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paten_verifs', function (Blueprint $table) {
            $table->dropColumn([
                'draft_paten_drive_url',
                'form_permohonan_drive_url',
                'surat_kepemilikan_drive_url',
                'surat_pengalihan_drive_url',
                'scan_ktp_drive_url',
                'gambar_prototipe_drive_url',
            ]);
        });
    }
};