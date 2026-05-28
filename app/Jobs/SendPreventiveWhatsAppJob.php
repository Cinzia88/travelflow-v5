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
    public function __construct(public Email $email) {}

    public function handle(WhatsAppService $whatsapp)
    {
        // Qui invii il messaggio. 
        // Assicurati che il tuo WhatsAppService abbia un metodo per i template
        $whatsapp->sendPreventiveMessage(
            $this->email->customer->telefono,
            'hello_world',
            ['nome' => $this->email->customer->nome]
        );
    }
}