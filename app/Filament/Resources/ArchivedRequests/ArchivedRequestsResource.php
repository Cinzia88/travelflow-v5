<?php

namespace App\Filament\Resources\ArchivedRequests;

use App\Filament\Resources\ArchivedRequests\Pages\CreateArchivedRequests;
use App\Filament\Resources\ArchivedRequests\Pages\EditArchivedRequests;
use App\Filament\Resources\ArchivedRequests\Pages\ListArchivedRequests;
use App\Filament\Resources\ArchivedRequests\Schemas\ArchivedRequestsForm;
use App\Filament\Resources\ArchivedRequests\Tables\ArchivedRequestsTable;
use App\Models\ArchivedRequests;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ArchivedRequestsResource extends Resource
{
    protected static ?string $model = ArchivedRequests::class;

   // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
   protected static string|UnitEnum|null $navigationGroup = 'Richieste';

    protected static ?string $navigationLabel = 'Richieste Archiviate';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return ArchivedRequestsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArchivedRequestsTable::configure($table);
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
            'index' => ListArchivedRequests::route('/'),
            'create' => CreateArchivedRequests::route('/create'),
            'edit' => EditArchivedRequests::route('/{record}/edit'),
        ];
    }
}
