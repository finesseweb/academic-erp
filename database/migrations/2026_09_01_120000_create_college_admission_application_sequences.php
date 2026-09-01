<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_admission_application_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id');
            $table->unsignedBigInteger('college_admission_cycle_id');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(
                ['college_id', 'college_admission_cycle_id'],
                'caa_seq_college_cycle_uq'
            );
            $table->foreign('college_id', 'caa_seq_college_fk')
                ->references('id')->on('colleges')->cascadeOnDelete();
            $table->foreign('college_admission_cycle_id', 'caa_seq_cycle_fk')
                ->references('id')->on('college_admission_cycles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_application_sequences');
    }
};
