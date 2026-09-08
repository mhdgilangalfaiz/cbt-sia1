<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StudentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reg_year')
                    ->label('Tahun Masuk'),
                TextEntry::make('nis')
                    ->label('Nomor Induk Siswa'),
                TextEntry::make('name')
                    ->label('Nama Lengkap')
                    ->columnSpanFull(),
                IconEntry::make('gender')
                    ->formatStateUsing(
                        fn (bool $state) => $state
                            ? 'Laki-laki'
                            : 'Perempuan'
                    ),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
