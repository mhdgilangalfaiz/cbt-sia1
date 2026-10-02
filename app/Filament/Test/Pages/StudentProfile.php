<?php

namespace App\Filament\Test\Pages;

use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use Override;

class StudentProfile extends EditProfile
{
    // Label ini yang tampil di dropdown avatar: nama siswa
    #[Override]
    public static function getLabel(): string
    {
        return Filament::auth()->user()?->name ?? 'Profil';
    }

    #[Override]
    public function getTitle(): string
    {
        return 'Profil Saya';
    }

    // Data nama & NIS diambil dari data siswa (bukan hanya dari tabel users)
    #[Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $student = $this->getUser()->students;

        $data['name'] = $student?->name ?? $data['name'];
        $data['nis']  = $student?->nis ?? $data['username'];

        return $data;
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Foto Profil')
                ->schema([
                    FileUpload::make('photo_path')
                        ->hiddenLabel()
                        ->image()
                        ->avatar()
                        ->disk('public')
                        ->directory('user-photos')
                        ->maxSize(1024)
                        ->imageEditor()
                        ->alignCenter(),
                ]),

            Section::make('Data Siswa')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Siswa')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('nis')
                        ->label('NIS')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Section::make('Ubah Password')
                ->description('Kosongkan bagian ini jika tidak ingin mengganti password.')
                ->schema([
                    $this->getPasswordFormComponent(),
                    $this->getPasswordConfirmationFormComponent(),
                    $this->getCurrentPasswordFormComponent(),
                ]),
        ]);
    }

    // Password baru opsional, tetapi tidak boleh sama dengan NIS
    #[Override]
    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->label('Password baru')
            ->rule(fn () => Rule::notIn([$this->getUser()->username]))
            ->validationMessages([
                'not_in' => 'Password baru tidak boleh sama dengan NIS.',
            ]);
    }

    #[Override]
    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->label('Ulangi password baru');
    }

    // Password lama hanya diminta kalau siswa mengisi password baru
    #[Override]
    protected function getCurrentPasswordFormComponent(): Component
    {
        return parent::getCurrentPasswordFormComponent()
            ->label('Password saat ini')
            ->visible(fn (Get $get): bool => filled($get('password')));
    }

    // Muat ulang halaman setelah simpan agar foto di pojok kanan atas ikut berganti
    #[Override]
    protected function getRedirectUrl(): ?string
    {
        return Filament::getProfileUrl();
    }

    #[Override]
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Profil berhasil disimpan.';
    }
}