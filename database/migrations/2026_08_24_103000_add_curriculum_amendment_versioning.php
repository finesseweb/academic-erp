<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curricula', function (Blueprint $table) {
            $table->foreignId('parent_curriculum_id')
                ->nullable()
                ->after('id')
                ->constrained('curricula')
                ->restrictOnDelete();

            $table->string('revision_type', 40)
                ->nullable()
                ->after('approval_status');

            $table->text('revision_reason')
                ->nullable()
                ->after('revision_type');

            $table->date('revision_effective_from')
                ->nullable()
                ->after('revision_reason');

            $table->index(
                ['parent_curriculum_id', 'lifecycle_status', 'approval_status'],
                'curricula_amendment_state_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('curricula', function (Blueprint $table) {
            $table->dropIndex('curricula_amendment_state_idx');
            $table->dropForeign(['parent_curriculum_id']);
            $table->dropColumn([
                'parent_curriculum_id',
                'revision_type',
                'revision_reason',
                'revision_effective_from',
            ]);
        });
    }
};
