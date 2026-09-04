<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_calendar_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_academic_calendar_id')->constrained('college_academic_calendars')->restrictOnDelete();
            $table->unsignedBigInteger('academic_calendar_id');
            $table->foreignId('academic_calendar_event_id')->constrained('academic_calendar_events')->restrictOnDelete();
            $table->string('title', 180);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description')->nullable();
            $table->text('reason');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('academic_calendar_id', 'college_cal_override_calendar_fk')
                ->references('id')->on('academic_calendars')->restrictOnDelete();
            $table->unique(['college_academic_calendar_id', 'academic_calendar_event_id'], 'college_cal_override_event_uq');
            $table->index(['academic_calendar_id', 'status'], 'college_cal_override_calendar_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_calendar_overrides');
    }
};
