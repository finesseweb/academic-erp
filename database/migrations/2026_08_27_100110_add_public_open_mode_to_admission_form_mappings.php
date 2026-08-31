<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('college_admission_form_mappings', function (Blueprint $table) {
            $table->enum('public_open_mode', ['SAME_WINDOW', 'NEW_WINDOW'])
                ->default('SAME_WINDOW')
                ->after('public_enabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('college_admission_form_mappings', function (Blueprint $table) {
            $table->dropColumn('public_open_mode');
        });
    }
};
