<?php

namespace App\Filament\Resources\Itineraries\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;


class ItineraryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')->label('Nome')
                    ->columnSpanFull()
                    ->required(),

                Select::make('tipo_visualizzazione_foto') // Sistemato l'errore di battitura
                    ->label('Tipo di Visualizzazione delle Foto')
                    ->options([
                        'per_giorno' => 'Foto specifiche per ogni giorno',
                        'in_fondo' => 'Tutte le foto alla fine dell\'itinerario',
                    ])
                    ->default('per_giorno')
                    ->live()
                    ->columnSpanFull(),

                Repeater::make('itinerario')
                    ->label('Programma Giornaliero')
                    ->schema([
                        TextInput::make('titolo')->label('Titolo')
                            ->columnSpanFull()
                            ->required(),
                        RichEditor::make('descrizione')
                            ->json()
                            ->toolbarButtons([
                                'bold', 'bulletList', 'italic', 'orderedList', 'redo', 'underline', 'undo',
                            ]),
                            
                        FileUpload::make('immagini')
                            ->label('Immagini Itinerario')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                            // Usa ../../ perché siamo dentro il repeater
                            ->hidden(fn(Get $get) => $get('../../tipo_visualizzazione_foto') === 'in_fondo')
                            ->required(fn(Get $get) => $get('../../tipo_visualizzazione_foto') === 'per_giorno')
                            ->multiple()
                            ->minFiles(0) // Messo a 0 per non bloccare se un giorno non ha foto
                            ->maxFiles(3)
                            ->preserveFilenames()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('preventivi')
                            ->dehydrated(true)
                            ->columnSpanFull(),
                    ])
                    ->addActionLabel('Aggiungi itinerario')
                    ->columnSpanFull(),

                FileUpload::make('immagini_itinerario')
                    ->label('Galleria Fotografica (In fondo)')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                    ->multiple()
                    ->hidden(fn(Get $get) => $get('tipo_visualizzazione_foto') === 'per_giorno')
                    ->required(fn(Get $get) => $get('tipo_visualizzazione_foto') === 'in_fondo')
                    ->minFiles(0)
                    ->maxFiles(6)
                    ->preserveFilenames()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('preventivi')
                    ->dehydrated(true)
                    ->helperText('Carica le immagini per la galleria finale dell\'itinerario.')
                    ->columnSpanFull(),
            ]);
    }
}