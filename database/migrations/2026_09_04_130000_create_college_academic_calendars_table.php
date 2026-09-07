<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_academic_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained('colleges')->restrictOnDelete();
            $table->unsignedBigInteger('university_academic_calendar_id');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('university_academic_calendar_id', 'college_acad_cal_univ_cal_fk')
                ->references('id')->on('academic_calendars')->restrictOnDelete();
            $table->unique(['college_id', 'university_academic_calendar_id'], 'college_acad_cal_college_univ_uq');
            $table->index(['college_id', 'status'], 'college_acad_cal_college_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_academic_calendars');
    }
};
