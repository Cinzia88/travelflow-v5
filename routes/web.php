<?php

use App\Http\Controllers\PreventivoController;
use Illuminate\Support\Facades\Route;

Route::get('/preventivi/{cod_alfa}', [PreventivoController::class, 'show'])->name('preventivo.show');
