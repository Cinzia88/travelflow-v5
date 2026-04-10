<?php

namespace App\Filament\Resources\TransportCompanies;

use App\Filament\Resources\TransportCompanies\Pages\CreateTransportCompany;
use App\Filament\Resources\TransportCompanies\Pages\EditTransportCompany;
use App\Filament\Resources\TransportCompanies\Pages\ListTransportCompanies;
use App\Filament\Resources\TransportCompanies\Pages\ViewTransportCompany;
use App\Filament\Resources\TransportCompanies\Schemas\TransportCompanyForm;
use App\Filament\Resources\TransportCompanies\Schemas\TransportCompanyInfolist;
use App\Filament\Resources\TransportCompanies\Tables\TransportCompaniesTable;
use App\Models\TransportCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TransportCompanyResource extends Resource
{
    protected static ?string $model = TransportCompany::class;

    //protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

     protected static string|UnitEnum|null $navigationGroup = 'Servizi';

    protected static ?string $navigationLabel = 'Aziende di Trasporti';

    protected static ?int $navigationSort = 16;

    public static function form(Schema $schema): Schema
    {
        return TransportCompanyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransportCompanyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransportCompaniesTable::configure($table);
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
            'index' => ListTransportCompanies::route('/'),
            'create' => CreateTransportCompany::route('/create'),
            'view' => ViewTransportCompany::route('/{record}'),
            'edit' => EditTransportCompany::route('/{record}/edit'),
        ];
    }
}
