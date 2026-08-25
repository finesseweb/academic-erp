<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('college_program_intake_allocations')) {
            return;
        }

        if (
            ! Schema::hasColumn(
                'college_program_intake_allocations',
                'parent_allocation_id'
            )
        ) {
            Schema::table(
                'college_program_intake_allocations',
                function (Blueprint $table) {
                    $table->unsignedBigInteger(
                        'parent_allocation_id'
                    )
                        ->nullable()
                        ->after(
                            'college_program_intake_id'
                        );

                    $table->foreign(
                        'parent_allocation_id',
                        'cpia_parent_fk'
                    )
                        ->references('id')
                        ->on(
                            'college_program_intake_allocations'
                        )
                        ->cascadeOnDelete();

                    $table->index(
                        [
                            'college_program_intake_id',
                            'parent_allocation_id',
                        ],
                        'cpia_intake_parent_idx'
                    );
                }
            );
        }
    }

    public function down(): void
    {
        // Intentionally left non-destructive.
        // This migration is a forward schema-alignment safeguard.
    }
};
