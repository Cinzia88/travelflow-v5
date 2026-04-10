<?php

namespace App\Filament\Resources\TransportCompanies\Pages;

use App\Filament\Resources\TransportCompanies\TransportCompanyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTransportCompany extends ViewRecord
{
    protected static string $resource = TransportCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
