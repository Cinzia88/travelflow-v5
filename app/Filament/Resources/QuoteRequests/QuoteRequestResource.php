<?php

namespace App\Filament\Resources\QuoteRequests;

use App\Filament\Resources\QuoteRequests\Pages\CreateQuoteRequest;
use App\Filament\Resources\QuoteRequests\Pages\EditQuoteRequest;
use App\Filament\Resources\QuoteRequests\Pages\ListQuoteRequests;
use App\Filament\Resources\QuoteRequests\Pages\ViewQuoteRequest;
use App\Filament\Resources\QuoteRequests\Schemas\QuoteRequestForm;
use App\Filament\Resources\QuoteRequests\Schemas\QuoteRequestInfolist;
use App\Filament\Resources\QuoteRequests\Tables\QuoteRequestsTable;
use App\Models\QuoteRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class QuoteRequestResource extends Resource
{
    protected static ?string $model = QuoteRequest::class;

    //protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

     protected static string|UnitEnum|null $navigationGroup = 'Richieste';

    protected static ?string $navigationLabel = 'Richieste Interne';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return QuoteRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuoteRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuoteRequestsTable::configure($table);
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
            'index' => ListQuoteRequests::route('/'),
            'create' => CreateQuoteRequest::route('/create'),
            'view' => ViewQuoteRequest::route('/{record}'),
            'edit' => EditQuoteRequest::route('/{record}/edit'),
        ];
    }
}
