<?php

use App\Http\Controllers\CeisaSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pusat notifikasi DJBC (Respon / Formulir / Informasi)
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');

    // Monitoring Manifes (BC 1.1) — kedatangan/keberangkatan sarana pengangkut
    Route::get('/manifes', [ManifestController::class, 'index'])->name('manifests.index');
    Route::post('/manifes/sync', [ManifestController::class, 'sync'])->name('manifests.sync');

    // Daftar dokumen lengkap (filter, search, jalur)
    Route::get('/daftar-dokumen', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/daftar-dokumen/export', [DocumentController::class, 'export'])->name('documents.export');

    // Dokumen CEISA
    Route::get('/dokumen/buat', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/dokumen/submit', [DocumentController::class, 'store'])->name('documents.store');
    Route::post('/dokumen/sync', [DocumentController::class, 'sync'])->name('documents.sync');

    // Arsip / rekam manual dokumen lama (PIB/PEB dari portal DJBC)
    Route::get('/dokumen/arsip', [DocumentController::class, 'archiveCreate'])->name('documents.archive.create');
    Route::post('/dokumen/arsip', [DocumentController::class, 'archiveStore'])->name('documents.archive.store');
    Route::post('/dokumen/import', [DocumentController::class, 'import'])->name('documents.import');
    Route::get('/dokumen/lookup', [DocumentController::class, 'lookup'])->name('documents.lookup');
    Route::post('/dokumen/lookup', [DocumentController::class, 'lookupSearch'])->name('documents.lookup.search');
    Route::get('/dokumen/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/dokumen/{document}/ubah', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/dokumen/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::put('/dokumen/{document}/arsip', [DocumentController::class, 'updateArchive'])->name('documents.archive.update');
    Route::delete('/dokumen/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('/dokumen/{document}/submit', [DocumentController::class, 'submit'])->name('documents.submit');
    Route::post('/dokumen/{document}/kirim-pembetulan', [DocumentController::class, 'submitRevision'])->name('documents.submit-revision');
    Route::post('/dokumen/{document}/perbarui-status', [DocumentController::class, 'refreshStatus'])->name('documents.refresh-status');
    Route::post('/dokumen/{document}/duplikasi', [DocumentController::class, 'duplicate'])->name('documents.duplicate');
    Route::post('/dokumen/{document}/validasi-ai', [DocumentController::class, 'validateAi'])->name('documents.validate');
    Route::get('/dokumen/{document}/download-respon', [DocumentController::class, 'downloadRespon'])->name('documents.download-respon');
    Route::get('/dokumen/{document}/cetak-formulir', [DocumentController::class, 'cetakFormulir'])->name('documents.cetak-formulir');
    Route::get('/dokumen/{document}/detail-v2', [DocumentController::class, 'detailV2'])->name('documents.detail-v2');
    Route::get('/dokumen/{document}/download-billing', [DocumentController::class, 'downloadBilling'])->name('documents.download-billing');

    // Pengaturan kredensial CEISA
    Route::get('/settings/ceisa', [CeisaSettingController::class, 'edit'])->name('settings.ceisa.edit');
    Route::post('/settings/ceisa', [CeisaSettingController::class, 'update'])->name('settings.ceisa.update');
    Route::post('/settings/ceisa/test', [CeisaSettingController::class, 'test'])->name('settings.ceisa.test');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
