<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('college_applicant_registration_settings', function (Blueprint $table) {
            $table->string('registration_number_format', 120)
                ->default('{COLLEGE_CODE}/{YEAR}/{SEQ:6}')
                ->after('captcha_required');
            $table->unsignedBigInteger('registration_sequence_next')
                ->default(1)
                ->after('registration_number_format');
        });

        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->string('registration_no', 80)->nullable()->unique()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->dropUnique(['registration_no']);
            $table->dropColumn('registration_no');
        });

        Schema::table('college_applicant_registration_settings', function (Blueprint $table) {
            $table->dropColumn(['registration_number_format', 'registration_sequence_next']);
        });
    }
};
