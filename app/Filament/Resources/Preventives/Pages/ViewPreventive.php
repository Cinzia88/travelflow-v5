<?php

namespace App\Filament\Resources\Preventives\Pages;

use App\Filament\Resources\Preventives\PreventiveResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPreventive extends ViewRecord
{
    protected static string $resource = PreventiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
