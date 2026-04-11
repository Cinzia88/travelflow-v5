<?php

namespace App\Filament\Resources\PreventiveResource\Pages\Concerns;

use App\Filament\Resources\Preventives\PreventiveResource;
use App\Models\Preventive;
use App\Models\Hotel;
use App\PreventiveStatus;
use Filament\Forms\Components\Livewire;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Filament\Resources; 

trait HandlesDraftSaving
{
    public bool $isDraft = false;



    protected function saveEmailDraft(Preventive $preventivo, array $data): void
{
    // Creiamo un array pulito con SOLO i campi che servono alla tabella 'emails'
    $emailCleanData = [
        'email_template_id' => $data['email_template_id'] ?? null,
        'email_cliente'     => $data['email_cliente'] ?? $preventivo->customer?->email,
        'email_cc'          => collect($data['email_cc'] ?? [])->pluck('email_cc')->filter()->values()->toArray(),
        'corpo_email'       => is_array($data['corpo_email'] ?? null) ? ($data['corpo_email']['html'] ?? '') : ($data['corpo_email'] ?? null),
        'allegati'          => $data['allegati'] ?? [],
        'customer_id'       => $preventivo->customer_id,
        'quote_request_id'  => $preventivo->quote_request_id,
        'sent_by'           => auth()->id(),
        'is_draft'          => true,
    ];

    // Ora cerchiamo la bozza esistente
    $existingDraft = $preventivo->emails()->where('is_draft', true)->first();

    if ($existingDraft) {
        $existingDraft->update($emailCleanData);
    } else {
        $newEmail = \App\Models\Email::create($emailCleanData);
        $preventivo->emails()->attach($newEmail->id);
    }
}

   protected function saveAsDraft()
{
    $this->isDraft = true;
    $this->resetValidation();
// Usiamo getRawState() perché è l'unico modo per vedere i dati 
    // prima che la convalida di Filament lanci l'errore del RichEditor.
    //dd($this->form->getRawState());
    // 1. Prendi tutti i dati dal form
    $data = $this->form->getState();

    DB::transaction(function () use ($data) {
        $preventivo = $this->record ?? new Preventive();
        
        // 2. SEPARAZIONE: Estraiamo i dati dell'email per dopo
        $emailData = [
            'email_template_id' => $data['email_template_id'] ?? null,
            'email_cliente'     => $data['email_cliente'] ?? null,
            'email_cc'          => $data['email_cc'] ?? [],
            'corpo_email'       => $data['corpo_email'] ?? null,
            'allegati'          => $data['allegati'] ?? [],
        ];

        // 3. PULIZIA: Togliamo i campi dell'email dall'array $data
        // così il preventivo non proverà a salvarli nel DB
        unset(
            $data['email_template_id'],
            $data['email_cliente'],
            $data['email_cc'],
            $data['corpo_email'],
            $data['allegati']
        );

        $data['stato'] = PreventiveStatus::BOZZA;

        // 4. SALVATAGGIO PREVENTIVO (ora è pulito e non crasha)
        if ($this->record) {
            $this->record->update($data);
        } else {
            $this->record = Preventive::create($data);
        }

        // 5. SALVATAGGIO RELAZIONI (Hotel, extra, ecc.)
        $this->form->model($this->record)->saveRelationships();
        
        // 6. SALVATAGGIO EMAIL (usiamo l'array filtrato al punto 2)
        //$this->saveEmailDraft($this->record, $emailData);
    });

    Notification::make()->title('Bozza salvata!')->success()->send();

    return redirect()->to(
        \App\Filament\Resources\Preventives\PreventiveResource::getUrl('edit', ['record' => $this->record->id]) . '?draft=1'
    );
}
    protected function processAllFiles(array $data): array
    {
        // File allegati email
        if (!empty($data['allegati'])) {
            $data['allegati'] = $this->processFiles($data['allegati'], 'email_allegati');
        }

        // File hotel
        if (!empty($data['hotel_preventives'])) {
            foreach ($data['hotel_preventives'] as $index => $hotel) {
                if (!empty($hotel['file_fornitore_hotel'])) {
                    $data['hotel_preventives'][$index]['file_fornitore_hotel'] =
                        $this->processFiles($hotel['file_fornitore_hotel'], 'fornitori_hotel');
                }
            }
        }

        // File trasporto andata
        if (!empty($data['trasporto_andata']['file_fornitore_trasporto'])) {
            $data['trasporto_andata']['file_fornitore_trasporto'] =
                $this->processFiles($data['trasporto_andata']['file_fornitore_trasporto'], 'fornitori_trasporti');
        }

        // File trasporto rientro
        if (!empty($data['trasporto_rientro']['file_fornitore_trasporto'])) {
            $data['trasporto_rientro']['file_fornitore_trasporto'] =
                $this->processFiles($data['trasporto_rientro']['file_fornitore_trasporto'], 'fornitori_trasporti');
        }

        // File servizi extra
        if (!empty($data['extra_services'])) {
            foreach ($data['extra_services'] as $index => $servizio) {
                if (!empty($servizio['file_fornitore_servizi_extra'])) {
                    $data['extra_services'][$index]['file_fornitore_servizi_extra'] =
                        $this->processFiles($servizio['file_fornitore_servizi_extra'], 'fornitori_servizi_extra');
                }
            }
        }

        return $data;
    }

    protected function processFiles(array $files, string $directory = 'preventivi'): array
    {
        return collect($files)->map(function ($file) use ($directory) {
            // Se è un file temporaneo, salvalo nello storage
            if ($file instanceof TemporaryUploadedFile) {
                // Usa storeAs per mantenere il nome originale (come preserveFilenames)
                $originalName = $file->getClientOriginalName();
                $filename = $this->sanitizeFilename($originalName);
                return $file->storeAs($directory, $filename, 'public');
            }

            // Se è già una stringa (path esistente), mantienila
            if (is_string($file)) {
                return $file;
            }

            return null;
        })->filter()->values()->toArray();
    }

    protected function sanitizeFilename(string $filename): string
    {
        // Separa nome ed estensione
        $pathInfo = pathinfo($filename);
        $name = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';

        // Rimuovi/sostituisci caratteri problematici
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);

        // Rimuovi underscore multipli consecutivi
        $name = preg_replace('/_+/', '_', $name);

        // Rimuovi underscore all'inizio e alla fine
        $name = trim($name, '_');

        // Ricostruisci il nome
        return $extension ? "{$name}.{$extension}" : $name;
    }

}