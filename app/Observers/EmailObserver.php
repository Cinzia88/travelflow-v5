<?php

namespace App\Observers;

use App\Mail\MultiplePreventives;
use App\Models\Email;
use Illuminate\Support\Facades\Mail;
use function PHPUnit\Framework\isArray;

class EmailObserver
{
    /**
     * Handle the Email "created" event.
     */
    public function created(Email $email): void
    {
        // 1. Invio Email
        \Illuminate\Support\Facades\Log::info('Observer scattato per email ID: ' . $email->id);
        $ccs = [];

            if (isArray($email->email_cc)) {
                foreach ($email->email_cc as $item) {
                    if (isset($item['email_cc'])) {
                        $ccs[] = $item['email_cc'];
                    }
                }
            } // (la tua logica per estrarre le email)
        Mail::to($email->email_cliente)->cc($ccs)->queue(new MultiplePreventives($email));

        // 2. Invio WhatsApp (Dispatch del Job)
        if ($email->customer?->telefono) {
            \App\Jobs\SendPreventiveWhatsAppJob::dispatch($email);
        }
        \Illuminate\Support\Facades\Log::info('Il Dispatch per WhatsApp è stato chiamato.');
    }
    /**
     * Handle the Email "updated" event.
     */
    public function updated(Email $email): void
    {
        //
    }

    /**
     * Handle the Email "deleted" event.
     */
    public function deleted(Email $email): void
    {
        //
    }

    /**
     * Handle the Email "restored" event.
     */
    public function restored(Email $email): void
    {
        //
    }

    /**
     * Handle the Email "force deleted" event.
     */
    public function forceDeleted(Email $email): void
    {
        //
    }
}
