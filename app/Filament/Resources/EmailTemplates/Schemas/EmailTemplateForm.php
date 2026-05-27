<?php

namespace App\Filament\Resources\EmailTemplates\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
                RichEditor::make('corpo_email')
                    ->extraInputAttributes(['style' => 'min-height: 300px;'])
                    ->toolbarButtons([
                        'bold',
                        'bulletList',
                        'italic',
                        'orderedList',
                        'redo',
                        'underline',
                        'undo',
                    ])
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
