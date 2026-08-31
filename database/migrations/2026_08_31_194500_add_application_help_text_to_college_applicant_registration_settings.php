<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('college_applicant_registration_settings', function (Blueprint $table) {
            $table->text('application_help_text')->nullable()->after('registration_sequence_next');
        });
    }

    public function down(): void
    {
        Schema::table('college_applicant_registration_settings', function (Blueprint $table) {
            $table->dropColumn('application_help_text');
        });
    }
};
