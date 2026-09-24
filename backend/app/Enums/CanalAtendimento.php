<?php

namespace App\Enums;

enum CanalAtendimento: string
{
    case Telefone = 'telefone';
    case Whatsapp = 'whatsapp';
    case App = 'app';

    public function label(): string
    {
        return match ($this) {
            self::Telefone => 'Telefone',
            self::Whatsapp => 'WhatsApp',
            self::App => 'App',
        };
    }
}
