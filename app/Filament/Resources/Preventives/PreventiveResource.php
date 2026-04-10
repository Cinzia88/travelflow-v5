<?php

namespace App\Filament\Resources\Preventives;

use App\Filament\Resources\Preventives\Pages\CreatePreventive;
use App\Filament\Resources\Preventives\Pages\EditPreventive;
use App\Filament\Resources\Preventives\Pages\ListPreventives;
use App\Filament\Resources\Preventives\Pages\ViewPreventive;
use App\Filament\Resources\Preventives\Schemas\PreventiveForm;
use App\Filament\Resources\Preventives\Schemas\PreventiveInfolist;
use App\Filament\Resources\Preventives\Tables\PreventivesTable;
use App\Models\Preventive;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PreventiveResource extends Resource
{
    protected static ?string $model = Preventive::class;

    //protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Preventivi';


    protected static ?string $navigationLabel = 'Lista Preventivi';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return PreventiveForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PreventiveInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PreventivesTable::configure($table);
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
            'index' => ListPreventives::route('/'),
            'create' => CreatePreventive::route('/create'),
            'view' => ViewPreventive::route('/{record}'),
            'edit' => EditPreventive::route('/{record}/edit'),
        ];
    }
}
