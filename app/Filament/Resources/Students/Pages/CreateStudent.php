<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Override;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    #[Override]
    public function mutateFormDataBeforeCreate(array $data): array
    {
        // buat akun siswa
        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['nis'],
            'password' => Hash::make($data['nis']),
            'is_staff' => false
        ]);
        // Letakan Id User ke Table Siswa
        $data['user_id'] = $user->id;
        // return data siswa yang baru
        return $data;
    }
}
