<?php

namespace App\Filament\Resources\ExtraServices;

use App\Filament\Resources\ExtraServices\Pages\CreateExtraService;
use App\Filament\Resources\ExtraServices\Pages\EditExtraService;
use App\Filament\Resources\ExtraServices\Pages\ListExtraServices;
use App\Filament\Resources\ExtraServices\Pages\ViewExtraService;
use App\Filament\Resources\ExtraServices\Schemas\ExtraServiceForm;
use App\Filament\Resources\ExtraServices\Schemas\ExtraServiceInfolist;
use App\Filament\Resources\ExtraServices\Tables\ExtraServicesTable;
use App\Models\ExtraService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ExtraServiceResource extends Resource
{
    protected static ?string $model = ExtraService::class;

    //protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

       protected static string|UnitEnum|null $navigationGroup = 'Servizi';

    protected static ?string $navigationLabel = 'Servizi Extra';
    protected static ?int $navigationSort = 17;

    public static function form(Schema $schema): Schema
    {
        return ExtraServiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExtraServiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExtraServicesTable::configure($table);
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
            'index' => ListExtraServices::route('/'),
            'create' => CreateExtraService::route('/create'),
            'edit' => EditExtraService::route('/{record}/edit'),
        ];
    }
}
