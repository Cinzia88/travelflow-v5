<?php

namespace App\Services;

use File;

class OptionsTravel
{
    public static function getOptionsTravel(): array
    {

        return [
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
                'gita_scolastica' => '🚌 Gita Scolastica',
            ],
        ];

    }
}