<?php

namespace App\Filament\Resources\Emails\Schemas;

use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Preventive;
use App\Models\QuoteRequest;
use App\Models\User;
use App\PreventiveStatus;
use App\QuoteRequestStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EmailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 TextInput::make('sent_by')
                    ->label('Inviata da')
                    ->disabled()
                    ->visibleOn('edit')
                    ->default(Auth::id())
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return null;
                        }
                        $user = User::find($state);
                        return $user ? "{$user->nome} {$user->cognome}" : 'Utente Rimosso';
                    })
                    ->columnSpanFull()
                    ->dehydrated(false),
                Select::make('tipo_preventivo')
                    ->label('Tipo Preventivo')
                    ->options([
                        'libero' => 'Preventivo Libero',
                        'con_richiesta' => 'Preventivo da Richiesta',
                    ])
                    ->default('libero')
                    ->required()
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, Set $set) {
                        // se è libero, svuoto l’eventuale richiesta collegata
                        if ($state === 'libero') {
                            $set('quote_request_id', null);
                        }
                    })
                    ->columnSpan([
                        'default' => 1,
                        'md' => fn(Get $get): int|string => $get('tipo_preventivo') === 'con_richiesta' ? 'full' : 1,
                    ]),
                Select::make('quote_request_id')
                    ->relationship('quote_request', 'oggetto', modifyQueryUsing: function ($query) {
                        if (auth()->user()->hasAnyRole(['admin', 'superadmin'])) {
                            return $query->where('stato_richiesta', '!=', QuoteRequestStatus::EVASA);
                        }
                        return $query->where('stato_richiesta', '!=', QuoteRequestStatus::EVASA)
                            ->where(function ($q) {
                                $q->where('created_by', auth()->id())
                                    ->orWhereHas('agenti_gestori', function ($agentiQuery) {
                                        $agentiQuery->where('user_id', auth()->id());
                                    });
                            });
                    })
                    ->preload()
                    ->hidden(fn(Get $get): bool => $get('tipo_preventivo') !== 'con_richiesta')
                    ->live(debounce: 500)
                    ->getSearchResultsUsing(function (string $search) {
                        return QuoteRequest::query()
                            ->where('stato_richiesta', '!=', QuoteRequestStatus::EVASA)
                            ->where(function ($query) {
                                $query->where('created_by', auth()->id())
                                    ->orWhereHas('agenti_gestori', function ($agentiQuery) {
                                        $agentiQuery->where('user_id', auth()->id());
                                    });
                            })->where(function ($query) use ($search) {
                                $query->where('oggetto', 'like', "%{$search}%")
                                    ->orWhere('id', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($supplier) {
                                return [
                                    $supplier->id => trim("{$supplier->nome} {$supplier->cognome}"),
                                ];
                            });
                    })
                    // quando cambia la richiesta, imposto il customer_id associato
                    ->afterStateUpdated(function ($state, Set $set) {
                        $req = QuoteRequest::find($state);
                        if ($req) {
                            $set('customer_id', $req->customer_id);
                            $set('meta_viaggio', $req->meta_viaggio);
                        }
                        if ($req && $req->customer?->email) {
                            $set('email_cliente', $req->customer->email);
                        }
                    })
                    ->columnSpanfull()
                    ->label('Richiesta')
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn(QuoteRequest $record) => "{$record->id} - {$record->oggetto}")
                    ->required(),
                Select::make('customer_id')
                    ->relationship('customer', 'nome')
                    ->preload()
                    ->label('Cliente')
                    ->searchable()
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, Set $set) { //$state è il numero del record
                        $customer = Customer::find($state);

                        if ($customer) {
                            $set('email_cliente', $customer->email);
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
                    ->getOptionLabelFromRecordUsing(fn(Customer $record) => "{$record->nome} {$record->cognome}")
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
                            ->maxLength(255)
                            ->default(null),
                    ])

                    ->required(),
                Select::make('preventives')
                    ->relationship('preventives', 'id', modifyQueryUsing: function (Builder $query) {

                        // Filtra per stato
                        $query->where('stato', '!=', PreventiveStatus::BOZZA);

                       
                        // Admin e Superadmin vedono tutti i preventivi (nessun filtro aggiuntivo)
            
                        return $query;
                    })
                    ->searchable()
                    ->label('Preventivi')
                    ->preload()
                    ->getSearchResultsUsing(function (string $search) {
                        $currentUserId = auth()->id();

                        return Preventive::query()
                            ->with('creator')
                            ->where('stato', '!=', PreventiveStatus::BOZZA)
                            ->where(function ($query) use ($search) {
                                $query->where('numero', 'like', "%{$search}%")
                                    ->orWhere('titolo', 'like', "%{$search}%")
                                    ->orWhereHas('creator', function ($q) use ($search) {
                                        $q->where('nome', 'like', "%{$search}%")
                                            ->orWhere('cognome', 'like', "%{$search}%")
                                            ->orWhereRaw("CONCAT(nome, ' ', cognome) LIKE ?", ["%{$search}%"]);
                                    });
                            })
                            ->orderByRaw('CASE WHEN created_by = ? THEN 0 ELSE 1 END', [$currentUserId])
                            ->orderBy('created_at', 'desc')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($preventive) {
                                // Gestisci il nome dell'agente con cognome opzionale
                                $agentName = $preventive->creator
                                    ? ' | Creato da: ' . trim("{$preventive->creator->nome} " . ($preventive->creator->cognome ?? ''))
                                    : '';

                                return [
                                    $preventive->id =>
                                        'N° ' . $preventive->numero .
                                        ' - ' . $preventive->titolo .
                                        $agentName .
                                        ' (' . $preventive->created_at->format('d/m/Y') . ')',
                                ];
                            });
                    })
                    ->getOptionLabelFromRecordUsing(
                        fn(Preventive $record) =>
                        "{$record->numero} - {$record->titolo}" .
                        ($record->creator
                            ? " | Creato da: " . trim("{$record->creator->nome} " . ($record->creator->cognome ?? ''))
                            : " | Creato da: Agente Rimosso"
                        )
                    )
                    ->multiple()
                    ->label('Preventivi'),
                //test

                Select::make('email_template_id')
                    ->relationship('template_email', 'nome')
                    ->required()
                    ->preload()
                    ->label('Template Email')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, Set $set) { //$state è il numero del record
                        $template_email = EmailTemplate::find($state);

                        if ($template_email) {
                            $set('corpo_email', $template_email->corpo_email);
                        }
                    })
                    ->searchable(),
                TextInput::make('email_cliente')
                    ->label('Email Cliente')
                    ->email()
                    ->disabled()/* anche se il campo è disabilitato il suo valore viene comunque “deidratato” (cioè incluso nei dati inviati a Filament quando salvi il record). */
                    ->dehydrated(),
                Repeater::make('email_cc')
                    ->label('')
                    ->schema([
                        TextInput::make('email_cc')
                            ->label('Email CC'),
                    ])
                    ->addActionLabel('Aggiungi Email CC')
                    ->columnSpanFull(),


                RichEditor::make('corpo_email')
                    ->label('Corpo Email')
                    ->extraInputAttributes(['style' => 'min-height: 300px;'])
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

                FileUpload::make('allegati')
                    ->multiple()
                    ->maxSize(3072)
                    ->acceptedFileTypes(['application/pdf'])
                    ->preserveFilenames()
                    ->directory('allegati_email')
                    ->label('Allegati')
                    ->columnSpanFull(),
            ]);
    }
}
