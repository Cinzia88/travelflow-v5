<?php

namespace App\Filament\Resources\QuoteRequests\Schemas;

use App\Models\Customer;
use App\Models\User;
use App\QuoteRequestStatus;
use App\Services\OptionsTravel;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class QuoteRequestForm
{
    
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo_richiesta')
                    ->label('Tipo Richiesta')
                    ->options(OptionsTravel::getOptionsTravel())
                    ->searchable()
                    ->live(),
                TextInput::make('created_by')
                    ->label('Creata da')
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

                TextInput::make('oggetto')
                    ->label('Oggetto')
                    ->maxLength(255)
                    ->required(),
                DatePicker::make('data_ricezione_richiesta')
                    ->displayFormat('d/m/Y')
                    ->label('Data Ricezione Richiesta')
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
                    ->getOptionLabelFromRecordUsing(fn(Customer $record) => "{$record->nome} {$record->cognome}")
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
                    //->getOptionLabelFromRecordUsing(fn(Customer $record) => "{$record->nome} {$record->cognome}")
                    ->createOptionForm([

                        Select::make('tipo_cliente')
                            ->label('Tipo Cliente')
                            ->options([
                                'azienda' => 'Azienda',
                                'privato' => 'Privato',
                                'scuola' => 'Scuola',
                            ])
                            ->default(null)
                            ->live()
                            ->required(),
                        Group::make()
                            ->schema([
                                TextInput::make('nome')
                                    ->label('Nome')
                                    ->required(),
                                TextInput::make('cognome')
                                    ->label('Cognome')
                                    ->nullable()
                                    ->visible(fn($get) => $get('tipo_cliente') === 'privato'),
                            ])
                            ->columns(2),
                        Select::make('genere')
                            ->label('Genere')
                            ->options([
                                'donna' => 'Donna',
                                'uomo' => 'Uomo',
                            ])
                            ->default(null)
                            ->required()
                            ->visible(fn($get) => $get('tipo_cliente') === 'privato'),
                        Group::make()
                            ->schema([
                                TextInput::make('ragione_sociale')
                                    ->label('Ragione Sociale')
                                    ->visible(fn($get) => in_array($get('tipo_cliente'), ['azienda', 'scuola']))
                                    ->default(null),
                                TextInput::make('piva_cf')
                                    ->label('P. Iva')
                                    ->maxLength(255)
                                    ->default(null),
                            ])
                            ->columns(2),
                        Group::make()
                            ->schema([
                                TextInput::make('indirizzo')
                                    ->label('Indirizzo')
                                    ->maxLength(255)
                                    ->default(null),
                                TextInput::make('citta')
                                    ->label('Città')
                                    ->maxLength(255)
                                    ->default(null),
                            ])
                            ->columns(2),
                        Group::make()
                            ->schema([
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
                            ])

                            ->columns(3),


                        Group::make()
                            ->schema([
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

                            ->columns(2),
                    ])
                    ->editOptionForm([

                        Select::make('tipo_cliente')
                            ->label('Tipo Cliente')
                            ->options([
                                'azienda' => 'Azienda',
                                'privato' => 'Privato',
                                'scuola' => 'Scuola',
                            ])
                            ->default(null)
                            ->live()
                            ->required(),
                        Group::make()
                            ->schema([
                                TextInput::make('nome')
                                    ->label('Nome')
                                    ->required(),
                                TextInput::make('cognome')
                                    ->label('Cognome')
                                    ->nullable()
                                    ->visible(fn($get) => $get('tipo_cliente') === 'privato'),
                            ])
                            ->columns(2),
                        Group::make()
                            ->schema([
                                TextInput::make('ragione_sociale')
                                    ->label('Ragione Sociale')
                                    ->visible(fn($get) => in_array($get('tipo_cliente'), ['azienda', 'scuola']))
                                    ->default(null),
                                TextInput::make('piva_cf')
                                    ->label('P. Iva')
                                    ->maxLength(255)
                                    ->default(null),
                            ])
                            ->columns(2),
                        Group::make()
                            ->schema([
                                TextInput::make('indirizzo')
                                    ->label('Indirizzo')
                                    ->maxLength(255)
                                    ->default(null),
                                TextInput::make('citta')
                                    ->label('Città')
                                    ->maxLength(255)
                                    ->default(null),
                            ])
                            ->columns(2),
                        Group::make()
                            ->schema([
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
                            ])

                            ->columns(3),


                        Group::make()
                            ->schema([
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

                            ->columns(2),
                    ])
                    ->required(),
                TextInput::make('email_cliente')
                    ->label('Email Cliente')
                    ->email()
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('meta_viaggio')
                    ->maxLength(255)
                    ->label('Meta Viaggio')
                    ->default(null),

                Select::make('stato_richiesta')
                    ->label('Stato')
                    ->options(QuoteRequestStatus::class)
                    ->default(QuoteRequestStatus::CREATA->value)
                    ->live()
                    ->required(),

                Textarea::make('motivazione_archivio')
                    ->label('Motivazione Archiviazione')
                    ->visible(fn(Get $get) => $get('stato_richiesta') == 'archiviata')
                    ->required(fn(Get $get): bool => $get('stato_richiesta') === 'archiviata')
                    ->dehydrated(fn(Get $get) => $get('stato') == false)
                    ->maxLength(255)
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('note')
                    ->columnSpanFull(),
            ]);
    }
}
