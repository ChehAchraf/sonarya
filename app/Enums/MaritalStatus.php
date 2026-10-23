<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case Single = 'single';
    case Married = 'married';
    case Divorced = 'divorced';
    case Widowed = 'widowed';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Célibataire',
            self::Married => 'Marié(e)',
            self::Divorced => 'Divorcé(e)',
            self::Widowed => 'Veuf(ve)',
            self::Other => 'Autre',
        };
    }
}
