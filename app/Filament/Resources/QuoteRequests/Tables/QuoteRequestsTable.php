<?php

namespace App\Filament\Resources\QuoteRequests\Tables;

use App\Filament\Resources\QuoteRequests\Schemas\QuoteRequestForm;
use App\PreventiveStatus;
use App\QuoteRequestStatus;
use App\Services\OptionsTravel;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuoteRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->placeholder('-')
                    ->searchable()
                    ->label('Numero'),
                TextColumn::make('created_by')
                    ->searchable()
                    ->placeholder('-')
                    ->limit(20)
                    ->getStateUsing(function ($record) {
                        if ($record->creator) {
                            return "{$record->creator->nome} {$record->creator->cognome}";
                        }
                        return 'Agente Rimosso';
                    })
                    ->tooltip(fn($state, $record) => ($record->creator ? "{$record->creator->nome} {$record->creator->cognome}" : 'Agente Rimosso'))
                    ->formatStateUsing(fn($state, $record) => $record->creator
                        ? "{$record->creator->nome} {$record->creator->cognome}"
                        : "Agente Rimosso")
                    ->badge(fn($record) => !$record->creator)
                    ->color(fn($record) => !$record->creator ? 'danger' : 'grey')
                    ->label('Creata da'),
                TextColumn::make('oggetto')
                    ->limit(30)
                    ->tooltip(fn($state, $record) => $record->oggetto)
                    ->formatStateUsing(fn($state, $record) => $record->oggetto)
                    ->placeholder('-')
                    ->searchable()
                    ->label('Oggetto'),
                    /* 1. array_values(...)
Questo comando prende il tuo array multidimensionale QuoteRequestForm::getOpzioniRichiesta() e scarta le chiavi di primo livello (ovvero le categorie: 'Neve & Inverno', 'Mare & Relax', ecc.).
Ti ritrovi con un array che contiene solo gli array interni (i "gruppi").

2. ... (Operatore di Unpacking / Splat)
I tre puntini (...) prima di array_values sono fondamentali. Dicono a PHP: "Prendi tutti gli elementi dentro questo array e passali come argomenti separati alla funzione successiva".
Senza questo, array_merge riceverebbe un unico array (quello con i gruppi), mentre con i tre puntini riceve tanti array singoli, uno per ogni categoria.

3. array_merge(...)
Questa funzione prende tutti gli array che gli abbiamo passato (grazie allo splat operator) e li fonde in un unico grande array piatto.
Il risultato finale di queste tre operazioni è questo:[
    'settimana_bianca' => '❄️ Settimana Bianca',
    'mercatini' => '🎄 Mercatini di Natale',
    'mare_italia' => '🇮🇹 Mare Italia',
    // ... tutte le altre voci fuse insieme
] */
                TextColumn::make('tipo_richiesta')
                    ->label('Tipo di Richiesta')
                    ->placeholder('-')
                    ->formatStateUsing(function ($state) {
                        $options = array_merge(...array_values(OptionsTravel::getOptionsTravel()));
                        return $options[$state] ?? $state;
                    }),
                TextColumn::make('meta_viaggio')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn($record) => $record->meta_viaggio)
                    ->formatStateUsing(fn($record) => $record->meta_viaggio)
                    ->placeholder('-')
                    ->label('Meta'),
                TextColumn::make('customer.nome')
                    ->label('Cliente')
                    ->placeholder('-')
                    ->formatStateUsing(fn($state, $record) => "{$record->customer?->nome} {$record->customer?->cognome}")
                    ->tooltip(fn($state, $record) => "{$record->customer?->nome} {$record->customer?->cognome}")
                    ->searchable(),
                TextColumn::make('stato_richiesta')
                    ->label('Stato Richiesta')
                    ->placeholder('-')
                    ->badge(),
                TextColumn::make('stato_preventivo')
                    ->label('Stato Preventivo')
                    ->placeholder('-')
                    ->getStateUsing(function ($record) {
                        $preventivo = $record->preventives->last();
                        if (!$preventivo?->stato) {
                            return '-';
                        }

                        return match ($preventivo->stato) {
                            PreventiveStatus::RIFIUTATO => 'Rifiutato',
                            PreventiveStatus::IN_ATTESA => 'In attesa',
                            PreventiveStatus::BOZZA => 'bozza',
                            PreventiveStatus::ACCETTATO => 'Accettato',
                            PreventiveStatus::INTERESSE_PIU_TEMPO => "L'offerta è di interesse, ma ho bisogno di più tempo",
                            PreventiveStatus::SUPERIORE_BUDGET => "L'offerta risulta superiore al budget previsto",
                            PreventiveStatus::OLTRE_TEMPI => "L'offerta è pervenuta oltre i tempi necessari alla valutazione",
                            PreventiveStatus::NON_INTERESSA => "Il programma proposto non incontra i miei interessi",
                            PreventiveStatus::DA_RIVEDERE => "Vorrei rivedere la proposta insieme a voi",
                            PreventiveStatus::ALTRO => $preventivo->stato_altro_testo ?? 'Altro',
                        };
                    })
                    ->badge()
                    ->color(fn($record) => $record->preventives->last()?->stato?->getColor() ?? 'secondary')
                    ->tooltip(function ($record) {
                        $preventivo = $record->preventives->last();
                        if (!$preventivo?->stato) {
                            return '';
                        }

                        return match ($preventivo->stato) {
                            PreventiveStatus::RIFIUTATO => 'Rifiutato',
                            PreventiveStatus::IN_ATTESA => 'In attesa',
                            PreventiveStatus::BOZZA => 'bozza',
                            PreventiveStatus::ACCETTATO => 'Accettato',
                            PreventiveStatus::INTERESSE_PIU_TEMPO => "L'offerta è di interesse, ma ho bisogno di più tempo",
                            PreventiveStatus::SUPERIORE_BUDGET => "L'offerta risulta superiore al budget previsto",
                            PreventiveStatus::OLTRE_TEMPI => "L'offerta è pervenuta oltre i tempi necessari alla valutazione",
                            PreventiveStatus::NON_INTERESSA => "Il programma proposto non incontra i miei interessi",
                            PreventiveStatus::DA_RIVEDERE => "Vorrei rivedere la proposta insieme a voi",
                            PreventiveStatus::ALTRO => $preventivo->stato_altro_testo ?? 'Altro',
                        };
                    }),

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
                Filter::make('data_ricezione_richiesta')
                    ->form([
                        DatePicker::make('from')
                            ->label('Da'),
                        DatePicker::make('until')
                            ->label('A'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $q, $date) => $q->whereDate('data_ricezione_richiesta', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $q, $date) => $q->whereDate('data_ricezione_richiesta', '<=', $date),
                            );
                    }),
                Filter::make('ultimo_mese')
                    ->label('Ultimo mese')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('data_ricezione_richiesta', '>=', now()->subMonth())
                    ),
                SelectFilter::make('tipo_richiesta')
                    ->label('Tipo Richiesta')
                    ->options([
                        'Neve & Inverno' => [
                            'settimana_bianca' => '❄️ Settimana Bianca',
                            'mercatini' => '🎄 Mercatini di Natale',
                        ],
                        'Mare & Relax' => [
                            'mare_italia' => '🇮🇹 Mare Italia',
                            'mare_estero' => '🏝️ Mare Estero / Tropicale',
                            'crociera' => '🚢 Crociera',
                        ],
                        'Grandi Viaggi' => [
                            'tour_organizzato' => '🚩 Tour Organizzato',
                            'on_the_road' => '🚗 On the Road / Fly & Drive',
                            'avventura' => '🌋 Avventura & Trekking',
                        ],
                        'Speciali' => [
                            'nozze' => '💍 Viaggio di Nozze',
                            'wellness' => '🧖 SPA & Wellness',
                            'business' => '💼 Business / Incentive',
                        ],
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!filled($data['value'])) {
                            return $query;
                        }
                        return $query->where('tipo_richiesta', $data['value']);
                    }),
                Filter::make('customer_nome')
                    ->form([
                        TextInput::make('q')
                            ->label('Cliente (nome/cognome)'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $term = trim($data['q'] ?? '');
                        return $query->when($term !== '', function (Builder $q) use ($term) {
                            $q->whereHas('customer', function (Builder $c) use ($term) {
                                $c->where(function (Builder $w) use ($term) {
                                    $w->where('nome', 'like', "%{$term}%")
                                        ->orWhere('cognome', 'like', "%{$term}%");
                                });
                            });
                        });
                    }),
                Filter::make('email_cliente')
                    ->form([
                        TextInput::make('q')
                            ->label('Email Cliente'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $term = trim($data['q'] ?? '');
                        return $query->when($term !== '', function (Builder $q) use ($term) {
                            $q->whereHas('customer', function (Builder $c) use ($term) {
                                $c->where(function (Builder $w) use ($term) {
                                    $w->where('email', 'like', "%{$term}%");
                                });
                            });
                        });
                    }),
                Filter::make('meta_viaggio')
                    ->form([
                        TextInput::make('q')
                            ->label('Meta Viaggio'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $term = trim($data['q'] ?? '');
                        return $query->when(
                            $term !== '',
                            fn(Builder $q) =>
                            $q->where('meta_viaggio', 'like', "%{$term}%")
                        );
                    }),

                //  filtro per stato richiesta
                SelectFilter::make('stato_richiesta')
                    ->label('Stato Richiesta')
                    ->options(QuoteRequestStatus::class)
                    ->multiple(),

            ])
            ->recordActions(
                [
                    ActionGroup::make([
                        ViewAction::make()
                            ->color('info')
                            ->label('Visualizza Preventivo')
                            ->visible(fn($record) => $record->stato_richiesta === QuoteRequestStatus::EVASA)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-globe-alt')
                            ->extraAttributes(['target' => '_blank'])
                            ->url(function ($record) {
                                // Recupera il preventivo associato che NON sia in stato bozza
                                $preventivo = $record->preventives()
                                    ->where('stato', '!=', 'bozza')
                                    ->latest()
                                    ->first();

                                if (!$preventivo) {
                                    return null;
                                }



                                // Altrimenti, usa la rotta standard per visualizzare il preventivo
                                return route('preventivo.show', ['cod_alfa' => $preventivo->cod_alfa]);
                            }),

                        ViewAction::make()
                            ->modalHeading(fn($record): string => 'Visualizza Richiesta'),
                        EditAction::make(),
                        DeleteAction::make()
                            ->modalHeading(fn($record): string => 'Elimina Richiesta'),
                    ]),
                ],
                position: RecordActionsPosition::BeforeColumns
            )
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
