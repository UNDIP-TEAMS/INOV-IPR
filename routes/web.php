<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminDashboardController;

use App\Http\Controllers\PatenController;
use App\Http\Controllers\PatenVerifController;
use App\Http\Controllers\HakCiptaController;

use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\FileUploadCiptaController;

use App\Http\Controllers\HakCiptaSubmitController;
use App\Http\Controllers\HakPatenSubmitController;
use App\Http\Controllers\HakPatenSubmit2Controller;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\IsiformController;
use App\Http\Controllers\InvensiController;
use App\Http\Controllers\PengalihanHakController;
use App\Http\Controllers\SkemaController;
use App\Http\Controllers\FormPendaftaranCiptaanController;
use App\Http\Controllers\HakCiptaVerifController;
use App\Http\Controllers\PernyataanCiptaController;
use App\Http\Controllers\PengalihanHakCiptaController;
use App\Http\Controllers\FormulirMasterController;
use App\Http\Controllers\DuplicateIsiFormController;
use App\Http\Controllers\DuplicateFormPendaftaranCiptaanController;
use App\Http\Controllers\DuplicatePernyataanCiptaController;
use App\Http\Controllers\DuplicatePengalihanHakCiptaController;
use App\Http\Controllers\DuplicateInvensiController;
use App\Http\Controllers\DuplicatePengalihanHakController;
use App\Http\Controllers\DuplicateSkemaController;
/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('admin.login.form');
    Route::post('/login', [AuthController::class, 'login'])->name('admin.login');

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::post('/password', [AuthController::class, 'updatePassword'])->name('admin.password.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::put('/paten/{id}/status', [AdminDashboardController::class, 'updateStatusPaten'])->name('admin.paten.updateStatus');
    Route::put('/cipta/{id}/status', [AdminDashboardController::class, 'updateStatusCipta'])->name('admin.cipta.updateStatus');

    Route::delete('/paten/{id}', [AdminDashboardController::class, 'destroyPaten'])->name('admin.paten.destroy');
    Route::delete('/cipta/{id}', [AdminDashboardController::class, 'destroyCipta'])->name('admin.cipta.destroy');

    Route::put('/status/{type}/{id}', [AdminDashboardController::class, 'updateStatusVerifikasi'])->name('admin.status.update');
    Route::post('/status/{type}/{id}/upload-sertifikat', [AdminDashboardController::class, 'uploadSertifikatVerifikasi'])->name('admin.status.uploadSertifikat');
    Route::post('/status/{type}/{id}/resend-email', [AdminDashboardController::class, 'resendEmail'])->name('admin.status.resendEmail');

    Route::post('/verifikasi-dokumen/{type}/{id}/set', [AdminDashboardController::class, 'setVerifikasiDokumen'])->name('admin.verifikasi_dokumen.set');
    Route::post('/verifikasi-dokumen/{type}/{id}/send-revisi', [AdminDashboardController::class, 'sendRevisiEmail'])->name('admin.verifikasi_dokumen.sendRevisi');

    Route::get('/paten/{id}/detail', [AdminDashboardController::class, 'detailPaten'])
    ->name('admin.paten.detail');

    Route::get('/cipta/{id}/detail', [AdminDashboardController::class, 'detailCipta'])
        ->name('admin.cipta.detail');
});

Route::post(
  '/admin/verifikasi-dokumen/{type}/{id}/approve',
  [AdminDashboardController::class, 'approveAjax']
)->name('admin.verifikasi_dokumen.approve');


Route::get('/debug-mail', function () {
    return [
        'mailer'   => config('mail.default'),
        'host'     => config('mail.mailers.smtp.host'),
        'port'     => config('mail.mailers.smtp.port'),
        'username' => config('mail.mailers.smtp.username'),
        'from'     => config('mail.from.address'),
    ];
});

Route::get('/admin/verifikasi-dokumen/{type}/{id}/wa-links', [AdminDashboardController::class, 'getWaLinks'])
    ->name('admin.verifikasi_dokumen.waLinks');
/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (Landing)
| PEMOHON GENERATE AKUN
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\PemohonAuthController;

Route::post('/login', [PemohonAuthController::class, 'login'])
    ->name('pemohon.login');

Route::post('/logout', [PemohonAuthController::class, 'logout'])
    ->name('pemohon.logout');

Route::get('/pemohon/login', [PemohonAuthController::class, 'showLogin'])
    ->name('pemohon.login.form');

Route::get('/pemohon/claim/{kode}', [PemohonAuthController::class, 'claim'])
    ->name('pemohon.claim');

Route::get('/pemohon/gantipassword', [PemohonAuthController::class, 'showPreChangePassword'])
    ->name('pemohon.prechange.form');

Route::post('/pemohon/gantipassword', [PemohonAuthController::class, 'storePreChangePassword'])
    ->name('pemohon.prechange.store');

Route::post('/pemohon/change-password', [PemohonAuthController::class, 'changePassword'])
  ->name('pemohon.change_password');

// ====== DASHBOARD PEMOHON (PAKAI PemohonDashboardController) ======
use App\Http\Controllers\PemohonDashboardController;

Route::middleware('auth:pemohon')->prefix('pemohon')->name('pemohon.')->group(function () {
    Route::get('/dashboard', [PemohonDashboardController::class, 'index'])->name('dashboard');
});

Route::get('/pemohon/tanda-terima/download/{type}/{ref}', [PemohonDashboardController::class, 'downloadTandaTerima'])
    ->name('pemohon.tanda_terima.download');

Route::post('/revisi/{type}/{id}', [AdminDashboardController::class, 'setRevisi'])
  ->name('admin.revisi.set');

Route::post('/admin/revisi/read/{id}', [AdminDashboardController::class, 'markRevisionRead'])
    ->name('admin.revisi.read');

Route::post('/pemohon/revisi/{id}/upload', [PemohonDashboardController::class, 'uploadRevisi'])
    ->name('pemohon.uploadRevisi');


Route::get('/paten/pendaftaran', function () {
    return view('hakpaten.hakpaten');
})->name('paten.pendaftaran');

Route::get('/hak-cipta/pendaftaran', function () {
    return view('hakcipta.hakcipta');
})->name('hakcipta.pendaftaran');


Route::prefix('admin')->group(function () {

    Route::get('/paten/export-excel', [AdminDashboardController::class, 'exportPatenExcel'])
        ->name('admin.paten.export_excel');

    Route::get('/paten/export-pdf', [AdminDashboardController::class, 'exportPatenPdf'])
        ->name('admin.paten.export_pdf');
});

Route::get('/paten/export-csv', [AdminDashboardController::class, 'exportPatenCsv'])
    ->name('admin.paten.export_csv');

Route::get('/admin/cipta/export-excel', [AdminDashboardController::class, 'exportCiptaExcel'])
    ->name('admin.cipta.export_excel');

Route::get('/admin/cipta/export-pdf', [AdminDashboardController::class, 'exportCiptaPdf'])
    ->name('admin.cipta.export_pdf');

Route::get('/admin/cipta/export-csv', [AdminDashboardController::class, 'exportCiptaCsv'])
    ->name('admin.cipta.export_csv');

Route::get('/revisi/download/{id}', [AdminDashboardController::class, 'downloadRevisi'])
  ->name('revisi.download');

Route::get('/admin/paten/{id}/doc/{doc_key}/download', [AdminDashboardController::class, 'adminDownloadDocPaten'])
  ->name('admin.paten.doc.download');

Route::get('/admin/cipta/{id}/doc/{doc_key}/download', [AdminDashboardController::class, 'adminDownloadDocCipta'])
  ->name('admin.cipta.doc.download');

  Route::get('/pemohon/dokumen/{type}/{ref}/{key}', [PemohonDashboardController::class, 'downloadDokumenAwal'])
    ->name('pemohon.dokumen.download');


Route::get('/pemohon/paten/edit-deskripsi-singkat', [PemohonDashboardController::class, 'editDeskripsiSingkat'])
    ->name('pemohon.paten.edit_deskripsi');

Route::post('/pemohon/paten/edit-deskripsi-singkat', [PemohonDashboardController::class, 'updateDeskripsiSingkat'])
    ->name('pemohon.paten.update_deskripsi');


    Route::get('/pemohon/pendaftaran/{type}/{ref}', [PemohonDashboardController::class, 'kirimKePendaftaran'])
    ->name('pemohon.pendaftaran.kirim');
/*s
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::view('/', 'test-header')->name('home');


Route::get('/hak-paten', fn () => view('hakpaten.menuhakpaten'))->name('menuhakpaten');

Route::get('/hak-cipta/form', fn () => view('hakcipta.menucipta'))->name('menucipta');
Route::get('/test-header', fn () => view('daftar'))->name('daftar');
Route::get('/hak-paten/form', fn () => view('hakpaten.hakpaten'))->name('hakpaten');

Route::get('/hak-cipta', fn () => view('hakcipta.hakcipta'))->name('hakcipta');

/*
|--------------------------------------------------------------------------
| API ROUTES (JSON) - optional
|--------------------------------------------------------------------------
*/
Route::post('/hak-cipta/store', [HakCiptaController::class, 'store']);
Route::post('/paten/store', [PatenController::class, 'store']);

/*
|--------------------------------------------------------------------------
| HAK PATEN - ISI FORMULIR (separate flow)
|--------------------------------------------------------------------------
*/
Route::prefix('hak-paten')->group(function () {
    Route::view('/draft-paten-isiformulir', 'hakpaten.isiformulir.draftpatenisiformulir')
        ->name('hakpaten.draftpatenisiformulir');

    Route::get('/isi-formulir', fn () => view('hakpaten.isiformulir.isiformulir'))
        ->name('hakpaten.isiformulir');

    Route::get('/invensi', fn () => view('hakpaten.isiformulir.invensiformulir'))
        ->name('hakpaten.invensiformulir');

    Route::get('/pengalihan', fn () => view('hakpaten.isiformulir.pengalihanhakformulir'))
        ->name('hakpaten.pengalihanhakformulir');

    Route::get('/peralihan', fn () => view('hakpaten.isiformulir.peralihankeverif'))
        ->name('hakpaten.peralihankeverif');
});

Route::post('/isiformpaten', [IsiformController::class, 'store'])->name('isiform.store');
Route::post('/invensipaten', [InvensiController::class, 'store'])->name('invensi.store');
Route::post('/pengalihanpaten', [PengalihanHakController::class, 'store'])->name('pengalihanhak.store');


/*
|--------------------------------------------------------------------------
| PATEN VERIF FLOW (verifikasi dokumen)
|--------------------------------------------------------------------------
*/
Route::view('/hak-paten/data-diri', 'hakpaten.verifikasidokumen.datadiri')->name('patenverif.datadiri');

Route::get('/patenverif/{verif}/all', 
    [PatenVerifController::class, 'all'])
    ->name('patenverif.all');

Route::get('/patenverif/{verif}/upload-semua', [PatenVerifController::class, 'uploadSemua'])
  ->name('patenverif.upload.semua');


// Step 1 submit -> create verif record
Route::post('/paten-verif', [PatenVerifController::class, 'start'])->name('patenverif.start');

// Step pages (GET)
Route::view('/hak-paten/data-diri', 'hakpaten.verifikasidokumen.datadiri')
  ->name('patenverif.datadiri');
Route::get('/paten-verif/{verif}/draft', [PatenVerifController::class, 'draft'])->name('patenverif.draft');
Route::get('/paten-verif/{verif}/formpermohonan', [PatenVerifController::class, 'formpermohonan'])->name('patenverif.formpermohonan');
Route::get('/paten-verif/{verif}/invensi', [PatenVerifController::class, 'invensi'])->name('patenverif.invensi');
Route::get('/paten-verif/{verif}/pengalihanhak', [PatenVerifController::class, 'pengalihanhak'])->name('patenverif.pengalihanhak');
Route::get('/paten-verif/{verif}/scanktp', [PatenVerifController::class, 'scanktp'])->name('patenverif.scanktp');
Route::get('/paten-verif/{verif}/uploadgambar', [PatenVerifController::class, 'uploadgambar'])->name('patenverif.uploadgambar');
Route::get('/paten-verif/{verif}/deskripsiprodukverif', [PatenVerifController::class, 'deskripsiprodukverif'])->name('patenverif.deskripsi');
Route::post('/paten-verif/{verif}/submit', [PatenVerifController::class, 'submitFinal'])->name('patenverif.submit.final');

// =========================
// HALAMAN HASIL SUBMIT
// =========================
Route::get('/paten-verif/{verif}/hasil', [PatenVerifController::class, 'hasilSubmit']
)->name('patenverif.hasil');

// Submit actions
Route::post('/paten-verif/{verif}/upload-draft', [PatenVerifController::class, 'uploadDraft'])->name('patenverif.upload.draft');
Route::post('/paten-verif/{verif}/submit-deskripsi', [PatenVerifController::class, 'submitDeskripsi'])->name('patenverif.submit');
Route::post('/paten-verif/{verif}/upload-form', [PatenVerifController::class, 'uploadForm'])
  ->name('patenverif.upload.form');
Route::post('/paten-verif/{verif}/upload-invensi', [PatenVerifController::class, 'uploadInvensi'])
  ->name('patenverif.upload.invensi');
Route::post('/paten-verif/{verif}/upload-pengalihan', [PatenVerifController::class, 'uploadPengalihan'])
  ->name('patenverif.upload.pengalihan');
Route::post('/paten-verif/{verif}/upload-ktp', [PatenVerifController::class, 'uploadKTP'])
  ->name('patenverif.upload.ktp');
Route::post('/paten-verif/{verif}/upload-gambar', [PatenVerifController::class, 'uploadGambarr'])
  ->name('patenverif.upload.gambar');


// Skema pengembangan
Route::get('/paten-verif/{verif}/skema', [SkemaController::class, 'showVerif'])->name('patenverif.skema.form');
Route::post('/paten-verif/{verif}/skema/download', [SkemaController::class, 'downloadVerif'])->name('patenverif.skema.download');
Route::post('/paten-verif/{verif}/skema/upload', [SkemaController::class, 'uploadVerif'])->name('patenverif.skema.upload');

/*
|--------------------------------------------------------------------------
| HAK PATEN FLOW
|--------------------------------------------------------------------------
*/
// START: bikin row paten + set session paten_id
Route::post('/hak-paten/start', [PatenController::class, 'start'])->name('paten.start');

// STEP PAGES (GET) - ANTI SKIP
Route::middleware('paten.seq')->group(function () {
    Route::get('/hak-paten/draftpaten', fn () => view('hakpaten.draftpaten'))->name('draftpaten');
    Route::get('/hak-paten/formulirpermohonan', fn () => view('hakpaten.formulirpermohonan'))->name('formulirpermohonan');
    Route::get('/hak-paten/kepemilikaninvensi', fn () => view('hakpaten.kepemilikaninvensi'))->name('kepemilikaninvensi');
    Route::get('/hak-paten/pengalihanhak', fn () => view('hakpaten.pengalihanhak'))->name('pengalihanhak');
    Route::get('/hak-paten/scanktp', fn () => view('hakpaten.scanktp'))->name('scanktp');
    Route::get('/hak-paten/tandaterima', fn () => view('hakpaten.tandaterima'))->name('tandaterima');
    Route::get('/hak-paten/uploadgambarprototipe', fn () => view('hakpaten.uploadgambarprototipe'))->name('uploadgambarprototipe');
    Route::get('/hak-paten/deskripsiproduk', fn () => view('hakpaten.deskripsiproduk'))->name('deskripsiproduk');
});

// sukses
Route::get('/hak-paten/sukses', fn () => view('hakpaten.sukses'))->name('hakpaten.sukses');


// PATEN (baru)
Route::get('/hak-paten/{paten}/skema', [SkemaController::class, 'showPaten'])->name('hakpaten.skema.form');
Route::post('/hak-paten/{paten}/skema/download', [SkemaController::class, 'downloadPaten'])->name('hakpaten.skema.download');
Route::post('/hak-paten/{paten}/skema/upload', [SkemaController::class, 'uploadPaten'])->name('hakpaten.skema.upload');

// UPLOAD PER STEP (POST)
Route::post('/hak-paten/upload-draft', [FileUploadController::class, 'draft'])->name('draftpaten.upload');
Route::post('/hak-paten/upload-form', [FileUploadController::class, 'form'])->name('formulirpermohonan.upload');
Route::post('/hak-paten/upload-surat-invensi', [FileUploadController::class, 'suratInvensi'])->name('kepemilikaninvensi.upload');
Route::post('/hak-paten/upload-surat-pengalihan-hak', [FileUploadController::class, 'pengalihanhak'])->name('pengalihanhak.upload');
Route::post('/hak-paten/upload-scanktp', [FileUploadController::class, 'scanKtp'])->name('scanktp.uploadScanKTP');
Route::post('/hak-paten/upload-surat-terima-berkas', [FileUploadController::class, 'tandaTerima'])->name('tandaterima.uploadFormSuratTandaTerimaBerkas');
Route::post('/hak-paten/upload-prototipe', [FileUploadController::class, 'gambarPrototipe'])->name('uploadgambarprototipe.uploadPrototipe');

// SUBMIT FINAL (POST)
Route::post('/hak-paten/submit', [HakPatenSubmit2Controller::class, 'submit'])->name('hakpaten.submit');

// DOWNLOAD TEMPLATE (GET)
Route::get('/hak-paten/download-template-draft', function () {
    $path = public_path('templates/Template Deskripsi Paten.docx');
    if (!File::exists($path)) abort(404, 'File Tidak Tersedia');
    return response()->download($path, 'Template Deskripsi Paten.docx');
})->name('download.template.draftpaten');

Route::get('/hak-paten/download-template-form', function () {
    $path = public_path('templates/Form Daftar Paten (2025).docx');
    if (!File::exists($path)) abort(404, 'File Tidak Tersedia');
    return response()->download($path, 'Form Daftar Paten (2025).docx');
})->name('download.template.formpaten');

Route::get('/hak-paten/download-template-surat-invensi', function () {
    $path = public_path('templates/Surat Pernyataan Kepemilikan Invensi oleh Inventor.docx');
    if (!File::exists($path)) abort(404, 'File Tidak Tersedia');
    return response()->download($path, 'Surat Pernyataan Kepemilikan Invensi oleh Inventor.docx');
})->name('download.template.surat_invensi');

Route::get('/hak-paten/download-template-surat-pengalihan-hak', function () {
    $path = public_path('templates/Surat Pernyataan Pengalihan Hak.docx');
    if (!File::exists($path)) abort(404, 'File Tidak Tersedia');
    return response()->download($path, 'Surat Pernyataan Pengalihan Hak.docx');
})->name('download.template.pengalihan_hak');

Route::get('/hak-paten/download-template-surat-terima-berkas', function () {
    $path = public_path('templates/TANDA_TERIMA_BERKAS_HAKI.pdf');
    if (!File::exists($path)) abort(404, 'File Tidak Tersedia');
    return response()->download($path, 'TANDA_TERIMA_BERKAS_HAKI.pdf');
})->name('download.template.tanda_terima_paten');

Route::post(
    '/hak-paten/form-master',
    [FormulirMasterController::class, 'store']
)->name('formulir.master.store');
/*
|--------------------------------------------------------------------------
| HAK CIPTA FLOW ISI FORM
|--------------------------------------------------------------------------
*/
Route::prefix('hak-cipta')->name('hakcipta.isiform.')->group(function () {
    Route::view('/pendaftaranCiptaan', 'hakcipta.isiform.formpendaftaranciptaan')->name('formpendaftaran');
    Route::view('/suratpernyataan-isiform', 'hakcipta.isiform.suratpernyataan')->name('suratpernyataan');
    Route::view('/pengalihanhakcipta', 'hakcipta.isiform.pengalihanhakcipta')->name('pengalihanhak');
    Route::view('/peralihancipta', 'hakcipta.isiform.peralihanverifcipta')->name('peralihanverifcipta');
});


Route::post('/isiform', [FormPendaftaranCiptaanController::class, 'store'])->name('isiformCipta.store');
Route::post('/pernyataan', [PernyataanCiptaController::class, 'store'])->name('pernyataanCipta.store');
Route::post('/pengalihan', [PengalihanHakCiptaController::class, 'store'])->name('pengalihanhakCipta.store');



/*
|--------------------------------------------------------------------------
| HAK CIPTA FLOW Verif
|--------------------------------------------------------------------------
*/


// STEP 1 - tampilkan form
Route::get('/cipta-verif', [HakCiptaVerifController::class, 'index'])
    ->name('ciptaverif.index');

// STEP 1 - submit form
Route::post('/cipta-verif', [HakCiptaVerifController::class, 'start'])
    ->name('ciptaverif.start');


Route::view('/hak-cipta/data-diri', 'hakcipta.verifikasi.datadiricipta')->name('datadiricipta');

Route::post('/cipta-verif', [HakCiptaVerifController::class, 'start'])->name('ciptaverif.start');


Route::view('/hak-cipta/data-diri', 'hakcipta.verifikasi.datadiricipta')
  ->name('datadiricipta');

  Route::get(
    '/cipta-verif/{verif}/formulir-permohonan',
    [HakCiptaVerifController::class, 'formpermohonan']
)->name('ciptaverif.formulirpermohonan');

  Route::get(
    '/cipta-verif/{verif}/surat-pernyataan',
    [HakCiptaVerifController::class, 'suratpernyataan']
)->name('ciptaverif.suratpernyataan');

  Route::get(
    '/cipta-verif/{verif}/surat-pengalihan',
    [HakCiptaVerifController::class, 'pengalihanhak']
)->name('ciptaverif.suratpengalihan');

Route::get(
  '/cipta-verif/{verif}/scan-ktp',
  [HakCiptaVerifController::class, 'scanktp']
)->name('ciptaverif.scanktp');


Route::get(
    '/cipta-verif/{verif}/hasil-ciptaan',
    [HakCiptaVerifController::class, 'hasilciptaan']
)->name('ciptaverif.hasilciptaan');

Route::get(
    '/cipta-verif/{verif}/link-ciptaan',
    [HakCiptaVerifController::class, 'linkciptaan']
)->name('ciptaverif.linkciptaan');

Route::post('/cipta-verif/{verif}/submit-final',
    [HakCiptaVerifController::class, 'submitFinal']
)->name('ciptaverif.submit.final');

Route::get('/cipta-verif/{verif}/hasil',
    [HakCiptaVerifController::class, 'hasilSubmit']
)->name('ciptaverif.hasil');

Route::get('/cipta-verif/{verif}/upload-semua', [HakCiptaVerifController::class, 'uploadSemua'])
  ->name('cipta-verif.upload.semua');
// Submit actions
// ==============================
// HALAMAN SEMUA UPLOAD
// ==============================
Route::get('/cipta-verif/{verif}/all',
    [HakCiptaVerifController::class, 'all']
)->name('ciptaverif.all');


// ==============================
// UPLOAD (POST)
// ==============================

Route::post('/cipta-verif/{verif}/upload-form',
    [HakCiptaVerifController::class, 'uploadForm']
)->name('ciptaverif.upload.form');

Route::post('/cipta-verif/{verif}/upload-pernyataan',
    [HakCiptaVerifController::class, 'uploadSuratPernyataan']
)->name('ciptaverif.upload.invensi'); // ← sesuai blade lo

Route::post('/cipta-verif/{verif}/upload-pengalihan',
    [HakCiptaVerifController::class, 'uploadSuratPengalihan']
)->name('ciptaverif.upload.pengalihan');

Route::post('/cipta-verif/{verif}/upload-ktp',
    [HakCiptaVerifController::class, 'uploadKTP']
)->name('ciptaverif.upload.scanktp');

Route::post('/cipta-verif/{verif}/upload-hasilciptaan',
    [HakCiptaVerifController::class, 'uploadHasilCiptaan']
)->name('ciptaverif.upload.hasilciptaan');

Route::post('/cipta-verif/{verif}/submit-final',
    [HakCiptaVerifController::class, 'submitFinal']
)->name('ciptaverif.submit.final');

Route::get('/cipta-verif/{verif}/hasil',
    [HakCiptaVerifController::class, 'hasilSubmit']
)->name('ciptaverif.hasil');

  // INI PENDAFTARAN CIPTA
Route::view('/hak-cipta/data-diri-pendaftaran', 'hakcipta.hakcipta')->name('hakciptapendaftaran');
Route::post('/hak-cipta/start', [HakCiptaController::class, 'start'])->name('hakcipta.start');
// Step pages (GET) - anti skip
Route::middleware('cipta.seq')->group(function () {
    Route::get('/hak-cipta/permohonan-pendaftaran', fn () => view('hakcipta.permohonanpendaftaran'))->name('hakcipta.permohonanpendaftaran');
    Route::get('/hak-cipta/suratpernyataan', fn () => view('hakcipta.suratpernyataan'))->name('hakcipta.suratpernyataan');
    Route::get('/hak-cipta/pengalihanhak', fn () => view('hakcipta.pengalihanhak'))->name('hakcipta.pengalihanhak');
    Route::get('/hak-cipta/tandaterima', fn () => view('hakcipta.tandaterima'))->name('hakcipta.tandaterima');
    Route::get('/hak-cipta/scanktp', fn () => view('hakcipta.scanktp'))->name('hakcipta.scanktp');
    Route::get('/hak-cipta/hasilciptaan', fn () => view('hakcipta.hasilciptaan'))->name('hakcipta.hasilciptaan');
    Route::get('/hak-cipta/linkciptaan', fn () => view('hakcipta.linkciptaan'))->name('hakcipta.linkciptaan');

 });

 // Upload per step (POST)
    Route::post('/hak-cipta/upload-permohonan', [FileUploadCiptaController::class, 'suratPermohonan'])->name('hakcipta.permohonanpendaftaran.uploadPendaftaran');
    Route::post('/hak-cipta/upload-pernyataan', [FileUploadCiptaController::class, 'suratPernyataan'])->name('hakcipta.suratpernyataan.uploadPernyataan');
    Route::post('/hak-cipta/upload-pengalihan', [FileUploadCiptaController::class, 'suratPengalihan'])->name('hakcipta.pengalihanhak.uploadPengalihan');
    Route::post('/hak-cipta/upload-scanktp', [FileUploadCiptaController::class, 'scanKtp'])->name('hakcipta.scanktp.uploadScanKTP');
    Route::post('/hak-cipta/upload-tandaterima', [FileUploadCiptaController::class, 'tandaTerima'])->name('hakcipta.tandaterima.uploadTandaTerima');
    Route::post('/hak-cipta/upload-hasilciptaan', [FileUploadCiptaController::class, 'hasilCiptaan'])->name('hakcipta.hasilciptaan.uploadHasilCiptaan');
    Route::post('/hak-cipta/simpan-link', [FileUploadCiptaController::class, 'linkCiptaan'])->name('hakcipta.linkciptaan.store');


// Download template hak cipta
Route::prefix('hak-cipta')->group(function () {
    Route::get('/download-template-permohonan', function () {
        $path = public_path('templates/Permohonan Pendaftaran Ciptaan 2021.docx');
        if (!File::exists($path)) abort(404, 'File Tidak Tersedia');
        return response()->download($path, 'Permohonan Pendaftaran Ciptaan 2021.docx');
    })->name('hakcipta.download.template.permohonan');

    Route::get('/download-template-pernyataan', function () {
        $path = public_path('templates/Surat Pernyataan Hak Cipta 2021.docx');
        if (!File::exists($path)) abort(404, 'File Tidak Tersedia');
        return response()->download($path, 'Surat Pernyataan Hak Cipta 2021.docx');
    })->name('hakcipta.download.template.pernyataan');

    Route::get('/download-template-pengalihan', function () {
        $path = public_path('templates/Surat Pengalihan Hak Cipta 2025.docx');
        if (!File::exists($path)) abort(404, 'File Tidak Tersedia');
        return response()->download($path, 'Surat Pengalihan Hak Cipta 2025.docx');
    })->name('hakcipta.download.template.pengalihan');

    Route::get('/download-template-tandaterima', function () {
        $path = public_path('templates/TANDA_TERIMA_BERKAS_HAKI.pdf');
        if (!File::exists($path)) abort(404, 'File Tidak Tersedia');
        return response()->download($path, 'TANDA_TERIMA_BERKAS_HAKI.pdf');
    })->name('hakcipta.download.template.tandaterima');
});

// SUBMIT FINAL (POST) + sukses
Route::post('/hak-cipta/submit', [HakCiptaSubmitController::class, 'submit'])->name('hakcipta.submit');
Route::get('/hak-cipta/sukses', fn () => view('hakcipta.sukses'))->name('hakcipta.sukses');

/*
|--------------------------------------------------------------------------
| TRACKING
|--------------------------------------------------------------------------
*/
Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking');

/*
|--------------------------------------------------------------------------
| DUPLICATE ISI FORMULIR
|--------------------------------------------------------------------------
*/
Route::get('/pemohon/revisi/edit', [PemohonDashboardController::class, 'editRevisi'])
  ->name('pemohon.revisi.edit');

/*
|--------------------------------------------------------------------------
| DUPLICATE HAK CIPTA - ISI FORMULIR
|--------------------------------------------------------------------------
*/
Route::prefix('dup/hak-cipta')->name('dup.hakcipta.isiform.')->group(function () {
    Route::get('/pendaftaranCiptaan', [DuplicateFormPendaftaranCiptaanController::class, 'index'])
        ->name('formpendaftaran');

    Route::get('/suratpernyataan-isiform', [DuplicatePernyataanCiptaController::class, 'index'])
        ->name('suratpernyataan');

    Route::get('/pengalihanhakcipta', [DuplicatePengalihanHakCiptaController::class, 'index'])
        ->name('pengalihanhak');

    Route::get('/peralihancipta', [DuplicateFormPendaftaranCiptaanController::class, 'peralihan']) 
        ->name('peralihanverifcipta');
});

Route::post('/dup/isiform', [DuplicateFormPendaftaranCiptaanController::class, 'store'])->name('dup.isiformCipta.store');
Route::post('/dup/pernyataan', [DuplicatePernyataanCiptaController::class, 'store'])->name('dup.pernyataanCipta.store');
Route::post('/dup/pengalihan', [DuplicatePengalihanHakCiptaController::class, 'store'])->name('dup.pengalihanhakCipta.store');


 /*
|--------------------------------------------------------------------------
| DUPLICATE HAK PATEN - ISI FORMULIR
|--------------------------------------------------------------------------
*/
Route::prefix('dup/hak-paten')->name('dup.hakpaten.isiformulir.')->group(function () {

    Route::view('/draft', 'isiform.paten.duplicatedraftpatenisiformulir')->name('draft');
    Route::view('/isi-formulir', 'isiform.paten.duplicateisiformulir')->name('isiform');
    Route::view('/invensi', 'isiform.paten.duplicateinvensiformulir')->name('invensi');
    Route::view('/pengalihan', 'isiform.paten.duplicatepengalihanhakformulir')->name('pengalihan');
    Route::view('/peralihan', 'isiform.paten.duplicateperalihankeverif')->name('peralihan');
});

// POST khusus duplicate
Route::post('/dup/isiformpaten', [DuplicateIsiFormController::class, 'store'])->name('dup.isiform.store');
Route::post('/dup/invensipaten', [DuplicateInvensiController::class, 'store'])->name('dup.invensi.store');
Route::post('/dup/pengalihanpaten', [DuplicatePengalihanHakController::class, 'store'])->name('dup.pengalihanhak.store');

// ROUTE KHUSUS DUPLICATE SKEMA TKT 7-9
Route::get('/dup/skema-tkt/{verif}', [DuplicateSkemaController::class, 'showDuplicate'])
    ->name('dup.skema.form');

Route::post('/dup/skema-tkt/{verif}/download', [DuplicateSkemaController::class, 'downloadDuplicate'])
    ->name('dup.skema.download');

Route::post('/dup/skema-tkt/{verif}/upload', [DuplicateSkemaController::class, 'uploadDuplicate'])
    ->name('dup.skema.upload');

// email inventor 1
Route::get('/pemohon/get-owner-email', [PemohonAuthController::class, 'getOwnerEmail']);

//pendaftaran sukses
Route::get('/pemohon/pendaftaran/{type}/{ref}/sukses', [PemohonDashboardController::class, 'pendaftaranSukses'])
    ->name('pemohon.pendaftaran.sukses');

use App\Http\Controllers\GoogleAuthController;

Route::get('/google/auth', [GoogleAuthController::class, 'redirect'])->name('google.auth');
Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

Route::get('/login', function () {
    return redirect('/pemohon/login');
})->name('login');