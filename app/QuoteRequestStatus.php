<?php

namespace App;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuoteRequestStatus: string implements HasLabel, HasColor
{
    case INVIATA = 'inviata';
    case RISPOSTA = 'risposta pervenuta';

    case IN_LAVORAZIONE = 'in lavorazione';
    case EVASA = 'evasa';
    case ARCHIVIATA = 'archiviata';


    //test

    public function getLabel(): ?string
    {
        return match ($this) {
            self::INVIATA => 'inviata',
            self::RISPOSTA => 'risposta pervenuta',
            self::IN_LAVORAZIONE => 'in lavorazione',
            self::EVASA => 'evasa',
            self::ARCHIVIATA => 'archiviata',
        };
    }



    public function getColor(): ?string
    {
        return match ($this) {
            self::INVIATA => 'primary',
            self::RISPOSTA => 'info',
            self::IN_LAVORAZIONE => 'warning',
            self::EVASA => 'success',
            self::ARCHIVIATA => 'dark',
        };
    }
}
