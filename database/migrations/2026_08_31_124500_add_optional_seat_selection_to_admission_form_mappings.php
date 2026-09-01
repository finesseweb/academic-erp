<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('college_admission_form_mappings', 'seat_selection_required')) {
            Schema::table('college_admission_form_mappings', function (Blueprint $table) {
                $table->boolean('seat_selection_required')->default(false)->after('public_open_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('college_admission_form_mappings', 'seat_selection_required')) {
            Schema::table('college_admission_form_mappings', function (Blueprint $table) {
                $table->dropColumn('seat_selection_required');
            });
        }
    }
};
