<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            // conton jika ingin data siswa ikut dihapus
            // $table->foreignIdFor(User::class)
            //    ->constrained()->cascadeOnDelete();
            // contoh jika ingin data siswa tetap ada di database
            $table->foreignIdFor(User::class)
                ->nullable()->constrained()->nullOnDelete();
            $table->string('nis')->unique();
            $table->string('name');
            $table->boolean('gender')->default(true);
            $table->unsignedInteger('reg_year');
            $table->unsignedTinyInteger('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
