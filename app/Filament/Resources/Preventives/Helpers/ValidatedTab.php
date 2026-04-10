<?php

namespace App\Filament\Helpers;

use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\Support\Htmlable;

class ValidatedTab
{
    /* Questo metodo è una "Factory Function" (un costruttore personalizzato) che serve a creare una Tab (una scheda del form di Filament) che sia "intelligente".
     Crea una Tab che sappia da sola se i dati al suo interno sono corretti o se ci sono errori 
     string|Htmlable|callable|null $label: La label è il nome della Tab (es. "Dati Generali"). Può essere una stringa semplice, del codice HTML, o una funzione che restituisce il nome
     array $fields: Questo è fondamentale. Qui passi l'elenco dei nomi dei campi (es. ['nome', 'cognome', 'email']) che appartengono a quella specifica Tab. Serve per controllare se proprio quei campi hanno errori di validazione.*/

    public static function make(string|Htmlable|callable|null $label = null, array $fields = []): Tab
    {
        return Tab::make($label)
            ->badge(fn(Get $get, Component $compontent) => static::hasErrors($get, $fields, $compontent) ? '●' : '✓')
            ->badgeColor(fn(Get $get, Component $compontent) => static::hasErrors($get, $fields, $compontent) ? 'danger' : 'success')
            ->live();
        /* $get è una funzione che ti permette di andare a sbirciare il valore di altri campi nel form in tempo reale.

A cosa serve: Se sei nella Tab "Dettagli Volo" e vuoi sapere se nella Tab "Anagrafica" l'utente ha selezionato "Privato" o "Azienda", usi $get.

Esempio pratico: $get('tipo_cliente') restituirà il valore attuale di quel campo.

Perché è nel tuo codice delle Tab: Viene passato alla funzione hasErrors perché quest'ultima deve "leggere" i valori dei campi per capire se sono vuoti o errati. 
$component rappresenta l'istanza fisica del pezzo di form che stai usando in quel momento (in questo caso, la specifica Tab).

A cosa serve: Ti dà accesso alle proprietà tecniche del componente stesso. Ad esempio, puoi sapere qual è il suo stato di validazione, quali sono i suoi componenti figli (i campi dentro la tab) o qual è il suo ID univoco.

Perché è nel tuo codice delle Tab: Serve a Filament per capire quale tab deve cambiare colore. Senza $component, il sistema non saprebbe su quale scheda accendere il pallino rosso o verde.*/
    }

    protected static function hasErrors(Get $get, array $fields, Component $component): bool
    {
        $livewire = $component->getLivewire();


        $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

        /* In breve, quella riga serve a capire "chi è" l'oggetto su cui stai lavorando. È il modo in cui la funzione recupera i dati salvati nel database per confrontarli con quelli che stai scrivendo nel form.

Ecco a cosa serve concretamente:

Identifica l'operazione:

Se $record è null: Stai creando un nuovo preventivo (il database è ancora vuoto).

Se $record ha un valore: Stai modificando un preventivo esistente (ha già un ID e dei dati salvati).

Recupera i dati "invisibili": Se un campo nel form appare vuoto (perché non lo hai ancora toccato), la funzione controlla dentro $record per vedere se quel dato è già presente nel database.

Evita errori: Se nel database la foto o il cliente ci sono già, la funzione dice alla Tab: "Resta verde! Il dato non è nel form in questo momento, ma è già al sicuro nel database".

Senza questa riga, la tua funzione hasErrors non saprebbe mai se un campo è vuoto perché l'utente l'ha dimenticato o perché è semplicemente un dato già salvato che non serve modificare. */

        foreach ($fields as $field) {
            $value = $get($field);




            // --- 2. Logica Repeater / Array Complessi ---
            if (is_array($value)) {
                if ($field === 'itinerario') {

                    if (empty($value))/* se è vuoto allora Tab rossa */
                        return true;

                    $valid = collect($value)->every(fn($i) => count($i['immagini'] ?? []) >= 3);
                    /* nella collect controlla se se le immagini sono almeno 3 
                    every controlla tutti gli elementi del Repeater.*/

                    if (!$valid)
                        return true;/* if (!$valid) return true;
Se il controllo fallisce (es. mancano immagini), la funzione si interrompe immediatamente e restituisce true (Segnale: "C'è un errore! Tab Rossa"). */
                    continue; /*  Se il controllo ha successo, continua con il prossimo step: quindi Tab verde */
                }

                if ($field === 'hotel_preventives') {
                    if (empty($value))
                        return true;/* se è vuoto allora Tab rossa */

                    $hasHotel = collect($value)->contains(function ($i) {/* Usa il metodo contains sulla collezione per verificare se esiste almeno un hotel configurato correttamente. */
                        // dd($i['hotel_id'] ?? 'Non hai ancora scelto l\'hotel nella tendina!');

                        /* la variabile $i contiene tutti i dati di una singola riga di quel Repeater. */
                        $id = data_get($i, 'hotel_id') ?? data_get($i, 'hotel.id');
                        $rooms = data_get($i, 'rooms_paganti', []);

                        $hasRealRooms = collect($rooms)->contains(fn($room) => !self::isEmpty($room['tipologia_stanza'] ?? null) && !self::isEmpty($room['costo_notte'] ?? null));
                        /* 1. Il posto A: hotel_id (Modalità "Inserimento")
    Quando stai scrivendo nel form (fase di Creazione), i dati non sono ancora nel database. Sono in un array "piatto".

    La struttura è: ['hotel_id' => 5, 'note' => '...']

    data_get($i, 'hotel_id') guarda qui e trova il numero 5.

    2. Il posto B: hotel.id (Modalità "Modifica")
    Quando invece carichi un preventivo già salvato, Laravel carica la relazione. I dati sono "annidati" dentro l'oggetto Hotel.

    La struttura è: ['hotel' => ['id' => 5, 'nome' => 'Hotel Roma']]

    data_get($i, 'hotel.id') usa il punto per entrare dentro "hotel" e poi prendere "id". Qui trova il numero 5. */
                        return !self::isEmpty($id) && $hasRealRooms;
                        ;/* data_get($i, 'rooms_paganti', [] repeater rooms_paganti, contenuto nel repeater hotel_preventives) */
                        /*!self::isEmpty($id) Controlla se hai selezionato qualcosa nella tendina dell'hotel. Se il campo è vuoto, questa parte restituisce false. */
                    });

                    if (!$hasHotel)
                        return true;
                    continue;
                }
                // --- 3. Logica Trasporti (Dot Notation) ---
                if (in_array($field, ['trasporto_andata', 'trasporto_rientro'])) {
                    $suffix = ($field === 'trasporto_andata') ? '_andata' : '_rientro';
                    $required = ['luogo_di_partenza', 'luogo_di_arrivo', 'data_ora_partenza', 'tipo_trasporto', 'prezzo'];

                    foreach ($required as $key) {
                        if (self::isEmpty($get("{$field}.{$key}{$suffix}")))
                            return true;
                    }
                    continue;
                }

                if ($field === 'extra_services') {
                    if (empty($value))
                        return true;
                    $valid = collect($value)->every(fn($i) => !self::isEmpty($i['tipo'] ?? null) && !self::isEmpty($i['tipo_costo'] ?? null) && !self::isEmpty($i['prezzo'] ?? null));
                    if (!$valid)
                        return true;
                    continue;
                }
            }



            // --- 4. Fallback: Controllo Base ---
            if (self::isEmpty($value)) {
                if ($record) {
                    if (method_exists($record, $field) && $record->{$field}()->count() > 0)
                        continue;
                    if (!self::isEmpty(data_get($record, $field)))
                        continue;
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Alternativa sicura a blank() che non rompe se riceve un array.
     */
    protected static function isEmpty($value): bool
    {
        if (is_array($value)) {
            return empty($value);
        }

        return $value === null || trim((string) $value) === '';
    }
}