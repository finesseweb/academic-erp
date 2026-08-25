<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('program_template_discipline_specializations') &&
            ! Schema::hasColumn(
                'program_template_discipline_specializations',
                'is_admission_seat_bearing'
            )
        ) {
            Schema::table(
                'program_template_discipline_specializations',
                function (Blueprint $table) {
                    $table->boolean('is_admission_seat_bearing')
                        ->default(false)
                        ->after('specialization_id');
                }
            );
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('program_template_discipline_specializations') &&
            Schema::hasColumn(
                'program_template_discipline_specializations',
                'is_admission_seat_bearing'
            )
        ) {
            Schema::table(
                'program_template_discipline_specializations',
                function (Blueprint $table) {
                    $table->dropColumn('is_admission_seat_bearing');
                }
            );
        }
    }
};
