<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curricula', function (Blueprint $table) {
            $table->string('structure_validation_hash', 64)
                ->nullable()
                ->after('approval_status');

            $table->timestamp('structure_validated_at')
                ->nullable()
                ->after('structure_validation_hash');

            $table->foreignId('structure_validated_by')
                ->nullable()
                ->after('structure_validated_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(
                ['approval_status', 'structure_validated_at'],
                'curricula_validation_approval_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('curricula', function (Blueprint $table) {
            $table->dropIndex('curricula_validation_approval_idx');
            $table->dropConstrainedForeignId('structure_validated_by');
            $table->dropColumn([
                'structure_validation_hash',
                'structure_validated_at',
            ]);
        });
    }
};
