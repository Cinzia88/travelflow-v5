<?php

namespace App\Filament\Resources\ExtraServices\Pages;

use App\Filament\Resources\ExtraServices\ExtraServiceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExtraService extends ViewRecord
{
    protected static string $resource = ExtraServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
