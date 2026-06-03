<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Models\Supplier;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                 ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('note')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
