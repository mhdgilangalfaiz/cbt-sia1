<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'email',
    'password',
    'username',
    'phone',
    'is_staff',
    'photo_path'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_Staff' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        // Hapus foto lama dari storage saat photo_path diupdate
        static::updating(function (User $user) {
            if ($user->isDirty('photo_path') && $user->getOriginal('photo_path')) {
                Storage::disk('public')->delete($user->getOriginal('photo_path'));
            }
        });

        // Hapus foto dari storage saat user dihapus
        static::deleting(function (User $user) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
        });
    }
}