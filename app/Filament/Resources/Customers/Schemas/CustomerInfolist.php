<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tipo_cliente')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('genere')
                    ->placeholder('-'),
                TextEntry::make('nome')
                    ->placeholder('-'),
                TextEntry::make('cognome')
                    ->placeholder('-'),
                TextEntry::make('ragione_sociale')
                    ->placeholder('-'),
                TextEntry::make('piva_cf')
                    ->placeholder('-'),
                TextEntry::make('indirizzo')
                    ->placeholder('-'),
                TextEntry::make('citta')
                    ->placeholder('-'),
                TextEntry::make('cap')
                    ->placeholder('-'),
                TextEntry::make('provincia')
                    ->placeholder('-'),
                TextEntry::make('stato')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('telefono')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
