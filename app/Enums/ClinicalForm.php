<?php

namespace App\Enums;

enum ClinicalForm: string
{
    case RelapsingRemitting = 'RR';
    case SecondaryProgressive = 'SP';
    case PrimaryProgressive = 'PP';
    case ClinicallyIsolatedSyndrome = 'CIS';
    case RadiologicallyIsolatedSyndrome = 'RIS';
    case Other = 'other';
    case Unspecified = 'unspecified';

    public function label(): string
    {
        return match ($this) {
            self::RelapsingRemitting => 'Rémittente-Récurrente (SEP-RR)',
            self::SecondaryProgressive => 'Secondairement Progressive (SEP-SP)',
            self::PrimaryProgressive => 'Primitivement Progressive (SEP-PP)',
            self::ClinicallyIsolatedSyndrome => 'Syndrome Cliniquement Isolé (CIS)',
            self::RadiologicallyIsolatedSyndrome => 'Syndrome Radiologiquement Isolé (RIS)',
            self::Other => 'Autre forme',
            self::Unspecified => 'Non précisée',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::RelapsingRemitting => 'SEP-RR',
            self::SecondaryProgressive => 'SEP-SP',
            self::PrimaryProgressive => 'SEP-PP',
            self::ClinicallyIsolatedSyndrome => 'CIS',
            self::RadiologicallyIsolatedSyndrome => 'RIS',
            self::Other => 'Autre',
            self::Unspecified => 'N/P',
        };
    }
}
