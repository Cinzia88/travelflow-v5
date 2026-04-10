<?php

namespace App\Filament\Resources\Hotels;

use App\Filament\Resources\Hotels\Pages\CreateHotel;
use App\Filament\Resources\Hotels\Pages\EditHotel;
use App\Filament\Resources\Hotels\Pages\ListHotels;
use App\Filament\Resources\Hotels\Pages\ViewHotel;
use App\Filament\Resources\Hotels\Schemas\HotelForm;
use App\Filament\Resources\Hotels\Schemas\HotelInfolist;
use App\Filament\Resources\Hotels\Tables\HotelsTable;
use App\Models\Hotel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    //protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

      protected static string|UnitEnum|null $navigationGroup = 'Servizi';

    protected static ?string $navigationLabel = 'Alloggi/Hotel';
    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return HotelForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HotelInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HotelsTable::configure($table);
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
            'index' => ListHotels::route('/'),
            'create' => CreateHotel::route('/create'),
            'view' => ViewHotel::route('/{record}'),
            'edit' => EditHotel::route('/{record}/edit'),
        ];
    }
}
