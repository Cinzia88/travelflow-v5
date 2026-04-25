<?php

namespace App\Filament\Resources\QuoteRequests\Pages;

use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use App\QuoteRequestStatus;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListQuoteRequests extends ListRecords
{
    protected static ?string $title = 'Richieste Interne';
    protected static string $resource = QuoteRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Tutte' => Tab::make(),
            'Archiviate' => Tab::make()->query(fn ($query) => $query->where('stato_richiesta', QuoteRequestStatus::ARCHIVIATA)),
        ];
    }
}
