<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('college_applicant_registration_settings', function (Blueprint $table) {
            $table->string('application_help_phone', 40)->nullable()->after('registration_sequence_next');
            $table->string('application_help_email', 190)->nullable()->after('application_help_phone');
        });
    }

    public function down(): void
    {
        Schema::table('college_applicant_registration_settings', function (Blueprint $table) {
            $table->dropColumn(['application_help_phone', 'application_help_email']);
        });
    }
};
