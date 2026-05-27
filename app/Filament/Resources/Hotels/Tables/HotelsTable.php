<?php

namespace App\Filament\Resources\Hotels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HotelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
               TextColumn::make('supplier.nome')
                    ->label('Fornitore')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('nome')
                    ->label('Hotel')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('indirizzo')
                    ->label('Indirizzo')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('stelle')
                    ->label('Stelle')
                    ->placeholder('-')
                    ->sortable()
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
