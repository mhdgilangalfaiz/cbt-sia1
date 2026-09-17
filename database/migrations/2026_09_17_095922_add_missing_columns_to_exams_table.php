<?php

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
            Schema::table('exams', function (Blueprint $table) {
                $table->string('title')->unique()->after('id');
                $table->integer('duration')->after('title');
                $table->decimal('threshold', 5, 2)->default(50)->after('duration');
                $table->boolean('exact_time')->default(true)->after('threshold');
                $table->dateTime('started_at')->after('exact_time');
                $table->dateTime('expired_at')->nullable()->after('started_at');
                $table->boolean('is_available')->default(true)->after('expired_at');
            });
        }

        public function down(): void
        {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropColumn([
                    'title', 'duration', 'threshold',
                    'exact_time', 'started_at', 'expired_at', 'is_available',
                ]);
            });
        }
};
