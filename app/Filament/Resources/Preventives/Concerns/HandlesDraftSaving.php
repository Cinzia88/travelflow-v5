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
/* Se esiste già una bozza: Non crea mille email inutili, ma sovrascrive quella esistente con le ultime modifiche.

Se è la prima volta: Crea il record dell'email e crea il legame (nella tabella pivot) con il preventivo. */
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

    $data = $this->form->getState();

    // Verifichiamo il cliente
    if (empty($data['customer_id'])) {
        Notification::make()
            ->title('Cliente mancante')
            ->body('Seleziona un cliente prima di salvare il preventivo.')
            ->danger()
            ->send();
        return;
    }

    $data = $this->processAllFiles($data);

    // Eseguiamo la transazione e facciamo in modo che restituisca il preventivo salvato
    $preventivo = DB::transaction(function () use ($data) {
        $preventivo = $this->record ?? new Preventive();

        dd($preventivo->extra_services());
        $emailData = [
            'email_template_id' => $data['email_template_id'] ?? null,
            'email_cliente'     => $data['email_cliente'] ?? null,
            'email_cc'          => $data['email_cc'] ?? [],
            'corpo_email'       => $data['corpo_email'] ?? null,
            'allegati'          => $data['allegati'] ?? [],
        ];

        unset(
            $data['email_template_id'],
            $data['email_cliente'],
            $data['email_cc'],
            $data['corpo_email'],
            $data['allegati']
        );


        $data['stato'] = PreventiveStatus::BOZZA;

        if ($this->record) {
            $this->record->update($data);
        } else {
            $this->record = Preventive::create($data);
        }

        // Salvataggio relazioni e email
        $this->form->model($this->record)->saveRelationships();
        $this->saveEmailDraft($this->record, $emailData);

        // Restituiamo il record aggiornato o creato
        return $this->record;
    });

    // Assegniamo nuovamente il record per sicurezza (necessario su Filament)
    $this->record = $preventivo;

    Notification::make()->title('Bozza salvata!')->success()->send();

    return redirect()->to(
        PreventiveResource::getUrl('edit', ['record' => $this->record->id]) . '?draft=1'
    );
}
    protected function processAllFiles(array $data): array
    {
        $tipoVisualizzazione = $data['tipo_visualizzazione_foto'] ?? 'per_giorno';
    // 1. SE LA MODALITÀ È "IN FONDO" -> Cancelliamo le immagini dai singoli giorni
    if ($tipoVisualizzazione === 'in_fondo') {
        if (!empty($data['itinerario'])) {
            foreach ($data['itinerario'] as $index => $giorno) {
                // Svuotiamo l'array delle immagini del singolo giorno
                $data['itinerario'][$index]['immagini'] = [];
            }
        }

        // Processiamo solo la galleria in fondo
        if (!empty($data['immagini_itinerario'])) {
            $data['immagini_itinerario'] = $this->processFiles($data['immagini_itinerario'], 'preventivi');
        }
    }

    // 2. SE LA MODALITÀ È "PER GIORNO" -> Cancelliamo la galleria in fondo
    if ($tipoVisualizzazione === 'per_giorno') {
        // Svuotiamo la galleria globale
        $data['immagini_itinerario'] = [];

        // Processiamo solo le immagini delle singole giornate
        if (!empty($data['itinerario'])) {
            foreach ($data['itinerario'] as $index => $giorno) {
                if (!empty($giorno['immagini'])) {
                    $data['itinerario'][$index]['immagini'] = $this->processFiles($giorno['immagini'], 'preventivi');
                }
            }
        }
    }
        // File allegati email
        if (!empty($data['allegati'])) {
            $data['allegati'] = $this->processFiles($data['allegati'], 'email_allegati');
        }
// 2. File della Galleria Itinerario (NUOVO CAMPO)
        if (!empty($data['immagini_itinerario'])) {
            $data['immagini_itinerario'] = $this->processFiles($data['immagini_itinerario'], 'preventivi');
        }

        // 3. File delle singole giornate dell'itinerario (se presenti dentro il repeater)
        if (!empty($data['itinerario'])) {
            foreach ($data['itinerario'] as $index => $giorno) {
                if (!empty($giorno['immagini'])) {
                    $data['itinerario'][$index]['immagini'] =
                        $this->processFiles($giorno['immagini'], 'preventivi');
                }
            }
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