<?php

namespace App\Filament\Test\Pages;

use Filament\Auth\Pages\Login;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use Override;

class StudentLogin extends Login
{
    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->label('NIS')
                    ->placeholder('Nomor Induk Siswa')
                    ->required(),
                $this->getPasswordFormComponent(),
            ]);
    }
    
    #[Override]
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    #[Override]
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.**' => 'Siswa dengan data tersebut tidak ditemukan.',
        ]);
    }
}
