<?php

namespace App\Filament\Resources\ArchivedRequests\Pages;

use App\Filament\Resources\ArchivedRequests\ArchivedRequestsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArchivedRequests extends EditRecord
{
    protected static string $resource = ArchivedRequestsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
