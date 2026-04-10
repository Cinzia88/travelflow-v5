<?php

namespace App\Filament\Resources\Suppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->label('Fornitore')
                    ->formatStateUsing(fn($state, $record) => trim("{$record->nome} {$record->cognome}"))
                    ->searchable(['nome', 'cognome']),
                TextColumn::make('type_supplier')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->type_supplier->pluck('tipologia_fornitore')->join(', ')
                    )
                    ->label('Tipologia'),
                TextColumn::make('reliability.nome')
                    ->label('Affidabilità')
                    ->badge()
                    ->color(fn($state, $record) => $record->reliability?->colore ?? 'gray'),
                TextColumn::make('ragione_sociale')
                    ->label('Ragione Sociale')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('citta')
                    ->label('Città')
                    ->sortable(),
                TextColumn::make('descrizione')
                    ->label('Descrizione')
                    ->limit(20)
                    ->tooltip(fn($record) => $record->descrizione) // mostra tutta la descrizione al passaggio del mouse
                    ->sortable(),
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
