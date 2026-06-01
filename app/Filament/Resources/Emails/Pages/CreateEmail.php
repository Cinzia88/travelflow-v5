<?php

namespace App\Filament\Resources\Emails\Pages;

use App\Filament\Resources\Emails\EmailResource;
use App\Mail\MultiplePreventives;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class CreateEmail extends CreateRecord
{
    protected static string $resource = EmailResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Crea il record normalmente
        $record = parent::handleRecordCreation($data);

        // 2. Invio SOLO se NON è una bozza
        if (!$record->is_draft) {

            // Invio Email
            Mail::to($record->email_cliente)
                ->cc($this->extractCcs($record))
                ->queue(new MultiplePreventives($record));

            // Invio WhatsApp
            if ($record->customer?->telefono) {
                \App\Jobs\SendPreventiveWhatsAppJob::dispatch($record);
            }
        }

        return $record;
    }
}
