<?php

namespace App\Filament\Resources\Itineraries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ItineraryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nome')
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
