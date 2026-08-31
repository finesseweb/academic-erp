<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('college_admission_form_templates', function (Blueprint $table) {
            $table->boolean('allow_college_override')
                ->default(false)
                ->after('governance_mode');
        });

        // Preserve the intent of templates created before this explicit gate existed.
        DB::table('college_admission_form_templates')
            ->where('owner_scope_type', 'UNIVERSITY')
            ->where('governance_mode', 'UNIVERSITY_BASE_COLLEGE_EXTENSION')
            ->update(['allow_college_override' => true]);
    }

    public function down(): void
    {
        Schema::table('college_admission_form_templates', function (Blueprint $table) {
            $table->dropColumn('allow_college_override');
        });
    }
};
