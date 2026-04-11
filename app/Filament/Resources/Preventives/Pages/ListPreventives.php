<?php

namespace App\Filament\Resources\Preventives\Pages;

use App\Filament\Resources\Preventives\PreventiveResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPreventives extends ListRecords
{
    protected static string $resource = PreventiveResource::class;

    protected static ?string $title = 'Lista Preventivi';


    

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
