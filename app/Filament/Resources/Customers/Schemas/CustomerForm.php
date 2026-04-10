<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ]);
    }
}
