<?php

namespace App\Enums;

/** Spinal cord lesion etiology (fixed list instead of free text: no accidental identifiers). */
enum Etiology: string
{
    case Traumatic = 'traumatic';
    case Degenerative = 'degenerative';
    case Vascular = 'vascular';
    case Tumor = 'tumor';
    case Infection = 'infection';
    case Inflammatory = 'inflammatory';
    case Congenital = 'congenital';
    case OtherNonTraumatic = 'other_non_traumatic';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Traumatic => 'Traumatic',
            self::Degenerative => 'Non-traumatic: degenerative',
            self::Vascular => 'Non-traumatic: vascular',
            self::Tumor => 'Non-traumatic: tumor',
            self::Infection => 'Non-traumatic: infection',
            self::Inflammatory => 'Non-traumatic: inflammatory / autoimmune',
            self::Congenital => 'Non-traumatic: congenital',
            self::OtherNonTraumatic => 'Non-traumatic: other',
            self::Unknown => 'Unknown',
        };
    }
}
