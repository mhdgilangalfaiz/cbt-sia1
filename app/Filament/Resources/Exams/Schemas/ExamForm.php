<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('duration')
                    ->label('Durasi (menit)')
                    ->numeric()
                    ->required(),
                TextInput::make('threshold')
                    ->label('Nilai Ambang Batas')
                    ->numeric()
                    ->default(50)
                    ->required(),    
                DateTimePicker::make('started_at')
                    ->label('Mulai Pada')
                    ->required(),    
                DateTimePicker::make('expired_at')
                    ->label('Berakhir Pada')
                    ->seconds(false)
                    ->native()
                    ->displayFormat('d F Y , H:i')
                    ->hidden(fn (Get $get): bool=>
                        $get('exact_time')),
                Toggle::make('exact_time')
                    ->label('Waktu Pasti')
                    ->live()
                    ->required(),
                Toggle::make('is_available')
                    ->label('Tersedia')
                    ->required()
                    ->default(true),
            ]);
    }
}