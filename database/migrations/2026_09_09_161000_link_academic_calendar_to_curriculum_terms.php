<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ADR 162: This migration may have been partially applied on MySQL.
        // MySQL DDL is not fully transactional, so the period table can remain
        // even when Laravel does not record the migration as completed.
        if (! Schema::hasTable('academic_calendar_term_periods')) {
            Schema::create('academic_calendar_term_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_calendar_id')->constrained('academic_calendars')->cascadeOnDelete();
                $table->foreignId('curriculum_term_id')->constrained('curriculum_terms')->restrictOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('allow_college_override')->default(false);
                $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['academic_calendar_id', 'curriculum_term_id'], 'ac_term_period_unique');
                $table->index(['academic_calendar_id', 'status'], 'ac_term_period_status_idx');
            });
        }

        // Continue safely from the point at which a previous attempt stopped.
        if (! Schema::hasColumn('academic_calendar_events', 'academic_calendar_term_period_id')) {
            Schema::table('academic_calendar_events', function (Blueprint $table) {
                $table->foreignId('academic_calendar_term_period_id')->nullable()->after('academic_calendar_id')
                    ->constrained('academic_calendar_term_periods')->nullOnDelete();
                $table->index(
                    ['academic_calendar_id', 'academic_calendar_term_period_id'],
                    'ace_calendar_period_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('academic_calendar_events', 'academic_calendar_term_period_id')) {
            Schema::table('academic_calendar_events', function (Blueprint $table) {
                $table->dropForeign(['academic_calendar_term_period_id']);
                $table->dropIndex('ace_calendar_period_idx');
                $table->dropColumn('academic_calendar_term_period_id');
            });
        }

        Schema::dropIfExists('academic_calendar_term_periods');
    }
};
