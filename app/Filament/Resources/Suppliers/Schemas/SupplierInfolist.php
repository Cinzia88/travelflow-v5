<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('affidabilita')
                    ->placeholder('-'),
                TextEntry::make('tipologia')
                    ->placeholder('-'),
                TextEntry::make('nome')
                    ->placeholder('-'),
                TextEntry::make('cognome')
                    ->placeholder('-'),
                TextEntry::make('ragione_sociale')
                    ->placeholder('-'),
                TextEntry::make('piva_cf')
                    ->placeholder('-'),
                TextEntry::make('codice_fiscale')
                    ->placeholder('-'),
                TextEntry::make('indirizzo')
                    ->placeholder('-'),
                TextEntry::make('regione')
                    ->placeholder('-'),
                TextEntry::make('stato')
                    ->placeholder('-'),
                TextEntry::make('citta')
                    ->placeholder('-'),
                TextEntry::make('cap')
                    ->placeholder('-'),
                TextEntry::make('provincia')
                    ->placeholder('-'),
                TextEntry::make('descrizione')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
