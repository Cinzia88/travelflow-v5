<?php

namespace App\Filament\Resources\Preventives\Tables;

use App\Exports\PreventiveExport;
use App\Filament\Resources\Preventives\PreventiveResource;
use App\Models\Preventive;
use App\Models\User;
use App\PreventiveStatus;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\RecordActionsPosition;
use Maatwebsite\Excel\Facades\Excel;


class PreventivesTable
{
    public static function getStatePreventive(Preventive $preventivo): array
    {/* 2. Perché non puoi farne a meno
Se provassi a scrivere questo nel tuo codice:

PHP
$array = [
   PreventiveStatus::RIFIUTATO => 'Rifiutato', // SENZA ->value
];
PHP ti darebbe un errore di tipo Illegal offset type, perché le chiavi di un array possono essere solo stringhe o numeri, non oggetti. */
        return [
            PreventiveStatus::RIFIUTATO->value => 'Rifiutato',
            PreventiveStatus::IN_ATTESA->value => 'In attesa',
            PreventiveStatus::BOZZA->value => 'bozza',
            PreventiveStatus::ACCETTATO->value => 'Accettato',
            PreventiveStatus::INTERESSE_PIU_TEMPO->value => "L'offerta è di interesse, ma ho bisogno di più tempo",
            PreventiveStatus::SUPERIORE_BUDGET->value => "L'offerta risulta superiore al budget previsto",
            PreventiveStatus::OLTRE_TEMPI->value => "L'offerta è pervenuta oltre i tempi necessari alla valutazione",
            PreventiveStatus::NON_INTERESSA->value => "Il programma proposto non incontra i miei interessi",
            PreventiveStatus::DA_RIVEDERE->value => "Vorrei rivedere la proposta insieme a voi",
            PreventiveStatus::ALTRO->value => $preventivo->stato_altro_testo ?? 'Altro',
        ];
    }
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->label('Numero'),
                TextColumn::make('customer.nome')
                    ->label('Cliente')
                    ->formatStateUsing(fn($state, $record) => "{$record->customer?->nome} {$record->customer?->cognome}")
                    ->tooltip(fn($state, $record) => "{$record->customer?->nome} {$record->customer?->cognome}")
                    ->searchable(),

                /*  TextColumn::make('created_by')
                     ->label('Creato da')
                     ->getStateUsing(function ($record) {
                         if ($record->creator) {
                             return "{$record->creator->nome} {$record->creator->cognome}";
                         }
                         return 'Agente Rimosso';
                     })
                     ->color(fn($record) => !$record->creator ? 'danger' : 'grey')
                     ->searchable()
                     ->badge(fn($record) => !$record->creator)
                     ->limit(20)
                     ->tooltip(fn($record) => $record->creator
                         ? "{$record->creator->nome} {$record->creator->cognome}"
                         : 'Agente Rimosso'),
                 /*   TextColumn::make('quote_request.agenti_gestori')
                      ->getStateUsing(fn($record) => $record->quote_request?->agenti_gestori?->map(fn($u) => "{$u->nome} {$u->cognome}"))
                      ->label('Gestore'),
  */

                TextColumn::make('titolo')
                    ->label('Titolo')
                    ->limit(20)
                    ->tooltip(fn($record) => $record->titolo) // mostra tutta la descrizione al passaggio del mouse
                    ->searchable(),
                TextColumn::make('data_preventivo')
                    ->date()
                    ->sortable(),
                TextColumn::make('data_inizio_viaggio')
                    ->date()
                    ->sortable(),
                TextColumn::make('data_fine_viaggio')
                    ->date()
                    ->sortable(),
                TextColumn::make('meta_viaggio')
                    ->searchable()
                    ->label('Meta'),
                TextColumn::make('stato')
                    ->label('Stato')
                    ->badge(),
                TextColumn::make('stato')
                    ->label('Stato')
                    /*  ->formatStateUsing(function (string $state, $record): string {
                        return match ($state) {
                            'accettato' => 'Accetto il preventivo',
                            'interesse più tempo' => "L'offerta è di interesse, ma ho bisogno di più tempo",
                            'superiore budget' => "L'offerta risulta superiore al budget previsto",
                            'oltre tempi' => "L'offerta è pervenuta oltre i tempi necessari alla valutazione",
                            'programma non interessa' => "Il programma proposto non incontra i miei interessi",
                            'rivedere proposta' => "Vorrei rivedere la proposta insieme a voi",
                            'altro' => $record->stato_altro_testo ?? 'Altro',
                            default => ucfirst($state),
                        };
                    })
                    ->limit(20)
                    ->tooltip(function ($record) {
                        return match ($record->stato) {
                            'accettato' => 'Accetto il preventivo',
                            'interesse più tempo' => "L'offerta è di interesse, ma ho bisogno di più tempo",
                            'superiore budget' => "L'offerta risulta superiore al budget previsto",
                            'oltre tempi' => "L'offerta è pervenuta oltre i tempi necessari alla valutazione",
                            'programma non interessa' => "Il programma proposto non incontra i miei interessi",
                            'rivedere proposta' => "Vorrei rivedere la proposta insieme a voi",
                            'altro' => $record->stato_altro_testo ?? 'Altro',
                            default => $record->stato,
                        };
                    }) */
                    ->formatStateUsing(function ($state, $record): string {
                        // Se $state è un oggetto Enum, prendiamo il suo valore (stringa/int)
                        $value = ($state instanceof \BackedEnum) ? $state->value : $state;

                        $options = self::getStatePreventive($record);
                        //$array[chiave]
                        return $options[$value] ?? $value;
                    })
                    ->limit(20)
                    ->tooltip(function ($record, $state) {
                        $value = ($state instanceof \BackedEnum) ? $state->value : $state;

                        $options = self::getStatePreventive($record);
                        return $options[$value] ?? $value;
                    })
                    ->sortable(),
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
                Filter::make('data_preventivo')
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
                                fn(Builder $q, $date) => $q->whereDate('data_preventivo', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $q, $date) => $q->whereDate('data_preventivo', '<=', $date),
                            );
                    }),
                /*   SelectFilter::make('gestore')
                      ->label('Agente Gestore')
                      ->options(function () {
                          return User::whereHas(
                              'role',
                              fn($q) => $q->whereNotIn('nome', ['admin', 'superadmin'])
                          )
                              ->pluck('nome', 'id')
                              ->toArray();
                      })
                      ->query(function (Builder $query, array $data) {
                          if (filled($data['value'])) {
                              return $query->whereHas('quote_request.agenti_gestori', function (Builder $q) use ($data) {
                                  $q->where('users.id', $data['value']);
                              });
                          }
                      })
                      ->searchable()
                      ->preload(), */
                SelectFilter::make('created_by')
                    ->label('Creato da')
                    ->options(function () {
                        return User::query()
                            ->select('id', 'nome', 'cognome')
                            ->whereHas('preventives')
                            ->get()
                            ->mapWithKeys(fn($user) => [
                                $user->id => "{$user->nome} {$user->cognome}",
                            ])
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            return $query->where('created_by', $data['value']);
                        }
                    })
                    ->searchable()
                    ->preload(),
                Filter::make('ultimo_mese')
                    ->label('Ultimo mese')
                    ->query(
                        fn(Builder $query): Builder => $query->where('data_preventivo', '>=', now()->subMonth()) // ultimi 30 gg
                    ),
                Filter::make('mese_precedente')
                    ->label('Mese precedente')
                    ->query(
                        fn(Builder $query): Builder => $query->whereBetween('data_preventivo', [
                            now()->subMonth()->startOfMonth(),
                            now()->subMonth()->endOfMonth(),
                        ])
                    ),

                Filter::make('customer_nome')
                    ->form([
                        TextInput::make('q')
                            ->label('Cliente (nome/cognome/azienda/scuola)'),
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
                SelectFilter::make('stato')
                    ->label('Stato Preventivo')
                    ->options(PreventiveStatus::class)
                    ->multiple(),

            ])
            ->recordActions(
                [
                    ActionGroup::make([
                        Action::make('export-single-excel')
                            ->label('Esporta Excel')
                            ->icon('heroicon-o-document-text')
                            ->color('success')
                            ->action(function ($record) {
                                $columns = [
                                    'id',
                                    'numero',
                                    'anno',
                                    'titolo',
                                    'created_by',
                                    'customer_id',
                                    'email_cliente',
                                    'data_preventivo',
                                    'meta_viaggio',
                                    'nome_itinerario',
                                    'data_inizio_viaggio',
                                    'data_fine_viaggio',
                                    'numero_persone',
                                    'numero_gratuita',
                                    'prezzo_per_persona',
                                    'markup',
                                    'totale_incasso',
                                    'stato',
                                ];
                                $filename = 'preventivo_' . $record->id . '_' . now()->format('Ymd_His') . '.xlsx';
                                return Excel::download(new PreventiveExport([$record->id], $columns), $filename);

                            }),
                        Action::make('scaricaPdf')
                            ->label('Scarica PDF')
                            ->icon('heroicon-o-document-arrow-down')
                            ->url(function ($record) {


                                // Altrimenti apri la pagina normale con il link
                                return route('preventivi.pdf', $record);
                            })
                            ->openUrlInNewTab(),
                        Action::make('duplicate')
                            ->label('Duplica')
                            ->icon('heroicon-o-document-duplicate')
                            ->color('gray')
                            ->label('Duplica Preventivo')
                            ->requiresConfirmation()
                            ->action(function ($record) {
                                $new = $record->duplicateWithRelations();
                                $url = PreventiveResource::getUrl('edit', parameters: ['record' => $new]);


                                Notification::make()
                                    ->title('Preventivo duplicato con successo!')
                                    ->body("È stata creata una copia: {$new->tag}")
                                    ->success()
                                    ->send();

                                return redirect($url);
                            }),

                        /* Action::make('scaricaPdf')
                            ->label('Scarica PDF')
                            ->icon('heroicon-o-document-arrow-down')
                            ->visible(
                                fn($record) =>
                                auth()->user()->hasAnyRole(['admin', 'superadmin']) ||
                                (auth()->user()->hasRole('agente') && $record->created_by === auth()->id())
                            )
                            ->url(function ($record) {
                                // Se allego_file è true, apri la rotta che mostra il file inline
                                if ($record->allego_file) {
                                    return route('preventivo.download.allegato', ['cod_alfa' => $record->cod_alfa]);
                                }

                                // Altrimenti apri la pagina normale con il link
                                return route('preventivi.pdf', $record);
                            })
                            ->openUrlInNewTab(), */
                        ViewAction::make()
                            ->icon('heroicon-o-globe-alt')
                            ->disabled(false)
                            ->label('Visualizza Preventivo')
                            ->visible(fn($record) => filled($record->cod_alfa))
                            ->openUrlInNewTab()
                            ->extraAttributes(['target' => '_blank'])
                            ->url(function ($record) {
                                return route('preventivo.show', ['cod_alfa' => $record->cod_alfa]);
                            }),

                        /*  ViewAction::make()
                             ->label('Scheda Preventivo')
                             ->modalHeading(fn($record): string => 'Scheda Preventivo'), */
                        EditAction::make()->visible(function ($record) {
                            $user = auth()->user();

                            // Admin e Superadmin possono modificare tutto
                            if ($user->hasAnyRole(['admin', 'superadmin'])) {
                                return true;
                            }

                            // Gli agenti possono modificare solo le proprie richieste
                            if ($user->hasRole('agente') && $record->created_by === $user->id) {
                                return true;
                            }

                            // Tutti gli altri no
                            return false;
                        }),
                        DeleteAction::make()
                            ->visible(
                                fn($record) =>
                                auth()->user()?->hasAnyRole(['admin', 'superadmin']) ||
                                $record->created_by === auth()->id()
                            )
                            ->modalHeading(fn($record): string => 'Elimina Preventivo'),
                    ]), // chiusura ActionGroup
                ],
                position: RecordActionsPosition::BeforeColumns
            ) // azioni a sinistra

            ->toolbarActions([
                BulkActionGroup::make([
                   
                        BulkAction::make('export-excel')
                            ->label('Esporta Excel')
                            ->icon('heroicon-o-document-text')
                            ->color('success')
                            ->form([
                                CheckboxList::make('columns')
                                    ->label('Colonne da esportare')
                                    ->options([
                                        'id' => 'ID',
                                        'numero' => 'Numero',
                                        'anno' => 'Anno',
                                        'titolo' => 'Titolo',
                                        'created_by' => 'Creato da',
                                        'customer_id' => 'Cliente',
                                        'email_cliente' => 'Email Cliente',
                                        'data_preventivo' => 'Data Preventivo',
                                        'meta_viaggio' => 'Meta Viaggio',
                                        'nome_itinerario' => 'Nome Itinerario',
                                        'data_inizio_viaggio' => 'Data Inizio Viaggio',
                                        'data_fine_viaggio' => 'Data Fine Viaggio',
                                        'numero_persone' => 'Numero Persone',
                                        'numero_gratuita' => 'Numero Gratuita',
                                        'prezzo_per_persona' => 'Prezzo per Persona',
                                        'markup' => 'Markup',
                                        'totale_incasso' => 'Totale Incasso',
                                        'stato' => 'Stato',
                                    ])
                                    ->default([
                                        'id',
                                        'numero',
                                        'anno',
                                        'titolo',
                                        'created_by',
                                        'customer_id',
                                        'email_cliente',
                                        'data_preventivo',
                                        'meta_viaggio',
                                        'nome_itinerario',
                                        'data_inizio_viaggio',
                                        'data_fine_viaggio',
                                        'numero_persone',
                                        'numero_gratuita',
                                        'prezzo_per_persona',
                                        'markup',
                                        'totale_incasso',
                                        'stato',
                                    ])
                                    ->columns(2)
                                    ->required(),

                                //  Aggiungiamo i campi del range di date
                                DatePicker::make('from')
                                    ->label('Da')
                                    ->placeholder('Data inizio')
                                    ->native(false), // opzionale, per UI più carina

                                DatePicker::make('until')
                                    ->label('A')
                                    ->placeholder('Data fine')
                                    ->native(false),
                            ])
                            ->action(function (array $data, $records) {
                                $ids = $records->pluck('id')->toArray();
                                $columns = $data['columns'] ?? [];
                                $from = $data['from'] ?? null;
                                $until = $data['until'] ?? null;

                                // Applichiamo il filtro per date se specificato
                                $query = Preventive::query()->whereIn('id', $ids);
                                if ($from) {
                                    $query->whereDate('data_preventivo', '>=', $from);
                                }
                                if ($until) {
                                    $query->whereDate('data_preventivo', '<=', $until);
                                }

                                $filteredIds = $query->pluck('id')->toArray();

                                if (empty($filteredIds)) {
                                    Notification::make()
                                        ->title('Nessun preventivo da esportare')
                                        ->body('Non ci sono preventivi che corrispondono ai criteri selezionati.')
                                        ->warning()
                                        ->send();
                                    return;
                                }

                                $filename = 'preventivi_' . now()->format('Ymd_His') . '.xlsx';
                                return Excel::download(new PreventiveExport($filteredIds, $columns), $filename);
                            }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
