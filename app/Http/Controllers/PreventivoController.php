<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Preventive;
use Illuminate\Support\Facades\Storage;

class PreventivoController extends Controller
{
    public function show($cod_alfa)
    {
        $preventivo = Preventive::where('cod_alfa', $cod_alfa)->firstOrFail();
        return view('preventivo.preventivo_web', [
            'preventivo' => $preventivo->toPdfArray(),
        ]);
    }

    public function risposta($cod_alfa)
    {
        $preventivo = Preventive::where('cod_alfa', $cod_alfa)->first();

        return view('emails.preventivi.risposta', ['preventivo' => $preventivo]);
    }


    public function salvaRisposta(Request $request, $cod_alfa)
    {
        $preventivo = Preventive::where('cod_alfa', $cod_alfa)->first();

        $validated = $request->validate([
            'stato' => 'required|string',  //name input in html
            'stato_altro_testo' => 'nullable|string|max:1000' //name input in html
        ]);

        $preventivo->stato = $validated['stato'];

        if ($validated['stato'] === 'altro') {
            $preventivo->stato_altro_testo = $validated['stato_altro_testo'];
        } else {
            $preventivo->stato_altro_testo = '';
        }

        $preventivo->save();

        return redirect()->back()->with('success', 'La Sua risposta è stata inviata con successo.');
    }

    public function showPolizza($cod_alfa)
{
    $preventive = Preventive::where('cod_alfa', $cod_alfa)->firstOrFail();

    // Trova il primo extra service di tipo "Polizza"
    $polizza = $preventive->extra_services()
        ->whereHas('extra_service', function ($q) {
            $q->where('tipo', 'Polizza');
        })
        ->with('extra_service')
        ->first();

    if (!$polizza || empty($polizza->extra_service->allegati)) {
        abort(404, 'Nessuna polizza trovata per questo preventivo.');
    }

    $file = $polizza->extra_service->allegati[0];

    // Se il file è nel disco "public"
    if (Storage::disk('public')->exists($file)) {
        return response()->file(Storage::disk('public')->path($file));
    }

    abort(404, 'File non trovato.');
}


}
