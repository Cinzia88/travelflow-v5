<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        return $record->cognome
                            ? "{$record->nome} {$record->cognome}"
                            : $record->nome;
                    }),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                /*                 Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(), */
                TextColumn::make('telefono')
                    ->label('Telefono')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Ruolo')
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
