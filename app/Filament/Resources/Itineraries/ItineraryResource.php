<?php

namespace App\Filament\Resources\Itineraries;

use App\Filament\Resources\Itineraries\Pages\CreateItinerary;
use App\Filament\Resources\Itineraries\Pages\EditItinerary;
use App\Filament\Resources\Itineraries\Pages\ListItineraries;
use App\Filament\Resources\Itineraries\Pages\ViewItinerary;
use App\Filament\Resources\Itineraries\Schemas\ItineraryForm;
use App\Filament\Resources\Itineraries\Schemas\ItineraryInfolist;
use App\Filament\Resources\Itineraries\Tables\ItinerariesTable;
use App\Models\Itinerary;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ItineraryResource extends Resource
{
    protected static ?string $model = Itinerary::class;

    //protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Itinerari';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ItineraryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ItineraryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItinerariesTable::configure($table);
    }

    public static function getBreadcrumb(): string
    {
        return 'Itinerari';
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItineraries::route('/'),
            'create' => CreateItinerary::route('/create'),
            'view' => ViewItinerary::route('/{record}'),
            'edit' => EditItinerary::route('/{record}/edit'),
        ];
    }
}
