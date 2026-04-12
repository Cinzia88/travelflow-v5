<?php

namespace App\Filament\Resources\ExtraServices\Schemas;

use App\Models\Supplier;
use App\Services\ServiceIconProvider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;

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
                        Select::make('type_supplier')
                            ->label('Tipologia Fornitore')
                            ->multiple()
                            ->relationship('type_supplier', 'tipologia_fornitore')
                            ->preload()
                            ->searchable(),
                            

                        Select::make('competence_area')
                            ->label('Area Geografica di Competenza')
                            ->multiple()
                            ->relationship('competence_area', 'area')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('area')
                                    ->label('Aggiungi Area Geografica di Competenza')
                                    ->required(),
                            ])
                            ->editOptionForm([
                                TextInput::make('area')
                                    ->label('Modifica Area Geografica di Competenza')
                                    ->required(),
                            ]),
                        Select::make('reliability_id')
                            ->label('Affidabilità')
                            ->relationship('reliability', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Group::make()
                                    ->schema([
                                        TextInput::make('nome')->required(),
                                        Select::make('colore')
                                            ->label('Colore badge')
                                            ->options([
                                                'gray' => 'Grigio',
                                                'primary' => 'Blu',
                                                'success' => 'Verde',
                                                'warning' => 'Giallo',
                                                'danger' => 'Rosso',
                                                'info' => 'Azzurro',
                                                'purple' => 'Viola',
                                                'pink' => 'Rosa',
                                            ])
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ])
                            ->editOptionForm([
                                Group::make()
                                    ->schema([
                                        TextInput::make('nome')->required(),
                                        Select::make('colore')
                                            ->label('Colore badge')
                                            ->options([
                                                'gray' => 'Grigio',
                                                'primary' => 'Blu',
                                                'success' => 'Verde',
                                                'warning' => 'Giallo',
                                                'danger' => 'Rosso',
                                                'info' => 'Azzurro',
                                                'purple' => 'Viola',
                                                'pink' => 'Rosa',
                                            ])
                                            ->required()
                                    ])
                                    ->columns(2),
                            ]),
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
