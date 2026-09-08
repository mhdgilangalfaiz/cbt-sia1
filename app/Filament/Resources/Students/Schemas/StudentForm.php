<?php

namespace App\Filament\Resources\Students\Schemas;

use App\StudentStatusEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reg_year')
                            ->label('Tahun Masuk')
                            ->required()
                            ->maxValue(now()->year)
                            ->placeholder('Empat Digit Angka Tahun Masuk. Contoh: 2026')
                            ->numeric(),
                        TextInput::make('nis')
                            ->label('Nomor Induk Siswa')
                            ->placeholder('Nomor Unik Registrasi Siswa. Contoh: 2023000001')
                            ->required()
                            ->unique(table: 'students', column: 'nis'),
                        TextInput::make('name')
                            ->label('Nama Siswa')
                            ->placeholder('Nama Lengkap Siswa')
                            ->required()
                            ->columnSpanFull(),
                        Radio::make('gender')
                            ->options([
                                true => 'Laki-laki',
                                false => 'Perempuan',
                            ])
                            ->required()
                            ->inline()
                            ->inlineLabel(false),
                        Select::make('status')
                            ->hiddenOn('create')
                            ->options(StudentStatusEnum::class)
                            ->default(1)
                            ->required(),
                    ])
            ]);
        }
    }
