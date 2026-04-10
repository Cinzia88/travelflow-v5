<?php

namespace App\Filament\Resources\ArchivedRequests\Pages;

use App\Filament\Resources\ArchivedRequests\ArchivedRequestsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArchivedRequests extends ListRecords
{
    protected static string $resource = ArchivedRequestsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
