<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('college_admission_form_mappings', function (Blueprint $table) {
            if (! Schema::hasColumn('college_admission_form_mappings', 'public_enabled')) {
                $table->boolean('public_enabled')->default(false)->after('status');
            }
            if (! Schema::hasColumn('college_admission_form_mappings', 'public_slug')) {
                $table->string('public_slug', 180)->nullable()->unique('cafm_public_slug_uq')->after('public_enabled');
            }
            if (! Schema::hasColumn('college_admission_form_mappings', 'public_enabled_at')) {
                $table->timestamp('public_enabled_at')->nullable()->after('public_slug');
            }
        });

        Schema::table('college_admission_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('college_admission_applications', 'entry_source')) {
                $table->enum('entry_source', ['INTERNAL', 'PUBLIC'])->default('INTERNAL')->after('admission_mode');
                $table->index(['college_id', 'entry_source', 'status'], 'caa_entry_source_status_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('college_admission_applications', function (Blueprint $table) {
            if (Schema::hasColumn('college_admission_applications', 'entry_source')) {
                $table->dropIndex('caa_entry_source_status_idx');
                $table->dropColumn('entry_source');
            }
        });

        Schema::table('college_admission_form_mappings', function (Blueprint $table) {
            if (Schema::hasColumn('college_admission_form_mappings', 'public_slug')) {
                $table->dropUnique('cafm_public_slug_uq');
            }
            $columns = array_values(array_filter(['public_enabled', 'public_slug', 'public_enabled_at'], fn ($column) => Schema::hasColumn('college_admission_form_mappings', $column)));
            if ($columns) $table->dropColumn($columns);
        });
    }
};
