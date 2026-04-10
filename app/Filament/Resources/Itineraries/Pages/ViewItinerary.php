<?php

namespace App\Filament\Resources\Itineraries\Pages;

use App\Filament\Resources\Itineraries\ItineraryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewItinerary extends ViewRecord
{
    protected static string $resource = ItineraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
