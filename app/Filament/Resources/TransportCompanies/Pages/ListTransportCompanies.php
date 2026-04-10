<?php

namespace App\Filament\Resources\TransportCompanies\Pages;

use App\Filament\Resources\TransportCompanies\TransportCompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransportCompanies extends ListRecords
{
    protected static string $resource = TransportCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
