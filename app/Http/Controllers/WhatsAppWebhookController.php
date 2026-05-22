<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        // La parola d'ordine che sceglierai tra poco
        $verifyToken = 'TravelFlow_Secret_2026'; 
        
        if ($request->query('hub_mode') === 'subscribe' && 
            $request->query('hub_verify_token') === $verifyToken) {
            return $request->query('hub_challenge');
        }
        return response('Token non valido', 403);
    }

    public function handle(Request $request)
    {
        $data = $request->all();
    
    // Controlla se ci sono messaggi in arrivo
    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'])) {
        
        $messaggioRicevuto = $data['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'];
        $mittente = $data['entry'][0]['changes'][0]['value']['messages'][0]['from'];

        // Ora scriviamo nel log esattamente il testo che hai scritto
        Log::info("Messaggio da $mittente: " . $messaggioRicevuto);
    }
        return response('EVENT_RECEIVED', 200);
    }
}