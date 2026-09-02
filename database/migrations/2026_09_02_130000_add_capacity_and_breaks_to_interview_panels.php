<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('college_admission_interview_panels', function (Blueprint $table) {
            $table->unsignedInteger('session_duration_minutes')->nullable()->after('slot_duration_minutes');
        });

        Schema::create('college_admission_interview_panel_breaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_interview_panel_id');
            $table->string('label', 100)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
            $table->foreign('college_admission_interview_panel_id', 'caipb_panel_fk')
                ->references('id')->on('college_admission_interview_panels')->cascadeOnDelete();
            $table->index(['college_admission_interview_panel_id', 'starts_at'], 'caipb_panel_start_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_interview_panel_breaks');
        Schema::table('college_admission_interview_panels', function (Blueprint $table) {
            $table->dropColumn('session_duration_minutes');
        });
    }
};
