<?php

namespace App\Filament\Resources\Itineraries\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ItineraryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')->label('Nome')
                    ->columnSpanFull()
                    ->required(),
                Repeater::make('itinerario')
                    ->label('')
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
                            ->minSize(50)
                            ->maxSize(1024)
                            ->multiple()
                            ->required()
                            ->minFiles(3)
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
            ]);
    }
}
