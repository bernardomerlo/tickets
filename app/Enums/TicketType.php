<?php

namespace App\Enums;

enum TicketType: string
{
    case TI = 'ti';
    case FINANCEIRO = 'financeiro';
    case ADMINISTRACAO = 'administracao';
    case MARKETING = 'marketing';
    case JURIDICO = 'juridico';


    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
