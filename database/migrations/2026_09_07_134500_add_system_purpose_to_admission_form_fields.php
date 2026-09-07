<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('college_admission_form_fields', function (Blueprint $table) {
            $table->string('system_purpose', 60)->nullable()->after('field_type');
            $table->index('system_purpose', 'caf_field_system_purpose_idx');
        });
    }

    public function down(): void
    {
        Schema::table('college_admission_form_fields', function (Blueprint $table) {
            $table->dropIndex('caf_field_system_purpose_idx');
            $table->dropColumn('system_purpose');
        });
    }
};
