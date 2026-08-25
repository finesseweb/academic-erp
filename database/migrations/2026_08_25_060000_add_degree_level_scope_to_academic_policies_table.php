<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_policies', function (Blueprint $table) {
            $table->foreignId('degree_level_id')
                ->nullable()
                ->after('academic_session_id')
                ->constrained('degree_levels')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('academic_policies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('degree_level_id');
        });
    }
};
