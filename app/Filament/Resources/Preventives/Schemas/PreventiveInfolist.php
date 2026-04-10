<?php

namespace App\Filament\Resources\Preventives\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PreventiveInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
