<?php

namespace App\Console\Commands;

use App\Models\Preventive;
use App\PreventiveStatus;
use Illuminate\Console\Command;

class SendExpiryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-expiry-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dataScadenza = now()->addDays(3)->format('Y-m-d');

        $preventiviInScadenza = Preventive::where('date_expiration', $dataScadenza)
            ->where('status', '=', PreventiveStatus::IN_ATTESA)
            ->get();
        
         if ($preventiviInScadenza->isEmpty()) {
            $this->info('Nessun preventivo in scadenza per oggi.');
            return Command::SUCCESS;
        }

        foreach ($preventiviInScadenza as $preventivo) {
            \Mail::to($preventivo->email_cliente)->send(new \App\Mail\PreventiveReminderMail($preventivo));
            $this->info("Promemoria inviato per il preventivo ID: {$preventivo->id} - Cliente: {$preventivo->email_cliente}");
        }
    }
}
