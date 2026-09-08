<?php

namespace App;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Override;

enum StudentStatusEnum: int implements HasLabel
{
    case Active = 1;
    case Graduated = 2;
    case Leave = 3;
    case Moved = 4;
    case DropOut = 5;

    #[Override]
    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Graduated => 'Alumni',
            self::Leave => 'Cuti',
            self::Moved => 'Pindah',
            self::DropOut => 'Dikeluarkan',
            default => 'invalid',
        };
    }
}
