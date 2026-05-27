<?php

namespace App\Filament\Resources\ExtraServices\Schemas;

use App\Models\Supplier;
use App\Services\ServiceIconProvider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ExtraServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                Select::make('tipo')
                    ->required()
                    ->dehydrated(true)
                    ->searchable()
                    ->label('Tipologia')
                    ->options(fn() => ServiceIconProvider::getIconsService()),
                TextInput::make('nome')
                    ->maxLength(255)
                    ->columnSpanFull(),
                RichEditor::make('descrizione_servizio')
                    ->toolbarButtons([
                        'bold',
                        'bulletList',
                        'italic',
                        'orderedList',
                        'redo',
                        'underline',
                        'undo',
                    ])
                    ->label('Descrizione')
                    ->columnSpanFull(),
                FileUpload::make('allegati')
                    ->multiple()
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(3072)
                    ->preserveFilenames()
                    ->directory('allegati_servizi')
                    ->label('Allegati')
                    ->columnSpanFull(),
            ]);
    }
}
