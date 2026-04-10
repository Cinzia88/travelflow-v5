<?php

namespace App\Filament\Resources\TransportCompanies\Pages;

use App\Filament\Resources\TransportCompanies\TransportCompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTransportCompany extends EditRecord
{
    protected static string $resource = TransportCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
