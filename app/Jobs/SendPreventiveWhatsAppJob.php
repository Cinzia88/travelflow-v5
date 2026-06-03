<?php

namespace App\Jobs;

use App\Models\Email;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPreventiveWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Passiamo l'oggetto Email al costruttore
    public function __construct(public Email $email)
    {
    }

    public function handle(WhatsAppService $whatsapp)
    {
        // Ricarichiamo il customer per essere sicuri di avere il telefono
        $this->email->load('customer');

        if (!$this->email->customer || !$this->email->customer->telefono) {
            \Log::error("WhatsApp non inviato: numero telefono mancante per Email ID: {$this->email->id}");
            return;
        }

        $whatsapp->sendPreventiveMessage(
            $this->email->customer->telefono,
            'hello_world', // Verifica che il template esista su WhatsApp/Brevo
            ['nome' => $this->email->customer->nome]
        );
    }
}