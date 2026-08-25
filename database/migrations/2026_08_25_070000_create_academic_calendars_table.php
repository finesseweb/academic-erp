<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('code', 50);
            $table->text('notes')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['university_id', 'academic_session_id'], 'academic_calendars_university_session_unique');
            $table->unique(['university_id', 'code'], 'academic_calendars_university_code_unique');
            $table->index(['university_id', 'status'], 'academic_calendars_university_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_calendars');
    }
};
