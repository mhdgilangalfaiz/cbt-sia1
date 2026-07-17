<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    // upload foto
                    FileUpload::make('photo_path')
                    ->label('Upload foto profil')
                    ->image()                   // buat upload gambar
                    ->avatar()                  // resize otomatis dan berbentuk bulat
                    ->disk('public')            // partisi storage
                    ->directory('user-photos')  // nama folder
                    ->maxSize(1024)             // Ukuran max 1mb
                    ->imageEditor()             // edit poto
                    ->columnSpanFull()
                    ->alignCenter(),
                    TextInput::make('name')
                    ->label('Nama lengkap')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('username')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('password')
                    ->password()
                    ->required(),
                    Toggle::make('is_staff')
                    ->required(),
                ]),
            ]);
    }
}
