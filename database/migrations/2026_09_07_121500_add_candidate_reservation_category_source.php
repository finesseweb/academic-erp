<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('college_admission_form_mappings', function (Blueprint $table) {
            $table->unsignedBigInteger('reservation_category_field_id')->nullable()->after('seat_selection_required');
            $table->foreign('reservation_category_field_id', 'cafm_rescat_field_fk')->references('id')->on('college_admission_form_fields')->restrictOnDelete();
        });
        Schema::table('college_admission_seat_allocations', function (Blueprint $table) {
            $table->unsignedBigInteger('candidate_reservation_category_id')->nullable()->after('final_weighted_score');
            $table->string('candidate_category_source', 20)->nullable()->after('candidate_reservation_category_id');
            $table->string('candidate_category_code', 80)->nullable()->after('candidate_category_source');
            $table->string('candidate_category_name', 160)->nullable()->after('candidate_category_code');
            $table->foreign('candidate_reservation_category_id', 'casa_candidate_rescat_fk')->references('id')->on('reservation_categories')->restrictOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('college_admission_seat_allocations', function (Blueprint $table) {
            $table->dropForeign('casa_candidate_rescat_fk');
            $table->dropColumn(['candidate_reservation_category_id','candidate_category_source','candidate_category_code','candidate_category_name']);
        });
        Schema::table('college_admission_form_mappings', function (Blueprint $table) {
            $table->dropForeign('cafm_rescat_field_fk');
            $table->dropColumn('reservation_category_field_id');
        });
    }
};
