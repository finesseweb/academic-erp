<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('college_admission_selection_rule_merit_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_admission_selection_rule_id')->constrained('college_admission_selection_rules','id','casrms_rule_fk')->cascadeOnDelete();
            $table->string('label', 150);
            $table->string('source_type', 30)->default('FORM_FIELD_PAIR');
            $table->unsignedBigInteger('obtained_field_id');
            $table->unsignedBigInteger('maximum_field_id');
            $table->decimal('weight_percent', 6, 2);
            $table->unsignedSmallInteger('display_order')->default(10);
            $table->timestamps();
            $table->foreign('obtained_field_id','casrms_obtained_fk')->references('id')->on('college_admission_form_fields')->restrictOnDelete();
            $table->foreign('maximum_field_id','casrms_maximum_fk')->references('id')->on('college_admission_form_fields')->restrictOnDelete();
            $table->index(['college_admission_selection_rule_id','display_order'],'casrms_rule_order_idx');
        });

        Schema::table('college_admission_scores', function (Blueprint $table) {
            $table->json('merit_source_snapshot')->nullable()->after('merit_normalized_score');
        });
    }

    public function down(): void
    {
        Schema::table('college_admission_scores', function (Blueprint $table) {
            $table->dropColumn('merit_source_snapshot');
        });
        Schema::dropIfExists('college_admission_selection_rule_merit_sources');
    }
};
