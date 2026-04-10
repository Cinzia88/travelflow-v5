<?php

namespace App\Filament\Resources\Hotels\Schemas;

use App\Models\Supplier;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use SebastianBergmann\CodeUnit\FileUnit;

class HotelForm
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
                        Repeater::make('telefono')
                            ->schema([
                                TextInput::make('telefono')
                                    ->label('Telefono')
                                    ->required(),
                            ])
                            ->addActionLabel('Aggiungi Numero di Telefono')
                            ->label('Numeri di Telefono')
                            ->columns(1)
                            ->collapsible()
                            ->defaultItems(1),
                        Repeater::make('email')
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->unique(ignorable: fn($record) => $record)
                                    ->required(),
                            ])
                            ->addActionLabel('Aggiungi Email')
                            ->label('Emails')
                            ->columns(1)
                            ->collapsible()
                            ->defaultItems(1),
                        Repeater::make('sito_web')
                            ->schema([
                                TextInput::make('sito_web')
                                    ->label('Sito Web')
                                    ->required(),
                            ])
                            ->addActionLabel('Aggiungi Sito Web')
                            ->label('Siti Web')
                            ->columns(1)
                            ->collapsible()
                            ->defaultItems(1),
                        Repeater::make('portale_web')
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
                            ->defaultItems(1),
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
                TextInput::make('nome')
                    ->required()
                    ->label('Nome')
                    ->maxLength(255),
                TextInput::make('indirizzo')
                    ->label('Indirizzo')
                    ->maxLength(255),
                TextInput::make('stelle')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(5),
                RichEditor::make('descrizione')
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
                FileUpload::make('foto')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                    ->minSize(50)
                    ->maxSize(1024)
                    ->preserveFilenames()
                    ->multiple()
                    ->disk('public')
                    ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per hotel/alloggio. Minimo 50KB.')
                    ->minFiles(3)
                    ->maxFiles(3)
                    ->visibility('public')
                    ->directory('foto_hotel')
                    ->columnSpanFull()
                    ->label('Foto'),

            ]);
    }
}
