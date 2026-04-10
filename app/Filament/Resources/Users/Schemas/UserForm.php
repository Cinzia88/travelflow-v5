<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('roles')
                    ->label('Ruolo')
                    ->relationship('roles', 'name')
                    ->disabled(fn() => !auth()->user()->hasRole(['super-admin', 'admin']))
                    ->multiple() // Opzionale: toglilo se vuoi che ogni utente abbia UN SOLO ruolo
                    ->preload()
                    ->searchable()
                    // Se vuoi limitare la scelta a un solo ruolo anche se è una pivot:
                    ->maxItems(1)
                    ->required(),
                TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('cognome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignorable: fn($record) => $record)
                    ->maxLength(255),
                TextInput::make('telefono')
                    ->tel()
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('password')
                    ->label('Nuova Password')
                    ->password()
                    ->revealable()
                    ->nullable()
                    ->hidden(fn($record): bool => $record?->role?->nome !== 'agente')
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->visible(fn($record) => $record && $record->id === Auth::id()),





            ]);
    }
}
