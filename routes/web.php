<?php

use App\Http\Controllers\CeisaSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Dokumen CEISA
    Route::get('/dokumen/buat', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/dokumen/submit', [DocumentController::class, 'store'])->name('documents.store');

    // Arsip / rekam manual dokumen lama (PIB/PEB dari portal DJBC)
    Route::get('/dokumen/arsip', [DocumentController::class, 'archiveCreate'])->name('documents.archive.create');
    Route::post('/dokumen/arsip', [DocumentController::class, 'archiveStore'])->name('documents.archive.store');
    Route::post('/dokumen/import', [DocumentController::class, 'import'])->name('documents.import');
    Route::get('/dokumen/lookup', [DocumentController::class, 'lookup'])->name('documents.lookup');
    Route::post('/dokumen/lookup', [DocumentController::class, 'lookupSearch'])->name('documents.lookup.search');
    Route::get('/dokumen/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::post('/dokumen/{document}/submit', [DocumentController::class, 'submit'])->name('documents.submit');

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
