<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Migration extends Model
{
    protected $table = 'migrations';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'batch' => 'integer',
    ];

    // Membaca isi asli file migration dari folder database/migrations,
    
    public function getFileContentAttribute(): string
    {
        $path = database_path("migrations/{$this->migration}.php");

        if (! file_exists($path)) {
            return "// File 'database/migrations/{$this->migration}.php' tidak ditemukan.\n// Kemungkinan nama migration di database tidak sesuai nama file aslinya,\n// atau file sudah dipindahkan/dihapus dari folder migrations.";
        }

        return file_get_contents($path);
    }
}