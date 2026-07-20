<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
                    ->label('Alamat email')
                    ->email(),
                TextInput::make('username')
                    ->label('Login Username')
                    // harus unique dengan user yang lain
                    ->unique(
                        table: 'users',
                        column:'username',
                    )
                    ->required(),
                TextInput::make('phone')
                    ->label('Nomor Telepom')
                    ->prefixIcon(Heroicon::OutlinedPhone)
                    ->tel(),
                TextInput::make('password')
                    ->hiddenOn('edit')      //disembunyikan di tabel admin
                    ->revealable()
                    ->password()
                    ->required(),
                    Toggle::make('is_staff')
                    ->required(),
                ]),
            ]);
    }
}
