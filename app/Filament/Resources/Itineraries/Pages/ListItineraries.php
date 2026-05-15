<?php

namespace App\Filament\Resources\Itineraries\Pages;

use App\Filament\Resources\Itineraries\ItineraryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListItineraries extends ListRecords
{
    protected static string $resource = ItineraryResource::class;

    protected static ?string $title = 'Lista Itinerari';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
