<?php

namespace App\Enums;

enum PatientStatus: string
{
    case Active = 'active';
    case FollowUp = 'follow_up';
    case LostToFollowUp = 'lost';
    case Deceased = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::FollowUp => 'En cours de suivi',
            self::LostToFollowUp => 'Perdu de vue',
            self::Deceased => 'Décédé',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800',
            self::FollowUp => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-400 dark:border-sky-800',
            self::LostToFollowUp => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800',
            self::Deceased => 'bg-zinc-100 text-zinc-600 border-zinc-300 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700',
        };
    }
}
