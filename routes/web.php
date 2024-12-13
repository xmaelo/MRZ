<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScanControllerAPI;

Route::get('/', function () {
    return view('welcome');
});

// Routes Web
Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
Route::post('/scan/perform', [ScanController::class, 'scan'])->name('scan.perform');
Route::post('/scan/import', [ScanController::class, 'import'])->name('scan.import');
Route::post('/scan/extract-mrz', [ScanController::class, 'extractMRZ'])->name('scan.extractMRZ');
Route::post('/scan/extract-mrz-scan', [ScanController::class, 'extractMRZscan'])->name('scan.extractMRZscan');

// Routes API RESTful
/*
Route::prefix('api/scan')->group(function () {
    // Liste des scans
    Route::get('/', [ScanControllerAPI::class, 'index'])->name('scan.api.index');  // Nom modifié ici

    // Importation d'un fichier via API
    Route::post('/import', [ScanControllerAPI::class, 'import'])->name('scan.api.import');  // Nom modifié ici
    
    // Scan d'un fichier
    Route::post('/scan', [ScanControllerAPI::class, 'scan'])->name('scan.api.perform');  // Nom modifié ici

    // Extraction de la MRZ
    Route::post('/extract-mrz', [ScanControllerAPI::class, 'extractMRZ'])->name('scan.api.extractMRZ');  // Nom modifié ici

    // Extraction MRZ depuis un scan
    Route::post('/extract-mrz-scan', [ScanControllerAPI::class, 'extractMRZscan'])->name('scan.api.extractMRZscan');  // Nom modifié ici
 });

    */

   
