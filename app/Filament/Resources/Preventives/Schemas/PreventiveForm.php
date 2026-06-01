<?php

namespace App\Filament\Resources\Preventives\Schemas;

use App\Models\Email;
use App\Models\EmailTemplate;
use App\Models\ExtraService;
use App\Models\Hotel;
use App\Models\Preventive;
use App\Models\User;
use App\Services\ServiceIconProvider;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use App\Filament\Helpers\ValidatedTab;
use Filament\Notifications\Notification;
use App\Models\Customer;
use App\Models\QuoteRequest;
use App\QuoteRequestStatus;
use App\PreventiveStatus;
use Carbon\Carbon;
use App\Models\Itinerary;
use App\Models\Supplier;
use Illuminate\Support\Facades\File;
use Filament\Schemas\Components\View;
use App\Models\TransportCompany;
use Filament\Forms\Components\TagsInput;
use Illuminate\Database\Eloquent\Model;




class PreventiveForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextEntry::make('avviso_bozza')
                    ->label('')
                    ->state('Attenzione: per visualizzare il preventivo aggiornato, è necessario prima cliccare su "Salva come bozza".')
                    ->visibleOn('edit')
                    ->columnSpanFull(),
                Tabs::make('Creazione Preventivo')
                    ->tabs([
                        ValidatedTab::make('Dati Preventivo', [
                            'tipo_preventivo',
                            'titolo',
                            'customer_id',
                            'meta_viaggio',
                            'data_preventivo',
                            'date_expiration',
                            'meta_viaggio',
                            'numero_persone',
                            'numero_gratuita',
                            'data_inizio_viaggio',
                            'data_fine_viaggio',
                            'foto_introduttiva',
                        ])
                            ->schema([

                                // All'interno del tuo Form Schema...
                                Grid::make(4) // Organizziamoli in una griglia
                                    ->schema([

                                        // 1. Numero Preventivo
                                        TextEntry::make('numero')
                                            ->label('N° Preventivo')
                                            ->visibleOn('edit'),

                                        // 2. Creato da (Agente)
                                        TextEntry::make('created_by')
                                            ->label('Creato da')
                                            ->visibleOn('edit')
                                            ->state(function ($record) {

                                                $user = User::find($record->created_by);
                                                return $user ? "{$user->nome} {$user->cognome}" : 'Agente Rimosso';
                                            }),

                                        // 3. Data Preventivo (Sostituito a DatePicker se vuoi solo visualizzarlo)
                                        TextEntry::make('anno')
                                            ->visibleOn('edit'),
                                        TextEntry::make('data_preventivo')
                                            ->label('Data Preventivo')
                                            ->dateTime('d/m/Y')
                                            ->visibleOn('edit'),
                                    ]),
                                /*  Group::make()
                                     ->schema([
                                         TextInput::make('numero')
                                             ->label('Numero Preventivo')
                                             ->disabled()
                                             ->dehydrated(true)
                                             ->visibleOn('edit'),
                                         TextInput::make('created_by')
                                             ->label('Creato da')
                                             ->disabled()
                                             ->visibleOn('edit')
                                             ->afterStateHydrated(function ($component, $state, $record) {
                                                 if (!$record || !$record->created_by) {
                                                     $component->state('Agente Rimosso');
                                                     return;
                                                 }

                                                 $user = User::find($record->created_by);
                                                 $component->state($user ? "{$user->nome} {$user->cognome}" : 'Agente Rimosso');
                                             })
                                             ->dehydrated(false),


                                     ])
                                     ->columns(2), */

                                Group::make()
                                    ->schema([

                                        Group::make()
                                            ->schema([
                                                Toggle::make('gita_giornaliera')
                                                    ->label('Gita Giornaliera')
                                                    ->live()
                                                    ->disabled(fn($record) => $record && $record->exists && $record->hotel_preventives()->count() > 0)

                                                    ->afterStateUpdated(function ($state, Set $set) {
                                                        if ($state === true) {
                                                            $set('hotel_preventives', []);
                                                        }
                                                        // Quando viene disattivato (false), non fa nulla
                                                    })
                                                    ->default(false),

                                                Actions::make([
                                                    Action::make('enable_gita_giornaliera')
                                                        ->label('Attiva Gita Giornaliera')
                                                        ->color('success')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Conferma Gita Giornaliera')
                                                        ->modalDescription('Attivando la gita giornaliera, i campi Hotels/Alloggi verranno eliminati. L\'operazione non è reversibile. Sei sicuro di voler continuare?')
                                                        ->modalSubmitActionLabel('Sì, elimina gli hotel')
                                                        ->modalCancelActionLabel('Annulla')
                                                        ->action(function (Set $set, $livewire) {
                                                            if ($livewire->record && $livewire->record->exists) {
                                                                // Elimina hotel e relative rooms
                                                                foreach ($livewire->record->hotel_preventives as $hotel) {
                                                                    $hotel->rooms_paganti()->delete();
                                                                    $hotel->rooms_gratuite()->delete();
                                                                    $hotel->delete();
                                                                }

                                                                Notification::make()
                                                                    ->title('Tab Hotel Rimosso')
                                                                    ->body('Questa è una gita giornaliera. Ora puoi attivare/disattivare il toggle liberamente.')
                                                                    ->success()
                                                                    ->send();
                                                            }

                                                            $set('gita_giornaliera', true);
                                                            $set('hotel_preventives', []);
                                                        })
                                                        ->visible(fn($record) => $record && $record->exists && $record->hotel_preventives()->count() > 0),
                                                ]),
                                            ]),






                                        /*      Hidden::make('anno')
                                                 ->label('Anno')
                                                 ->default(Carbon::now()->format('Y'))
                                                 ->dehydrated()
                                                 ->required(), */

                                    ])
                                    ->columns(2),
                                Group::make()
                                    ->schema([
                                        /*     DatePicker::make('data_preventivo')
                                                ->displayFormat('d/m/Y')
                                                ->default(Carbon::now())
                                                ->disabled()
                                                ->dehydrated(true)
                                                ->label('Data Preventivo')
                                                ->required(fn($livewire) => !$livewire->isDraft), */

                                        DatePicker::make('date_expiration')
                                            ->displayFormat('d/m/Y')
                                            ->label('Data Validità Preventivo')
                                            ->required(fn($livewire) => !$livewire->isDraft),
                                        Select::make('tipo_preventivo')
                                            ->label('Tipo Preventivo')
                                            ->options([
                                                'libero' => 'Libero',
                                                'con_richiesta' => 'Collegato a Richiesta',
                                            ])
                                            ->default('libero')
                                            ->dehydrated(fn($state) => $state != null)
                                            ->required(fn($livewire) => !$livewire->isDraft),
                                    ])
                                    ->columns(2),
                                Select::make('quote_request_id')
                                    ->relationship('quote_request', 'oggetto', modifyQueryUsing: function ($query) {
                                        return $query->where('stato_richiesta', '!=', QuoteRequestStatus::EVASA);

                                    })
                                    ->preload()
                                    ->hidden(fn(Get $get): bool => $get('tipo_preventivo') !== 'con_richiesta')
                                    ->live(debounce: 500)
                                    ->getSearchResultsUsing(function (string $search) {
                                        return QuoteRequest::query()
                                            ->where('stato_richiesta', '!=', QuoteRequestStatus::EVASA)
                                            ->where(function ($query) use ($search) {
                                                $query->where('oggetto', 'like', "%{$search}%")
                                                    ->orWhere('id', 'like', "%{$search}%");
                                            })
                                            ->limit(50)
                                            ->get();

                                    })
                                    // quando cambia la richiesta, imposto il customer_id associato
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $req = QuoteRequest::find($state);
                                            if ($req) {
                                                $set('customer_id', $req->customer_id);
                                                $set('meta_viaggio', $req->meta_viaggio);
                                                $set('titolo', 'Preventivo per richiesta: ' . $req->tipo_richiesta);
                                            }
                                            if ($req && $req->customer?->email) {
                                                $set('email_cliente', $req->customer->email);
                                            }
                                        } else {
                                            // Se viene deselezionata la richiesta, pulisco i campi collegati
                                            $set('customer_id', null);
                                            $set('meta_viaggio', null);
                                            $set('email_cliente', null);
                                            $set('titolo', null);
                                        }
                                    })
                                    ->columnSpanfull()
                                    ->label('Richiesta')
                                    ->searchable()
                                    ->getOptionLabelFromRecordUsing(fn(QuoteRequest $record) => "{$record->id} - {$record->oggetto}")
                                    ->required(fn($livewire) => !$livewire->isDraft),

                                TextInput::make('titolo')
                                    ->label('Titolo')
                                    ->dehydrated(fn($state) => $state != null)
                                    ->maxLength(255)
                                    ->required(fn($livewire) => !$livewire->isDraft)
                                    ->columnSpanFull(),
                                Group::make()
                                    ->schema([

                                        TextInput::make('meta_viaggio')
                                            ->label('Meta')
                                            ->maxLength(255)
                                            ->required(fn($livewire) => !$livewire->isDraft),
                                        Select::make('customer_id')
                                            ->relationship('customer', 'nome', )
                                            ->preload()
                                            ->label('Cliente')
                                            ->searchable()
                                            ->required(fn($livewire) => !$livewire->isDraft)
                                            ->getOptionLabelFromRecordUsing(
                                                fn(Customer $record) =>
                                                "{$record->nome} {$record->cognome}" . ($record->ragione_sociale ? " ({$record->ragione_sociale})" : '')
                                            )
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $customer = Customer::find($state);
                                                if ($customer) {
                                                    $set('email_cliente', $customer->email);
                                                } else {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Attenzione')
                                                        ->body('Nessun cliente valido selezionato.')
                                                        ->warning()
                                                        ->send();
                                                }
                                            })
                                            ->getSearchResultsUsing(function (string $search) {
                                                return Customer::query()
                                                    ->where(function ($query) use ($search) {
                                                        $query->where('nome', 'like', "%{$search}%")
                                                            ->orWhere('cognome', 'like', "%{$search}%")
                                                            ->orWhere('email', 'like', "%{$search}%");
                                                    })
                                                    ->limit(50)
                                                    ->get()
                                                    ->mapWithKeys(function ($customer) {
                                                        return [
                                                            $customer->id => $customer->nome . ' ' . $customer->cognome,
                                                        ];
                                                    });
                                            })
                                            ->createOptionForm([


                                                Select::make('tipo_cliente')
                                                    ->label('Tipologia')
                                                    ->options([
                                                        'azienda' => 'Azienda',
                                                        'privato' => 'Privato',
                                                        'scuola' => 'Scuola',
                                                    ])
                                                    ->default(null)
                                                    ->required()
                                                    ->live(),
                                                TextInput::make('nome')
                                                    ->label('Nome')
                                                    ->required()
                                                    ->maxLength(255),
                                                TextInput::make('cognome')
                                                    ->label('Cognome')
                                                    ->visible(fn($get) => $get('tipo_cliente') === 'privato'),
                                                Select::make('genere')
                                                    ->label('Genere')
                                                    ->options([
                                                        'donna' => 'Donna',
                                                        'uomo' => 'Uomo',
                                                    ])
                                                    ->default(null)
                                                    ->required()
                                                    ->visible(fn($get) => $get('tipo_cliente') === 'privato'),
                                                TextInput::make('ragione_sociale')
                                                    ->label('Ragione Sociale')
                                                    ->visible(fn($get) => in_array($get('tipo_cliente'), ['azienda', 'scuola']))
                                                    ->default(null),
                                                TextInput::make('piva_cf')
                                                    ->label('P. Iva')
                                                    ->maxLength(255)
                                                    ->default(null),
                                                TextInput::make('indirizzo')
                                                    ->label('Indirizzo')
                                                    ->maxLength(255)
                                                    ->default(null),
                                                TextInput::make('citta')
                                                    ->label('Città')
                                                    ->maxLength(255)
                                                    ->default(null),
                                                TextInput::make('cap')
                                                    ->label('CAP')
                                                    ->maxLength(255)
                                                    ->default(null),
                                                TextInput::make('provincia')
                                                    ->label('Provincia')
                                                    ->maxLength(255)
                                                    ->default(null),
                                                TextInput::make('stato')
                                                    ->label('Stato')
                                                    ->maxLength(255)
                                                    ->default(null),
                                                TextInput::make('email')
                                                    ->label('Email')
                                                    ->email()
                                                    ->unique(ignorable: fn($record) => $record)
                                                    ->maxLength(255)
                                                    ->default(null),
                                                TextInput::make('telefono')
                                                    ->label('Telefono')
                                                    ->tel()
                                                    ->helperText('Formato: +39 seguito dal numero (es. +393331234567)')
                                                    ->regex('/^\+39[0-9]{9,10}$/')
                                                    ->validationMessages([
                                                        'regex' => 'Il numero deve iniziare con +39 seguito da 9-10 cifre',
                                                    ])
                                                    ->required(),
                                            ])
                                    ])
                                    ->columns(2),
                                Group::make()
                                    ->schema([
                                        TextInput::make('numero_persone')
                                            ->label('Numero Persone')
                                            ->numeric()
                                            ->required(fn($livewire) => !$livewire->isDraft)->default(1),
                                        TextInput::make('numero_gratuita')
                                            ->label('Numero Gratuità')
                                            ->numeric()
                                            ->default(1)
                                            ->required(fn($livewire) => !$livewire->isDraft),

                                    ])->columns(2),
                                Group::make()
                                    ->schema([
                                        DatePicker::make('data_inizio_viaggio')
                                            ->label('Data Inizio Viaggio')
                                            ->displayFormat('d/m/Y')
                                            ->live(debounce: 500)
                                            ->required(fn($livewire) => !$livewire->isDraft)->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if ($state) {
                                                    $date = Carbon::parse($state)->startOfDay()->format('Y-m-d H:i:s');
                                                    $set('trasporto_andata.data_ora_partenza_andata', $date . ' 00:00:00');
                                                    $set('trasporto_andata.data_ora_arrivo_andata', $date . ' 00:00:00');

                                                }
                                            }),
                                        DatePicker::make('data_fine_viaggio')
                                            ->label('Data Fine Viaggio')
                                            ->displayFormat('d/m/Y')
                                            ->live(debounce: 500)
                                            ->required(fn($livewire) => !$livewire->isDraft)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if ($state) {
                                                    $date = Carbon::parse($state)->startOfDay()->format('Y-m-d H:i:s');

                                                    $set('trasporto_rientro.data_ora_partenza_rientro', $date . ' 00:00:00');
                                                    $set('trasporto_rientro.data_ora_arrivo_rientro', $date . ' 00:00:00');


                                                }
                                            }),
                                    ])->columns(2),

                                Group::make()
                                    ->schema([
                                        FileUpload::make('foto_introduttiva')
                                            ->image()
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                            ->required(fn($livewire) => !$livewire->isDraft)
                                            ->preserveFilenames()
                                            ->maxSize(1024)
                                            ->helperText('Carica un\'immagine (.png, .jpg o .jpeg) di almeno 200 KB per garantire una buona qualità nel preventivo')
                                            ->directory('preventivi')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->label('Foto Introduttiva')
                                            ->columnSpanFull(),





                                    ])->columns(2),


                                Fieldset::make('Allegati')
                                    ->visible(fn($get, $context) => $context === 'edit')

                                    ->schema([

                                        FileUpload::make('files_pratica_accettata')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->preserveFilenames()
                                            ->maxSize(3072)
                                            ->visibleOn('edit')
                                            ->directory('preventivi')
                                            ->label('Files Pratica Accettata')
                                            ->columnSpanFull(),
                                    ])->columns(2),

                            ]),
                        ValidatedTab::make('Itinerario', [
                            'itinerary_id',
                            'nome_itinerario',
                            'itinerario',
                            'tipo_visualizzazione_foto',
                            'immagini_itinerario',
                        ])

                            ->schema([
                                Group::make()
                                    ->schema([
                                        Select::make('itinerary_id')
                                            ->label('Tipo Itinerario')
                                            ->relationship('itinerary', 'nome')
                                            ->searchable()
                                            ->preload()
                                            ->required(fn($livewire) => !$livewire->isDraft)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if (!$state) {
                                                    $set('nome_itinerario', null);
                                                    $set('tipo_visualizzazione_foto', 'per_giorno');
                                                    $set('itinerario', []);
                                                    $set('immagini_itinerario', []);
                                                    return;
                                                }

                                                $it = Itinerary::find($state);

                                                if ($it) {
                                                    // 1. Popoliamo il nome dell'itinerario a livello radice
                                                    $it = Itinerary::find($state);

                                                    if ($it) {
                                                        $set('nome_itinerario', $it->nome);
                                                        $set('tipo_visualizzazione_foto', $it->tipo_visualizzazione_foto ?? 'per_giorno');
                                                        $set('itinerario', $it->itinerario ?? []);
                                                        $set('immagini_itinerario', $it->immagini_itinerario ?? []);
                                                    } else {
                                                        $set('nome_itinerario', null);
                                                        $set('nome', null);
                                                        $set('tipo_visualizzazione_foto', 'per_giorno');
                                                        $set('itinerario', []);
                                                        $set('immagini_itinerario', []);
                                                    }

                                                } else {
                                                    $set('nome_itinerario', null);
                                                    $set('nome', null);
                                                    $set('tipo_visualizzazione_foto', 'per_giorno');
                                                    $set('itinerario', []);
                                                    $set('immagini_itinerario', []);
                                                }
                                            })
                                            ->createOptionForm([

                                                TextInput::make('nome')->label('Nome')
                                                    ->columnSpanFull()
                                                    ->required(),
                                                Select::make('tipo_visualizzazione_foto')
                                                    ->label('Tipo di Visualizzazione delle Foto')
                                                    ->options([
                                                        'per_giorno' => 'Foto specifiche per ogni giorno',
                                                        'in_fondo' => 'Tutte le foto alla fine dell\'itinerario',
                                                    ])
                                                    ->default('per_giorno')
                                                    ->live(),
                                                Repeater::make('itinerario')
                                                    ->label('Programma Giornaliero')
                                                    ->schema([
                                                        TextInput::make('titolo')->label('Titolo')
                                                            ->columnSpanFull()
                                                            ->required(),
                                                        RichEditor::make('descrizione')
                                                            ->json()
                                                            ->toolbarButtons([
                                                                'bold',
                                                                'bulletList',
                                                                'italic',
                                                                'orderedList',
                                                                'redo',
                                                                'underline',
                                                                'undo',
                                                            ]),
                                                        FileUpload::make('immagini')
                                                            ->label('Immagini Itinerario')
                                                            ->image()
                                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                            /* ->minSize(50)
                                                            ->maxSize(1024) */
                                                            ->visible(function (Get $get) {
                                                                return $get('../../tipo_visualizzazione_foto') === 'per_giorno';
                                                            })
                                                            ->required(fn(Get $get) => $get('../../tipo_visualizzazione_foto') === 'per_giorno')
                                                            ->multiple()
                                                            ->maxFiles(3)
                                                            ->preserveFilenames()
                                                            ->disk('public')
                                                            ->visibility('public')
                                                            ->directory('preventivi')
                                                            ->dehydrated(true) //  fondamentale: invia i file anche se il repeater è annidato
                                                            ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per l’itinerario. Minimo 50KB.')
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->addActionLabel('Aggiungi itinerario')
                                                    ->columnSpanFull(),
                                                FileUpload::make('immagini_itinerario')
                                                    ->label('Galleria Fotografica (In fondo)')
                                                    ->image()
                                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                    /* ->minSize(50)
                                                    ->maxSize(1024) */
                                                    ->multiple()
                                                    ->visible(fn(Get $get) => $get('../../tipo_visualizzazione_foto') === 'in_fondo' || $get('tipo_visualizzazione_foto') === 'in_fondo')
                                                    ->required(fn(Get $get) => $get('tipo_visualizzazione_foto') === 'in_fondo')
                                                    ->maxFiles(3)
                                                    ->preserveFilenames()
                                                    ->disk('public')
                                                    ->visibility('public')
                                                    ->directory('preventivi')
                                                    ->dehydrated(true) //  fondamentale: invia i file anche se il repeater è annidato
                                                    ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per l’itinerario. Minimo 50KB.')
                                                    ->columnSpanFull(),
                                            ])
                                            ->editOptionAction(
                                                fn(Action $action) => $action->after(function (Set $set, Get $get) {
                                                    $itineraryId = $get('itinerary_id');
                                                    if ($itineraryId) {
                                                        $it = Itinerary::find($itineraryId);
                                                        $set('nome_itinerario', $it->nome);
                                                        $set('nome', $it->nome);
                                                        $set('tipo_visualizzazione_foto', $it->tipo_visualizzazione_foto ?? 'per_giorno');
                                                        $set('itinerario', $it->itinerario ?? []);
                                                        $set('immagini_itinerario', $it->immagini_itinerario ?? []);
                                                    }
                                                })
                                            )
                                            ->editOptionForm([
                                                TextInput::make('nome')->label('Nome')
                                                    ->columnSpanFull()
                                                    ->required(),
                                                Select::make('tipo_visualizzazione_foto')
                                                    ->label('Tipo di Visualizzazione delle Foto')
                                                    ->options([
                                                        'per_giorno' => 'Foto specifiche per ogni giorno',
                                                        'in_fondo' => 'Tutte le foto alla fine dell\'itinerario',
                                                    ])
                                                    ->default('per_giorno')
                                                    ->live(),
                                                Repeater::make('itinerario')
                                                    ->label('Programma Giornaliero')
                                                    ->schema([
                                                        TextInput::make('titolo')->label('Titolo')
                                                            ->columnSpanFull()
                                                            ->required(),
                                                        RichEditor::make('descrizione')
                                                            ->json()
                                                            ->toolbarButtons([
                                                                'bold',
                                                                'bulletList',
                                                                'italic',
                                                                'orderedList',
                                                                'redo',
                                                                'underline',
                                                                'undo',
                                                            ]),
                                                        FileUpload::make('immagini')
                                                            ->label('Immagini Itinerario')
                                                            ->image()
                                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                            /* ->minSize(50)
                                                            ->maxSize(1024) */
                                                            ->multiple()
                                                            ->visible(fn(Get $get) => $get('../../tipo_visualizzazione_foto') === 'per_giorno')
                                                            ->required(fn(Get $get) => $get('../../tipo_visualizzazione_foto') === 'per_giorno')
                                                            ->maxFiles(3)
                                                            ->preserveFilenames()
                                                            ->disk('public')
                                                            ->visibility('public')
                                                            ->directory('preventivi')
                                                            ->dehydrated(true) //  fondamentale: invia i file anche se il repeater è annidato
                                                            ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per l’itinerario. Minimo 50KB.')
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->addActionLabel('Aggiungi itinerario')
                                                    ->columnSpanFull(),
                                                FileUpload::make('immagini_itinerario')
                                                    ->label('Galleria Fotografica (In fondo)')
                                                    ->image()
                                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                    /* ->minSize(50)
                                                    ->maxSize(1024) */
                                                    ->multiple()
                                                    ->visible(fn(Get $get) => $get('../../tipo_visualizzazione_foto') === 'in_fondo' || $get('tipo_visualizzazione_foto') === 'in_fondo')
                                                    ->required(fn(Get $get) => $get('tipo_visualizzazione_foto') === 'in_fondo')
                                                    ->maxFiles(3)
                                                    ->preserveFilenames()
                                                    ->disk('public')
                                                    ->visibility('public')
                                                    ->directory('preventivi')
                                                    ->dehydrated(true) //  fondamentale: invia i file anche se il repeater è annidato
                                                    ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per l’itinerario. Minimo 50KB.')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),



                                Section::make('Dettagli Programma di Viaggio')
                                    ->label('Programma di Viaggio')
                                    ->schema([
                                        TextInput::make('nome_itinerario')->label('Nome')
                                            ->columnSpanFull()
                                            ->required(fn($livewire) => !$livewire->isDraft),
                                        Select::make('tipo_visualizzazione_foto')
                                            ->label('Tipo di Visualizzazione delle Foto')
                                            ->options([
                                                'per_giorno' => 'Foto specifiche per ogni giorno',
                                                'in_fondo' => 'Tutte le foto alla fine dell\'itinerario',
                                            ])
                                            ->default('per_giorno')
                                            ->live(),
                                        Repeater::make('itinerario')
                                            ->label('Programma Giornaliero')
                                            ->schema([
                                                TextInput::make('titolo')->label('Titolo')
                                                    ->columnSpanFull()
                                                    ->required(fn($livewire) => !$livewire->isDraft),
                                                RichEditor::make('descrizione')
                                                    ->json()
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'bulletList',
                                                        'italic',
                                                        'orderedList',
                                                        'redo',
                                                        'underline',
                                                        'undo',
                                                    ]),
                                                FileUpload::make('immagini')
                                                    ->label('Immagini Itinerario')
                                                    ->image()
                                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                    /* ->minSize(50)
                                                    ->maxSize(1024) */
                                                    ->visible(function (Get $get) {
                                                        return $get('../../tipo_visualizzazione_foto') === 'per_giorno';
                                                    })
                                                    ->required(fn(Get $get, $livewire) => $get('../../tipo_visualizzazione_foto') === 'per_giorno' && !$livewire->isDraft)
                                                    ->multiple()
                                                    ->maxFiles(3)
                                                    ->preserveFilenames()
                                                    ->disk('public')
                                                    ->visibility('public')
                                                    ->directory('preventivi')
                                                    ->dehydrated(true) //  fondamentale: invia i file anche se il repeater è annidato
                                                    ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per l’itinerario. Minimo 50KB.')
                                                    ->columnSpanFull(),
                                            ])
                                            ->addActionLabel('Aggiungi itinerario')
                                            ->columnSpanFull(),
                                        FileUpload::make('immagini_itinerario')
                                            ->label('Galleria Fotografica (In fondo)')
                                            ->image()
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                            /* ->minSize(50)
                                            ->maxSize(1024) */
                                            ->multiple()
                                            ->visible(fn(Get $get) => $get('tipo_visualizzazione_foto') === 'in_fondo')
                                            ->required(fn(Get $get, $livewire) => $get('tipo_visualizzazione_foto') === 'in_fondo' && !$livewire->isDraft)
                                            ->maxFiles(3)
                                            ->preserveFilenames()
                                            ->disk('public')
                                            ->visibility('public')
                                            ->directory('preventivi')
                                            ->dehydrated(true) //  fondamentale: invia i file anche se il repeater è annidato
                                            ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per l’itinerario. Minimo 50KB.')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                        ValidatedTab::make('Hotels/Alloggi', [
                            'hotel_preventives',
                        ])
                            ->hidden(condition: fn(Get $get): bool => $get('gita_giornaliera') === true)

                            ->schema([
                                /*  TextEntry::make('info_persone_forzate')
                                     ->state(function (Get $get) {
                                         $persone = (int) $get('numero_persone');
                                         $forzate = (int) $get('n_persone_forzato');

                                         if ($forzate > 0 && $forzate !== $persone) {
                                             return "Calcolo basato su {$forzate} partecipanti (forzato, anziché {$persone} partecipanti)";
                                         }

                                         return null; // Non mostra nulla se il campo non è impostato
                                     })
                                     ->columnSpanFull()
                                     ->disableLabel(), */
                                Repeater::make('hotel_preventives')
                                    ->label('Hotel collegati al preventivo')
                                    ->required(fn($livewire) => !$livewire->isDraft)
                                    ->relationship('hotel_preventives')
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->hint(function (Get $get) {
                                        $hotelId = $get('hotel_id');
                                        if (!$hotelId)
                                            return null;

                                        $hotel = Hotel::find($hotelId);

                                        if (!$hotel)
                                            return null;

                                        if (empty($hotel->foto) || count($hotel->foto) === 0) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<span class="text-warning-600">⚠️ Nessuna foto presente per questo hotel.</span>'
                                            );
                                        }

                                        return new \Illuminate\Support\HtmlString(
                                            '<span class="text-success-600">✅ Foto disponibili ('
                                            . count($hotel->foto)
                                            . ')</span>'
                                        );
                                    })
                                    ->itemLabel(fn(array $state): ?string => isset($state['hotel_id'])
                                        ? optional(Hotel::find($state['hotel_id']))?->nome
                                        : 'Hotel')
                                    ->schema([
                                        Select::make('hotel_id')
                                            ->label('Hotel')
                                            ->relationship('hotel', 'nome')  // HotelPreventive::hotel()
                                            ->searchable()
                                            ->preload()
                                            ->required(fn($livewire) => !$livewire->isDraft)                                            // crea Hotel “al volo”
                                            ->createOptionForm([
                                                Group::make()
                                                    ->schema([
                                                        Select::make('supplier_id')
                                                            ->relationship('supplier', 'nome')
                                                            ->preload()
                                                            ->label('Fornitore')
                                                            ->searchable()
                                                            ->getSearchResultsUsing(function (string $search) {
                                                                return Supplier::query()
                                                                    ->where(function ($query) use ($search) {
                                                                        $query->where('nome', 'like', "%{$search}%")
                                                                            ->orWhere('cognome', 'like', "%{$search}%");
                                                                    })
                                                                    ->limit(50)
                                                                    ->get()
                                                                    ->mapWithKeys(function ($supplier) {
                                                                        return [
                                                                            $supplier->id => $supplier->nome . ' ' . $supplier->cognome,
                                                                        ];
                                                                    });
                                                            })
                                                            ->getOptionLabelFromRecordUsing(callback: fn(Supplier $record) => "{$record->nome} {$record->cognome}")->createOptionForm([
                                                                    TextInput::make('nome')
                                                                        ->required()
                                                                        ->maxLength(255),
                                                                    TextInput::make('cognome')
                                                                        ->required()
                                                                        ->maxLength(255),
                                                                    TextInput::make('ragione_sociale')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    TextInput::make('piva_cf')
                                                                        ->label('P.Iva')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    TextInput::make('codice_fiscale')
                                                                        ->label('Codice Fiscale')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    TextInput::make('indirizzo')
                                                                        ->maxLength(255)
                                                                        ->default(null),


                                                                    TagsInput::make('telefono')
                                                                        ->label('Telefoni')
                                                                        ->placeholder('Inserisci un numero di telefono e premi Invio')

                                                                        ->rule(function (Get $get, ?Model $record) {
                                                                            return function (string $attribute, $value, \Closure $fail) use ($record) {
                                                                                $emails = is_array($value) ? $value : [];

                                                                                foreach ($emails as $email) {
                                                                                    $exists = Supplier::whereJsonContains('telefono', $email)
                                                                                        ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                                                                                        ->exists();

                                                                                    if ($exists) {
                                                                                        $fail("Il telefono \"{$email}\" è già associato ad un altro fornitore.");
                                                                                    }
                                                                                }
                                                                            };
                                                                        })
                                                                        ->required(),

                                                                    TagsInput::make('email')
                                                                        ->label('Indirizzi Email')
                                                                        ->placeholder('Inserisci un\'email e premi Invio')
                                                                        ->nestedRecursiveRules([
                                                                            'email',
                                                                        ])
                                                                        ->rule(function (Get $get, ?Model $record) {
                                                                            return function (string $attribute, $value, \Closure $fail) use ($record) {
                                                                                $emails = is_array($value) ? $value : [];

                                                                                foreach ($emails as $email) {
                                                                                    $exists = Supplier::whereJsonContains('email', $email)
                                                                                        ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                                                                                        ->exists();

                                                                                    if ($exists) {
                                                                                        $fail("L'email \"{$email}\" è già associata ad un altro fornitore.");
                                                                                    }
                                                                                }
                                                                            };
                                                                        })
                                                                        ->required(),


                                                                    TagsInput::make('sito_web')
                                                                        ->label('Siti Web')
                                                                        ->placeholder('Inserisci un sito web e premi Invio')

                                                                        ->rule(function (Get $get, ?Model $record) {
                                                                            return function (string $attribute, $value, \Closure $fail) use ($record) {
                                                                                $emails = is_array($value) ? $value : [];

                                                                                foreach ($emails as $email) {
                                                                                    $exists = Supplier::whereJsonContains('sito_web', $email)
                                                                                        ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                                                                                        ->exists();

                                                                                    if ($exists) {
                                                                                        $fail("Il sito web \"{$email}\" è già associato ad un altro fornitore.");
                                                                                    }
                                                                                }
                                                                            };
                                                                        })
                                                                        ->required(),
                                                                    /*  Repeater::make('portale_web')
                                                                         ->addActionLabel('Aggiungi Portale Web')
                                                                         ->schema([
                                                                             RichEditor::make('portale_web')
                                                                                 ->label('Portale Web')
                                                                                 ->default("<h3>Credenziali</h3><p>Utente: <br>Password:</p>")
                                                                                 ->toolbarButtons([
                                                                                     'bold',
                                                                                     'bulletList',
                                                                                     'italic',
                                                                                     'orderedList',
                                                                                     'redo',
                                                                                     'link',
                                                                                     'underline',
                                                                                     'undo',
                                                                                 ])
                                                                                 ->required(),
                                                                         ])
                                                                         ->label('Portali Web')
                                                                         ->columns(1)
                                                                         ->collapsible()
                                                                         ->defaultItems(1), */
                                                                    TextInput::make('regione')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    TextInput::make('stato')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    TextInput::make('citta')
                                                                        ->label('Città')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    TextInput::make('cap')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    TextInput::make('provincia')
                                                                        ->maxLength(255)
                                                                        ->default(null),

                                                                    Textarea::make('descrizione')
                                                                        ->columnSpanFull(),
                                                                    Textarea::make('note')
                                                                        ->columnSpanFull(),

                                                                ]),
                                                        TextInput::make('stelle')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->minValue(0)
                                                            ->maxValue(5),
                                                    ])->columns(2),
                                                Group::make()
                                                    ->schema([
                                                        TextInput::make('nome')->required(),
                                                        TextInput::make('indirizzo'),
                                                    ])->columns(2),
                                                RichEditor::make('descrizione')
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'bulletList',
                                                        'italic',
                                                        'orderedList',
                                                        'redo',
                                                        'underline',
                                                        'undo',
                                                    ]),
                                                FileUpload::make('foto')
                                                    ->image()
                                                    ->preserveFilenames()
                                                    ->multiple()
                                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                    /*  ->minSize(50)
                                                     ->maxSize(1024) */
                                                    ->disk('public')
                                                    ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per hotel/alloggio. Minimo 50KB.')
                                                    ->minFiles(3)
                                                    ->maxFiles(3)
                                                    ->visibility('public')
                                                    ->directory('foto_hotel')
                                                    ->columnSpanFull()
                                                    ->label('Foto'),

                                            ])
                                            ->editOptionForm([
                                                Group::make()
                                                    ->schema([
                                                        Select::make('supplier_id')
                                                            ->relationship('supplier', 'nome')
                                                            ->preload()
                                                            ->label('Fornitore')
                                                            ->searchable()
                                                            ->getSearchResultsUsing(function (string $search) {
                                                                return Supplier::query()
                                                                    ->where(function ($query) use ($search) {
                                                                        $query->where('nome', 'like', "%{$search}%")
                                                                            ->orWhere('cognome', 'like', "%{$search}%");
                                                                    })
                                                                    ->limit(50)
                                                                    ->get()
                                                                    ->mapWithKeys(function ($supplier) {
                                                                        return [
                                                                            $supplier->id => trim("{$supplier->nome} {$supplier->cognome}"),
                                                                        ];
                                                                    });
                                                            })
                                                            ->getOptionLabelFromRecordUsing(fn(Supplier $record) => trim("{$record->nome} {$record->cognome}"))
                                                            ->createOptionForm([
                                                                TextInput::make('nome')
                                                                    ->required()
                                                                    ->maxLength(255),
                                                                TextInput::make('cognome')
                                                                    ->required()
                                                                    ->maxLength(255),
                                                                TextInput::make('ragione_sociale')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                TextInput::make('piva_cf')
                                                                    ->label('P.Iva')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                TextInput::make('codice_fiscale')
                                                                    ->label('Codice Fiscale')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                TextInput::make('indirizzo')
                                                                    ->maxLength(255)
                                                                    ->default(null),


                                                                TagsInput::make('telefono')
                                                                    ->label('Telefoni')
                                                                    ->placeholder('Inserisci un numero di telefono e premi Invio')

                                                                    ->rule(function (Get $get, ?Model $record) {
                                                                        return function (string $attribute, $value, \Closure $fail) use ($record) {
                                                                            $emails = is_array($value) ? $value : [];

                                                                            foreach ($emails as $email) {
                                                                                $exists = Supplier::whereJsonContains('telefono', $email)
                                                                                    ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                                                                                    ->exists();

                                                                                if ($exists) {
                                                                                    $fail("Il telefono \"{$email}\" è già associato ad un altro fornitore.");
                                                                                }
                                                                            }
                                                                        };
                                                                    })
                                                                    ->required(),

                                                                TagsInput::make('email')
                                                                    ->label('Indirizzi Email')
                                                                    ->placeholder('Inserisci un\'email e premi Invio')
                                                                    ->nestedRecursiveRules([
                                                                        'email',
                                                                    ])
                                                                    ->rule(function (Get $get, ?Model $record) {
                                                                        return function (string $attribute, $value, \Closure $fail) use ($record) {
                                                                            $emails = is_array($value) ? $value : [];

                                                                            foreach ($emails as $email) {
                                                                                $exists = Supplier::whereJsonContains('email', $email)
                                                                                    ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                                                                                    ->exists();

                                                                                if ($exists) {
                                                                                    $fail("L'email \"{$email}\" è già associata ad un altro fornitore.");
                                                                                }
                                                                            }
                                                                        };
                                                                    })
                                                                    ->required(),


                                                                TagsInput::make('sito_web')
                                                                    ->label('Siti Web')
                                                                    ->placeholder('Inserisci un sito web e premi Invio')

                                                                    ->rule(function (Get $get, ?Model $record) {
                                                                        return function (string $attribute, $value, \Closure $fail) use ($record) {
                                                                            $emails = is_array($value) ? $value : [];

                                                                            foreach ($emails as $email) {
                                                                                $exists = Supplier::whereJsonContains('sito_web', $email)
                                                                                    ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                                                                                    ->exists();

                                                                                if ($exists) {
                                                                                    $fail("Il sito web \"{$email}\" è già associato ad un altro fornitore.");
                                                                                }
                                                                            }
                                                                        };
                                                                    })
                                                                    ->required(),
                                                                /*  Repeater::make('portale_web')
                                                                     ->addActionLabel('Aggiungi Portale Web')
                                                                     ->schema([
                                                                         RichEditor::make('portale_web')
                                                                             ->label('Portale Web')
                                                                             ->default("<h3>Credenziali</h3><p>Utente: <br>Password:</p>")
                                                                             ->toolbarButtons([
                                                                                 'bold',
                                                                                 'bulletList',
                                                                                 'italic',
                                                                                 'orderedList',
                                                                                 'redo',
                                                                                 'link',
                                                                                 'underline',
                                                                                 'undo',
                                                                             ])
                                                                             ->required(),
                                                                     ])
                                                                     ->label('Portali Web')
                                                                     ->columns(1)
                                                                     ->collapsible()
                                                                     ->defaultItems(1), */
                                                                TextInput::make('regione')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                TextInput::make('stato')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                TextInput::make('citta')
                                                                    ->label('Città')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                TextInput::make('cap')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                TextInput::make('provincia')
                                                                    ->maxLength(255)
                                                                    ->default(null),

                                                                Textarea::make('descrizione')
                                                                    ->columnSpanFull(),
                                                                Textarea::make('note')
                                                                    ->columnSpanFull(),

                                                            ]),
                                                        TextInput::make('stelle')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->minValue(0)
                                                            ->maxValue(5),
                                                    ])->columns(2),
                                                Group::make()
                                                    ->schema([
                                                        TextInput::make('nome')->required(),
                                                        TextInput::make('indirizzo'),
                                                    ])->columns(2),
                                                RichEditor::make('descrizione')
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'bulletList',
                                                        'italic',
                                                        'orderedList',
                                                        'redo',
                                                        'underline',
                                                        'undo',
                                                    ]),
                                                FileUpload::make('foto')
                                                    ->image()
                                                    /* ->minSize(50)
                                                    ->maxSize(1024) */
                                                    ->preserveFilenames()
                                                    ->multiple()
                                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                    ->disk('public')
                                                    ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per hotel/alloggio. Minimo 50KB.')
                                                    ->minFiles(3)
                                                    ->maxFiles(3)
                                                    ->visibility('public')
                                                    ->directory('foto_hotel')
                                                    ->columnSpanFull()
                                                    ->label('Foto'),

                                            ]),



                                        // Stanze (tabella: hotel_preventive_rooms)((paganti))
                                        Repeater::make('rooms_paganti')
                                            ->label('Stanze Paganti')
                                            ->relationship('rooms_paganti')
                                            ->defaultItems(0)
                                            ->dehydrated(false)
                                            ->collapsible()

                                            ->schema([
                                                Group::make()
                                                    ->schema([
                                                        Select::make('tipologia_stanza')
                                                            ->label('Tipologia Stanza')
                                                            ->required(fn($livewire) => !$livewire->isDraft)->live(debounce: 500)
                                                            ->options([
                                                                'singola' => 'Singola',
                                                                'doppia' => 'Doppia',
                                                                'tripla' => 'Tripla',
                                                                'quadrupla' => 'Quadrupla',
                                                                'stanza da 5' => 'Stanza da 5',
                                                                'stanza da 6' => 'Stanza da 6',
                                                                'stanza da 7' => 'Stanza da 7',
                                                                'stanza da 8' => 'Stanza da 8',
                                                                'stanza da 9' => 'Stanza da 9',
                                                                'stanza da 10' => 'Stanza da 10',
                                                                'multipla' => 'Multipla',
                                                            ]),
                                                        Select::make('tipo_costo')
                                                            ->label('Tipo Costo')
                                                            ->label('Tipo Costo')
                                                            ->options([
                                                                'a persona' => 'Per persona',
                                                                'a camera' => 'Per camera',
                                                            ])
                                                            ->default('a persona')
                                                            ->required(fn($livewire) => !$livewire->isDraft)->live(debounce: 500),
                                                        TextInput::make('quantita_camere')
                                                            ->label('N° Camere')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->visible(fn(Get $get) => $get('tipo_costo') === 'a camera'),
                                                        TextInput::make('n_notti')
                                                            ->label('N° Notti')
                                                            ->numeric()
                                                            ->required()
                                                            ->afterStateHydrated(function ($state, Set $set, Get $get, $record, $livewire) {
                                                                // Recupera sempre le date
                                                                $inizio = Carbon::parse($get('../../../../data_inizio_viaggio') ?? $record?->data_inizio_viaggio);
                                                                $fine = Carbon::parse($get('../../../../data_fine_viaggio') ?? $record?->data_fine_viaggio);

                                                                // Se non ci sono date valide, non fare nulla
                                                                if (!$inizio || !$fine) {
                                                                    return;
                                                                }

                                                                // Se il campo ha già un valore, non lo toccare
                                                                if (filled($state)) {
                                                                    return;
                                                                }

                                                                $count_notti = $inizio->diffInDays($fine);
                                                                $set('n_notti', max($count_notti, 1));
                                                            }),

                                                        TextInput::make('numero_paganti_stanza')
                                                            ->label('N° Paganti')
                                                            ->numeric()
                                                            ->required(fn($livewire) => !$livewire->isDraft)->live()
                                                            ->afterStateHydrated(function ($state, Set $set, Get $get, $record, $livewire) {
                                                                // Se è già valorizzato (modifica o reload), non toccarlo
                                                                if (filled($state)) {
                                                                    return;
                                                                }

                                                                // Prendo numero persone normale e forzato
                                                                $numeroPersone = (int) ($get('../../../../numero_persone') ?? $record?->numero_persone ?? 0);
                                                                //$numeroForzate = (int) ($get('../../../../n_persone_forzato') ?? $record?->n_persone_forzato ?? 0);
                                                    
                                                                // Se esiste numero persone forzato > 0, lo uso
                                                                //$personeEffettive = $numeroForzate > 0 ? $numeroForzate : $numeroPersone;
                                                    
                                                                // Tolgo le gratuità
                                                                $numeroGratuitaT = (int) ($get('../../../../numero_gratuita') ?? $record?->numero_gratuita ?? 0);
                                                                $totPaganti = max($numeroPersone - $numeroGratuitaT, 0);


                                                                // Stanze paganti già presenti nel repeater
                                                                $stanze = $get('../../rooms_paganti') ?? [];

                                                                // Somma già assegnata (esclude null / stringhe)
                                                                $sommaPrecedente = collect($stanze)
                                                                    ->pluck('numero_paganti_stanza')
                                                                    ->map(fn($v) => (int) $v)
                                                                    ->sum();

                                                                $rimanenti = max($totPaganti - $sommaPrecedente, 0);

                                                                // Prima stanza → totale, altrimenti rimanenti
                                                                $valore = count($stanze) <= 1 ? $totPaganti : $rimanenti;

                                                                $set('numero_paganti_stanza', $valore);
                                                            }),



                                                        TextInput::make('costo_notte')
                                                            ->label('Costo / notte')
                                                            ->numeric()->minValue(0)
                                                            ->required(fn($livewire) => !$livewire->isDraft)->live(onBlur: true)
                                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                                $data = $get('../../../../');

                                                                [$quota, $tot] = self::calcolaCostoPerPersona($data);

                                                                $set('../../../../prezzo_per_persona', $quota);
                                                                $set('../../../../totale_incasso', $tot);
                                                            })
                                                            ->prefix('€'),

                                                    ])->columns(3),
                                                Hidden::make('gratuita')
                                                    ->default(false),



                                            ])
                                            ->addActionLabel('Aggiungi stanza'),

                                        //((gratuità))
                                        Repeater::make('rooms_gratuite')
                                            ->label('Stanze Gratuità')
                                            ->relationship('rooms_gratuite')
                                            ->defaultItems(0)
                                            ->dehydrated(false)
                                            ->collapsible()

                                            ->schema([
                                                Group::make()
                                                    ->schema([
                                                        Select::make('tipologia_stanza')
                                                            ->label('Tipologia Stanza')
                                                            ->required()
                                                            ->live(debounce: 500)
                                                            ->options([
                                                                'singola' => 'Singola',
                                                                'doppia' => 'Doppia',
                                                                'tripla' => 'Tripla',
                                                                'quadrupla' => 'Quadrupla',
                                                                'stanza da 5' => 'Stanza da 5',
                                                                'stanza da 6' => 'Stanza da 6',
                                                                'stanza da 7' => 'Stanza da 7',
                                                                'stanza da 8' => 'Stanza da 8',
                                                                'stanza da 9' => 'Stanza da 9',
                                                                'stanza da 10' => 'Stanza da 10',
                                                                'multipla' => 'Multipla',
                                                            ]),
                                                        Select::make('tipo_costo')
                                                            ->label('Tipo Costo')
                                                            ->label('Tipo Costo')
                                                            ->options([
                                                                'a persona' => 'Per persona',
                                                                'a camera' => 'Per camera',
                                                            ])
                                                            ->default('a persona')
                                                            ->required()
                                                            ->live(debounce: 500),
                                                        TextInput::make('quantita_camere')
                                                            ->label('N° Camere')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->visible(fn(Get $get) => $get('tipo_costo') === 'a camera'),
                                                        TextInput::make('n_notti')
                                                            ->label('N° Notti')
                                                            ->numeric()
                                                            ->required()
                                                            ->afterStateHydrated(function ($state, Set $set, Get $get, $record, $livewire) {
                                                                // Recupera sempre le date
                                                                $inizio = Carbon::parse($get('../../../../data_inizio_viaggio') ?? $record?->data_inizio_viaggio);
                                                                $fine = Carbon::parse($get('../../../../data_fine_viaggio') ?? $record?->data_fine_viaggio);

                                                                // Se non ci sono date valide, non fare nulla
                                                                if (!$inizio || !$fine) {
                                                                    return;
                                                                }

                                                                // Se il campo ha già un valore, non lo toccare
                                                                if (filled($state)) {
                                                                    return;
                                                                }

                                                                $count_notti = $inizio->diffInDays($fine);
                                                                $set('n_notti', max($count_notti, 1));
                                                            }),
                                                        TextInput::make('numero_gratuita_stanza')
                                                            ->label('Numero Gratuità')
                                                            ->numeric()
                                                            ->afterStateHydrated(function ($state, Set $set, Get $get, $record, $livewire) {
                                                                // Se è già valorizzato (modifica o reload), non toccarlo
                                                                if (filled($state)) {
                                                                    return;
                                                                }

                                                                // Totale gratuita dalla form state (fallback al record in edit)
                                                                $totGratuita = (int) ($get('../../../../numero_gratuita') ?? $record?->numero_gratuita ?? 0);

                                                                // Stanze gratuite già presenti nel repeater
                                                                $stanze = $get('../../rooms_gratuite') ?? [];

                                                                $sommaPrecedente = collect($stanze)
                                                                    ->pluck('numero_gratuita_stanza')
                                                                    ->map(fn($v) => (int) $v)
                                                                    ->sum();

                                                                $rimanenti = max($totGratuita - $sommaPrecedente, 0);

                                                                $valore = count($stanze) <= 1 ? $totGratuita : $rimanenti;

                                                                $set('numero_gratuita_stanza', $valore);
                                                            })

                                                            ->required(),
                                                        TextInput::make('costo_notte')
                                                            ->label('Costo / notte')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                                $data = $get('../../../../');

                                                                [$quota, $tot] = self::calcolaCostoPerPersona($data);

                                                                $set('../../../../prezzo_per_persona', $quota);
                                                                $set('../../../../totale_incasso', $tot);
                                                            })
                                                            ->required(),



                                                        Hidden::make('gratuita')
                                                            ->default(true),

                                                    ])->columns(3),


                                            ])
                                            ->collapsible()
                                            ->addActionLabel('Aggiungi stanza'),
                                        RichEditor::make('quota_comprende_hotel')
                                            ->label('La quota comprende')
                                            ->toolbarButtons([
                                                'bold',
                                                'bulletList',
                                                'italic',
                                                'orderedList',
                                                'redo',
                                                'underline',
                                                'undo',
                                            ])
                                            ->columnSpanFull(),
                                        RichEditor::make('quota_non_comprende_hotel')
                                            ->label('La quota non comprende')
                                            ->toolbarButtons([
                                                'bold',
                                                'bulletList',
                                                'italic',
                                                'orderedList',
                                                'redo',
                                                'underline',
                                                'undo',
                                            ])->columnSpanFull(),

                                        FileUpload::make('file_fornitore_hotel')
                                            ->disk('public')
                                            ->directory('fornitori_hotel')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->maxSize(3072)
                                            ->preserveFilenames()
                                            ->columnSpanFull()
                                            ->label('File Fornitore Hotel'),
                                        Textarea::make('note')
                                            ->label('Note ad uso interno')
                                            ->columnSpanFull(),




                                    ])
                                    ->addActionLabel('Aggiungi hotel'),
                            ]),
                        ValidatedTab::make('Trasporti', [
                            'trasporto_andata',
                            'trasporto_rientro',
                        ])

                            ->schema([
                                Tabs::make('Tabs')
                                    ->columnSpanFull()
                                    ->contained(false)->tabs([
                                            Tabs\Tab::make('Andata')
                                                ->schema([

                                                    Group::make()
                                                        ->relationship('trasporto_andata')
                                                        ->schema([
                                                            Group::make()
                                                                ->schema([
                                                                    TextInput::make('luogo_di_partenza_andata')
                                                                        ->label('Luogo di Partenza (Andata)')
                                                                        ->required(fn($livewire) => !$livewire->isDraft),
                                                                    TextInput::make('luogo_di_arrivo_andata')
                                                                        ->label('Luogo di Arrivo (Andata)')
                                                                        ->required(fn($livewire) => !$livewire->isDraft),
                                                                ])->columns(2),

                                                            Group::make()
                                                                ->schema([


                                                                    /* DateTimePicker::make('data_ora_partenza_andata')
                                                                        ->label('Data/Ora Partenza (Andata)')
                                                                        ->displayFormat('d/m/Y H:i')
                                                                        ->seconds(false)

                                                                        ->required(fn($livewire) => !$livewire->isDraft), */
                                                                    /*   Placeholder::make('data_inizio_viaggio')
                                                                          ->label('Data Partenza')
                                                                          ->content(function ($livewire) {
                                                                              $inizio = $livewire->data['data_inizio_viaggio'] ?? null;
                                                                              return $inizio ? Carbon::parse($inizio)->format('d/m/Y') : 'Non impostata';
                                                                          })->extraAttributes([
                                                                                  'class' => 'p-2 bg-gray-50 border border-gray-200 rounded-lg shadow-sm block w-full text-gray-500 ring-1 ring-gray-950/5'
                                                                              ]), */
                                                                    TextInput::make('data_inizio_viaggio_display')
                                                                        ->label('Data Partenza')
                                                                        ->placeholder(function (Get $get) {
                                                                            // Usiamo Get invece di $livewire per evitare l'errore di inizializzazione
                                                                            // Risaliamo l'albero: ../ (esce dal Group) ../ (esce dal Tab)
                                                                            $inizio = $get('../data_inizio_viaggio');

                                                                            return $inizio ? \Carbon\Carbon::parse($inizio)->format('d/m/Y') : 'Data non impostata';
                                                                        })
                                                                        ->disabled()
                                                                        ->dehydrated(false),

                                                                    TimePicker::make('data_ora_partenza_andata')
                                                                        ->label('Orario Partenza (Andata)')
                                                                        ->displayFormat('H:i')
                                                                        ->seconds(false)
                                                                        ->required(fn($livewire) => !$livewire->isDraft)
                                                                        // Forza il salvataggio unendo l'ora alla data fissa
                                                                        ->dehydrateStateUsing(function ($state, Get $get) {
                                                                            if (!$state)
                                                                                return null;
                                                                            $dataInizio = $get('../data_inizio_viaggio');
                                                                            return Carbon::parse($dataInizio)
                                                                                ->setTimeFrom(Carbon::parse($state))
                                                                                ->toDateTimeString();
                                                                        }),
                                                                    TimePicker::make('data_ora_arrivo_andata')
                                                                        ->label('Orario Arrivo (Andata)')
                                                                        ->displayFormat('H:i')
                                                                        ->seconds(false)
                                                                        ->required(fn($livewire) => !$livewire->isDraft)
                                                                        // Forza il salvataggio unendo l'ora alla data fissa
                                                                        ->dehydrateStateUsing(function ($state, Get $get) {
                                                                            if (!$state)
                                                                                return null;
                                                                            $dataInizio = $get('../data_inizio_viaggio');
                                                                            return Carbon::parse($dataInizio)
                                                                                ->setTimeFrom(Carbon::parse($state))
                                                                                ->toDateTimeString();
                                                                        }),
                                                                    /*  DateTimePicker::make('data_ora_arrivo_andata')
                                                                         ->label('Data/Ora Arrivo (Andata)')
                                                                         ->displayFormat('d/m/Y H:i')
                                                                         ->seconds(false)
                                                                         ->required(fn($livewire) => !$livewire->isDraft),
  */
                                                                ])->columns(3),
                                                            Group::make()
                                                                ->schema([
                                                                    Select::make('tipo_trasporto')
                                                                        ->label('Tipologia Trasporto')
                                                                        ->native(false)
                                                                        ->options([
                                                                            'bus' => 'Bus',
                                                                            'aereo' => 'Aereo',
                                                                            'treno' => 'Treno',
                                                                            'nave' => 'Nave',
                                                                            'traghetto' => 'Traghetto',
                                                                        ])
                                                                        ->default(null)
                                                                        ->required(fn($livewire) => !$livewire->isDraft)->live()
                                                                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                                            if ($state !== 'aereo') {
                                                                                $set('kg_bg_a_mano', null);
                                                                                $set('kg_bg_in_stiva', null);
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                            $aziendaId = $get('transport_company_id');
                                                                            $azienda = $aziendaId ? TransportCompany::find($aziendaId) : null;

                                                                            if ($state === 'aereo' && $azienda) {
                                                                                $set('misura_bg_a_mano', $azienda->misura_bg_a_mano);
                                                                            } else {
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                        }),
                                                                    Select::make('transport_company_id')
                                                                        ->label('Azienda di Trasporto')
                                                                        ->relationShip('transport_company', 'nome')
                                                                        ->searchable()
                                                                        ->preload()

                                                                        ->live(debounce: 500)
                                                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                                            $azienda = $state ? TransportCompany::find($state) : null;

                                                                            // Controllo il tipo di trasporto
                                                                            if ($get('tipo_trasporto') === 'aereo' && $azienda) {
                                                                                // Imposta il campo del preventivo
                                                                                $set('misura_bg_a_mano', $azienda->misura_bg_a_mano);
                                                                            } else {
                                                                                // Reset nel caso non serva
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                        })
                                                                        ->getOptionLabelFromRecordUsing(fn(TransportCompany $record) => "{$record->nome}" ?: 'Senza nome')
                                                                        ->createOptionForm([
                                                                            TextInput::make('nome')
                                                                                ->label('Nome')
                                                                                ->maxLength(255),
                                                                            FileUpload::make('immagine')
                                                                                ->image()
                                                                                ->maxSize(1024)
                                                                                ->helperText('Carica un\'immagine (.png, .jpg o .jpeg)')
                                                                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                                                ->preserveFilenames()
                                                                                ->label('Immagine'),
                                                                        ]),
                                                                ])
                                                                ->columns(2),
                                                            Hidden::make('direzione_trasporto')
                                                                ->default('andata'),
                                                            Group::make()
                                                                ->schema([


                                                                    Select::make('tipo_costo')->label('Tipologia Costo')->native(false)->options([
                                                                        'una tantum' => 'Una Tantum',
                                                                        'a persona' => 'A Persona',
                                                                    ])
                                                                        ->live(debounce: 500)
                                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                                            $data = $get('../');

                                                                            [$quota, $tot] = self::calcolaCostoPerPersona($data);

                                                                            $set('../prezzo_per_persona', $quota);
                                                                            $set('../totale_incasso', $tot);
                                                                        })
                                                                        ->default('a persona'),
                                                                    TextInput::make('prezzo')
                                                                        ->label('Prezzo')
                                                                        ->numeric()
                                                                        ->required(fn($livewire) => !$livewire->isDraft)->live()
                                                                        ->live(onBlur: true)
                                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                                            $data = $get('../');

                                                                            [$quota, $tot] = self::calcolaCostoPerPersona($data);

                                                                            $set('../prezzo_per_persona', $quota);
                                                                            $set('../totale_incasso', $tot);
                                                                        })
                                                                        ->prefix('€'),
                                                                ])
                                                                ->columns(2),


                                                            Toggle::make('scorpora_trasporto')
                                                                ->label('Scorpora')
                                                                ->live(debounce: 500)
                                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                                    $data = $get('../');

                                                                    [$quota, $tot] = self::calcolaCostoPerPersona($data);

                                                                    $set('../prezzo_per_persona', $quota);
                                                                    $set('../totale_incasso', $tot);
                                                                })
                                                                ->inline(false),



                                                            Group::make()
                                                                ->schema([
                                                                    TextInput::make('kg_bg_a_mano')
                                                                        ->label('Kg Bagagli a Mano')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo')
                                                                        ->numeric(),
                                                                    TextInput::make('kg_bg_in_stiva')
                                                                        ->label('Kg Bagagli in Stiva')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo')
                                                                        ->numeric(),
                                                                    TextInput::make('misura_bg_a_mano')
                                                                        ->label('Misura Bagagli a Mano')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo'),
                                                                ])
                                                                ->columns(2),
                                                            FileUpload::make('file_fornitore_trasporto')
                                                                ->disk('public')
                                                                ->directory('fornitori_trasporti')
                                                                ->multiple()
                                                                ->acceptedFileTypes(['application/pdf'])
                                                                ->maxSize(3072)
                                                                ->preserveFilenames()
                                                                ->columnSpanFull()

                                                                ->label('File Fornitore Trasporto'),
                                                            RichEditor::make('quota_comprende_trasporti')
                                                                ->label('La quota comprende')
                                                                ->toolbarButtons([
                                                                    'bold',
                                                                    'bulletList',
                                                                    'italic',
                                                                    'orderedList',
                                                                    'redo',
                                                                    'underline',
                                                                    'undo',
                                                                ])
                                                                ->columnSpanFull(),
                                                            RichEditor::make('quota_non_comprende_trasporti')
                                                                ->label('La quota non comprende')
                                                                ->toolbarButtons([
                                                                    'bold',
                                                                    'bulletList',
                                                                    'italic',
                                                                    'orderedList',
                                                                    'redo',
                                                                    'underline',
                                                                    'undo',
                                                                ])->columnSpanFull(),


                                                        ]),
                                                ]),
                                            Tabs\Tab::make('Rientro')
                                                ->schema([
                                                    Group::make()
                                                        ->relationship('trasporto_rientro')
                                                        ->schema([
                                                            Group::make()
                                                                ->schema([
                                                                    TextInput::make('luogo_di_partenza_rientro')
                                                                        ->label('Luogo di Partenza (Rientro)')
                                                                        ->required(fn($livewire) => !$livewire->isDraft),
                                                                    TextInput::make('luogo_di_arrivo_rientro')
                                                                        ->label('Luogo di Arrivo (Rientro)')
                                                                        ->required(fn($livewire) => !$livewire->isDraft),
                                                                ])->columns(2),

                                                            Group::make()
                                                                ->schema([
                                                                    /* DateTimePicker::make('data_ora_partenza_rientro')
                                                                        ->label('Data/Ora Partenza (Rientro)')
                                                                        ->seconds(false)

                                                                        ->required(fn($livewire) => !$livewire->isDraft),
                                                                    DateTimePicker::make('data_ora_arrivo_rientro')
                                                                        ->label('Data/Ora Arrivo (Rientro)')
                                                                        ->seconds(false)
                                                                        ->default(function (Get $get, $state, $set, $livewire) {
                                                                            // Se non è già valorizzato, prova a prendere la data dal primo step
                                                                            $fine = $livewire->data['data_fine_viaggio'] ?? null;
                                                                            return $fine ? \Carbon\Carbon::parse($fine) : null;
                                                                        })
                                                                        ->required(fn($livewire) => !$livewire->isDraft), */
                                                                    /*  Placeholder::make('data_fine_viaggio')
                                                                         ->label('Data Rientro')
                                                                         ->content(function ($livewire) {
                                                                             $fine = $livewire->data['data_fine_viaggio'] ?? null;
                                                                             return $fine ? Carbon::parse($fine)->format('d/m/Y') : 'Non impostata';
                                                                         }), */
                                                                    TextInput::make('data_fine_viaggio_display')
                                                                        ->label('Data Rientro')
                                                                        ->placeholder(function (Get $get) {
                                                                            // Usiamo Get invece di $livewire per evitare l'errore di inizializzazione
                                                                            // Risaliamo l'albero: ../ (esce dal Group) ../ (esce dal Tab)
                                                                            $fine = $get('../data_fine_viaggio');

                                                                            return $fine ? Carbon::parse($fine)->format('d/m/Y') : 'Data non impostata';
                                                                        })
                                                                        ->disabled()
                                                                        ->dehydrated(false),
                                                                    TimePicker::make('data_ora_partenza_rientro')
                                                                        ->label('Orario Partenza (Rientro)')
                                                                        ->displayFormat('H:i')
                                                                        ->seconds(false)
                                                                        ->required(fn($livewire) => !$livewire->isDraft)
                                                                        // Forza il salvataggio unendo l'ora alla data fissa
                                                                        ->dehydrateStateUsing(function ($state, Get $get) {
                                                                            if (!$state)
                                                                                return null;
                                                                            $dataInizio = $get('../data_fine_viaggio');
                                                                            return Carbon::parse($dataInizio)
                                                                                ->setTimeFrom(Carbon::parse($state))
                                                                                ->toDateTimeString();
                                                                        }),
                                                                    TimePicker::make('data_ora_arrivo_rientro')
                                                                        ->label('Orario Arrivo (Rientro)')
                                                                        ->displayFormat('H:i')
                                                                        ->seconds(false)
                                                                        ->required(fn($livewire) => !$livewire->isDraft)
                                                                        // Forza il salvataggio unendo l'ora alla data fissa
                                                                        ->dehydrateStateUsing(function ($state, Get $get) {
                                                                            if (!$state)
                                                                                return null;
                                                                            $dataInizio = $get('../data_fine_viaggio');
                                                                            return Carbon::parse($dataInizio)
                                                                                ->setTimeFrom(Carbon::parse($state))
                                                                                ->toDateTimeString();
                                                                        }),
                                                                ])->columns(3),
                                                            Group::make()
                                                                ->schema([
                                                                    Select::make('tipo_trasporto')
                                                                        ->label('Tipologia Trasporto')
                                                                        ->native(false)
                                                                        ->options([
                                                                            'bus' => 'Bus',
                                                                            'aereo' => 'Aereo',
                                                                            'treno' => 'Treno',
                                                                            'nave' => 'Nave',
                                                                            'traghetto' => 'Traghetto',
                                                                        ])
                                                                        ->default(null)
                                                                        ->required(fn($livewire) => !$livewire->isDraft)
                                                                        ->live()
                                                                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                                            if ($state !== 'aereo') {
                                                                                $set('kg_bg_a_mano', null);
                                                                                $set('kg_bg_in_stiva', null);
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                            $aziendaId = $get('transport_company_id');
                                                                            $azienda = $aziendaId ? TransportCompany::find($aziendaId) : null;

                                                                            if ($state === 'aereo' && $azienda) {
                                                                                $set('misura_bg_a_mano', $azienda->misura_bg_a_mano);
                                                                            } else {
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                        }),
                                                                    Select::make('transport_company_id')
                                                                        ->label('Azienda di Trasporto')
                                                                        ->relationShip('transport_company', 'nome')
                                                                        ->searchable()
                                                                        ->preload()
                                                                        ->live(debounce: 500)
                                                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                                            $azienda = $state ? TransportCompany::find($state) : null;

                                                                            // Controllo il tipo di trasporto
                                                                            if ($get('tipo_trasporto') === 'aereo' && $azienda) {
                                                                                // Imposta il campo del preventivo
                                                                                $set('misura_bg_a_mano', $azienda->misura_bg_a_mano);
                                                                            } else {
                                                                                // Reset nel caso non serva
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                        })
                                                                        ->getOptionLabelFromRecordUsing(fn(TransportCompany $record) => "{$record->nome}" ?: 'Senza nome')
                                                                        ->createOptionForm([
                                                                            TextInput::make('nome')
                                                                                ->label('Nome')
                                                                                ->maxLength(255),
                                                                            FileUpload::make('immagine')
                                                                                ->image()
                                                                                ->maxSize(1024)
                                                                                ->helperText('Carica un\'immagine (.png, .jpg o .jpeg)')
                                                                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                                                ->preserveFilenames()
                                                                                ->label('Immagine'),
                                                                        ]),

                                                                ])
                                                                ->columns(2),
                                                            Hidden::make('direzione_trasporto')
                                                                ->default('rientro'),
                                                            Group::make()
                                                                ->schema([



                                                                    Select::make('tipo_costo')
                                                                        ->label('Tipologia Costo')
                                                                        ->native(false)
                                                                        ->options([
                                                                            'una tantum' => 'Una Tantum',
                                                                            'a persona' => 'A Persona'
                                                                        ])
                                                                        ->live(debounce: 500)
                                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                                            $data = $get('../');

                                                                            [$quota, $tot] = self::calcolaCostoPerPersona($data);

                                                                            $set('../prezzo_per_persona', $quota);
                                                                            $set('../totale_incasso', $tot);
                                                                        })
                                                                        ->default('a persona'),
                                                                    TextInput::make('prezzo')
                                                                        ->label('Prezzo')
                                                                        ->numeric()
                                                                        ->required(fn($livewire) => !$livewire->isDraft)
                                                                        ->live(onBlur: true)
                                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                                            $data = $get('../');

                                                                            [$quota, $tot] = self::calcolaCostoPerPersona($data);

                                                                            $set('../prezzo_per_persona', $quota);
                                                                            $set('../totale_incasso', $tot);
                                                                        })
                                                                        ->prefix('€'),
                                                                ])
                                                                ->columns(2),


                                                            Toggle::make('scorpora_trasporto')
                                                                ->label('Scorpora')
                                                                ->live(debounce: 500)
                                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                                    $data = $get('../');

                                                                    [$quota, $tot] = self::calcolaCostoPerPersona($data);

                                                                    $set('../prezzo_per_persona', $quota);
                                                                    $set('../totale_incasso', $tot);
                                                                })
                                                                ->default(false)
                                                                ->inline(false),



                                                            Group::make()
                                                                ->schema([
                                                                    TextInput::make('kg_bg_a_mano')
                                                                        ->label('Kg Bagagli a Mano')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->numeric(),
                                                                    TextInput::make('kg_bg_in_stiva')
                                                                        ->label('Kg Bagagli in Stiva')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->numeric(),
                                                                    TextInput::make('misura_bg_a_mano')
                                                                        ->label('Misura Bagagli a Mano')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo'),
                                                                ])
                                                                ->columns(2),
                                                            FileUpload::make('file_fornitore_trasporto')
                                                                ->disk('public')
                                                                ->directory('fornitori_trasporti')
                                                                ->multiple()
                                                                ->acceptedFileTypes(['application/pdf'])
                                                                ->maxSize(3072)
                                                                ->preserveFilenames()
                                                                ->columnSpanFull()

                                                                ->label('File Fornitore Trasporto'),
                                                            RichEditor::make('quota_comprende_trasporti')
                                                                ->label('La quota comprende')
                                                                ->toolbarButtons([
                                                                    'bold',
                                                                    'bulletList',
                                                                    'italic',
                                                                    'orderedList',
                                                                    'redo',
                                                                    'underline',
                                                                    'undo',
                                                                ])
                                                                ->columnSpanFull(),
                                                            RichEditor::make('quota_non_comprende_trasporti')
                                                                ->label('La quota non comprende')
                                                                ->toolbarButtons([
                                                                    'bold',
                                                                    'bulletList',
                                                                    'italic',
                                                                    'orderedList',
                                                                    'redo',
                                                                    'underline',
                                                                    'undo',
                                                                ])->columnSpanFull(),

                                                        ]),

                                                ]),

                                        ]),
                                Textarea::make('note')
                                    ->label('Note ad uso interno')
                                    ->columnSpanFull(),
                            ]), //fine trasporti

                        ValidatedTab::make('Servizi Extra', [
                            'extra_services',
                        ])

                            ->schema([
                                Repeater::make('extra_services')
                                    ->relationship('extra_services')
                                    ->columns(3) // Layout più compatto
                                    ->collapsible()
                                    ->required(fn($livewire) => !$livewire->isDraft)
                                    ->collapsed()
                                    ->default([]) // <--- Forza il repeater a partire completamente vuoto su un nuovo record
                                    ->minItems(0) // <--- Permette di avere zero elementi (utile per le bozze)
                                    ->itemLabel(function (array $state): ?string {
                                        if (!empty($state['extra_service_id'])) {
                                            $service = ExtraService::find($state['extra_service_id']);
                                            return $service->nome ?? "Servizio #{$state['extra_service_id']}";
                                        }

                                        return 'Nuovo Servizio Extra';
                                    })
                                    ->addActionLabel('Aggiungi servizio')
                                    ->schema([

                                        // 1. TIPOLOGIA (Usa le tue icone come filtro)
                                        Select::make('tipo')
                                            ->label('Tipologia (Icona)')
                                            ->options(fn() => ServiceIconProvider::getIconsService())
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set) => $set('extra_service_id', null))
                                            ->columnSpan(1),

                                        // 2. NOME SERVIZIO (Il cuore della relazione)
                                        Select::make('extra_service_id')
                                            ->label('Nome Servizio')
                                            ->required(fn($livewire) => !$livewire->isDraft)
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->options(function (Get $get) {
                                                $tipo = $get('tipo');
                                                $query = ExtraService::query();

                                                // Se l'utente ha scelto una tipologia, filtra i nomi
                                                if ($tipo) {
                                                    $query->where('tipo', $tipo);
                                                }

                                                return $query->pluck('nome', 'id');
                                            })
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state) {
                                                    $service = ExtraService::find($state);
                                                    // Precompila la descrizione dal catalogo
                                                    $set('descrizione_servizio', $service?->descrizione_servizio);
                                                }
                                            })
                                            // Permette di creare un nuovo servizio nel catalogo al volo
                                            ->createOptionForm([
                                                Select::make('tipo')
                                                    ->options(fn() => ServiceIconProvider::getIconsService())
                                                    ->required(),
                                                TextInput::make('nome')
                                                    ->required(),
                                                RichEditor::make('descrizione_servizio'),
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                return ExtraService::create($data)->id;
                                            })
                                            ->columnSpan(2),

                                        // 3. DATI ECONOMICI (Salvati sulla tabella pivot)
                                        Select::make('tipo_costo')
                                            ->label('Tipo Costo')
                                            ->options([
                                                'a_persona' => 'A persona',
                                                'una_tantum' => 'Una tantum',
                                            ])
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                $data = $get('../../');

                                                [$quota, $tot] = self::calcolaCostoPerPersona($data);

                                                $set('../../prezzo_per_persona', $quota);
                                                $set('../../totale_incasso', $tot);
                                            })
                                            ->default('a_persona')
                                            ->live() // serve per mostrare/nascondere "quantita"
                                            ->required(fn($livewire) => !$livewire->isDraft),

                                        // Prezzo unitario

                                        TextInput::make('prezzo')
                                            ->label('Prezzo')
                                            ->prefix('€')
                                            ->numeric()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                $data = $get('../../');

                                                [$quota, $tot] = self::calcolaCostoPerPersona($data);

                                                $set('../../prezzo_per_persona', $quota);
                                                $set('../../totale_incasso', $tot);
                                            })

                                            ->required(fn($livewire) => !$livewire->isDraft),



                                        TextInput::make('quantita_a_persona')
                                            ->label('Quantità (per persona)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn(Get $get) => $get('tipo_costo') === 'a_persona'),
                                        TextInput::make('quantita')
                                            ->label('Quantità')
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn(Get $get) => $get('tipo_costo') === 'una_tantum'),

                                        // 4. DETTAGLI (Salvati sulla tabella pivot)
                                        RichEditor::make('descrizione_servizio')
                                            ->label('Descrizione Personalizzata')
                                            ->columnSpanFull()
                                            ->toolbarButtons(['bold', 'bulletList', 'italic', 'undo', 'redo']),

                                        Toggle::make('scorpora_servizio')
                                            ->label('Scorpora')
                                            ->inline(false)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                $data = $get('../../');

                                                [$quota, $tot] = self::calcolaCostoPerPersona($data);

                                                $set('../../prezzo_per_persona', $quota);
                                                $set('../../totale_incasso', $tot);
                                            }),


                                        RichEditor::make('quota_comprende_servizi')
                                            ->label('La quota comprende')
                                            ->columnSpanFull()
                                            ->toolbarButtons([
                                                'bold',
                                                'bulletList',
                                                'italic',
                                                'orderedList',
                                                'redo',
                                                'underline',
                                                'undo',
                                            ]),
                                        RichEditor::make('quota_non_comprende_servizi')
                                            ->label('La quota non comprende')
                                            ->columnSpanFull()
                                            ->toolbarButtons([
                                                'bold',
                                                'bulletList',
                                                'italic',
                                                'orderedList',
                                                'redo',
                                                'underline',
                                                'undo',
                                            ]),
                                        FileUpload::make('file_fornitore_servizi_extra')
                                            ->disk('public')
                                            ->maxSize(3072)
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->directory('fornitori_servizi_extra')
                                            ->multiple()
                                            ->preserveFilenames()
                                            ->columnSpanFull()

                                            ->label('File Fornitore Servizi Extra'),
                                        Textarea::make('note')
                                            ->label('Note ad uso interno')
                                            ->columnSpanFull(),
                                    ])
                            ]),
                        ValidatedTab::make('Riepilogo e Costi', [
                            'prezzo_per_persona',
                            'markup',
                            //'prezzo_forzato',
                            'totale_incasso',
                            'stato',
                        ])
                            ->hidden(
                                fn(Get $get, string $operation): bool =>

                                $operation === 'create'
                            )
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Group::make()
                                            ->schema([
                                                // --- Campo calcolato ---
                                                TextInput::make('prezzo_per_persona')
                                                    ->label('Prezzo per Persona')
                                                    ->prefix('€')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated(true)
                                                    ->default(function (Get $get) {
                                                        [$quota] = self::calcolaCostoPerPersona($get());
                                                        return $quota;
                                                    })
                                                    ->afterStateHydrated(function (Set $set, Get $get) {
                                                        [$quota, $tot] = self::calcolaCostoPerPersona($get());
                                                        $set('prezzo_per_persona', $quota);
                                                        $set('totale_incasso', $tot);
                                                    }),

                                                // --- Markup ---
                                                TextInput::make('markup')
                                                    ->label('Markup (€ per persona)')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required(fn($livewire) => !$livewire->isDraft)
                                                    ->prefix('€')
                                                    ->debounce(500)
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                        $data = $get();
                                                        $data['markup'] = $state;
                                                        [$quota, $tot] = self::calcolaCostoPerPersona($data);
                                                        $set('prezzo_per_persona', $quota);
                                                        $set('totale_incasso', $tot);
                                                    }),

                                                // --- Prezzo Forzato ---
                                                /*  TextInput::make('prezzo_forzato')
                                                     ->label('Prezzo Forzato')
                                                     ->prefix('€')
                                                     ->numeric()
                                                     ->required(fn($livewire) => !$livewire->isDraft), */

                                                /*  TextInput::make('n_persone_forzato')
                                                     ->label('N° Persone Forzato')
                                                     ->numeric()
                                                     ->nullable()
                                                     ->default(null)
                                                     ->helperText('Dato ad uso interno. Quota individuale calcolata sulla base del numero inserito.')
                                                     ->debounce(500)
                                                     ->live()
                                                     ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                         $data = $get();
                                                         $data['n_persone_forzato'] = $state;
                                                         [$quota, $tot] = self::calcolaCostoPerPersona($data);
                                                         $set('prezzo_per_persona', $quota);
                                                         $set('totale_incasso', $tot);
                                                     }), */
                                            ])->columns(2),
                                    ])->columnSpanFull(),

                                Select::make('stato')
                                    ->label('Stato Preventivo')
                                    ->options(PreventiveStatus::class)
                                    ->default(PreventiveStatus::BOZZA->value)
                                    ->required(),
                                RichEditor::make('campo_attenzione')
                                    ->label('Campo Attenzione')
                                    ->toolbarButtons(['bold', 'bulletList', 'italic', 'orderedList', 'redo', 'underline', 'undo'])
                                    ->dehydrated(true)
                                    ->columnSpanFull(),


                                // -------- RIEPILOGO COMPLETO (anche in CREATE) --------
                                View::make('forms.riepilogo_section')
                                    ->viewData(function (Get $get, $livewire) {
                                        $record = $livewire->record ?? null;
                                        $form = $get();

                                        // Helper: prendi dai record se esiste, altrimenti dal form
                                        $pick = function ($path, $default = null) use ($record, $form) {
                                            $v = data_get($form, $path);
                                            if ((is_null($v) || $v === '') && $record) {
                                                $v = data_get($record, $path);
                                            }

                                            return $v ?? $default;
                                        };

                                        $isGitaGiornaliera = (bool) $pick('gita_giornaliera', false);

                                        // Collezioni grezze per create/edit
                                        $hotelPreventives = $isGitaGiornaliera ? [] : $pick('hotel_preventives', []);
                                        $traspAndata = $pick('trasporto_andata', null);
                                        $traspRientro = $pick('trasporto_rientro', null);
                                        $traspIntermedi = $pick('trasporto_intermedio', []);
                                        $extraServices = $pick('extra_services', []);

                                        $persone = max((int) $pick('numero_persone', 0), 0);
                                        //$forzate = (int) $pick('n_persone_forzato', 0);
                                        /*  $persone = $forzate > 0
                                             ? $forzate
                                             : max((int) $pick('numero_persone', 0), 0); */
                                        $markup = (int) $pick('markup', 0);
                                        $gratuite = max((int) $pick('numero_gratuita', 0), 0);
                                        $pagantiBase = max($persone - $gratuite, 1);
                                        $paganti = $pagantiBase;

                                        // -------- HOTEL: dettagli e totale --------
                                        $hotelDettaglio = collect($hotelPreventives)->map(function ($hotel) {
                                            $hotelNome = optional(Hotel::find($hotel['hotel_id'] ?? null))?->nome ?? '-';
                                            $rooms = collect($hotel['rooms_paganti'] ?? collect())
                                                ->merge($hotel['rooms_gratuite'] ?? collect());
                                            if ($rooms->isEmpty())
                                                return "{$hotelNome}: nessuna stanza inserita.";

                                            return $rooms->map(function ($room) use ($hotelNome) {
                                                $tipo = $room['tipo_costo'] ?? 'a persona';
                                                $notti = (int) ($room['n_notti'] ?? 0);
                                                $costo = (float) ($room['costo_notte'] ?? 0);
                                                $quantita = (int) ($room['quantita_camere'] ?? 1);
                                                $paganti = (int) ($room['numero_paganti_stanza'] ?? 0);
                                                $gratuita = (bool) ($room['gratuita'] ?? false);
                                                $numGrat = (int) ($room['numero_gratuita_stanza'] ?? 0);

                                                if ($tipo === 'a persona') {
                                                    $persone = $gratuita ? $numGrat : $paganti;
                                                    $totale = $costo * $notti * max(1, $persone);
                                                    $tipoLabel = $gratuita ? 'gratuità' : 'paganti';
                                                    return "<b>{$hotelNome}</b>: " . number_format($totale, 2, ',', '.') .
                                                        " € ({$room['tipologia_stanza']} – {$notti} notti × €{$costo}/notte × {$persone} {$tipoLabel})";
                                                }

                                                if ($tipo === 'a camera') {
                                                    $totale = $costo * $notti * max(1, $quantita);
                                                    return "<b>{$hotelNome}</b>: " . number_format($totale, 2, ',', '.') .
                                                        " € ({$room['tipologia_stanza']} – {$notti} notti × €{$costo}/notte × {$quantita} camere)";
                                                }

                                                return '';
                                            })->filter()->implode('<br>');
                                        })->implode('<hr>');

                                        $hotelTot = collect($hotelPreventives)->reduce(function ($carry, $hotel) {
                                            $rooms = collect($hotel['rooms_paganti'] ?? collect())
                                                ->merge($hotel['rooms_gratuite'] ?? collect());

                                            foreach ($rooms as $room) {
                                                $tipo = $room['tipo_costo'] ?? 'a persona';
                                                $costo = (float) ($room['costo_notte'] ?? 0);
                                                $notti = (int) ($room['n_notti'] ?? 0);
                                                $quantita = (int) ($room['quantita_camere'] ?? 1);

                                                if ($tipo === 'a persona') {
                                                    $persone = ((int) ($room['numero_paganti_stanza'] ?? 0))
                                                        + ((int) ($room['numero_gratuita_stanza'] ?? 0));
                                                    $carry += $costo * $notti * max(1, $persone);
                                                }

                                                if ($tipo === 'a camera') {
                                                    $carry += $costo * $notti * max(1, $quantita);
                                                }
                                            }

                                            return $carry;
                                        }, 0);


                                        // -------- TRASPORTI: dettagli e totale --------
                            



                                        // Partecipanti e paganti
                                        $nPartecipanti = max($persone, 1);
                                        $nPaganti = max($paganti, 1);

                                        // --- DETTAGLIO ANDATA/RIENTRO ---
                                        /* Qui stai "impacchettando" i dati dei due trasporti in un unico contenitore.

La Chiave ('Andata', 'Rientro') è l'etichetta che useremo nel testo del riepilogo.

Il Valore ($traspAndata, $traspRientro) sono gli array (o oggetti) che contengono i prezzi e i tipi di costo.

2. Il Ciclo Intelligente (->map(...))
Il metodo map attraversa la collezione. La cosa fondamentale è che accetta due argomenti nella funzione:

PHP
function ($t, $tipoViaggio) ...
$t: È il contenuto (i dati del trasporto). Nel primo giro sarà $traspAndata, nel secondo $traspRientro.

$tipoViaggio: È la chiave dell'array. Laravel la passa automaticamente. Quindi varrà prima "Andata" e poi "Rientro". */
                                        $traspBlocks = collect([
                                            'Andata' => $traspAndata,
                                            'Rientro' => $traspRientro,
                                        ])->map(function ($t, $tipoViaggio) use ($nPartecipanti, $nPaganti) {
                                            $scorpora = $t['scorpora_trasporto'] ?? $t->scorpora_trasporto;

                                            $tipo = ucfirst($t['tipo_trasporto'] ?? 'N/D');
                                            $prezzo = $t['prezzo'] ?? 0;
                                            $tc = $t['tipo_costo'] ?? 'a persona';

                                            if ($scorpora) {
                                                return "<b>{$tipoViaggio}</b> = <span style='color: #ef4444; font-weight: bold;'>ESCLUSO (Scorporato)</span>";
                                            }

                                            if ($tc === 'a persona') {
                                                return "{$tipoViaggio}: {$tipo} × {$nPartecipanti} ({$tc}) → € {$prezzo}";
                                            }

                                            return "{$tipoViaggio}: {$tipo} ({$tc} ÷ {$nPaganti} paganti) → € {$prezzo}";
                                        })
                                            ->implode('<br>');


                                        // --- DETTAGLIO INTERMEDI ---
                                        $traspBlocks .= collect($traspIntermedi ?? [])
                                            ->values()
                                            ->map(function ($t, $i) use ($nPartecipanti, $nPaganti) {

                                            $tipo = ucfirst($t['tipo_trasporto'] ?? 'N/D');
                                            $prezzo = $t['prezzo'] ?? 0;
                                            $tc = $t['tipo_costo'] ?? 'a persona';

                                            if ($tc === 'a persona') {
                                                return "<br>Intermedio #" . ($i + 1) . ": {$tipo} × {$nPartecipanti} ({$tc}) → € {$prezzo}";
                                            }

                                            return "<br>Intermedio #" . ($i + 1) . ": {$tipo} ({$tc} ÷ {$nPaganti} paganti) → € {$prezzo}";
                                        })
                                            ->implode('');


                                        // --- TOTALE TRASPORTI ---
                                        $traspTot = 0;

                                        // Andata + Rientro
                                        foreach (array_filter([$traspAndata, $traspRientro]) as $t) {
                                            $scorpora = (bool) ($t['scorpora_trasporto'] ?? false);
                                            if ($scorpora) {
                                                continue;
                                            }
                                            $prezzo = $t['prezzo'] ?? 0;
                                            $tc = $t['tipo_costo'] ?? '';

                                            $traspTot += ($tc === 'a persona')
                                                ? $prezzo * $nPartecipanti
                                                : $prezzo;
                                        }



                                        // -------- SERVIZI EXTRA: dettagli e totale --------
                                        $serviziDett = collect($extraServices)
                                            ->map(function ($s) use ($nPartecipanti, $nPaganti) {
                                            // Gestisci sia array che oggetti
                                            /* Esattamente. In Filament, la distinzione tra quando i dati sono un array e quando sono un oggetto è fondamentale per non far crashare l'applicazione.

Ecco come funziona il "dietro le quinte":

1. In fase di Creazione (CreateRecord)
In questa fase il database non ha ancora visto nulla.

Stato dei dati: Tutto quello che scrivi nel form vive temporaneamente nella memoria di Livewire come un array associativo.

Accesso: Per leggere un valore devi usare la sintassi delle parentesi quadre: $data['prezzo'].

Perché? Perché non esiste ancora un "Record" (una riga nella tabella del DB) a cui associare un oggetto Eloquent.

2. In fase di Modifica (EditRecord)
Qui il record esiste già nel database.

Stato dei dati: Quando carichi la pagina, Filament recupera la riga dal DB e la trasforma in un Modello Eloquent (Oggetto).

Accesso: In teoria dovresti usare la sintassi della freccia: $record->prezzo. */
                                            $isArrray = is_array($s);
                                            $serviceId = $isArrray ? ($s['extra_service_id'] ?? null) : ($s->extra_service_id ?? null);
                                            $scorpora = $isArrray ? ($s['scorpora_servizio'] ?? false) : ($s->scorpora_servizio ?? false);

                                            $service = ExtraService::find($serviceId);
                                            $tipo = optional(ExtraService::find($serviceId))->tipo ?? '-';
                                            $nome = $service ? ($service->nome ?: '-') : '';

                                            if ($scorpora) {
                                                return "<b>{$nome}</b> = <span style='color: #ef4444; font-weight: bold;'>ESCLUSO (Scorporato)</span>";
                                            }

                                            $tc = $isArrray ? ($s['tipo_costo'] ?? 'a_persona') : ($s->tipo_costo ?? 'a_persona');
                                            $prezzo = $isArrray ? ($s['prezzo'] ?? 0) : ($s->prezzo ?? 0);

                                            if ($tc === 'a_persona') {
                                                $q = $isArrray ? (int) ($s['quantita_a_persona'] ?? 1) : (int) ($s->quantita_a_persona ?? 1);
                                                return ($nome != "" ? "$nome" : $tipo) . " = € {$prezzo} (a persona × {$nPartecipanti} × {$q})";
                                            }

                                            // UNA TANTUM
                                            $q = is_array($s) ? (int) ($s['quantita'] ?? 1) : (int) ($s->quantita ?? 1);
                                            return ($nome != "" ? "$nome" : $tipo) . " = € {$prezzo} (una tantum × {$q})";
                                        })
                                            ->implode('<br>');
                                        //test
                            
                                        // -------- TOTALE SERVIZI --------
                                        $serviziTot = collect($extraServices)->reduce(function ($carry, $s) use ($nPartecipanti, $nPaganti) {
                                            // Gestisci sia array che oggetti
                                            $scorpora = is_array($s)
                                                ? ($s['scorpora_servizio'] ?? false)
                                                : ($s->scorpora_servizio ?? false);

                                            if ($scorpora) {
                                                return $carry;
                                            }

                                            $prezzo = is_array($s) ? ($s['prezzo'] ?? 0) : ($s->prezzo ?? 0);
                                            $tc = is_array($s) ? ($s['tipo_costo'] ?? 'a_persona') : ($s->tipo_costo ?? 'a_persona');

                                            if ($tc === 'a_persona') {
                                                $q = is_array($s) ? (int) ($s['quantita_a_persona'] ?? 1) : (int) ($s->quantita_a_persona ?? 1);
                                                $carry += $prezzo * $q * $nPartecipanti;
                                            } else {
                                                // UNA TANTUM
                                                $q = is_array($s) ? (int) ($s['quantita'] ?? 1) : (int) ($s->quantita ?? 1);
                                                $carry += ($prezzo * $q);
                                            }

                                            return $carry;
                                        }, 0);
                                        $cleanHtml = function ($html) {
                                            if (empty($html))
                                                return '';

                                            // Se è un array (molto probabile nel repeater), lo trasformiamo in stringa
                                            if (is_array($html)) {
                                                // Se è la struttura JSON di Tiptap convertita in array
                                                if (isset($html['type']) && $html['type'] === 'doc') {
                                                    // Estraiamo ricorsivamente tutto il testo dai nodi
                                                    $extractText = function ($node) use (&$extractText) {
                                                        $text = $node['text'] ?? '';
                                                        if (isset($node['content'])) {
                                                            foreach ($node['content'] as $child) {
                                                                $text .= $extractText($child) . ' ';
                                                            }
                                                        }
                                                        return $text;
                                                    };
                                                    $html = $extractText($html);
                                                } else {
                                                    // Altrimenti facciamo un implode brutale dei valori testuali
                                                    $html = collect($html)->flatten()->filter(fn($v) => is_string($v))->implode(' ');
                                                }
                                            }

                                            // Se è una stringa che contiene ancora il JSON "doc paragraph"
                                            if (is_string($html) && (str_contains($html, '"type":"doc"') || str_contains($html, 'doc paragraph'))) {
                                                // Tentiamo di pulire i nomi dei nodi JSON se il parsing è fallito
                                                $html = preg_replace('/"type":"[^"]*"|"name":"[^"]*"|doc|paragraph|start|content|text|{|}|\d|\[|\]|:/i', '', $html);
                                                $html = str_replace(['"', ','], '', $html);
                                            }

                                            // Infine, pulizia HTML classica
                                            return trim(
                                                preg_replace([
                                                    '#<p><br></p>#i',
                                                    '#<p>&nbsp;</p>#i',
                                                    '#^\s*<br\s*/?>#i',
                                                    '#<\/p>\s*<p>#i',
                                                ], ' ', strip_tags((string) $html, '<b><i><strong>')) // strip_tags aiuta a pulire il resto
                                            );
                                        };

                                        // -------- Quote comprende / non comprende --------
                                        $cleanAndFilter = function ($item) use ($cleanHtml) {
                                            $cleaned = $cleanHtml($item);
                                            $hasContent = !empty($cleaned);
                                            return $hasContent ? "<li>{$cleaned}</li>" : null;
                                        };
                                        $quotaComprende = collect([
                                            ...collect($hotelPreventives)->pluck('quota_comprende_hotel')->filter()->toArray(),
                                            data_get($traspAndata, 'quota_comprende_trasporti'),
                                            data_get($traspRientro, 'quota_comprende_trasporti'),
                                            ...collect($traspIntermedi)->pluck('quota_comprende_trasporti')->filter()->toArray(),
                                            ...collect($extraServices)->pluck('quota_comprende_servizi')->filter()->toArray(),
                                            $pick('quota_comprende_generico'),
                                        ])->filter()->map($cleanAndFilter)// <--- QUI: Laravel prende ogni elemento dell'array e lo passa come $item
                                            ->implode('');

                                        $quotaNonComprende = collect([
                                            ...collect($hotelPreventives)->pluck('quota_non_comprende_hotel')->filter()->toArray(),
                                            data_get($traspAndata, 'quota_non_comprende_trasporti'),
                                            data_get($traspRientro, 'quota_non_comprende_trasporti'),
                                            ...collect($traspIntermedi)->pluck('quota_non_comprende_trasporti')->filter()->toArray(),
                                            ...collect($extraServices)->pluck('quota_non_comprende_servizi')->filter()->toArray(),
                                            $pick('quota_non_comprende_generico'),
                                        ])->filter()->map($cleanAndFilter)// <--- QUI: Laravel prende ogni elemento dell'array e lo passa come $item
                                            ->implode('');

                                        // -------- Totale costi (hotel+trasporti+servizi) --------
                                        $totaleCosti = $hotelTot + $traspTot + $serviziTot;

                                        // -------- Prezzo per persona e incasso (dal form calcolato) --------
                                        [$quotaInd, $totIncasso] = self::calcolaCostoPerPersona($form);

                                        // -------- Metadati testata --------
                                        $fieldsHead = [
                                            'Creato da' => $record?->creator
                                                ? trim(($record->creator->nome ?? '') . ' ' . ($record->creator->cognome ?? ''))
                                                : '-',
                                            'Stato' => ($pick('stato') instanceof \UnitEnum)
                                                ? $pick('stato')->value
                                                : $pick('stato', '-'),
                                            'Tipo Preventivo' => $pick('tipo_preventivo') === 'libero' ? 'Preventivo Libero' : 'Preventivo da Richiesta',
                                            'Numero Preventivo' => $pick('numero', '-'),
                                            'Anno' => $pick('anno', '-'),
                                            'Data Preventivo' => $pick('data_preventivo')
                                                ? Carbon::parse($pick('data_preventivo'))->format('d-m-Y') : '-',
                                            'Data Validità' => $pick('date_expiration'),
                                            'Cliente' => (function () use ($record, $pick) {
                                            // se è già associato al record, prendo da relazione Eloquent
                                            if ($record?->customer) {
                                                return trim(($record->customer->nome ?? '') . ' ' . ($record->customer->cognome ?? ''));
                                            }

                                            // altrimenti provo a leggerlo dal form (create mode)
                                            $customer = $pick('customer') ?? $pick('customer_id');
                                            if (is_array($customer)) {
                                                return trim(($customer['nome'] ?? '') . ' ' . ($customer['cognome'] ?? ''));
                                            }

                                            // se è solo l'id, puoi opzionalmente caricarlo da DB
                                            if (is_numeric($customer)) {
                                                $c = Customer::find($customer);
                                                return $c ? trim(($c->nome ?? '') . ' ' . ($c->cognome ?? '')) : '-';
                                            }

                                            return '-';
                                        })(),

                                            'Numero Persone' => $persone ?: '-',
                                            'Numero Gratuità' => $gratuite ?: '-',
                                            'Numero Paganti' => $paganti ?: '-',
                                            'Data Inizio Viaggio' => $pick('data_inizio_viaggio')
                                                ? Carbon::parse($pick('data_inizio_viaggio'))->format('d-m-Y') : '-',
                                            'Data Fine Viaggio' => $pick('data_fine_viaggio')
                                                ? Carbon::parse($pick('data_fine_viaggio'))->format('d-m-Y') : '-',
                                        ];

                                        return [
                                            'fields' => [
                                                ...$fieldsHead,

                                                //---File-----
                                                'File fornitore Hotel' => new \Illuminate\Support\HtmlString(
                                                    collect($record->hotel_preventives ?? [])->map(function ($hotel) {
                                                        $files = $hotel['file_fornitore_hotel'] ?? [];
                                                        if (empty($files)) {
                                                            return 'Nessun file caricato';
                                                        }

                                                        return collect($files)->map(function ($file) {
                                                            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                                return "File (in caricamento...)";
                                                            } elseif (is_string($file)) {
                                                                $url = asset('storage/' . $file);
                                                                $name = basename($file);
                                                                return "<a href='{$url}' target='_blank' class='text-blue-600 underline'>Visualizza file</a>";
                                                            }
                                                            return null;
                                                        })->filter()->implode('<br>');
                                                    })->implode('<hr>') ?: 'Nessun file caricato'
                                                ),
                                                'File fornitore Andata' => new \Illuminate\Support\HtmlString(
                                                    collect($record?->trasporto_andata['file_fornitore_trasporto'] ?? [])->map(function ($file) {
                                                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                            return "File (in caricamento...)";
                                                        } elseif (is_string($file)) {
                                                            $url = asset('storage/' . $file);
                                                            $name = basename($file);
                                                            return "<a href='{$url}' target='_blank' class='text-blue-600 underline'>Visualizza file</a>";
                                                        }
                                                        return null;
                                                    })->filter()->implode('<br>') ?: 'Nessun file caricato'
                                                ),
                                                'File fornitore Rientro' => new \Illuminate\Support\HtmlString(
                                                    collect($record?->trasporto_rientro['file_fornitore_trasporto'] ?? [])->map(function ($file) {
                                                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                            return "File (in caricamento...)";
                                                        } elseif (is_string($file)) {
                                                            $url = asset('storage/' . $file);
                                                            $name = basename($file);
                                                            return "<a href='{$url}' target='_blank' class='text-blue-600 underline'>Visualizza file</a>";
                                                        }
                                                        return null;
                                                    })->filter()->implode('<br>') ?: 'Nessun file caricato'
                                                ),

                                                'File fornitore Servizi Extra' => new \Illuminate\Support\HtmlString(
                                                    collect($record?->extra_services ?? [])->map(function ($servizio) {
                                                        $files = $servizio['file_fornitore_servizi_extra'] ?? [];
                                                        if (empty($files))
                                                            return 'Nessun file caricato';

                                                        return collect($files)->map(function ($file) {
                                                            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                                return "File (in caricamento...)";
                                                            } elseif (is_string($file)) {
                                                                $url = asset('storage/' . $file);
                                                                $name = basename($file);
                                                                return "<a href='{$url}' target='_blank' class='text-blue-600 underline'>Visualizza file</a>";
                                                            }
                                                            return null;
                                                        })->filter()->implode('<br>');
                                                    })->filter()->implode('<hr>') ?: 'Nessun file caricato'
                                                ),

                                                // --- Dettagli economici ---
                                                ...($isGitaGiornaliera ? [] : [
                                                    'Prezzi dettaglio hotel' => new \Illuminate\Support\HtmlString($hotelDettaglio ?: '—'),
                                                    'Prezzo totale hotel' => '€ ' . number_format((int) $hotelTot, 0, ',', '.'),
                                                ]),

                                                'Prezzi dettaglio trasporti' => new \Illuminate\Support\HtmlString($traspBlocks ?: '—'),
                                                'Prezzo totale trasporti' => '€ ' . number_format((int) $traspTot, 0, ',', '.'),

                                                'Prezzi dettaglio servizi' => new \Illuminate\Support\HtmlString($serviziDett ?: '—'),
                                                'Prezzo totale servizi' => '€ ' . number_format((int) $serviziTot, 0, ',', '.'),

                                                'Totale costi' => '€ ' . number_format((int) $totaleCosti, 0, ',', '.'),
                                                // --- Quota/Incasso (live) ---
                                                'Prezzo per persona' => '€ ' . number_format($quotaInd, 0, ',', '.'),
                                                'Totale incasso ' => '€ ' . number_format((int) $totIncasso, 0, ',', '.'),

                                                // --- Comprende / Non comprende ---
                                                'La quota comprende' => new \Illuminate\Support\HtmlString($quotaComprende ?: '-'),
                                                'La quota non comprende' => new \Illuminate\Support\HtmlString($quotaNonComprende ?: '-'),
                                            ],
                                        ];
                                    }),

                                TextInput::make('totale_incasso')
                                    ->label('Totale Incasso')
                                    ->prefix('€')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(function (Get $get) {
                                        [, $tot] = self::calcolaCostoPerPersona($get());
                                        return $tot;
                                    })
                                    ->afterStateHydrated(function (Set $set, Get $get) {
                                        [, $tot] = self::calcolaCostoPerPersona($get());
                                        $set('totale_incasso', $tot);
                                    }),

                            ]),

                        ValidatedTab::make('Invio Email', [
                            'email_template_id',
                            'email_cliente',
                        ])
                            ->hiddenOn('create')
                            ->schema([
                                Select::make('email_template_id')
                                    ->label('Template Email')
                                    ->options(EmailTemplate::pluck('nome', 'id'))
                                    ->required(fn($livewire) => !$livewire->isDraft)
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($template = EmailTemplate::find($state)) {
                                            $set('corpo_email', $template->corpo_email);
                                        }
                                    })
                                    ->afterStateHydrated(function ($state, $set, $get, $record) {
                                        // Se non c'è record O il record non esiste nel DB O non ci sono email, esci
                                        if (!$record || !$record->exists || !$record->emails()->exists()) {
                                            return;
                                        }

                                        // Carica l'ultima email solo se esistente E corrisponde al cliente corrente
                                        if (!$state) {
                                            $lastEmail = $record->emails()
                                                ->where('customer_id', $record->customer_id)
                                                ->orderBy('created_at', 'desc')
                                                ->first();

                                            if ($lastEmail) {
                                                if (empty($get('email_template_id'))) {
                                                    $set('email_template_id', $lastEmail->email_template_id);
                                                }
                                                if (empty($get('corpo_email'))) {
                                                    $set('corpo_email', $lastEmail->corpo_email);
                                                }

                                                if (empty($get('email_cc'))) {
                                                    $set('email_cc', collect($lastEmail->email_cc ?? [])
                                                        ->map(fn($cc) => ['email_cc' => $cc])
                                                        ->toArray());
                                                }
                                                if (empty($get('allegati'))) {
                                                    $set('allegati', $lastEmail->allegati ?? []);
                                                }
                                            }
                                        }
                                    })
                                    ->createOptionForm([
                                        TextInput::make('nome')->required()->columnSpanFull(),
                                        RichEditor::make('corpo_email')
                                            ->toolbarButtons(['bold', 'bulletList', 'italic', 'orderedList', 'redo', 'underline', 'undo'])
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->createOptionUsing(fn(array $data) => EmailTemplate::create($data)->getKey()),

                                TextInput::make('email_cliente')
                                    ->label('Email Cliente')
                                    ->dehydrated(true)
                                    ->live()
                                    ->readOnly()
                                    ->required(fn($livewire) => !$livewire->isDraft)
                                    ->afterStateHydrated(function ($state, Set $set, $record) {
                                        // SEMPRE forza l'email del cliente corrente
                                        if ($record && $record->customer && $record->customer->email) {
                                            $set('email_cliente', $record->customer->email);
                                        }
                                    }),

                                Repeater::make('email_cc')
                                    ->label('')
                                    ->schema([
                                        TextInput::make('email_cc')->label('Email CC'),
                                    ])
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->addActionLabel('Aggiungi Email CC')
                                    ->columnSpanFull(),

                                RichEditor::make('corpo_email')
                                    ->label('Corpo Email')
                                    ->live(onBlur: true)
                                    ->dehydrated(true)
                                    ->toolbarButtons(['bold', 'bulletList', 'italic', 'orderedList', 'redo', 'underline', 'undo'])
                                    ->columnSpanFull(),



                                FileUpload::make('allegati')
                                    ->directory('allegati_email')
                                    ->multiple()
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(3072)
                                    ->preserveFilenames()
                                    ->label('Allegati'),

                                Group::make([
                                    Actions::make([
                                        Action::make('inviaEmail')
                                            ->label('Salva & Invia Email')
                                            ->color('success')

                                            ->requiresConfirmation()
                                            ->action(function ($livewire, array $data) {
                                                try {
                                                    $livewire->validate();
                                                } catch (\Filament\Support\Exceptions\Halt $e) {
                                                    Notification::make()
                                                        ->title('Compila tutti i campi obbligatori')
                                                        ->danger()
                                                        ->persistent()
                                                        ->send();
                                                    return;
                                                }
                                                /** @var Preventive|null $preventivo */
                                                $preventivo = $livewire->record;
                                                $formData = $livewire->form->getState();

                                                $allegoFile = $formData['allego_file'] ?? false;

                                                if (!$allegoFile) {
                                                    //  Validazione hotel ed extra services
                                                    $hasHotels = false;
                                                    $hasServices = false;

                                                    // Controlla se il record esiste già
                                                    if ($preventivo && $preventivo->exists) {
                                                        // Record esistente: controlla le relazioni caricate
                                                        $hasHotels = $preventivo->hotel_preventives()->exists();
                                                        $hasServices = $preventivo->extra_services()->exists();
                                                    } else {
                                                        // Nuovo record: controlla i dati del form
                                                        $hasHotels = !empty($formData['hotel_preventives']);
                                                        $hasServices = !empty($formData['extra_services']);
                                                    }



                                                    // Blocca se mancano hotel
                                                    if (!$hasHotels && $formData['gita_giornaliera'] === false) {
                                                        Notification::make()
                                                            ->title('Hotel mancanti')
                                                            ->body('Devi inserire almeno un hotel.')
                                                            ->danger()
                                                            ->persistent()
                                                            ->send();
                                                        return;
                                                    }

                                                    // Blocca se mancano servizi extra
                                                    if (!$hasServices) {
                                                        Notification::make()
                                                            ->title('Servizi Extra mancanti')
                                                            ->body('Devi inserire almeno un servizio.')
                                                            ->danger()
                                                            ->persistent()
                                                            ->send();
                                                        return;
                                                    }
                                                    $itinerari = $formData['itinerario'] ?? [];

                                                    $itinerariSenzaFoto = collect($itinerari)->filter(function ($item) {
                                                        $foto = $item['immagini'] ?? [];
                                                        return empty($foto) || count($foto) < 3;
                                                    });

                                                    if ($itinerariSenzaFoto->isNotEmpty() && ($formData['tipo_visualizzazione_foto'] === 'per_giorno')) {
                                                        Notification::make()
                                                            ->title('Salvataggio interrotto')
                                                            ->body('Le tappe dell\'itinerario non hanno le 3 immagini richieste.')
                                                            ->danger()
                                                            ->persistent()
                                                            ->send();
                                                        return;
                                                    }

                                                    //  VALIDAZIONE 3: Controlla foto negli hotel
                                                    $hotelIds = collect();

                                                    if ($preventivo && $preventivo->exists) {
                                                        // Record esistente: prendi gli hotel già salvati
                                                        $hotelIds = $preventivo->hotel_preventives()->pluck('hotel_id')->filter()->unique();
                                                    } else {
                                                        // Nuovo record: prendi gli hotel dal form
                                                        $hotelIds = collect($formData['hotel_preventives'] ?? [])
                                                            ->pluck('hotel_id')
                                                            ->filter()
                                                            ->unique();
                                                    }

                                                    if ($hotelIds->isNotEmpty()) {
                                                        $hotels = Hotel::whereIn('id', $hotelIds)->get();

                                                        $hotelSenzaFoto = $hotels->filter(function ($hotel) {
                                                            $foto = $hotel->foto;

                                                            // Se è una stringa JSON, decodificala
                                                            if (is_string($foto)) {
                                                                $decoded = json_decode($foto, true);
                                                                $foto = is_array($decoded) ? $decoded : [];
                                                            }

                                                            // Se è null o array vuoto
                                                            if ($foto === null || (is_array($foto) && count($foto) === 0)) {
                                                                return true; // Hotel senza foto
                                                            }

                                                            return false;
                                                        });

                                                        if ($hotelSenzaFoto->isNotEmpty()) {
                                                            $nomi = $hotelSenzaFoto->pluck('nome')->join(', ');

                                                            Notification::make()
                                                                ->title('Salvataggio interrotto')
                                                                ->body("I seguenti hotel non hanno foto: {$nomi}.")
                                                                ->danger()
                                                                ->persistent()
                                                                ->send();
                                                            return;
                                                        }
                                                    }
                                                    $hotelSenzaStanze = [];

                                                    if ($preventivo && $preventivo->exists) {
                                                        // Record esistente: controlla le relazioni
                                                        foreach ($preventivo->hotel_preventives as $hotelPrev) {
                                                            $totalRooms = $hotelPrev->rooms_paganti()->count() + $hotelPrev->rooms_gratuite()->count();

                                                            if ($totalRooms === 0) {
                                                                $hotelSenzaStanze[] = $hotelPrev->hotel->nome ?? 'Hotel senza nome';
                                                            }
                                                        }
                                                    } else {
                                                        // Nuovo record: controlla i dati del form
                                                        foreach ($formData['hotel_preventives'] ?? [] as $hotelData) {
                                                            $hasRooms = !empty($hotelData['rooms_paganti']) || !empty($hotelData['rooms_gratuite']);

                                                            if (!$hasRooms) {
                                                                $hotelId = $hotelData['hotel_id'] ?? null;
                                                                if ($hotelId) {
                                                                    $hotel = Hotel::find($hotelId);
                                                                    $hotelSenzaStanze[] = $hotel?->nome ?? 'Hotel senza nome';
                                                                }
                                                            }
                                                        }
                                                    }

                                                    if (!empty($hotelSenzaStanze)) {
                                                        $nomi = implode(', ', $hotelSenzaStanze);

                                                        Notification::make()
                                                            ->title('Salvataggio interrotto')
                                                            ->body("I seguenti hotel non hanno stanze (paganti o gratuite): {$nomi}.")
                                                            ->danger()
                                                            ->persistent()
                                                            ->send();
                                                        return;
                                                    }
                                                }
                                                $emailDraft = [
                                                    'email_template_id' => $formData['email_template_id'] ?? null,
                                                    'email_cliente' => $formData['email_cliente'] ?? null,
                                                    'email_cc' => collect($formData['email_cc'] ?? [])->pluck('email_cc')->filter()->values()->toArray(),
                                                    'corpo_email' => $formData['corpo_email'] ?? null,
                                                    'allegati' => $formData['allegati'] ?? [],
                                                ];
                                                if (empty($emailDraft['email_cliente']) && empty($emailDraft['email_cc'])) {
                                                    Notification::make()
                                                        ->title('Destinatario mancante')
                                                        ->body('Inserisci almeno un indirizzo email del cliente.')
                                                        ->danger()
                                                        ->persistent()
                                                        ->send();
                                                    return;
                                                }

                                                //  Salva usando il metodo standard di Filament
                                                if (!$preventivo || !$preventivo->exists) {
                                                    try {
                                                        // Rimuovi i campi email temporanei dal form data
                                                        $dataToSave = $formData;
                                                        unset(
                                                            $dataToSave['email_template_id'],
                                                            //$dataToSave['email_cliente'],
                                                            $dataToSave['email_cc'],
                                                            $dataToSave['corpo_email'],
                                                            $dataToSave['allegati']
                                                        );

                                                        // Usa il metodo save() di Filament
                                                        $livewire->form->model($preventivo ?? Preventive::class)->saveRelationships();
                                                        $preventivo = $livewire->form->model(Preventive::class)->create($dataToSave);
                                                        $livewire->form->model($preventivo)->saveRelationships();
                                                        $livewire->record = $preventivo;

                                                    } catch (\Throwable $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Errore durante il salvataggio del preventivo.')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                } else {
                                                    // Se il record esiste già, aggiornalo
                                                    try {
                                                        $dataToSave = $formData;
                                                        unset(
                                                            $dataToSave['email_template_id'],
                                                            // $dataToSave['email_cliente'],
                                                            $dataToSave['email_cc'],
                                                            $dataToSave['corpo_email'],
                                                            $dataToSave['allegati']
                                                        );

                                                        $preventivo->update($dataToSave);
                                                        $livewire->form->model($preventivo)->saveRelationships();

                                                    } catch (\Throwable $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Errore durante l\'aggiornamento del preventivo.')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                }

                                                //  Normalizza email CC
                                                $emailCc = collect($formData['email_cc'] ?? [])
                                                    ->pluck('email_cc')
                                                    ->filter()
                                                    ->values()
                                                    ->toArray();

                                                //  Gestione allegati
                                                $storedFiles = [];
                                                if (!empty($formData['allegati']) && is_array($formData['allegati'])) {
                                                    foreach ($formData['allegati'] as $file) {
                                                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                            $storedFiles[] = $file->store('allegati_email', 'public');
                                                        } elseif (is_string($file)) {
                                                            $storedFiles[] = $file;
                                                        } elseif (is_array($file)) {
                                                            $path = reset($file);
                                                            if (is_string($path)) {
                                                                $storedFiles[] = $path;
                                                            }
                                                        }
                                                    }
                                                }




                                                $existingEmail = $preventivo->emails()->latest('updated_at')->first();

                                                if ($existingEmail) {
                                                    $existingEmail->update([
                                                        'customer_id' => $preventivo->customer_id,
                                                        'sent_by' => $preventivo->created_by,
                                                        'email_template_id' => $emailDraft['email_template_id'],
                                                        'email_cliente' => $emailDraft['email_cliente'],
                                                        'email_cc' => $emailDraft['email_cc'],
                                                        'corpo_email' => $emailDraft['corpo_email'],
                                                        'allegati' => $storedFiles,
                                                        'quote_request_id' => $preventivo->quote_request_id ?: null,
                                                        'is_draft' => false,
                                                    ]);

                                                    $email = $existingEmail->fresh();

                                                    \Log::info('Email AGGIORNATA', [
                                                        'email_id' => $email->id,
                                                        'updated_at' => $email->updated_at,
                                                    ]);
                                                } else {
                                                    $email = Email::create([
                                                        'customer_id' => $preventivo->customer_id,
                                                        'sent_by' => $preventivo->created_by,
                                                        'email_template_id' => $emailDraft['email_template_id'],
                                                        'email_cliente' => $emailDraft['email_cliente'],
                                                        'email_cc' => $emailDraft['email_cc'],
                                                        'corpo_email' => $emailDraft['corpo_email'],
                                                        'allegati' => $storedFiles,
                                                        'quote_request_id' => $preventivo->quote_request_id ?: null,
                                                        'is_draft' => false,
                                                    ]);

                                                    $preventivo->emails()->attach($email->id);

                                                    \Log::info('Email CREATA', [
                                                        'email_id' => $email->id,
                                                    ]);
                                                }
                                                try {
                                                    if (empty($email->email_cliente)) {
                                                        throw new \Exception('Email cliente non presente.');
                                                    }

                                                    $mail = \Illuminate\Support\Facades\Mail::to($email->email_cliente);

                                                    if (!empty($email->email_cc)) {
                                                        $mail->cc($email->email_cc);
                                                    }

                                                     $mail->send(new \App\Mail\PreventiveCreatedMail($preventivo, $email));
                                    
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Email inviata con successo')
                                                        ->success()
                                                        ->send();

                                                } catch (\Throwable $e) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Errore durante l\'invio della email.')
                                                        ->body($e->getMessage())
                                                        ->danger()
                                                        ->send();

                                                    \Log::error('Errore invio email', [
                                                        'error' => $e->getMessage(),
                                                        'email_cliente' => $email->email_cliente ?? 'NULL',
                                                    ]);

                                                    return;
                                                }

                                                //  Aggiorna stato preventivo
                                                $preventivo->update([
                                                    'data_invio' => $preventivo->data_invio ?? now(),
                                                    'stato' => PreventiveStatus::IN_ATTESA,
                                                ]);

                                                if (!empty($preventivo->quote_request)) {
                                                    $preventivo->quote_request->update([
                                                        'stato_richiesta' => QuoteRequestStatus::EVASA,
                                                    ]);
                                                }
                                            }),
                                    ]),
                                ])->columnSpanFull(),
                            ])
                            ->columnSpanFull()





                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function calcolaCostoPerPersona(array $test): array
    {

        $personeTot = 0;
        $partecipanti = (int) ($test['numero_persone'] ?? 0);
        //$persone_forzate = (int) ($test['n_persone_forzato'] ?? 0);

        /* if (!empty($persone_forzate) && $persone_forzate > 0) {
            $personeTot = (int) $persone_forzate;
        } else {
            $personeTot = $partecipanti;
        } */
        $personeTot = $partecipanti;

        $gratuite = (int) ($test['numero_gratuita'] ?? 0);
        $paganti = max(1, $personeTot - $gratuite);



        $totale = 0;

        $isGitaGiornaliera = (bool) ($test['gita_giornaliera'] ?? false);

        // ---------------- HOTEL ----------------

        //  Evita doppioni dovuti a ->relationship() + ->live()
        if (!$isGitaGiornaliera) {
            $hotelPreventives = collect($test['hotel_preventives'] ?? [])
                ->map(fn($item) => (array) $item)
                ->unique(fn($item) => $item['hotel_id'] ?? spl_object_id((object) $item))
                ->values()
                ->toArray();

            foreach ($hotelPreventives as $hotel) {
                // Somma sia stanze paganti che gratuite
                $allRooms = array_merge($hotel['rooms_paganti'] ?? [], $hotel['rooms_gratuite'] ?? []);

                foreach ($allRooms as $room) {
                    $tipo_costo = $room['tipo_costo'] ?? 'a persona';
                    $costo_notte = (float) ($room['costo_notte'] ?? 0);
                    $n_notti = (int) ($room['n_notti'] ?? 0);
                    $quantita = (int) ($room['quantita_camere'] ?? 1);
                    $isGratuita = (bool) ($room['gratuita'] ?? false);

                    // Persone nella stanza
                    $personeInStanza = $isGratuita
                        ? (int) ($room['numero_gratuita_stanza'] ?? 0)
                        : (int) ($room['numero_paganti_stanza'] ?? 0);

                    $costo_unitario = $costo_notte * $n_notti;

                    if ($tipo_costo === 'a persona') {
                        //  Usa il numero corretto di persone, includendo anche le gratuite
                        $totale += $costo_unitario * max(1, $personeInStanza);
                    } elseif ($tipo_costo === 'a camera') {
                        $totale += $costo_unitario * max(1, $quantita);
                    }
                }
            }
        }

        // ---------------- TRASPORTI ----------------
        $tutti_trasporti = array_values(array_filter([
            $test['trasporto_andata'] ?? null,
            $test['trasporto_rientro'] ?? null,
            ...($test['trasporto_intermedio'] ?? []),
        ]));

        foreach ($tutti_trasporti as $trasporto) {
            $tipo_costo = $trasporto['tipo_costo'] ?? null;
            $prezzo = (float) ($trasporto['prezzo'] ?? 0);
            $scorpora = (bool) ($trasporto['scorpora_trasporto'] ?? false);
            if ($scorpora) {
                continue;
            }

            if ($tipo_costo === 'a persona') {
                $totale += $prezzo * $personeTot;
            } elseif ($tipo_costo === 'una tantum') {
                $totale += $prezzo;
            }
        }

        // ---------------- SERVIZI EXTRA ----------------
        foreach ($test['extra_services'] ?? [] as $service) {
            $tipo_costo = $service['tipo_costo'] ?? null;
            $prezzo = (float) ($service['prezzo'] ?? 0);
            $scorpora = (bool) ($service['scorpora_servizio'] ?? false);
            $quantita = (int) ($service['quantita'] ?? 1);
            $quantita_persona = (int) ($service['quantita_a_persona'] ?? 1);

            if ($scorpora) {
                continue;
            }

            if ($tipo_costo === 'a_persona') {
                $totale += $prezzo * $quantita_persona * $personeTot;
            } elseif ($tipo_costo === 'una_tantum') {
                $totale += $prezzo * $quantita;
            }
        }

        $markup = (float) ($test['markup'] ?? 0);


        $quota_individuale = ($paganti > 0 ? ($totale / $paganti) : 0) + $markup;


        $quota_individuale = round($quota_individuale);
        $totale_incasso = $quota_individuale * $paganti;

        return [$quota_individuale, $totale_incasso];
    }



}
