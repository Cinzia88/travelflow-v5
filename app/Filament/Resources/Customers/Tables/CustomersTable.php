<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->label('Cliente')
                    ->formatStateUsing(fn($state, $record) => trim("{$record->nome} {$record->cognome}"))
                    ->searchable(),
                TextColumn::make('tipo_cliente')
                    ->label('Tipo Cliente')
                    ->searchable(),
                /*  Tables\Columns\TextColumn::make('ragione_sociale')
                    ->searchable(),
                Tables\Columns\TextColumn::make('piva_cf')
                    ->searchable(),
                Tables\Columns\TextColumn::make('indirizzo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('citta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cap')
                    ->searchable(),
                Tables\Columns\TextColumn::make('provincia')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stato')
                    ->searchable(), */
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->label('Telefono')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
