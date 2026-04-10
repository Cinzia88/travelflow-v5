<?php

namespace App\Filament\Pages;

use App\Models\Event;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CalendarPage extends Page
{

    // Nome che apparirà nella sidebar
    protected static ?string $navigationLabel = 'Calendario Viaggi';

    // Opzionale: ordinamento (es. mettila per prima)
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.calendar-page';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nuovo-evento')
                ->label('Aggiungi Evento')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->form([
                    TextInput::make('title')
                        ->label('Nome Evento/Viaggio')
                        ->placeholder('Es: Gruppo Giappone Aprile')
                        ->required(),
                    Textarea::make('description')
                        ->label('Descrizione')
                        ->placeholder('Es: Viaggio di gruppo in Giappone per visitare Tokyo, Kyoto e Osaka. Partenza il 10 Aprile e ritorno il 20 Aprile.')
                        ->rows(3),
                    DatePicker::make('start_time')
                        ->label('Data Inizio')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(),
                    DatePicker::make('end_time')
                        ->label('Data Fine')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(),
                    Select::make('color_id')
                        ->label('Colore Fascia')
                        ->options([
                            '1' => 'Blu Lavanda',
                            '2' => 'Verde Salvia',
                            '3' => 'Viola',
                            '4' => 'Rosa Fenicottero',
                            '5' => 'Giallo Banana',
                            '6' => 'Arancione Mandarino',
                            '7' => 'Azzurro Pavone',
                            '8' => 'Grigio Grafite',
                            '9' => 'Blu Mirtillo',
                            '10' => 'Verde Basilico',
                            '11' => 'Rosso Pomodoro',
                        ])
                        ->default('9') // Blu di default
                        ->required(),
                    /* se invece vuoi anche l'orario devi sostituire DatePicker con DateTimePicker */
                ])
                ->action(function (array $data) {
                    $inizio = Carbon::parse($data['start_time']);
                    $fine = Carbon::parse($data['end_time']);
                    $googleEvent = \Spatie\GoogleCalendar\Event::create([
                        'name' => $data['title'],
                        'startDate' => $inizio,
                        'endDate' => $fine,
                        'description' => $data['description'] ?? '',
                        'colorId' => $data['color_id'],
                    ]);

                    Event::create([
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'start_time' => $inizio,
                        'end_time' => $fine,
                        'google_event_id' => $googleEvent->id,
                    ]);

                    /* con. l'orario
                     $googleEvent = \Spatie\GoogleCalendar\Event::create([
                        'name' => $data['title'],
                        'startDateTime' => $inizio,
                        'endDateTime' => $fine,
                        'description' => $data['description'] ?? '',
                    ]);

                    Event::create([
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'start_time' => $inizio,
                        'end_time' => $fine,
                        'google_event_id' => $googleEvent->id,
                    ]);
 */


                    Notification::make()
                        ->title('Evento salvato correttamente!')
                        ->body('La fascia apparirà tra pochi istanti nel calendario.')
                        ->success()
                        ->send();
                    $this->js('window.location.reload();');
                }),
            Action::make('elimina_evento')
                ->label('Elimina Evento')
                ->color('danger')
                ->icon('heroicon-m-trash')
                // Chiediamo all'utente quale evento vuole eliminare
                ->form([
                    Select::make('event_id')
                        ->label('Seleziona l\'evento da rimuovere')
                        ->options(Event::query()->pluck('title', 'id'))
                        ->required()
                        ->searchable(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Elimina Evento')
                ->modalDescription('Sei sicuro di voler eliminare questo impegno? Verrà rimosso anche da Google Calendar.')
                ->action(function (array $data) {

                    $evento = Event::find($data['event_id']);


                    if (!$evento)
                        return;

                    if ($evento->google->id) {
                        try {
                            $googleEvent = \Spatie\GoogleCalendar\Event::find($evento->google->id);
                            if ($googleEvent) {
                                $googleEvent->delete();
                            }
                        } catch (\Throwable $th) {
                            //throw $th;
                        }
                    }

                    $evento->delete();


                    Notification::make()
                        ->title('Evento eliminato con successo')
                        ->success()
                        ->send();

                    // Refresh per aggiornare l'Iframe
                    $this->js('window.location.reload();');
                }),
            /*  Action::make('pulisci_calendario')
                 ->label('Elimina tutti gli eventi')
                 ->color('danger')
                 ->requiresConfirmation()
                 ->action(function () {
                     // Recupera tutti gli eventi dal calendario impostato
                     $events = \Spatie\GoogleCalendar\Event::get();

                     foreach ($events as $event) {
                         $event->delete();
                     }

                     Notification::make()->title('Calendario svuotato!')->success()->send();
                     $this->js('window.location.reload();');
                 }) */
        ];
    }
}
