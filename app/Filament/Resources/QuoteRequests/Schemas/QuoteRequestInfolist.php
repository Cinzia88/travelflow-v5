<?php

namespace App\Filament\Resources\QuoteRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuoteRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
               Section::make('Dettagli Richiesta')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('oggetto')
                                ->label('Oggetto'),

                            TextEntry::make('tipo_richiesta')
                                ->label('Tipo Richiesta')
                                ->badge(), // Lo mostra come un badge pulito

                            TextEntry::make('customer.nome')
                                ->label('Cliente')
                                ->formatStateUsing(fn ($record) => $record->customer ? "{$record->customer->nome} {$record->customer->cognome}" : 'Non assegnato'),

                            TextEntry::make('email_cliente')
                                ->label('Email Cliente')
                                ->copyable(), // Permette di copiare l'email con un clic

                            TextEntry::make('data_ricezione_richiesta')
                                ->label('Data Ricezione')
                                ->date('d/m/Y'),

                            TextEntry::make('stato_richiesta')
                                ->label('Stato')
                                ->badge(),
                        ])
                ]),

            Section::make('Note e Archivio')
                ->schema([
                    TextEntry::make('motivazione_archivio')
                        ->label('Motivazione Archiviazione')
                        ->visible(fn ($record) => $record->stato_richiesta === 'archiviata'),

                    TextEntry::make('note')
                        ->label('Note della Richiesta')
                        ->markdown(), // Se usi formattazione nelle note
                ])
    
            ]);
    }
}
