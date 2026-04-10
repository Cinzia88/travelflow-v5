<?php

namespace App\Filament\Resources\TransportCompanies\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransportCompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->label('Nome')
                    ->maxLength(255),
                TextInput::make('misura_bg_a_mano')
                    ->label('Misura Bagagli a Mano'),
                FileUpload::make('immagine')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                    ->maxSize(1024)
                    ->helperText('Carica un\'immagine (.png, .jpg o .jpeg)')
                    ->label('Logo')
                    ->preserveFilenames(),
            ]);
    }
}
