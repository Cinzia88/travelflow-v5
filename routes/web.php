<?php

use App\Http\Controllers\PreventivoController;
use App\Models\Preventive;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Spatie\Browsershot\Browsershot;

use Illuminate\Http\Response;

Route::get('/preventivi/{cod_alfa}', [PreventivoController::class, 'show'])->name('preventivo.show');

Route::get('/polizza/{cod_alfa}', [PreventivoController::class, 'showPolizza'])->name('polizza.show');


Route::get('/preventivi/{cod_alfa}/risposta', [PreventivoController::class, 'risposta'])->name('preventivo.risposta');

Route::post('/preventivi/{cod_alfa}/risposta', [PreventivoController::class, 'salvaRisposta'])->name('preventivo.risposta.salva');

Route::get('/preventivi/{preventivo}/pdf', function (Preventive $preventivo) {
ini_set('memory_limit', '512M');   // oppure '-1' per illimitato
set_time_limit(120);   
    $pdf = Pdf::loadView('preventivo.file', [
        'preventivo' => $preventivo->toPdfArray(),
    ]);


    // Puoi scaricare direttamente il file
    return $pdf->download("preventivo_{$preventivo->numero}.pdf");


})->name('preventivi.pdf');

// Route per generare PDF con Browsershot (puoi testare questa versione se hai problemi con DomPDF)


/* Route::get('/preventivi/{preventivo}/pdf', function (Preventive $preventivo) {
    $html = view('preventivo.preventivo_web', [
        'preventivo' => $preventivo->toPdfArray(),
    ])->render();

    // Use the Browsershot class directly
    $pdf = Browsershot::html($html)
        ->format('A4')
        ->timeout(180)
        ->waitUntilNetworkIdle()
        ->noSandbox()
        ->pdf();

    return response($pdf)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', "attachment; filename=\"preventivo_{$preventivo->id}.pdf\"");
})->name('preventivi.pdf'); */
