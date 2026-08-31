<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_admission_form_access_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_form_access_control_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->foreign('college_admission_form_access_control_id', 'cafar_access_fk')
                ->references('id')->on('college_admission_form_access_controls')->cascadeOnDelete();
            $table->foreign('role_id', 'cafar_role_fk')->references('id')->on('roles')->cascadeOnDelete();
            $table->unique(['college_admission_form_access_control_id', 'role_id'], 'cafar_access_role_uq');
            $table->index('role_id', 'cafar_role_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_form_access_roles');
    }
};
