<?php

namespace App\Filament\Resources\Emails\Tables;

use App\PreventiveStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
             ->modifyQueryUsing(function (Builder $query) {
                $query->where(function ($q) {
                    // Mostra solo email inviate (is_draft = false)
                    $q->where('is_draft', false)
                        ->orWhereHas('preventives', function ($preventiveQuery) {
                        $preventiveQuery->where('stato', '!=', PreventiveStatus::BOZZA);
                    });
                });
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('sent_by')
                    ->searchable()
                    ->placeholder('-')
                    ->limit(20)
                    ->searchable()
                    ->tooltip(fn($state, $record) => ($record->sentBy ? "{$record->sentBy->nome} {$record->sentBy->cognome}" : 'Agente Rimosso'))
                    ->formatStateUsing(fn($state, $record) => $record->sentBy
                        ? "{$record->sentBy->nome} {$record->sentBy->cognome}"
                        : 'Utente Rimosso')
                    ->badge(fn($record) => !$record->sentBy)
                    ->color(fn($record) => !$record->sentBy ? 'danger' : 'grey')
                    ->label('Inviata da'),
                TextColumn::make('customer.nome')
                    ->placeholder('-')
                    ->searchable()
                    ->formatStateUsing(fn($state, $record) => "{$record->customer?->nome} {$record->customer?->cognome}")
                    ->searchable()
                    ->label('Inviata a'),
                TextColumn::make('preventives_count')
                    ->placeholder('0')
                    ->label('Preventivi Inviati')
                    ->counts('preventives')
                    ->sortable()
                    ->badge(),
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
