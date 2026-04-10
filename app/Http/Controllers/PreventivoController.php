<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Preventive;

class PreventivoController extends Controller
{
    public function show($cod_alfa)
    {
        $preventivo = Preventive::where('cod_alfa', $cod_alfa)->firstOrFail();
        return view('preventivo.preventivo_web', [
            'preventivo' => $preventivo->toPdfArray(),
        ]);
    }
}
