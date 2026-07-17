<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('email');
            $table->string('phone')->nullable()->after('username');
            $table->boolean('is_staff')->default(false)->after('phone');
            $table->string('photo_path')->nullable()->after('is_staff');
            // ubah kolom email menjadi nullabel
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // rollback column yang ditambahkan
            $table->dropColumn('username', 'phone', 'is_staff');
            //
        });
    }
};
